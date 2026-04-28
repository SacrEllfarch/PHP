<?php

return [
    'signature_window' => (int) env('GATEWAY_SIGNATURE_WINDOW', 300),
    'admin_token' => env('GATEWAY_ADMIN_TOKEN', ''),
    'upstream' => [
        'base_url' => rtrim((string) env('GATEWAY_UPSTREAM_BASE_URL', ''), '/'),
        'connect_timeout' => (float) env('GATEWAY_UPSTREAM_CONNECT_TIMEOUT', 3.0),
        'read_timeout' => (float) env('GATEWAY_UPSTREAM_READ_TIMEOUT', 10.0),
        'max_body_size' => (int) env('GATEWAY_MAX_BODY_SIZE', 1048576),
        'allowed_hosts' => array_filter(array_map(
            'trim',
            explode(',', env('GATEWAY_ALLOWED_UPSTREAM_HOSTS', 'localhost,127.0.0.1'))
        )),
    ],
    'cors' => [
        'allowed_origins' => array_filter(array_map(
            'trim',
            explode(',', env('GATEWAY_CORS_ALLOWED_ORIGINS', '*'))
        )),
        'allowed_methods' => env('GATEWAY_CORS_ALLOWED_METHODS', 'GET,POST,PUT,PATCH,DELETE,OPTIONS'),
        'allowed_headers' => env('GATEWAY_CORS_ALLOWED_HEADERS', 'Content-Type,Authorization,X-Requested-With,X-Trace-Id'),
        'exposed_headers' => env('GATEWAY_CORS_EXPOSED_HEADERS', 'X-Trace-Id'),
        'max_age' => (int) env('GATEWAY_CORS_MAX_AGE', 86400),
        'allow_credentials' => filter_var(env('GATEWAY_CORS_ALLOW_CREDENTIALS', false), FILTER_VALIDATE_BOOLEAN),
    ],
];
