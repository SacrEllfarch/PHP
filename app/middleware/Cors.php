<?php

declare(strict_types=1);

namespace app\middleware;

use Closure;
use think\Request;
use think\Response;

class Cors
{
    public function handle(Request $request, Closure $next): Response
    {
        if (strtoupper($request->method()) === 'OPTIONS') {
            return response('', 204)->header($this->headers($request));
        }

        /** @var Response $response */
        $response = $next($request);
        $response->header($this->headers($request));

        return $response;
    }

    private function headers(Request $request): array
    {
        $origin = (string) $request->header('Origin', '');
        $allowedOrigins = (array) config('gateway.cors.allowed_origins', ['*']);
        $allowCredentials = (bool) config('gateway.cors.allow_credentials', false);

        $allowOrigin = $this->resolveAllowOrigin($origin, $allowedOrigins, $allowCredentials);

        $headers = [
            'Access-Control-Allow-Origin' => $allowOrigin,
            'Access-Control-Allow-Methods' => (string) config('gateway.cors.allowed_methods'),
            'Access-Control-Allow-Headers' => (string) config('gateway.cors.allowed_headers'),
            'Access-Control-Expose-Headers' => (string) config('gateway.cors.exposed_headers'),
            'Access-Control-Max-Age' => (string) config('gateway.cors.max_age'),
            'Vary' => 'Origin',
        ];

        if ($allowCredentials) {
            $headers['Access-Control-Allow-Credentials'] = 'true';
        }

        return $headers;
    }

    private function resolveAllowOrigin(string $origin, array $allowedOrigins, bool $allowCredentials): string
    {
        if (in_array('*', $allowedOrigins, true)) {
            return $allowCredentials && $origin !== '' ? $origin : '*';
        }

        if ($origin !== '' && in_array($origin, $allowedOrigins, true)) {
            return $origin;
        }

        return $allowedOrigins[0] ?? 'null';
    }
}
