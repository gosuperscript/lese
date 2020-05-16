<?php

namespace DigitalRisks\Lese\Handlers;

use Amp\Failure;
use Amp\Promise;
use Amp\Success;
use DateTimeInterface;
use DigitalRisks\Lese\StubModel;
use Exception;
use Prooph\EventStore\Async\EventAppearedOnPersistentSubscription;
use Prooph\EventStore\Async\EventStorePersistentSubscription;
use Prooph\EventStore\ResolvedEvent;
use Spatie\EventSourcing\StoredEvent;
use Illuminate\Support\Str;
use Prooph\EventStore\PersistentSubscriptionNakEventAction;
use Spatie\SchemalessAttributes\SchemalessAttributes;

class OnEvent implements EventAppearedOnPersistentSubscription
{
    public function __invoke(EventStorePersistentSubscription $subscription, ResolvedEvent $resolvedEvent, ?int $retryCount = null): Promise
    {
        $event = $resolvedEvent->event();

        $metaModel = new StubModel(['meta_data' => $event->metadata() ?: null]);

        try {
            $storedEvent = new StoredEvent([
                'id' => $event->eventNumber(),
                'event_properties' => $event->data(),
                'aggregate_uuid' => Str::before($event->eventStreamId(), '-'), // @todo remove $ce- so this works
                'event_class' => $event->eventType(),
                'meta_data' => new SchemalessAttributes($metaModel, 'meta_data'),
                'created_at' => $event->created()->format(DateTimeInterface::ATOM),
            ]);

            $storedEvent->handle();

            $subscription->acknowledge($resolvedEvent);
        }
        catch (Exception $e) {
            report($e);

            $subscription->fail($resolvedEvent, PersistentSubscriptionNakEventAction::unknown(), $e->getMessage());
        }

        return new Success();
    }
};
