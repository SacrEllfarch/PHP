# 开发指南

## 1. 环境要求

- PHP `8.0+`
- Composer
- MySQL `8.0`
- Redis `6.0+`
- ThinkPHP `8.0`

## 2. 本地初始化建议

如果当前目录尚未初始化 ThinkPHP 项目，可在安全目录中执行：

```bash
composer create-project topthink/think api-gateway
cd api-gateway
composer require predis/predis
```

如果已在本仓库初始化，请不要重复执行覆盖命令，优先检查已有 `composer.json`。

## 3. 配置项建议

`.env` 推荐包含：

```dotenv
APP_DEBUG=true
APP_TRACE=true

DATABASE_TYPE=mysql
DATABASE_HOST=127.0.0.1
DATABASE_PORT=3306
DATABASE_NAME=api_gateway
DATABASE_USER=root
DATABASE_PASS=

REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=
REDIS_DB=0

GATEWAY_SIGNATURE_WINDOW=300
GATEWAY_MAX_BODY_SIZE=1048576
GATEWAY_UPSTREAM_CONNECT_TIMEOUT=3
GATEWAY_UPSTREAM_READ_TIMEOUT=10
GATEWAY_ADMIN_TOKEN=change-me
```

注意：真实 `.env` 不应提交到版本库。

## 4. 分支和任务建议

- 每个 SDD 任务对应一个小分支或一次小提交。
- 先实现主链路，再补管理体验和扩展能力。
- 修改需求时先更新 `docs/specs/*/requirements.md`。
- 修改数据库时同步更新迁移和 `docs/database.md`。

## 5. 代码组织建议

```text
app/service/Gateway/GatewayService.php
app/service/Auth/AuthService.php
app/service/RateLimit/RateLimitService.php
app/service/Routing/RouteMatchService.php
app/service/Logging/GatewayLogService.php
app/middleware/GatewayAuthMiddleware.php
app/middleware/RateLimitMiddleware.php
app/middleware/TraceMiddleware.php
```

## 6. 响应格式

成功响应：

```json
{
  "code": "OK",
  "message": "success",
  "trace_id": "01HYEXAMPLE",
  "data": {}
}
```

失败响应：

```json
{
  "code": "ROUTE_NOT_FOUND",
  "message": "未匹配到可用路由",
  "trace_id": "01HYEXAMPLE",
  "data": null
}
```

## 7. 测试建议

优先覆盖：

- 签名原文构造
- 签名校验
- nonce 防重放
- 限流计数
- 路由匹配
- 上游超时
- 错误响应格式

建议命令：

```bash
composer validate
php think route:list
vendor/bin/phpunit
```

具体命令以项目实际安装的依赖为准。

