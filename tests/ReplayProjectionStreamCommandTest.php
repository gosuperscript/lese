<?php

namespace DigitalRisks\Lese\Tests;

use DigitalRisks\Lese\EventStoreStoredEventRepository;
use DigitalRisks\Lese\Tests\TestClasses\Account;
use DigitalRisks\Lese\Tests\TestClasses\AccountAggregate;
use DigitalRisks\Lese\Tests\TestClasses\BalanceProjector;
use DigitalRisks\Lese\Tests\TestClasses\BrokeReactor;
use DigitalRisks\Lese\Tests\TestClasses\MoneyAddedEvent;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Spatie\EventSourcing\Events\FinishedEventReplay;
use Spatie\EventSourcing\Events\StartingEventReplay;
use Spatie\EventSourcing\Facades\Projectionist;
use Spatie\EventSourcing\Models\EloquentStoredEvent;
use Spatie\EventSourcing\StoredEventRepository;

class ReplayProjectionStreamCommandTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $category = $this->faker->md5;
        config()->set("lese.aggregate_category_map." . AccountAggregate::class, $category);
        config()->set("lese.all", '$ce-' . $category);

        $account = AccountAggregate::retrieve($this->faker->uuid);
        foreach (range(1, 3) as $i) {
            $account->addMoney(1000);
        }
        $account->persist();

        $account2 = AccountAggregate::retrieve($this->faker->uuid);
        foreach (range(1, 3) as $i) {
            $account2->addMoney(500);
        }
        $account2->persist();

        $this->app->singleton(StoredEventRepository::class, EventStoreStoredEventRepository::class);
    }

    /** @test */
    public function it_will_replay_events_to_the_given_projectors()
    {
        $projector = Mockery::mock(BalanceProjector::class.'[onMoneyAdded]');
        $projector->shouldReceive('onMoneyAdded')->andReturnNull()->times(6);

        Projectionist::addProjector($projector);

        $this->artisan('event-sourcing:replay', ['projector' => [get_class($projector)]])
            ->expectsOutput('Replaying 6 events...')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_can_replay_events_starting_from_a_specific_number()
    {
        $projector = Mockery::mock(BalanceProjector::class.'[onMoneyAdded]');
        $projector->shouldReceive('onMoneyAdded')->andReturnNull()->times(5);

        Projectionist::addProjector($projector);

        $this->artisan('event-sourcing:replay', ['projector' => [get_class($projector)], '--from' => 2])
            ->expectsOutput('Replaying 5 events...')
            ->assertExitCode(0);
    }
}
