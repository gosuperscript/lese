<?php

namespace DigitalRisks\Lese;

use Prooph\EventStore\EventData;
use Prooph\EventStore\EventId;
use Prooph\EventStore\ExpectedVersion;
use Prooph\EventStore\UserCredentials;
use Prooph\EventStoreHttpClient\EventStoreConnectionFactory;
use Spatie\EventSourcing\EventSerializers\EventSerializer;
use Spatie\EventSourcing\Snapshots\Snapshot;
use Spatie\EventSourcing\Snapshots\SnapshotRepository;

class EventStoreSnapshotRepository implements SnapshotRepository
{
    protected $category;

    public function __construct($category)
    {
        $this->category = $category;
    }

    public function retrieve(string $aggregateUuid): ?Snapshot
    {
        $connection = EventStoreConnectionFactory::create();

        $slice = $connection->readStreamEventsBackward(
            '$' . $this->category . '-' . $aggregateUuid . '-snapshot',
            -1,
            1,
            true,
            new UserCredentials('admin', 'changeit'),
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
        $connection = EventStoreConnectionFactory::create();

        $metadata = [
            'aggregateVersion' => $snapshot->aggregateVersion,
        ];
        $event = new EventData(EventId::generate(), get_class($snapshot), true, json_encode($snapshot->state), json_encode($metadata));

        $write = $connection->appendToStream(
            '$' . $this->category . '-' . $snapshot->aggregateUuid . '-snapshot',
            ExpectedVersion::ANY,
            [$event],
            new UserCredentials('admin', 'changeit'),
        );

        return $snapshot;
    }
}
