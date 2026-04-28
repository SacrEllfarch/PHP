<?php

use app\support\ApiResponse;
use think\facade\Route;

Route::get('health', 'HealthController/index');
Route::any('gateway/<path>', 'GatewayController/proxy')->pattern(['path' => '.+']);
Route::any('gateway', 'GatewayController/proxy');

Route::miss(function () {
    return ApiResponse::error('ROUTE_NOT_FOUND', '接口不存在', 404);
});
