<?php

return [
    'app_debug' => env('APP_DEBUG', false),
    'app_trace' => env('APP_TRACE', false),
    'default_timezone' => 'Asia/Shanghai',
    'exception_handle' => app\ExceptionHandle::class,
];
