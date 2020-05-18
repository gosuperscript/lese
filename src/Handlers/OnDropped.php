<?php

namespace DigitalRisks\Lese\Handlers;

use Exception;
use Prooph\EventStore\Async\PersistentSubscriptionDropped;
use Prooph\EventStore\SubscriptionDropReason;
use Throwable;
use Prooph\EventStore\Async\EventStorePersistentSubscription;

class OnDropped implements PersistentSubscriptionDropped {
    public function __invoke(EventStorePersistentSubscription $subscription, SubscriptionDropReason $reason, ?Throwable $exception = null): void {
        throw $exception ?? new Exception("Subscription dropped: {$reason}");
    }
}
