<?php

declare(strict_types=1);

namespace app\controller;

use app\support\ApiResponse;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use think\Request;
use think\Response;

class GatewayController
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client(['http_errors' => false]);
    }

    public function proxy(Request $request): Response
    {
        $path = (string) $request->route('path', '');
        $upstreamUrl = $this->buildUpstreamUrl($path, (string) $request->server('QUERY_STRING'));

        if ($upstreamUrl === '') {
            return ApiResponse::error('UPSTREAM_NOT_CONFIGURED', '未配置上游服务地址', 503);
        }

        if (!$this->isAllowedUpstream($upstreamUrl)) {
            return ApiResponse::error('UPSTREAM_NOT_ALLOWED', '上游服务地址不在允许范围内', 502);
        }

        $body = $request->getContent();
        if (strlen($body) > (int) config('gateway.upstream.max_body_size')) {
            return ApiResponse::error('REQUEST_BODY_TOO_LARGE', '请求体超过网关限制', 413);
        }

        try {
            $upstreamResponse = $this->client->request($request->method(), $upstreamUrl, [
                'headers' => $this->forwardHeaders($request),
                'body' => $body,
                'connect_timeout' => (float) config('gateway.upstream.connect_timeout'),
                'timeout' => (float) config('gateway.upstream.read_timeout'),
            ]);
        } catch (ConnectException) {
            return ApiResponse::error('UPSTREAM_TIMEOUT', '上游服务连接超时或不可达', 504);
        } catch (GuzzleException) {
            return ApiResponse::error('UPSTREAM_ERROR', '上游服务请求失败', 502);
        }

        return Response::create(
            (string) $upstreamResponse->getBody(),
            'html',
            $upstreamResponse->getStatusCode()
        )->header($this->responseHeaders($upstreamResponse->getHeaders()));
    }

    private function buildUpstreamUrl(string $path, string $queryString): string
    {
        $baseUrl = (string) config('gateway.upstream.base_url');
        if ($baseUrl === '') {
            return '';
        }

        $url = rtrim($baseUrl, '/') . '/' . ltrim($path, '/');

        return $queryString === '' ? $url : $url . '?' . $queryString;
    }

    private function isAllowedUpstream(string $url): bool
    {
        $parts = parse_url($url);
        if (!in_array($parts['scheme'] ?? '', ['http', 'https'], true)) {
            return false;
        }

        $allowedHosts = (array) config('gateway.upstream.allowed_hosts', []);
        if ($allowedHosts === []) {
            return true;
        }

        return in_array($parts['host'] ?? '', $allowedHosts, true);
    }

    private function forwardHeaders(Request $request): array
    {
        $headers = [];
        $allowed = [
            'accept',
            'authorization',
            'content-type',
            'user-agent',
            'x-request-id',
            'x-trace-id',
        ];

        foreach ($request->header() as $name => $value) {
            if (in_array($name, $allowed, true)) {
                $headers[$this->normalizeHeaderName($name)] = $value;
            }
        }

        $headers['X-Forwarded-For'] = $request->ip();

        return $headers;
    }

    private function responseHeaders(array $headers): array
    {
        $allowed = [
            'cache-control',
            'content-type',
            'etag',
            'last-modified',
        ];

        $filtered = [];
        foreach ($headers as $name => $values) {
            if (in_array(strtolower($name), $allowed, true)) {
                $filtered[$name] = implode(', ', $values);
            }
        }

        return $filtered;
    }

    private function normalizeHeaderName(string $name): string
    {
        return implode('-', array_map('ucfirst', explode('-', $name)));
    }
}
