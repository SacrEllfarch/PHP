<?php

return [
    'default' => env('DATABASE_TYPE', 'mysql'),
    'connections' => [
        'mysql' => [
            'type' => env('DATABASE_TYPE', 'mysql'),
            'hostname' => env('DATABASE_HOST', '127.0.0.1'),
            'database' => env('DATABASE_NAME', 'api_gateway'),
            'username' => env('DATABASE_USERNAME', 'root'),
            'password' => env('DATABASE_PASSWORD', ''),
            'hostport' => env('DATABASE_PORT', '3306'),
            'charset' => env('DATABASE_CHARSET', 'utf8mb4'),
            'prefix' => env('DATABASE_PREFIX', ''),
            'debug' => env('APP_DEBUG', false),
        ],
    ],
];
