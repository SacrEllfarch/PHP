# 简单 API 网关系统

本项目计划基于 `PHP 8.0`、`ThinkPHP 8.0`、`MySQL 8.0` 和 `Redis` 开发一个简单 API 网关系统，并采用 `SDD（规格驱动开发）` 模式推进。

## 项目目标

当前 MVP 目标先收敛为一个可运行、可验证的最小 ThinkPHP API 网关：

- ThinkPHP 8 最小 API 项目骨架
- `/health` 健康检查
- `/gateway/*` 简单 HTTP 代理入口
- 伪静态入口配置
- 全局 trace_id、访问日志和错误响应
- 浏览器跨域访问 CORS 处理

## 当前实现状态

当前仓库已经完成最小 MVP 骨架，重点是先把请求入口、代理转发和基础可观测性跑通。应用管理、签名鉴权、Redis 限流和数据库持久化仍处于规格和任务规划阶段。

| 能力 | 当前状态 | 说明 |
| --- | --- | --- |
| ThinkPHP 8 项目骨架 | 已完成 | 已包含 `app/`、`config/`、`route/`、`public/` 等基础目录。 |
| `/health` 健康检查 | 已完成 | 当前返回应用状态，MySQL 和 Redis 依赖状态仍为 `unchecked`。 |
| `/gateway/*` 简单代理 | 已完成 | 按 `GATEWAY_UPSTREAM_BASE_URL` 拼接路径并转发请求。 |
| trace_id、访问日志、统一错误响应 | 已完成 | 全局中间件会写入访问日志并返回稳定错误结构。 |
| CORS 和 OPTIONS 预检 | 已完成 | 支持通过环境变量配置跨域策略。 |
| MySQL 数据模型和迁移 | 未完成 | 表结构已在文档中设计，迁移文件待补。 |
| 应用密钥、签名鉴权、防重放 | 未完成 | 已有安全设计，代码实现待补。 |
| Redis 限流 | 未完成 | 已有 Redis Key 设计，代码实现待补。 |
| 管理 API | 未完成 | 应用、路由、限流策略 CRUD 待补。 |
| 自动化测试 | 未完成 | `composer.json` 已声明 PHPUnit，测试用例待补。 |

后续完整版本再逐步扩展：

- 调用方应用与密钥管理
- API 路由配置与请求转发
- 基于签名的接口鉴权
- 基于 Redis 的限流
- 请求日志和错误日志
- 健康检查接口
- 基础管理 API

## 技术栈

| 类型  | 技术               |
| --- | ---------------- |
| 语言  | PHP 8.0+         |
| 框架  | ThinkPHP 8.0     |
| 数据库 | MySQL 8.0        |
| 缓存  | Redis            |
| 包管理 | Composer         |
| 接口  | RESTful JSON API |

## SDD 工作流

本项目采用规格驱动开发，功能开发前先补齐规格文档。

```text
PRD 产品基线 -> SDD 需求规格 -> 技术设计 -> 任务拆分 -> BDD 行为场景 -> 代码实现 -> 验证交付
```

主要文档：

- [项目协作规范](AGENTS.md)
- [文档索引](docs/README.md)
- [产品需求文档 PRD](docs/prd.md)
- [SDD 工作流](docs/sdd/README.md)
- [BDD 行为规格](docs/bdd/README.md)
- [API 网关需求规格](docs/specs/api-gateway/requirements.md)
- [API 网关技术设计](docs/specs/api-gateway/design.md)
- [API 网关开发任务](docs/specs/api-gateway/tasks.md)
- [系统架构说明](docs/architecture.md)
- [API 网关框架图](docs/architecture-diagram.md)
- [开发指南](docs/development.md)
- [数据库设计](docs/database.md)
- [安全设计](docs/security.md)

## 本地启动

当前仓库已经初始化 ThinkPHP 代码，不要重复执行 `composer create-project`。

### 1. 安装依赖并启动网关

```bash
composer install
cp .env.example .env
php think run -H 0.0.0.0 -p 8000
```

### 2. 启动本地测试上游

`/gateway/*` 会转发到 `.env` 中的 `GATEWAY_UPSTREAM_BASE_URL`。默认值是 `http://127.0.0.1:9000`，因此可以另开一个终端启动临时上游服务：

```bash
mkdir -p /tmp/api-gateway-upstream
printf '{"upstream":"ok","path":"demo"}' > /tmp/api-gateway-upstream/demo
php -S 127.0.0.1:9000 -t /tmp/api-gateway-upstream
```

### 3. 验证接口

```bash
curl http://127.0.0.1:8000/health
curl http://127.0.0.1:8000/gateway/demo
curl -i -X OPTIONS http://127.0.0.1:8000/gateway/demo -H "Origin: http://localhost:5173"
curl http://127.0.0.1:8000/not-exists
```

预期结果：

- `/health` 返回 `code=OK`，并包含 `trace_id`。
- `/gateway/demo` 返回本地测试上游的响应内容。
- `OPTIONS /gateway/demo` 返回 CORS 相关响应头。
- `/not-exists` 返回统一错误响应，错误码为 `ROUTE_NOT_FOUND`。

## 关键配置

配置文件来自 `.env`，示例见 [.env.example](.env.example)，读取逻辑见 [config/gateway.php](config/gateway.php)。

| 配置项 | 默认值 | 说明 |
| --- | --- | --- |
| `GATEWAY_UPSTREAM_BASE_URL` | `http://127.0.0.1:9000` | 简单代理模式下的上游基础地址。 |
| `GATEWAY_ALLOWED_UPSTREAM_HOSTS` | `localhost,127.0.0.1` | 允许转发的上游 Host，用于降低 SSRF 风险。 |
| `GATEWAY_UPSTREAM_CONNECT_TIMEOUT` | `3.0` | 连接上游的超时时间，单位秒。 |
| `GATEWAY_UPSTREAM_READ_TIMEOUT` | `10.0` | 读取上游响应的超时时间，单位秒。 |
| `GATEWAY_MAX_BODY_SIZE` | `1048576` | 最大请求体大小，默认 1 MiB。 |
| `GATEWAY_CORS_ALLOWED_ORIGINS` | `*` | 允许跨域访问的来源。 |
| `GATEWAY_CORS_ALLOWED_METHODS` | `GET,POST,PUT,PATCH,DELETE,OPTIONS` | 允许跨域访问的方法。 |
| `GATEWAY_CORS_ALLOWED_HEADERS` | `Content-Type,Authorization,X-Requested-With,X-Trace-Id` | 允许跨域请求携带的 Header。 |

更多本地开发说明见 [开发指南](docs/development.md)。

## 验证与测试

当前阶段建议先运行以下检查：

```bash
composer validate
php think route:list
composer test:smoke
```

自动化测试用例尚未补齐，后续需要优先覆盖签名原文构造、签名校验、nonce 防重放、限流计数、路由匹配、上游超时和统一错误响应。当前 MVP 已补充 `composer test:smoke` 冒烟测试，用于验证健康检查、代理转发、CORS、404 兜底和前端控制台可访问性。

## MVP 前端控制台

启动网关后可以打开：

```text
http://127.0.0.1:8000/dashboard.html
```

当前控制台支持：

- 查看 `/health` 健康状态、依赖状态和 `trace_id`。
- 通过 `/gateway/*` 发起代理请求调试。
- 查看响应状态码、耗时、响应体和返回的 `X-Trace-Id`。
- 查看 MVP 业务链路和本地验收清单。

更多测试说明见 [测试与运行验收](docs/testing.md)。

## 部署入口

生产或测试环境的 Web 根目录必须指向 `public/`，不要暴露仓库根目录，避免 `.env`、源码和运行时文件被直接访问。

- Apache 可使用 [public/.htaccess](public/.htaccess)。
- Nginx 可参考 [public/nginx.conf.example](public/nginx.conf.example)。

部署时需要确认：

- PHP 版本满足 `8.0+`。
- Composer 依赖已经安装。
- `.env` 中没有使用默认管理令牌或真实明文密钥。
- `GATEWAY_ALLOWED_UPSTREAM_HOSTS` 只配置可信上游域名或 IP。

## 已知限制

- `/health` 暂未真实探测 MySQL 和 Redis。
- 当前代理只支持单一基础上游地址，尚未接入数据库路由表。
- 当前未启用应用鉴权、签名校验、nonce 防重放和 Redis 限流。
- 当前访问日志写入应用日志，尚未落库到 `gateway_request_logs`。
- 当前没有管理 API 和自动化测试用例。

## 版本规划

| 版本   | 目标                 |
| ---- |:------------------ |
| v0.1 | 完成文档、规格、目录约束和技术方案  |
| v0.2 | 完成应用、密钥、路由、策略的数据模型 |
| v0.3 | 完成鉴权、限流、路由匹配和转发    |
| v0.4 | 完成日志、健康检查和基础管理 API |
| v1.0 | 补齐测试、部署说明和基础稳定性优化  |
