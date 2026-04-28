<?php

return [
    'default' => 'redis',
    'stores' => [
        'redis' => [
            'type' => 'redis',
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => (int) env('REDIS_PORT', 6379),
            'password' => env('REDIS_PASSWORD', ''),
            'select' => (int) env('REDIS_DATABASE', 0),
            'timeout' => (float) env('REDIS_TIMEOUT', 3.0),
            'persistent' => false,
            'prefix' => 'api_gateway:',
        ],
    ],
];
