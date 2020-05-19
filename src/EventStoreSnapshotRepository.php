<?php

namespace DigitalRisks\Lese;

use Prooph\EventStore\EventData;
use Prooph\EventStore\EventId;
use Prooph\EventStore\ExpectedVersion;
use Prooph\EventStore\UserCredentials;
use Prooph\EventStoreHttpClient\EventStoreConnectionFactory;
use Spatie\EventSourcing\AggregateRoot;
use Spatie\EventSourcing\EventSerializers\EventSerializer;
use Prooph\EventStore\EventStoreConnection;
use Spatie\EventSourcing\Snapshots\Snapshot;
use Spatie\EventSourcing\Snapshots\SnapshotRepository;

class EventStoreSnapshotRepository implements SnapshotRepository
{
    protected EventStoreConnection $eventstore;
    protected Lese $lese;
    protected ?AggregateRoot $aggregate;

    public function __construct(EventStoreConnection $eventstore, Lese $lese, AggregateRoot $aggregate = null)
    {
        $this->eventstore = $eventstore;
        $this->aggregate = $aggregate;
        $this->lese = $lese;
    }

    public function retrieve(string $aggregateUuid): ?Snapshot
    {
        $slice = $this->eventstore->readStreamEventsBackward(
            $this->lese->aggregateToSnapshotStream($this->aggregate, $aggregateUuid),
            -1,
            1,
        );

        if ($slice->status()->name() === 'StreamNotFound') {
            return null;
        }

        $snap = $slice->events()[0];
        $state = json_decode($snap->event()->data(), true);
        $metadata = json_decode($snap->event()->metadata(), true);

        return new Snapshot($aggregateUuid, $metadata['aggregateVersion'], $state);
    }

    public function persist(Snapshot $snapshot): Snapshot
    {
        $metadata = [
            'aggregateVersion' => $snapshot->aggregateVersion,
        ];

        $event = new EventData(
            EventId::generate(),
            '$' . get_class($snapshot),
            true,
            json_encode($snapshot->state),
            json_encode($metadata)
        );

        $write = $this->eventstore->appendToStream(
            $this->lese->aggregateToSnapshotStream($this->aggregate, $snapshot->aggregateUuid),
            ExpectedVersion::ANY,
            [$event],
        );

        return $snapshot;
    }
}
