<?php

declare(strict_types=1);

namespace app;

use app\support\ApiResponse;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\ValidateException;
use think\Response;
use Throwable;

class ExceptionHandle extends Handle
{
    public function render($request, Throwable $e): Response
    {
        if ($e instanceof ValidateException) {
            return ApiResponse::error('BAD_REQUEST', $e->getMessage(), 400);
        }

        if ($e instanceof HttpException) {
            $statusCode = $e->getStatusCode();

            return ApiResponse::error(
                $statusCode === 404 ? 'ROUTE_NOT_FOUND' : 'HTTP_ERROR',
                $statusCode === 404 ? '接口不存在' : $e->getMessage(),
                $statusCode
            );
        }

        if (config('app.app_debug')) {
            return parent::render($request, $e);
        }

        return ApiResponse::error('INTERNAL_ERROR', '系统内部错误', 500);
    }
}
