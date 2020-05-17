<?php

namespace DigitalRisks\Lese;

use Amp\Loop;
use Amp\Promise;
use Amp\Success;
use DateTimeInterface;
use DigitalRisks\Lese\Handlers\OnEvent;
use DigitalRisks\Lese\Handlers\OnDropped;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Prooph\EventStore\Async\EventAppearedOnPersistentSubscription;
use Prooph\EventStore\Async\EventStorePersistentSubscription;
use Prooph\EventStore\Async\PersistentSubscriptionDropped;
use Prooph\EventStore\EndPoint;
use Prooph\EventStore\Exception\InvalidOperationException;
use Prooph\EventStore\PersistentSubscriptionSettings;
use Prooph\EventStore\ResolvedEvent;
use Prooph\EventStore\SubscriptionDropReason;
use Prooph\EventStore\UserCredentials;
use Prooph\EventStoreClient\EventStoreConnectionFactory;
use Spatie\EventSourcing\Projectionist;
use Throwable;
use Illuminate\Support\Str;
use Prooph\EventStore\Async\EventStoreConnection;
use Spatie\EventSourcing\StoredEvent;
use Spatie\SchemalessAttributes\SchemalessAttributes;

class EventStoreSubscribeCommand extends Command
{
    protected $signature = 'event-sourcing:subscribe';

    protected $description = 'Subscribe to a persistent subscription';

    public function handle(EventStoreConnection $eventstore, OnEvent $onEvent, OnDropped $onDropped): void
    {
        Loop::run(function () use ($eventstore, $onEvent, $onDropped) {
            $eventstore->onConnected(function (): void {
                echo 'connected' . PHP_EOL;
            });

            $eventstore->onClosed(function (): void {
                echo 'connection closed' . PHP_EOL;
            });

            $eventstore->onErrorOccurred(function () {
                echo 'error';
            });

            $eventstore->onDisconnected(function () {
                echo 'error';
            });

            yield $eventstore->connectAsync();

            foreach (config('lese.subscription_streams') as $stream) {
                yield $eventstore->connectToPersistentSubscriptionAsync(
                    $stream,
                    config('lese.group'),
                    $onEvent,
                    $onDropped,
                    10,
                    false, // we ack
                );
            }
        });
    }
}
