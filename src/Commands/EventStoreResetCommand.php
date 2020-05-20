<?php

namespace DigitalRisks\Lese\Commands;

use DigitalRisks\LaravelEventStore\Client;
use Illuminate\Console\Command;
use GuzzleHttp\Exception\ClientException;
use Prooph\EventStore\EventStoreConnection;
use Prooph\EventStore\Exception\InvalidOperationException;
use Prooph\EventStore\PersistentSubscriptionSettings;
use Illuminate\Support\Str;

class EventStoreResetCommand extends Command
{
    protected $signature = 'event-sourcing:reset';

    protected $description = 'Recreate required persistent subscriptions.';

    protected EventStoreConnection $eventstore;

    public function __construct(EventStoreConnection $eventstore)
    {
        $this->eventstore = $eventstore;

        parent::__construct();
    }

    public function handle()
    {
        if (! $this->confirm('Please stop all workers first. Continue?')) return;

        $streams = collect(config('lese.subscription_streams'));

        $streams->map([$this, 'deleteSubscription']);
        $streams->map([$this, 'createSubscription']);
    }

    public function deleteSubscription($stream)
    {
        $name = config('lese.group');

        try {
            $this->eventstore->deletePersistentSubscription($stream, $name);
        }
        catch (InvalidOperationException $e) {
            throw_unless(Str::contains($e->getMessage(), 'Not Found'), $e);
        }
    }

    public function createSubscription($stream)
    {
        $name = config('lese.group');
        $settings = PersistentSubscriptionSettings::create()->build();

        $this->eventstore->createPersistentSubscription($stream, $name, $settings);
    }
}
