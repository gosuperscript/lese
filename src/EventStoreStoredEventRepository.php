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
use Spatie\EventSourcing\AggregateRoot;

class EventStoreStoredEventRepository implements StoredEventRepository
{
    protected EventStoreConnection $eventstore;
    protected Lese $lese;
    protected string $all;
    protected ?AggregateRoot $aggregate;
    protected int $read_size;

    public function __construct(EventStoreConnection $eventstore, Lese $lese, AggregateRoot $aggregate = null, $config = null)
    {
        $this->eventstore = $eventstore;
        $this->lese = $lese;
        $this->aggregate = $aggregate;
        $this->all = $config['all'] ?? config('lese.all');
        $this->read_size = $config['read_size'] ?? config('lese.read_size');
    }

    public function retrieveAll(string $uuid = null): LazyCollection
    {
        return $this->retrieveAllAfterVersion(0, $uuid);
    }

    /**
     * * @todo LazyCollection and paginate?
     */
    public function retrieveAllAfterVersion(int $aggregateVersion, string $aggregateUuid): LazyCollection
    {
        return $this->streamEventsToStoredEvents(
            $this->lese->aggregateToStream($this->aggregate, $aggregateUuid),
            $aggregateVersion,
        );
    }

    /**
     * @todo Support a stream to read from instead of $all
     * @todo LazyCollection and paginate?
     */
    public function retrieveAllStartingFrom(int $startingFrom, string $uuid = null): LazyCollection
    {
        throw_if($this->all === '$all' && $startingFrom > 0, new InvalidArgumentException('Starting from not valid for $all stream'));

        $startingFrom = max($startingFrom - 1, 0); // if we wanted to start from 1, we actually mean event 0

        return $this->streamEventsToStoredEvents($this->all, $startingFrom);
    }

    /**
     * @todo Should this support $all or guide people to do https://github.com/EventStore/EventStore/issues/718#issuecomment-317355088
     * @todo Support https://github.com/EventStore/EventStore/pull/2009 for EventStore 6 once released
     */
    protected function streamEventsToStoredEvents($stream, $from)
    {
        return LazyCollection::make(function () use ($stream, $from) {
            do {
                $slice = $stream == '$all' ?
                    $this->eventstore->readAllEventsForward(Position::start(), $this->read_size) :
                    $this->eventstore->readStreamEventsForward($stream, $from, $this->read_size);

                foreach ($slice->events() as $event) {
                    if (!$this->lese->shouldSkipEvent($event)) {
                        yield $this->lese->recordedEventToStoredEvent($event->event());
                    }
                }
            } while (!$slice->isEndOfStream());
        });
    }

    /**
     * @todo Support soft deleted streams?
     */
    public function countAllStartingFrom(int $startingFrom, string $uuid = null): int
    {
        $lastEventNumber = $this->getLatestEventNumber($this->all);
        $startingFrom = max($startingFrom - 1, 0); // if we wanted to start from 1, we actually mean event 0

        return $lastEventNumber - $startingFrom;
    }

    public function persist(ShouldBeStored $event, string $uuid = null, int $aggregateVersion = null): StoredEvent
    {
        return $this->persistMany([$event], $uuid, $aggregateVersion)[0];
    }

    public function persistMany(array $events, string $uuid = null, int $aggregateVersion = null): LazyCollection
    {
        $storedEvents = [];

        $dataEvents = collect($events)->map(function ($event) {
            $json = app(EventSerializer::class)->serialize(clone $event);
            $metadata = '{}';
            return new EventData(EventId::generate(), get_class($event), true, $json, $metadata);
        });

        $this->eventstore->appendToStream(
            $this->lese->aggregateToStream($this->aggregate, $uuid),
            ExpectedVersion::ANY,
            $dataEvents->toArray(),
        );

        $storedEvents = $dataEvents->map(function ($event) use ($uuid, $aggregateVersion) {
            return $this->lese->eventDataToStoredEvent($event, $uuid, $aggregateVersion++);
        });

        return new LazyCollection($storedEvents);
    }

    public function update(StoredEvent $storedEvent): StoredEvent
    {
        throw new BadMethodCallException('EventStore is immutable');
    }

    public function getLatestAggregateVersion(string $aggregateUuid): int
    {
        return $this->getLatestEventNumber($this->lese->aggregateToStream($this->aggregate, $aggregateUuid));
    }

    protected function getLatestEventNumber($stream)
    {
        $slice = $this->eventstore->readStreamEventsBackward(
            $stream,
            -1,
            1,
            true,
        );

        if ($slice->status()->name() === 'StreamNotFound') {
            return 0;
        }

        return $slice->lastEventNumber() + 1; // ES starts from 0
    }
}
