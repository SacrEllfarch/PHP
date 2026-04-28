# 数据库设计

本文档描述第一阶段 MySQL 数据表建议。字段可在实现时根据 ThinkPHP 迁移工具调整，但语义需要保持一致。

## 1. gateway_apps

调用方应用表。

| 字段 | 类型 | 说明 |
| --- | --- | --- |
| id | bigint unsigned | 主键 |
| name | varchar(100) | 应用名称 |
| app_key | varchar(64) | 应用标识，唯一 |
| app_secret_hash | varchar(255) | 密钥哈希或加密值 |
| status | tinyint | 状态：1 启用，0 禁用 |
| remark | varchar(255) | 备注 |
| created_at | datetime | 创建时间 |
| updated_at | datetime | 更新时间 |

索引：

- `uk_app_key(app_key)`
- `idx_status(status)`

## 2. gateway_routes

路由配置表。

| 字段 | 类型 | 说明 |
| --- | --- | --- |
| id | bigint unsigned | 主键 |
| name | varchar(100) | 路由名称 |
| method | varchar(10) | HTTP 方法，支持 ANY |
| gateway_path | varchar(255) | 网关路径 |
| match_type | varchar(20) | 匹配类型：exact、prefix |
| upstream_url | varchar(500) | 上游地址 |
| priority | int | 优先级，数值越大越优先 |
| connect_timeout | int | 连接超时秒数 |
| read_timeout | int | 读取超时秒数 |
| status | tinyint | 状态：1 启用，0 禁用 |
| created_at | datetime | 创建时间 |
| updated_at | datetime | 更新时间 |

索引：

- `idx_method_path(method, gateway_path)`
- `idx_status_priority(status, priority)`

## 3. gateway_rate_limits

限流策略表。

| 字段 | 类型 | 说明 |
| --- | --- | --- |
| id | bigint unsigned | 主键 |
| app_id | bigint unsigned null | 应用 ID，空表示全局策略 |
| route_id | bigint unsigned null | 路由 ID，空表示应用级策略 |
| window_seconds | int | 时间窗口秒数 |
| max_requests | int | 窗口内最大请求数 |
| status | tinyint | 状态：1 启用，0 禁用 |
| created_at | datetime | 创建时间 |
| updated_at | datetime | 更新时间 |

索引：

- `idx_app_route(app_id, route_id)`
- `idx_status(status)`

## 4. gateway_request_logs

请求日志表。

| 字段 | 类型 | 说明 |
| --- | --- | --- |
| id | bigint unsigned | 主键 |
| trace_id | varchar(64) | 链路 ID |
| app_key | varchar(64) | 调用方标识 |
| route_id | bigint unsigned null | 匹配路由 ID |
| method | varchar(10) | HTTP 方法 |
| path | varchar(500) | 请求路径 |
| upstream_url | varchar(500) | 上游地址 |
| request_ip | varchar(64) | 客户端 IP |
| status_code | int | 响应状态码 |
| error_code | varchar(64) null | 网关错误码 |
| duration_ms | int | 请求耗时毫秒 |
| created_at | datetime | 创建时间 |

索引：

- `idx_trace_id(trace_id)`
- `idx_app_key_created(app_key, created_at)`
- `idx_route_created(route_id, created_at)`
- `idx_error_code(error_code)`

## 5. gateway_admin_logs

管理操作日志表。

| 字段 | 类型 | 说明 |
| --- | --- | --- |
| id | bigint unsigned | 主键 |
| operator | varchar(100) | 操作者 |
| action | varchar(100) | 操作类型 |
| target_type | varchar(50) | 操作对象类型 |
| target_id | bigint unsigned null | 操作对象 ID |
| change_summary | json | 变更摘要 |
| request_ip | varchar(64) | 操作 IP |
| created_at | datetime | 创建时间 |

索引：

- `idx_operator_created(operator, created_at)`
- `idx_target(target_type, target_id)`

## 6. Redis Key 设计

| Key | 说明 | TTL |
| --- | --- | --- |
| `gateway:nonce:{app_key}:{nonce}` | 防重放 nonce | 签名窗口秒数 |
| `gateway:rate:app:{app_key}:{window}` | 应用级限流计数 | 窗口秒数 |
| `gateway:rate:route:{app_key}:{route_id}:{window}` | 路由级限流计数 | 窗口秒数 |
| `gateway:routes:enabled` | 启用路由缓存 | 按配置 |

## 7. 数据安全

- `app_secret` 不保存明文。
- 日志只保存脱敏后的关键字段。
- 删除策略优先使用软删除或状态禁用，避免审计信息丢失。

