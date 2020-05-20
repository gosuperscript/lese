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
     * If not using aggregates, events need to be mapped to streams so they can be
     * published. For example mapping App\Events\AccountCreated to accounts.
     */
    'event_stream_map' => [],

    /**
     * If the event is not mapped to a stream, publish to this stream by default.
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
