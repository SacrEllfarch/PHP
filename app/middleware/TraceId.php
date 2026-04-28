<?php

declare(strict_types=1);

namespace app\middleware;

use app\support\TraceContext;
use Closure;
use think\Request;
use think\Response;

class TraceId
{
    public function handle(Request $request, Closure $next): Response
    {
        $traceId = $request->header('X-Trace-Id') ?: TraceContext::generate();
        TraceContext::set($traceId);

        /** @var Response $response */
        $response = $next($request);
        $response->header(['X-Trace-Id' => $traceId]);

        return $response;
    }
}
