<?php

declare(strict_types=1);

namespace app\support;

use think\Response;

class ApiResponse
{
    public static function success(mixed $data = null, string $message = 'success', int $httpStatus = 200): Response
    {
        return json([
            'code' => 'SUCCESS',
            'message' => $message,
            'trace_id' => TraceContext::get(),
            'data' => $data,
        ], $httpStatus);
    }

    public static function error(
        string $code,
        string $message,
        int $httpStatus = 400,
        mixed $data = null
    ): Response {
        return json([
            'code' => $code,
            'message' => $message,
            'trace_id' => TraceContext::get(),
            'data' => $data,
        ], $httpStatus);
    }
}
