<?php

use DigitalRisks\Lese\Lese;

return [
    'tcp_url' => env('EVENTSTORE_TCP_URL', 'tcp://admin:changeit@localhost:1113'),
    'http_url' => env('EVENTSTORE_HTTP_URL', 'http://admin:changeit@localhost:2113'),
    'subscription_streams' => array_filter(explode(',', env('EVENTSTORE_SUBSCRIPTION_STREAMS'))),
    'group' => env('EVENTSTORE_SUBSCRIPTION_GROUP', env('APP_NAME', 'laravel')),
    'aggregate_category_map' => [],
    'all' => env('EVENTSTORE_ALL', '$all'),
    'read_size' => env('EVENTSTORE_READ_SIZE', 4096),
    'batch_size' => env('EVENTSTORE_BATCH_SIZE', 4096),
    'lese_class' => env('EVENTSTORE_LESE_CLASS', Lese::class),
];
