<?php

return [
    'default' => 'file',
    'channels' => [
        'file' => [
            'type' => 'file',
            'path' => runtime_path('log'),
            'level' => ['error', 'warning', 'notice', 'info', 'debug'],
            'single' => false,
            'apart_level' => ['error'],
            'max_files' => 30,
        ],
    ],
];
