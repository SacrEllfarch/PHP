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

后续完整版本再逐步扩展：

- 调用方应用与密钥管理
- API 路由配置与请求转发
- 基于签名的接口鉴权
- 基于 Redis 的限流
- 请求日志和错误日志
- 健康检查接口
- 基础管理 API

## 技术栈

| 类型 | 技术 |
| --- | --- |
| 语言 | PHP 8.0+ |
| 框架 | ThinkPHP 8.0 |
| 数据库 | MySQL 8.0 |
| 缓存 | Redis |
| 包管理 | Composer |
| 接口 | RESTful JSON API |

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
- [开发指南](docs/development.md)
- [数据库设计](docs/database.md)
- [安全设计](docs/security.md)

## 本地启动

当前仓库已经初始化 ThinkPHP 代码，不要重复执行 `composer create-project`。

```bash
composer install
cp .env.example .env
php think run -H 0.0.0.0 -p 8000
```

验证：

```bash
curl http://127.0.0.1:8000/health
curl -i -X OPTIONS http://127.0.0.1:8000/gateway/demo -H "Origin: http://localhost:5173"
```

## 版本规划

| 版本 | 目标 |
| --- | --- |
| v0.1 | 完成文档、规格、目录约束和技术方案 |
| v0.2 | 完成应用、密钥、路由、策略的数据模型 |
| v0.3 | 完成鉴权、限流、路由匹配和转发 |
| v0.4 | 完成日志、健康检查和基础管理 API |
| v1.0 | 补齐测试、部署说明和基础稳定性优化 |
