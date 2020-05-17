<?php

namespace DigitalRisks\Lese;

use DigitalRisks\LaravelEventStore\Console\Commands\EventStoreReset;
use DigitalRisks\LaravelEventStore\Console\Commands\EventStoreWorker;
use DigitalRisks\LaravelEventStore\Console\Commands\EventStoreWorkerThread;
use DigitalRisks\LaravelEventStore\Contracts\ShouldBeStored;
use DigitalRisks\LaravelEventStore\EventStore;
use DigitalRisks\LaravelEventStore\Listeners\SendToEventStoreListener;
use DigitalRisks\Lese\Tests\TestClasses\AccountAggregateRoot;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Prooph\EventStore\Async\EventStoreConnection as TcpEventStoreConnection;
use Prooph\EventStore\EndPoint;
use Prooph\EventStore\UserCredentials;
use Prooph\EventStoreClient\ConnectionSettingsBuilder as TcpConnectionSettingsBuilder;
use Prooph\EventStoreClient\EventStoreConnectionFactory as TcpEventStoreConnectionFactory;
use Spatie\EventSourcing\StoredEventRepository;
use Prooph\EventStore\EventStoreConnection as HttpEventStoreConnection;
use Prooph\EventStoreHttpClient\ConnectionSettings as HttpConnectionSettings;
use Prooph\EventStoreHttpClient\EventStoreConnectionFactory as HttpEventStoreConnectionFactory;

class LeseServiceProvider extends LaravelServiceProvider
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

        $this->bindTcpEventStore();
        $this->bindHttpEventStore();
        $this->bindAggregates();
    }

    protected function bindTcpEventStore()
    {
        $tcp = parse_url(config('lese.tcp_url'));

        $creds = new UserCredentials($tcp['user'], $tcp['pass']);

        $builder = new TcpConnectionSettingsBuilder();
        if ($tcp['scheme'] === 'tls') $builder->useSslConnection($tcp['host'], false);
        $builder->setDefaultUserCredentials($creds);
        $settings = $builder->build();

        $connection = TcpEventStoreConnectionFactory::createFromEndPoint(
            new EndPoint($tcp['host'], $tcp['port']),
            $settings,
        );

        $this->app->instance(TcpEventStoreConnection::class, $connection);
    }

    protected function bindHttpEventStore()
    {
        $http = parse_url(config('lese.http_url'));

        $creds = new UserCredentials($http['user'], $http['pass']);

        $settings = new HttpConnectionSettings(
            new EndPoint($http['host'], $http['port']),
            $http['scheme'],
            $creds
        );

        $connection = HttpEventStoreConnectionFactory::create($settings);

        $this->app->instance(HttpEventStoreConnection::class, $connection);
    }

    protected function bindAggregates()
    {
        $this->app->when(AccountAggregateRoot::class)->needs(StoredEventRepository::class)->give(function () {
            return resolve(EventStoreStoredEventRepository::class, ['category' => 'account']);
        });
    }
}
