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

class EventStoreStoredEventRepository implements StoredEventRepository
{
    protected string $storedEventModel;

    protected $category;

    public function __construct($category = null)
    {
        $this->category = $category;
    }

    public function retrieveAll(string $uuid = null): LazyCollection
    {
        return $this->retrieveAllAfterVersion(0, $uuid);
    }

    public function retrieveAllStartingFrom(int $startingFrom, string $uuid = null): LazyCollection
    {
        throw new BadMethodCallException('EventStore IDs are UUIDs');
    }

    public function retrieveAllAfterVersion(int $aggregateVersion, string $aggregateUuid): LazyCollection
    {
        $connection = EventStoreConnectionFactory::create();

        $slice = $connection->readStreamEventsForward(
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

    public function countAllStartingFrom(int $startingFrom, string $uuid = null): int
    {
        throw new BadMethodCallException('EventStore IDs are UUIDs');
    }

    public function persist(ShouldBeStored $event, string $uuid = null, int $aggregateVersion = null): StoredEvent
    {
        $connection = EventStoreConnectionFactory::create();

        $json = app(EventSerializer::class)->serialize(clone $event);
        $metadata = '{}';
        $event = new EventData(EventId::generate(), get_class($event), true, $json, $metadata);

        $write = $connection->appendToStream(
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
        $connection = EventStoreConnectionFactory::create();

        $slice = $connection->readStreamEventsBackward(
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
