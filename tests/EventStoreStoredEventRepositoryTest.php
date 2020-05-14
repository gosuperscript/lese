<?php

namespace DigitalRisks\Lese\Tests;

use DigitalRisks\Lese\EventStoreStoredEvent;
use DigitalRisks\Lese\EventStoreStoredEventRepository;
use DigitalRisks\Lese\Tests\TestClasses\AccountAggregateRoot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class EventStoreStoredEventRepositoryTest extends TestCase
{
    use WithFaker;

    /** @test */
    public function it_can_get_the_latest_version_id_for_a_given_aggregate_uuid()
    {
        $eloquentStoredEventRepository = new EventStoreStoredEventRepository('account');

        $this->assertEquals(0, $eloquentStoredEventRepository->getLatestAggregateVersion($this->faker->uuid));

        $uuid1 = $this->faker->uuid;
        $aggregateRoot = AccountAggregateRoot::retrieve($uuid1);
        $this->assertEquals(0, $eloquentStoredEventRepository->getLatestAggregateVersion($uuid1));

        $aggregateRoot->addMoney(100)->persist();
        $this->assertEquals(1, $eloquentStoredEventRepository->getLatestAggregateVersion($uuid1));

        $aggregateRoot->addMoney(100)->persist();
        $this->assertEquals(2, $eloquentStoredEventRepository->getLatestAggregateVersion($uuid1));

        $uuid2 = $this->faker->uuid;
        $anotherAggregateRoot = AccountAggregateRoot::retrieve($uuid2);
        $anotherAggregateRoot->addMoney(100)->persist();
        $this->assertEquals(1, $eloquentStoredEventRepository->getLatestAggregateVersion($uuid2));
        $this->assertEquals(2, $eloquentStoredEventRepository->getLatestAggregateVersion($uuid1));
    }
}
