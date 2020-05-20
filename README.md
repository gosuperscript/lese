# Laravel Event Sourcing and Eventstore (lese) Bridge

Or German for `read` which is somewhat applicable to Event Sourcing. It's almost a good name.

This package swaps out the Event and Snapshot storage model for [Laravel Event Souring](https://docs.spatie.be/laravel-event-sourcing/v1/getting-familiar-with-event-sourcing/introduction) with [EventStore](https://eventstore.com/). EventStore has a few advantages over a database in that it is purpose built for event sourcing. 

The package also includes a subscribe command so that you may listen to events origination from other services in your system. 

## Installation

First of all let's bring in the package and Laravel Event Sourcing into our Laravel app. 

```bash
composer require digitalrisks/lese
```

Then publish the Laravel Event Sourcing and Lese configuration files.

```bash
php artisan vendor:publish --provider="Spatie\EventSourcing\EventSourcingServiceProvider" --tag="config"
php artisan vendor:publish --provider="DigitalRisks\Lese\LeseServiceProvider" --tag="config"
```

Then jump into `config/event-sourcing.php` to configure EventStore as our event and snapshot storage repositories.

```php
    /*
     * This class is responsible for storing events. To add extra behaviour you
     * can change this to a class of your own. The only restriction is that
     * it should implement \Spatie\EventSourcing\StoredEventRepository.
     */
    'stored_event_repository' => \DigitalRisks\Lese\EventStoreStoredEventRepository::class,

    /*
     * This class is responsible for storing snapshots. To add extra behaviour you
     * can change this to a class of your own. The only restriction is that
     * it should implement \Spatie\EventSourcing\StoredEventRepository.
     */
    'snapshot_repository' => \DigitalRisks\Lese\EventStoreSnapshotRepository::class,
```

## Configuration

This is the default content of the config file that will be published at `config/lese.php`

```php
return [
    'tcp_url' => env('EVENTSTORE_TCP_URL', 'tcp://admin:changeit@localhost:1113'),
    'http_url' => env('EVENTSTORE_HTTP_URL', 'http://admin:changeit@localhost:2113'),
    'subscription_streams' => array_filter(explode(',', env('EVENTSTORE_SUBSCRIPTION_STREAMS'))),
    'group' => env('EVENTSTORE_SUBSCRIPTION_GROUP', env('APP_NAME', 'laravel')),
    'aggregate_category_map' => [],
    'all' => env('EVENTSTORE_ALL', '$all'),
    'read_size' => env('EVENTSTORE_READ_SIZE', 4096),
    'batch_size' => env('EVENTSTORE_BATCH_SIZE', 4096),
    'lese_class' => env('EVENTSTORE_LESE_CLASS', DigitalRisks\Lese\Lese::class),
];
```

