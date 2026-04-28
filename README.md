# 简单 API 网关系统

本项目计划基于 `PHP 8.0`、`ThinkPHP 8.0`、`MySQL 8.0` 和 `Redis` 开发一个简单 API 网关系统，并采用 `SDD（规格驱动开发）` 模式推进。

## 项目目标

第一阶段目标是交付一个可运行、可验证、易扩展的 API 网关基础版本：

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

## 推荐初始化命令

当前仓库尚未初始化 ThinkPHP 代码时，可参考：

```bash
composer create-project topthink/think api-gateway
cd api-gateway
composer require topthink/think-orm
composer require predis/predis
```

如果已在当前目录初始化项目，请根据实际目录调整命令，避免覆盖已有文件。

## 版本规划

| 版本 | 目标 |
| --- | --- |
| v0.1 | 完成文档、规格、目录约束和技术方案 |
| v0.2 | 完成应用、密钥、路由、策略的数据模型 |
| v0.3 | 完成鉴权、限流、路由匹配和转发 |
| v0.4 | 完成日志、健康检查和基础管理 API |
| v1.0 | 补齐测试、部署说明和基础稳定性优化 |
