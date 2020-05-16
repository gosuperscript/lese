<?php

namespace DigitalRisks\Lese\Tests;

use Amp\Failure;
use Amp\Success;
use Carbon\Carbon;
use DateTimeImmutable;
use DigitalRisks\Lese\Handlers\OnEvent;
use DigitalRisks\Lese\Tests\TestClasses\BalanceProjector;
use DigitalRisks\Lese\Tests\TestClasses\BrokeReactor;
use DigitalRisks\Lese\Tests\TestClasses\MoneyAddedEvent;
use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Spatie\EventSourcing\Events\FinishedEventReplay;
use Spatie\EventSourcing\Events\StartingEventReplay;
use Spatie\EventSourcing\Facades\Projectionist;
use Spatie\EventSourcing\Models\EloquentStoredEvent;
use Prooph\EventStore\Async\EventStorePersistentSubscription;
use Prooph\EventStore\EventId;
use Prooph\EventStore\RecordedEvent;
use Prooph\EventStore\ResolvedEvent;
use Spatie\EventSourcing\Projectionist as TheRealProjectionist;

class SubscribeCommandTest extends TestCase
{
    /** @test */
    public function it_calls_projector_and_reactor_on_receiving_an_event_and_returns_success()
    {
        // Arrrange.
        $subscription = $this->mock(EventStorePersistentSubscription::class);
        $recordedEvent = new RecordedEvent('test', 1, EventId::generate(), MoneyAddedEvent::class, true, '{ "amount": 100 }', '{}', new DateTimeImmutable());
        $resolvedEvent = new ResolvedEvent($recordedEvent, null, null);

        $projector = $this->mock(BalanceProjector::class.'[onMoneyAdded]');
        $projector->shouldReceive('onMoneyAdded')->andReturnNull()->once();
        Projectionist::addProjector($projector);

        $reactor = $this->mock(BrokeReactor::class.'[onMoneyAdded]');
        $reactor->shouldReceive('onMoneyAdded')->andReturnNull()->once();
        Projectionist::addReactor($reactor);

        $onEvent = new OnEvent();
        $result = $onEvent->__invoke($subscription, $resolvedEvent);
        $this->assertInstanceOf(Success::class, $result);
    }

    /** @test */
    public function it_returns_failure_if_an_error_happens_in_a_projector()
    {
        // Arrrange.
        $subscription = $this->mock(EventStorePersistentSubscription::class);
        $recordedEvent = new RecordedEvent('test', 1, EventId::generate(), MoneyAddedEvent::class, true, '{ "amount": 100 }', '{}', new DateTimeImmutable());
        $resolvedEvent = new ResolvedEvent($recordedEvent, null, null);

        $projector = $this->mock(BalanceProjector::class.'[onMoneyAdded]');
        $projector->shouldReceive('onMoneyAdded')->andThrow(new Exception)->once();
        Projectionist::addProjector($projector);

        $onEvent = new OnEvent();
        $result = $onEvent->__invoke($subscription, $resolvedEvent);
        $this->assertInstanceOf(Failure::class, $result);
    }

    /** @test */
    public function it_returns_failure_if_event_isnt_registered()
    {
        // Arrrange.
        $subscription = $this->mock(EventStorePersistentSubscription::class);
        $recordedEvent = new RecordedEvent('test', 1, EventId::generate(), 'i do not exist', true, '{}', '{}', new DateTimeImmutable());
        $resolvedEvent = new ResolvedEvent($recordedEvent, null, null);

        // Act.
        $onEvent = new OnEvent();
        $result = $onEvent->__invoke($subscription, $resolvedEvent);

        // Assert.
        $this->assertInstanceOf(Failure::class, $result);
    }
}
