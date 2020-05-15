<?php

namespace DigitalRisks\Lese;

use DigitalRisks\LaravelEventStore\Console\Commands\EventStoreReset;
use DigitalRisks\LaravelEventStore\Console\Commands\EventStoreWorker;
use DigitalRisks\LaravelEventStore\Console\Commands\EventStoreWorkerThread;
use DigitalRisks\LaravelEventStore\Contracts\ShouldBeStored;
use DigitalRisks\LaravelEventStore\EventStore;
use DigitalRisks\LaravelEventStore\Listeners\SendToEventStoreListener;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {

    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__ . '/../config/lese.php', 'lese');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/lese.php' => config_path('lese.php'),
            ], 'config');

            $this->commands([
                EventStoreSubscribeCommand::class,
            ]);
        }
    }
}
