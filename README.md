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
<?php

return [
    /**
     * The EventStore connection to use when subscribing to events from external
     * services. Works with TCP or TLS connections.
     */
    'tcp_url' => env('EVENTSTORE_TCP_URL', 'tcp://admin:changeit@localhost:1113'),

    /**
     * The EventStore connection to use when publishing and reconstituting
     * aggregates. Supports HTTP or HTTPS.
     */
    'http_url' => env('EVENTSTORE_HTTP_URL', 'http://admin:changeit@localhost:2113'),

    /**
     * Listen to these streams when running `event-sourcing:subscribe`. Uses
     * a comma delimetered list from the environment as default.
     */
    'subscription_streams' => array_filter(explode(',', env('EVENTSTORE_SUBSCRIPTION_STREAMS'))),

    /**
     * Used as the group when connecting to an EventStore persisten subscription.
     */
    'group' => env('EVENTSTORE_SUBSCRIPTION_GROUP', env('APP_NAME', 'laravel')),

    /**
     * By default Aggregate classes are mapped to a category name based on their
     * class name. Example App\Aggregates\AccountAggregate would be published
     * to an account-uuid stream. This allows you to implicitly map classes
     * to categories so that it could be published to account_v2-uuid.
     */
    'aggregate_category_map' => [],

    /**
     * If not using aggregates, events need to mapped to streams to be
     * published. An example would be the AccoutCreated event
     * could be published on to the accounts stream.
     */
    'event_stream_map' => [],

    /**
     * If the event is not mapped to a stream,
     * publish to this stream by default.
     */
    'default_stream' => env('EVENTSTORE_DEFAULT_STREAM', 'events'),

    /**
     * The stream to listen to when replaying all events. Instead of using
     * $all, it is recommended to setup a project which emits events
     * from various streams into a stream specific for your app.
     */
    'all' => env('EVENTSTORE_ALL', '$all'),

    /**
     * Number of events to read in a single API
     * call when reconstituting events.
     */
    'read_size' => env('EVENTSTORE_READ_SIZE', 4096),

    /**
     * Number of events to read in a single TCP
     * message when replaying all events.
     */
    'batch_size' => env('EVENTSTORE_BATCH_SIZE', 4096),

    /**
     * This class contains a few callbacks to govern the bridge between EventStore and the
     * Laravel Event Sourcing package. You can customise the class to include your
     * own business logic. It should extend DigitalRisks\Lese\Lese
     */
    'lese_class' => env('EVENTSTORE_LESE_CLASS', DigitalRisks\Lese\Lese::class),
];
```

## Getting Started

I would recommend getting familiar with Event Sourcing by reading through the excellent guide at https://docs.spatie.be/laravel-event-sourcing/v3/introduction/. 

The next step is to get a local version of the EventStore running (you won't need a database). There are instructions for every platform at https://eventstore.com/docs/getting-started/index.html

Let's now create a simple event:

```php
<?php

namespace App\Events;

use Spatie\EventSourcing\ShouldBeStored;

class MoneyAdded implements ShouldBeStored
{
    /** @var string */
    public $accountUuid;

    /** @var int */
    public $amount;

    public function __construct(string $accountUuid, int $amount)
    {
        $this->accountUuid = $accountUuid;

        $this->amount = $amount;
    }
}
```

And fire it off:

```php
<?php

event(new MoneyAdded('21410-81231', 100))
```

Let's create a simple Projection to put account information in a database.

```php
<?php

namespace App\Projectors;

use App\Account;
use App\Events\AccountCreated;
use App\Events\AccountDeleted;
use App\Events\MoneyAdded;
use App\Events\MoneySubtracted;
use Spatie\EventSourcing\Projectors\Projector;
use Spatie\EventSourcing\Projectors\ProjectsEvents;

class AccountsProjector implements Projector
{
    use ProjectsEvents;

    public function onMoneyAdded(MoneyAdded $event)
    {
        $account = Account::uuid($event->accountUuid);

        $account->balance += $event->amount;

        $account->save();
    }
}
```

And also send an event to the FBI for large transactions:

```php
<?php

namespace App\Reactors;

use App\Account;
use App\Events\MoneyAdded;
use App\Mail\BigAmountAddedMail;
use Illuminate\Support\Facades\Mail;
use Spatie\EventSourcing\EventHandlers\EventHandler;
use Spatie\EventSourcing\EventHandlers\HandlesEvents;

class BigAmountAddedReactor implements EventHandler
{
    use HandlesEvents;

    public function onMoneyAdded(MoneyAdded $event)
    {
        if ($event->amount < 5000) {
            return;
        }

        $account = Account::uuid($event->accountUuid);

        Mail::to('director@fbi.gov')->send(new BigAmountAddedMail($account, $event->amount));
    }
}
```

If, later on, the business wants to have an attribute on the model for `number_of_deposits`, we update the Projector:

```php
<?php

namespace App\Projectors;

use App\Account;
use App\Events\AccountCreated;
use App\Events\AccountDeleted;
use App\Events\MoneyAdded;
use App\Events\MoneySubtracted;
use Spatie\EventSourcing\Projectors\Projector;
use Spatie\EventSourcing\Projectors\ProjectsEvents;

class AccountsProjector implements Projector
{
    use ProjectsEvents;

    public function onMoneyAdded(MoneyAdded $event)
    {
        $account = Account::uuid($event->accountUuid);

        $account->balance += $event->amount;
      	$account->number_of_deposits += 1;

        $account->save();
    }
}
```

And re-run the events:

```bash
php artisan event-sourcing:replay App\\Projectors\\AccountsProjector
```

Learn more how to use Event Sourcing by following the guides at https://docs.spatie.be/laravel-event-sourcing/v3/introduction/

## Aggregates

> If you're not using aggregates, you can skip this section.

In order for the EventStore repositories to fetch the events and/or snapshots related to an aggregate, it needs to know about the aggregate. To do this we simply override the two methods below to initiate the repostiory and pass in the aggregate.

```php
protected function getStoredEventRepository(): StoredEventRepository
{
    return resolve(EventStoreStoredEventRepository::class, ['aggregate' => $this]);
}

protected function getSnapshotRepository(): SnapshotRepository
{
    return resolve(EventStoreSnapshotRepository::class, ['aggregate' => $this]);
}
```

## Subscribing to Streams

The package also includes a long-running process, similar to [Pub / Sub](https://laravel.com/docs/7.x/redis#pubsub)  with `php artisan redis:subscribe` whereby you can listen to events from a stream.

Let's say this is the the `accounts-service` but we wanted listen for events from the `quotes-service`. When a quote is converted, we want to create an account for it. 

> Careful: If you listen to events that you publish, projectors and reactors will process them once in your application and again when they come back down the stream. It's recommended you subscribe only to streams that you don't publish to. 

In `config/lese.php` we would add the stream for quote converted events:

```php
/**
 * Listen to these streams when running `event-sourcing:subscribe`. Uses
 * a comma delimetered list from the environment as default.
 */
'subscription_streams' => ['$et-Events\Quotes\QuoteConverted'],
```

We could then run the following command to create the persistent subscriptions on EventStore

```bash
php artisan event-sourcing:reset
```

> Careful: When resetting persistent subscriptions, it will start from the first event again. If you have reactors, you should go into the eventstore admin and set the `start from` value to the event number you want to start from.

And finally start the subscribe process

```bash
php artisan event-sourcing:subscribe
```

## Event Metadata

Metadata can help trace events around your system. You can include any of the following traits on your event to attach metadata automatically

* `AddsHerokuMetadata`
* `AddsLaravelMetadata`
* `AddsUserMetaData`

Or you can define your own methods to collect metadata. Any method with the `@metadata` annotation will be called:

``` php
<?php

namespace App\Events;

use DigitalRisks\Lese\MetaData\HasMetaData;
use DigitalRisks\Lese\MetaData\CollectsMetaData;

use DigitalRisks\Lese\MetaData\AddsHerokuMetadata;
use DigitalRisks\Lese\MetaData\AddsLaravelMetadata;
use DigitalRisks\Lese\MetaData\AddsUserMetaData;

use Spatie\EventSourcing\ShouldBeStored;

class MoneyAdded implements ShouldBeStored, HasMetaData
{
    use CollectsMetaData, AddsUserMetaData, AddsHerokuMetadata, AddsLaravelMetadata;

    /** @var string */
    public $accountUuid;

    /** @var int */
    public $amount;

    public function __construct(string $accountUuid, int $amount)
    {
        $this->accountUuid = $accountUuid;

        $this->amount = $amount;
    }
  
    /** @metadata */
    public function collectIpMetadata()
    {
        return [
            'ip' => $_SERVER['REMOTE_ADDR'],
        ];
    }
}
```

## Changelog

Please see [CHANGELOG](../../releases) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email craig.morris@digitalrisks.co.uk instead of using the issue tracker.

## Credits

- [Freek Van der Herten](https://github.com/freekmurze)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
