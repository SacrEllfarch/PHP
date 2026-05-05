#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
APP_HOST="${APP_HOST:-127.0.0.1}"
APP_PORT="${APP_PORT:-8000}"
UPSTREAM_HOST="${UPSTREAM_HOST:-127.0.0.1}"
UPSTREAM_PORT="${UPSTREAM_PORT:-9000}"
APP_URL="http://${APP_HOST}:${APP_PORT}"
UPSTREAM_URL="http://${UPSTREAM_HOST}:${UPSTREAM_PORT}"
UPSTREAM_DIR="${ROOT_DIR}/runtime/upstream-demo"
APP_LOG="${TMPDIR:-/tmp}/api-gateway-app-smoke.log"
UPSTREAM_LOG="${TMPDIR:-/tmp}/api-gateway-upstream-smoke.log"

require_command() {
  if ! command -v "$1" >/dev/null 2>&1; then
    echo "缺少命令：$1" >&2
    exit 1
  fi
}

wait_until_ready() {
  local url="$1"
  local name="$2"

  for _ in $(seq 1 30); do
    if curl -fsS "$url" >/dev/null 2>&1; then
      return 0
    fi
    sleep 0.3
  done

  echo "${name} 未在预期时间内就绪：${url}" >&2
  exit 1
}

assert_contains() {
  local value="$1"
  local expected="$2"
  local label="$3"

  if [[ "$value" != *"$expected"* ]]; then
    echo "断言失败：${label}，期望包含 ${expected}" >&2
    echo "$value" >&2
    exit 1
  fi
}

cleanup() {
  if [[ -n "${APP_PID:-}" ]]; then
    kill "$APP_PID" >/dev/null 2>&1 || true
  fi
  if [[ -n "${UPSTREAM_PID:-}" ]]; then
    kill "$UPSTREAM_PID" >/dev/null 2>&1 || true
  fi
}

trap cleanup EXIT

require_command php
require_command curl

cd "$ROOT_DIR"

php -S "${UPSTREAM_HOST}:${UPSTREAM_PORT}" -t "$UPSTREAM_DIR" >"$UPSTREAM_LOG" 2>&1 &
UPSTREAM_PID=$!

GATEWAY_UPSTREAM_BASE_URL="$UPSTREAM_URL" php think run -H "$APP_HOST" -p "$APP_PORT" >"$APP_LOG" 2>&1 &
APP_PID=$!

wait_until_ready "${UPSTREAM_URL}/hello.json" "本地上游服务"
wait_until_ready "${APP_URL}/health" "API 网关"

health_response="$(curl -fsS "${APP_URL}/health")"
assert_contains "$health_response" '"code":"SUCCESS"' "健康检查 code"
assert_contains "$health_response" '"status":"ok"' "健康检查 status"
assert_contains "$health_response" '"trace_id"' "健康检查 trace_id"

proxy_response="$(curl -fsS "${APP_URL}/gateway/hello.json")"
assert_contains "$proxy_response" "hello from upstream" "网关代理转发"

cors_response="$(curl -isS -X OPTIONS "${APP_URL}/gateway/hello.json" \
  -H "Origin: http://localhost:5173" \
  -H "Access-Control-Request-Method: GET")"
assert_contains "$cors_response" "204 No Content" "CORS 预检状态码"
assert_contains "$cors_response" "Access-Control-Allow-Origin" "CORS 响应头"

miss_response="$(curl -isS "${APP_URL}/not-exists")"
assert_contains "$miss_response" "404 Not Found" "不存在路由状态码"
assert_contains "$miss_response" "ROUTE_NOT_FOUND" "不存在路由错误码"

dashboard_response="$(curl -fsS "${APP_URL}/dashboard.html")"
assert_contains "$dashboard_response" "API 网关 MVP 控制台" "前端控制台页面"

echo "冒烟测试通过：health、gateway 代理、CORS、404 兜底、前端控制台均可访问。"
