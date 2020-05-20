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
     * When subscribing to other strea
     */
    'subscription_streams' => array_filter(explode(',', env('EVENTSTORE_SUBSCRIPTION_STREAMS'))),

    /**
     * Used as the group when connecting to an EventStore persisten subscription.
     */
    'group' => env('EVENTSTORE_SUBSCRIPTION_GROUP', env('APP_NAME', 'laravel')),

    /**
     *
     */
    'aggregate_category_map' => [],

    /**
     *
     */
    'all' => env('EVENTSTORE_ALL', '$all'),

    /**
     *
     */
    'read_size' => env('EVENTSTORE_READ_SIZE', 4096),

    /**
     *
     */
    'batch_size' => env('EVENTSTORE_BATCH_SIZE', 4096),

    /**
     *
     */
    'lese_class' => env('EVENTSTORE_LESE_CLASS', DigitalRisks\Lese\Lese::class),
];
