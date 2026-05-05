# 开发指南

## 1. 环境要求

- PHP `8.0+`
- Composer
- MySQL `8.0`
- Redis `6.0+`
- ThinkPHP `8.0`

## 2. 本地初始化建议

当前仓库已经初始化 ThinkPHP 项目骨架，首次拉取后执行：

```bash
composer install
cp .env.example .env
php think run
```

不要在当前目录重复执行 `composer create-project`，避免覆盖已有 SDD 文档和业务代码。

## 3. 配置项建议

`.env` 推荐包含：

```dotenv
APP_DEBUG=true
APP_TRACE=true

DATABASE_TYPE=mysql
DATABASE_HOST=127.0.0.1
DATABASE_PORT=3306
DATABASE_NAME=api_gateway
DATABASE_USERNAME=root
DATABASE_PASSWORD=

REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=
REDIS_DATABASE=0
REDIS_TIMEOUT=3.0

GATEWAY_SIGNATURE_WINDOW=300
GATEWAY_UPSTREAM_BASE_URL=http://127.0.0.1:9000
GATEWAY_MAX_BODY_SIZE=1048576
GATEWAY_UPSTREAM_CONNECT_TIMEOUT=3
GATEWAY_UPSTREAM_READ_TIMEOUT=10
GATEWAY_ADMIN_TOKEN=change-me
GATEWAY_ALLOWED_UPSTREAM_HOSTS=localhost,127.0.0.1

GATEWAY_CORS_ALLOWED_ORIGINS=*
GATEWAY_CORS_ALLOWED_METHODS=GET,POST,PUT,PATCH,DELETE,OPTIONS
GATEWAY_CORS_ALLOWED_HEADERS=Content-Type,Authorization,X-Requested-With,X-Trace-Id
GATEWAY_CORS_EXPOSED_HEADERS=X-Trace-Id
GATEWAY_CORS_MAX_AGE=86400
GATEWAY_CORS_ALLOW_CREDENTIALS=false
```

注意：真实 `.env` 不应提交到版本库。

## 4. 最小 MVP 路由

当前最小网关只保留三类入口：

| 方法 | 路径 | 说明 |
| --- | --- | --- |
| GET | `/health` | 返回应用健康状态。 |
| ANY | `/gateway` | 代理到 `GATEWAY_UPSTREAM_BASE_URL` 根路径。 |
| ANY | `/gateway/<path>` | 将 `<path>` 拼接到上游基础地址后转发。 |

示例：`GET /gateway/users?id=1` 会转发到 `GATEWAY_UPSTREAM_BASE_URL/users?id=1`。

## 5. 伪静态配置

本地开发可直接使用 ThinkPHP 内置服务器：

```bash
php think run -H 0.0.0.0 -p 8000
```

Apache 使用 [public/.htaccess](../public/.htaccess)，Nginx 可参考 [public/nginx.conf.example](../public/nginx.conf.example)。生产环境的 Web 根目录必须指向 `public/`，避免暴露项目源码、`.env` 和运行时文件。

## 6. 分支和任务建议

- 每个 SDD 任务对应一个小分支或一次小提交。
- 先实现主链路，再补管理体验和扩展能力。
- 修改需求时先更新 `docs/specs/*/requirements.md`。
- 修改数据库时同步更新迁移和 `docs/database.md`。

## 7. 代码组织建议

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

## 8. 响应格式

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

## 9. 测试建议

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
composer test:smoke
vendor/bin/phpunit
```

具体命令以项目实际安装的依赖为准。

## 10. MVP 前端控制台

当前仓库提供一个无需构建工具的静态控制台：

```text
public/dashboard.html
```

启动网关后访问：

```text
http://127.0.0.1:8000/dashboard.html
```

该页面用于本地联调和演示，支持查看健康状态、发起 `/gateway/*` 代理请求、查看响应耗时和 `X-Trace-Id`。正式管理后台仍应按 SDD 规格在管理 API、鉴权和权限模型补齐后再扩展。
