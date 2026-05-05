# 测试与运行验收

本文档说明当前 API 网关 MVP 的本地运行方式、自动化冒烟测试和人工验收口径。

## 1. 测试目标

当前 MVP 已实现的可测能力包括：

- `/health` 健康检查。
- `/gateway/*` 基于 `GATEWAY_UPSTREAM_BASE_URL` 的简单代理。
- `TraceId`、访问日志、CORS 预检和统一错误响应。
- `public/dashboard.html` 前端控制台页面。

暂未进入自动化覆盖的能力：

- App-Key、签名、timestamp、nonce 防重放。
- Redis 限流。
- MySQL 应用、路由、策略和审计日志持久化。
- 管理 API CRUD。

## 2. 本地运行

安装依赖：

```bash
composer install
cp .env.example .env
```

启动模拟上游服务：

```bash
php -S 127.0.0.1:9000 -t runtime/upstream-demo
```

另开一个终端启动网关：

```bash
php think run -H 0.0.0.0 -p 8000
```

访问入口：

- 网关健康检查：`http://127.0.0.1:8000/health`
- 网关代理示例：`http://127.0.0.1:8000/gateway/hello.json`
- 前端控制台：`http://127.0.0.1:8000/dashboard.html`

## 3. 自动化冒烟测试

执行：

```bash
composer test:smoke
```

脚本会自动完成：

1. 启动 `runtime/upstream-demo` 作为本地上游服务。
2. 启动 ThinkPHP 网关服务。
3. 验证 `/health` 返回 `SUCCESS`、`status=ok` 和 `trace_id`。
4. 验证 `/gateway/hello.json` 能拿到上游响应。
5. 验证 `OPTIONS /gateway/hello.json` 返回 CORS 响应头。
6. 验证不存在路由返回 `ROUTE_NOT_FOUND`。
7. 验证前端控制台页面可访问。

可选环境变量：

```bash
APP_HOST=127.0.0.1 APP_PORT=8010 UPSTREAM_PORT=9010 composer test:smoke
```

## 4. 手工接口验收

健康检查：

```bash
curl -i http://127.0.0.1:8000/health
```

验收标准：

- HTTP 状态码为 `200`。
- JSON 包含 `code=SUCCESS`。
- JSON 包含 `trace_id`。
- `dependencies.mysql` 和 `dependencies.redis` 当前允许为 `unchecked`。

代理转发：

```bash
curl -i http://127.0.0.1:8000/gateway/hello.json
```

验收标准：

- HTTP 状态码跟随上游。
- 响应体包含本地上游返回的内容。
- 响应头包含 `X-Trace-Id`。

CORS 预检：

```bash
curl -i -X OPTIONS http://127.0.0.1:8000/gateway/hello.json \
  -H "Origin: http://localhost:5173" \
  -H "Access-Control-Request-Method: GET"
```

验收标准：

- HTTP 状态码为 `204`。
- 响应头包含 `Access-Control-Allow-Origin`、`Access-Control-Allow-Methods` 和 `Access-Control-Allow-Headers`。

不存在路由：

```bash
curl -i http://127.0.0.1:8000/not-exists
```

验收标准：

- HTTP 状态码为 `404`。
- JSON 包含 `code=ROUTE_NOT_FOUND`。
- JSON 包含 `trace_id`。

## 5. 后续测试补充

下一阶段实现鉴权、限流和持久化后，需要继续补充：

- 签名原文构造和 HMAC-SHA256 校验单元测试。
- timestamp 有效窗口和 nonce 重放测试。
- Redis 限流计数、过期时间和并发边界测试。
- 路由匹配优先级和禁用路由测试。
- 上游连接超时、读取超时、非法 Host 和大请求体测试。
- 管理接口鉴权、参数校验和敏感字段脱敏测试。
