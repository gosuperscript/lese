<?php

namespace DigitalRisks\Lese\Handlers;

use Prooph\EventStore\Async\PersistentSubscriptionDropped;
use Prooph\EventStoreClient\Internal\EventStorePersistentSubscription;
use Prooph\EventStore\SubscriptionDropReason;
use Throwable;

class OnDropped implements PersistentSubscriptionDropped {
    public function __invoke(EventStorePersistentSubscription $subscription, SubscriptionDropReason $reason, ?Throwable $exception = null): void {
        echo 'dropped with reason: ' . $reason->name() . PHP_EOL;

        if ($exception) {
            echo 'ex: ' . $exception->getMessage() . PHP_EOL;
        }
    }
}
