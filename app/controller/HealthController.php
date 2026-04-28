<?php

declare(strict_types=1);

namespace app\controller;

use app\support\ApiResponse;
use think\Response;

class HealthController
{
    public function index(): Response
    {
        return ApiResponse::success([
            'status' => 'ok',
            'service' => 'api-gateway',
            'dependencies' => [
                'mysql' => 'unchecked',
                'redis' => 'unchecked',
            ],
        ]);
    }
}
