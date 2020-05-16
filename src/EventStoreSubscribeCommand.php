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
use Spatie\EventSourcing\StoredEvent;
use Spatie\SchemalessAttributes\SchemalessAttributes;

class EventStoreSubscribeCommand extends Command
{
    protected $signature = 'event-sourcing:subscribe {--group=} {--stream=}';

    protected $description = 'Subscribe to a persistent subscription';

    public function handle(): void
    {
        Loop::run(function () {
            $connection = EventStoreConnectionFactory::createFromEndPoint(
                new EndPoint('localhost', 1113)
            );

            $connection->onConnected(function (): void {
                echo 'connected' . PHP_EOL;
            });

            $connection->onClosed(function (): void {
                echo 'connection closed' . PHP_EOL;
            });

            yield $connection->connectAsync();

            yield $connection->connectToPersistentSubscriptionAsync(
                $this->option('stream'),
                $this->option('group'),
                new OnEvent(),
                new OnDropped(),
                10,
                true,
                new UserCredentials('admin', 'changeit')
            );
        });
    }
}
