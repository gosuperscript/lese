<?php

namespace DigitalRisks\Lese;

use BadMethodCallException;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\LazyCollection;
use InvalidArgumentException;
use Prooph\EventStore\EventData;
use Prooph\EventStore\EventId;
use Prooph\EventStore\ExpectedVersion;
use Prooph\EventStore\Position;
use Prooph\EventStore\SliceReadStatus;
use Prooph\EventStore\UserCredentials;
use Prooph\EventStoreHttpClient\EventStoreConnectionFactory;
use Spatie\EventSourcing\EventSerializers\EventSerializer;
use Spatie\EventSourcing\ShouldBeStored;
use Spatie\EventSourcing\StoredEvent;
use Spatie\EventSourcing\StoredEventRepository;
use Spatie\SchemalessAttributes\SchemalessAttributes;
use Illuminate\Database\Eloquent\Model;
use Prooph\EventStore\Internal\Consts;
use Illuminate\Support\Str;
use Spatie\EventSourcing\Exceptions\InvalidStoredEvent;
use Prooph\EventStore\EventStoreConnection;

class EventStoreStoredEventRepository implements StoredEventRepository
{
    public static $all = '$ce-account';

    protected $category;
    protected EventStoreConnection $eventstore;

    public function __construct(EventStoreConnection $eventstore, $category = null)
    {
        $this->eventstore = $eventstore;
        $this->category = $category;
    }

    public function retrieveAll(string $uuid = null): LazyCollection
    {
        return $this->retrieveAllAfterVersion(0, $uuid);
    }

    /**
     * @todo Support a stream to read from instead of $all
     * @todo LazyCollection and paginate?
     */
    public function retrieveAllStartingFrom(int $startingFrom, string $uuid = null): LazyCollection
    {
        throw_if(self::$all === '$all' && $startingFrom > 0, 'Starting from not valid for $all stream');

        $startingFrom = max($startingFrom - 1, 0); // if we wanted to start from 1, we actually mean event 0

        $slice = $this->eventstore->readStreamEventsForward(
            self::$all,
            $startingFrom,
            Consts::MAX_READ_SIZE,
            true,
            new UserCredentials('admin', 'changeit'),
        );

        return LazyCollection::make(function () use ($slice) {
            foreach ($slice->events() as $event) {
                $emptyModel = new class extends Model {
                };
                $model = new $emptyModel();
                $model->meta_data = $event->event()->metadata() ?: null;

                yield new StoredEvent([
                    'id' => $event->originalEventNumber(),
                    'event_properties' => $event->event()->data(),
                    'aggregate_uuid' => Str::before($event->originalStreamName(), '-'), // @todo remove $ce- so this works
                    'event_class' => $event->event()->eventType(),
                    'meta_data' => new SchemalessAttributes($model, 'meta_data'),
                    'created_at' => $event->event()->created()->format(DateTimeInterface::ATOM),
                ]);
            }
        });
    }

    /**
     * * @todo LazyCollection and paginate?
     */
    public function retrieveAllAfterVersion(int $aggregateVersion, string $aggregateUuid): LazyCollection
    {
        $slice = $this->eventstore->readStreamEventsForward(
            $this->category . '-' . $aggregateUuid,
            $aggregateVersion,
            Consts::MAX_READ_SIZE,
            true,
            new UserCredentials('admin', 'changeit'),
        );

        return LazyCollection::make(function () use ($slice) {
            foreach ($slice->events() as $event) {
                $emptyModel = new class extends Model {
                };
                $model = new $emptyModel();
                $model->meta_data = $event->event()->metadata();

                // yield 1;
                yield new StoredEvent([
                    'id' => $event->originalEventNumber(),
                    'event_properties' => $event->event()->data(),
                    'aggregate_uuid' => Str::before($event->originalStreamName(), '-'),
                    'event_class' => $event->event()->eventType(),
                    'meta_data' => new SchemalessAttributes($model, 'meta_data'),
                    'created_at' => $event->event()->created()->format(DateTimeInterface::ATOM),
                ]);
            }
        });
    }

    /**
     * @todo Support soft deleted streams?
     */
    public function countAllStartingFrom(int $startingFrom, string $uuid = null): int
    {
        $startingFrom = max($startingFrom - 1, 0); // if we wanted to start from 1, we actually mean event 0

        $slice = $this->eventstore->readStreamEventsBackward(
            self::$all,
            -1,
            1,
            true,
            new UserCredentials('admin', 'changeit'),
        );

        if ($slice->status()->name() === 'StreamNotFound') {
            return 0;
        }

        $totalEvents = $slice->events()[0]->link()->eventNumber() + 1; // ES starts from 0

        return $totalEvents - $startingFrom;
    }

    public function persist(ShouldBeStored $event, string $uuid = null, int $aggregateVersion = null): StoredEvent
    {
        $json = app(EventSerializer::class)->serialize(clone $event);
        $metadata = '{}';
        $event = new EventData(EventId::generate(), get_class($event), true, $json, $metadata);

        $write = $this->eventstore->appendToStream(
            $this->category . '-' . $uuid,
            ExpectedVersion::ANY,
            [$event],
            new UserCredentials('admin', 'changeit'),
        );

        $emptyModel = new class extends Model { };
        $model = new $emptyModel();
        $model->meta_data = $metadata;

        return new StoredEvent([
            'id' => $write->nextExpectedVersion(),
            'event_properties' => $event->data(),
            'aggregate_uuid' => $uuid ?? '',
            'event_class' => $event->eventType(),
            'meta_data' => new SchemalessAttributes($model, 'meta_data'),
            'created_at' => Carbon::now(),
        ]);
    }

    public function persistMany(array $events, string $uuid = null, int $aggregateVersion = null): LazyCollection
    {
        $storedEvents = [];

        foreach ($events as $event) {
            $storedEvents[] = self::persist($event, $uuid, $aggregateVersion);
        }

        return new LazyCollection($storedEvents);
    }

    public function update(StoredEvent $storedEvent): StoredEvent
    {
        throw new BadMethodCallException('EventStore is immutable');
    }

    public function getLatestAggregateVersion(string $aggregateUuid): int
    {
        $slice = $this->eventstore->readStreamEventsBackward(
            $this->category . '-' . $aggregateUuid,
            -1,
            1,
            true,
            new UserCredentials('admin', 'changeit'),
        );

        if ($slice->status()->name() === 'StreamNotFound') {
            return 0;
        }

        return $slice->lastEventNumber() + 1; // ES starts from 0
    }
}
