<?php

namespace DigitalRisks\Lese\Tests;

use DigitalRisks\Lese\Tests\TestClasses\AccountAggregate;
use Spatie\EventSourcing\Snapshots\EloquentSnapshot;

class AggregateRootTest extends TestCase
{
    private string $aggregateUuid;

    public function setUp(): void
    {
        parent::setUp();

        $this->aggregateUuid = $this->faker->uuid;
    }

    /** @test */
    public function when_retrieving_an_aggregate_root_all_events_will_be_replayed_to_it()
    {
        /** @var \Spatie\EventSourcing\Tests\TestClasses\AggregateRoots\AccountAggregate $aggregateRoot */
        $aggregateRoot = AccountAggregate::retrieve($this->aggregateUuid);

        $aggregateRoot
            ->addMoney(100)
            ->addMoney(100)
            ->addMoney(100);

        $aggregateRoot->persist();

        $aggregateRoot = AccountAggregate::retrieve($this->aggregateUuid);

        $this->assertEquals(300, $aggregateRoot->balance);
    }

    /** @test */
    public function when_retrieving_an_aggregate_root_all_events_will_be_replayed_to_it_with_small_read_size()
    {
        /** @var \Spatie\EventSourcing\Tests\TestClasses\AggregateRoots\AccountAggregate $aggregateRoot */
        $aggregateRoot = AccountAggregate::retrieve($this->aggregateUuid);

        $aggregateRoot
            ->addMoney(100)
            ->addMoney(100)
            ->addMoney(100);

        $aggregateRoot->persist();

        config()->set('lese.read_size', 3);
        $aggregateRoot = AccountAggregate::retrieve($this->aggregateUuid);

        $this->assertEquals(300, $aggregateRoot->balance);
    }

    /** @test */
    public function restoring_an_aggregate_root_with_a_snapshot_restores_public_properties()
    {
        /** @var \Spatie\EventSourcing\Tests\TestClasses\AggregateRoots\AccountAggregate $aggregateRoot */
        $aggregateRoot = AccountAggregate::retrieve($this->aggregateUuid);

        $aggregateRoot
            ->addMoney(100)
            ->addMoney(100)
            ->addMoney(100);

        $aggregateRoot->snapshot();

        $aggregateRootRetrieved = AccountAggregate::retrieve($this->aggregateUuid);

        $this->assertEquals(3, $aggregateRootRetrieved->aggregateVersion());
        $this->assertEquals(300, $aggregateRootRetrieved->balance);
    }

    /** @test */
    public function events_saved_after_the_snapshot_are_reconstituted()
    {
        /** @var \Spatie\EventSourcing\Tests\TestClasses\AggregateRoots\AccountAggregate $aggregateRoot */
        $aggregateRoot = AccountAggregate::retrieve($this->aggregateUuid);

        $aggregateRoot
            ->addMoney(100)
            ->addMoney(100)
            ->addMoney(100)
            ->persist();

        $aggregateRoot->snapshot();
        $aggregateRoot->addMoney(100)->persist();

        $aggregateRootRetrieved = AccountAggregate::retrieve($this->aggregateUuid);

        $this->assertEquals(4, $aggregateRootRetrieved->aggregateVersion());
        $this->assertEquals(400, $aggregateRootRetrieved->balance);
    }

    /** @test */
    public function when_retrieving_an_aggregate_root_all_events_will_be_replayed_to_it_in_the_correct_order()
    {
        /** @var \Spatie\EventSourcing\Tests\TestClasses\AggregateRoots\AccountAggregate $aggregateRoot */
        $aggregateRoot = AccountAggregate::retrieve($this->aggregateUuid);

        $aggregateRoot
            ->multiplyMoney(5)
            ->addMoney(100);

        $aggregateRoot->persist();

        $aggregateRoot = AccountAggregate::retrieve($this->aggregateUuid);

        $this->assertEquals(100, $aggregateRoot->balance);
    }
}
