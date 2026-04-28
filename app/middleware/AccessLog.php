<?php

declare(strict_types=1);

namespace app\middleware;

use app\support\TraceContext;
use Closure;
use think\facade\Log;
use think\Request;
use think\Response;
use Throwable;

class AccessLog
{
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);

        try {
            /** @var Response $response */
            $response = $next($request);
            $this->write($request, $response->getCode(), $startedAt);

            return $response;
        } catch (Throwable $e) {
            $this->write($request, 500, $startedAt, $e->getMessage());
            throw $e;
        }
    }

    private function write(Request $request, int $statusCode, float $startedAt, ?string $error = null): void
    {
        $context = [
            'trace_id' => TraceContext::get(),
            'method' => $request->method(),
            'path' => '/' . ltrim($request->pathinfo(), '/'),
            'status_code' => $statusCode,
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'ip' => $request->ip(),
            'error' => $error,
        ];

        Log::info('gateway_access ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
