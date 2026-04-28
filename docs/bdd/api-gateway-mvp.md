# API 网关 MVP BDD 行为场景

## 1. 文档信息

| 项目 | 内容 |
| --- | --- |
| 文档版本 | v0.1 |
| 来源 PRD | [API 网关系统 PRD](../prd.md) |
| 来源 SDD | [API 网关需求规格](../specs/api-gateway/requirements.md) |
| 目标版本 | MVP |
| 状态 | 草稿 |

## 2. 全局约定

公共响应格式：

```json
{
  "code": "OK",
  "message": "success",
  "trace_id": "01HYEXAMPLE",
  "data": {}
}
```

公共错误响应：

```json
{
  "code": "AUTH_SIGNATURE_INVALID",
  "message": "签名无效",
  "trace_id": "01HYEXAMPLE",
  "data": null
}
```

公共前置条件：

```gherkin
Background:
  Given 网关服务已启动
  And MySQL 连接可用
  And Redis 连接可用
  And 管理接口使用固定管理令牌保护
```

## 3. Feature: 管理接口保护

关联：`FR-016`、`NFR-001`、`BR-010`、`TASK-301`

### BDD-S-001 未携带管理令牌时拒绝访问管理接口

优先级：P0

```gherkin
Scenario: 未携带管理令牌创建应用
  Given 管理接口要求请求携带有效管理令牌
  When 请求方不携带管理令牌调用 POST /admin/apps
  Then 网关应返回 401
  And 响应 code 应为 AUTH_REQUIRED
  And 系统不应创建任何应用
```

### BDD-S-002 携带有效管理令牌时允许访问管理接口

优先级：P0

```gherkin
Scenario: 携带有效管理令牌创建应用
  Given 管理接口要求请求携带有效管理令牌
  When 管理员携带有效管理令牌调用 POST /admin/apps
  Then 网关应继续执行业务参数校验
  And 不应因管理令牌问题拒绝请求
```

## 4. Feature: 应用管理

关联：`FR-001`、`FR-002`、`FR-003`、`FR-004`、`BR-001`、`BR-002`、`TASK-302` 至 `TASK-305`

### BDD-S-003 创建应用时返回一次性密钥

优先级：P0

```gherkin
Scenario: 管理员创建调用方应用
  Given 管理员已通过管理接口认证
  And 系统中不存在名称为 "订单系统" 的应用
  When 管理员提交应用名称 "订单系统" 创建应用
  Then 系统应返回 200
  And 响应 data 应包含 app_key
  And 响应 data 应包含 app_secret
  And 应用状态应为启用
  And app_key 应全局唯一
```

### BDD-S-004 查询应用详情时不返回明文密钥

优先级：P0

```gherkin
Scenario: 管理员查询应用详情
  Given 系统中已存在启用应用 "订单系统"
  When 管理员调用 GET /admin/apps/{id}
  Then 系统应返回应用详情
  And 响应 data 不应包含明文 app_secret
  And 响应 data 可以包含密钥创建或更新时间
```

### BDD-S-005 禁用应用后调用方不能通过鉴权

优先级：P0

```gherkin
Scenario: 禁用应用后请求网关
  Given 系统中已存在应用 A
  And 应用 A 已被管理员禁用
  And 调用方使用应用 A 的有效密钥生成正确签名
  When 调用方请求 GET /gateway/orders
  Then 网关应返回 403
  And 响应 code 应为 APP_DISABLED
  And 网关不应转发请求到上游服务
```

### BDD-S-006 重置密钥后旧密钥立即失效

优先级：P0

```gherkin
Scenario: 管理员重置应用密钥
  Given 系统中已存在启用应用 A
  And 调用方保存了应用 A 的旧 app_secret
  When 管理员调用 POST /admin/apps/{id}/secret/reset
  Then 系统应返回新的 app_secret
  And 后续查询应用详情不应返回新的明文 app_secret
  When 调用方继续使用旧 app_secret 生成签名请求网关
  Then 网关应返回 401
  And 响应 code 应为 AUTH_SIGNATURE_INVALID
```

## 5. Feature: 路由管理和路由匹配

关联：`FR-005`、`FR-006`、`FR-007`、`BR-005`、`BR-006`、`TASK-306`、`TASK-307`、`TASK-601`、`TASK-602`

### BDD-S-007 创建启用路由后可被请求匹配

优先级：P0

```gherkin
Scenario: 创建精确匹配路由
  Given 管理员已通过管理接口认证
  When 管理员创建 GET /gateway/orders 到 http://order-service.local/orders 的精确匹配路由
  Then 系统应保存路由配置
  And 路由状态应为启用
  When 调用方使用合法签名请求 GET /gateway/orders
  Then 网关应匹配该路由
```

### BDD-S-008 禁用路由不参与匹配

优先级：P0

```gherkin
Scenario: 请求已禁用路由
  Given 系统中存在 GET /gateway/orders 路由
  And 该路由已被管理员禁用
  When 调用方使用合法签名请求 GET /gateway/orders
  Then 网关不应匹配该禁用路由
  And 如果没有其他可用路由，网关应返回 404
  And 响应 code 应为 ROUTE_NOT_FOUND
```

### BDD-S-009 精确匹配优先于前缀匹配

优先级：P0

```gherkin
Scenario: 同时存在精确路由和前缀路由
  Given 系统中存在启用前缀路由 GET /gateway/orders/*
  And 系统中存在启用精确路由 GET /gateway/orders/detail
  When 调用方使用合法签名请求 GET /gateway/orders/detail
  Then 网关应优先匹配精确路由
```

### BDD-S-010 ANY 方法可以匹配任意 HTTP 方法

优先级：P1

```gherkin
Scenario Outline: ANY 路由匹配多个方法
  Given 系统中存在启用路由 ANY /gateway/common
  When 调用方使用合法签名请求 <method> /gateway/common
  Then 网关应匹配该 ANY 路由

  Examples:
    | method |
    | GET    |
    | POST   |
    | PUT    |
    | DELETE |
```

## 6. Feature: 签名鉴权和防重放

关联：`FR-008`、`FR-009`、`BR-003`、`BR-004`、`TASK-401` 至 `TASK-405`

### BDD-S-011 正确签名请求可以进入路由匹配流程

优先级：P0

```gherkin
Scenario: 调用方使用正确签名访问网关
  Given 系统中存在启用应用 A
  And 应用 A 使用有效 app_secret
  And 请求 timestamp 在 300 秒有效窗口内
  And 请求 nonce 未被使用过
  And 请求签名符合 HMAC-SHA256 规则
  When 调用方请求 GET /gateway/orders
  Then 网关应通过鉴权
  And 请求应进入限流和路由匹配流程
```

### BDD-S-012 缺少认证 Header 时拒绝请求

优先级：P0

```gherkin
Scenario Outline: 缺少必要鉴权 Header
  Given 系统中存在启用应用 A
  When 调用方请求 GET /gateway/orders 且缺少 <header>
  Then 网关应返回 401
  And 响应 code 应为 AUTH_REQUIRED
  And 网关不应转发请求到上游服务

  Examples:
    | header       |
    | X-App-Key    |
    | X-Timestamp  |
    | X-Nonce      |
    | X-Signature  |
```

### BDD-S-013 签名错误时拒绝请求

优先级：P0

```gherkin
Scenario: 调用方使用错误签名访问网关
  Given 系统中存在启用应用 A
  And 请求 timestamp 在 300 秒有效窗口内
  And 请求 nonce 未被使用过
  When 调用方使用错误 X-Signature 请求 GET /gateway/orders
  Then 网关应返回 401
  And 响应 code 应为 AUTH_SIGNATURE_INVALID
  And 网关不应转发请求到上游服务
```

### BDD-S-014 时间戳过期时拒绝请求

优先级：P0

```gherkin
Scenario: 调用方使用过期 timestamp
  Given 系统中存在启用应用 A
  And 请求 timestamp 早于当前时间 300 秒以上
  When 调用方请求 GET /gateway/orders
  Then 网关应返回 401
  And 响应 code 应为 AUTH_TIMESTAMP_EXPIRED
  And 网关不应转发请求到上游服务
```

### BDD-S-015 nonce 重复时拒绝请求

优先级：P0

```gherkin
Scenario: 调用方重复使用 nonce
  Given 系统中存在启用应用 A
  And nonce "nonce-001" 已在有效窗口内使用过
  When 调用方再次使用 nonce "nonce-001" 请求 GET /gateway/orders
  Then 网关应返回 401
  And 响应 code 应为 AUTH_NONCE_REPLAYED
  And 网关不应转发请求到上游服务
```

## 7. Feature: 限流保护

关联：`FR-010`、`FR-011`、`BR-007`、`TASK-501` 至 `TASK-504`

### BDD-S-016 未超过应用级限流时继续处理请求

优先级：P0

```gherkin
Scenario: 应用请求未超过限流阈值
  Given 应用 A 的限流策略为每 60 秒最多 100 次
  And 应用 A 当前窗口请求次数为 99
  When 应用 A 使用合法签名请求 GET /gateway/orders
  Then 网关应允许请求进入路由匹配流程
  And 当前窗口请求次数应增加到 100
```

### BDD-S-017 超过应用级限流时拒绝请求

优先级：P0

```gherkin
Scenario: 应用请求超过限流阈值
  Given 应用 A 的限流策略为每 60 秒最多 100 次
  And 应用 A 当前窗口请求次数已达到 100
  When 应用 A 使用合法签名请求 GET /gateway/orders
  Then 网关应返回 429
  And 响应 code 应为 RATE_LIMITED
  And 网关不应转发请求到上游服务
```

### BDD-S-018 路由级限流优先于应用级限流

优先级：P0

```gherkin
Scenario: 同时存在应用级和路由级限流策略
  Given 应用 A 的应用级限流策略为每 60 秒最多 100 次
  And 路由 R 的路由级限流策略为每 60 秒最多 10 次
  And 应用 A 对路由 R 的当前窗口请求次数已达到 10
  When 应用 A 使用合法签名请求路由 R
  Then 网关应按路由级策略返回 429
  And 响应 code 应为 RATE_LIMITED
```

## 8. Feature: 请求转发和上游异常处理

关联：`FR-012`、`FR-013`、`BR-008`、`BR-011`、`TASK-603` 至 `TASK-605`

### BDD-S-019 匹配路由后成功转发到上游

优先级：P0

```gherkin
Scenario: 网关成功转发请求
  Given 系统中存在启用应用 A
  And 系统中存在启用路由 GET /gateway/orders -> http://order-service.local/orders
  And 上游服务返回 200 和订单列表
  When 应用 A 使用合法签名请求 GET /gateway/orders?status=paid
  Then 网关应向上游发起 GET /orders?status=paid
  And 网关应返回上游响应状态码 200
  And 网关应返回上游响应体
```

### BDD-S-020 上游超时时返回稳定错误

优先级：P0

```gherkin
Scenario: 上游服务读取超时
  Given 系统中存在启用路由 GET /gateway/slow -> http://slow-service.local/slow
  And 该路由读取超时配置为 10 秒
  And 上游服务在 10 秒内没有响应
  When 调用方使用合法签名请求 GET /gateway/slow
  Then 网关应返回 504
  And 响应 code 应为 UPSTREAM_TIMEOUT
  And 响应不应包含内部异常堆栈
```

### BDD-S-021 上游地址不符合白名单时拒绝保存路由

优先级：P0

```gherkin
Scenario: 管理员创建不安全上游地址路由
  Given 管理员已通过管理接口认证
  And 上游白名单只允许 internal.local 域名
  When 管理员创建上游地址为 http://169.254.169.254/latest/meta-data 的路由
  Then 系统应拒绝创建路由
  And 响应 code 应为 BAD_REQUEST
  And 系统不应保存该路由
```

## 9. Feature: 日志审计和可追踪性

关联：`FR-014`、`NFR-002`、`NFR-005`、`BR-009`、`TASK-701` 至 `TASK-705`

### BDD-S-022 成功请求应记录请求日志

优先级：P1

```gherkin
Scenario: 网关成功处理请求后写入日志
  Given 调用方请求已通过鉴权、限流和路由匹配
  And 上游服务返回 200
  When 网关完成请求处理
  Then 系统应记录请求日志
  And 日志应包含 trace_id、app_key、route_id、method、path、status_code、duration_ms
  And 日志不应包含明文 app_secret
```

### BDD-S-023 鉴权失败请求应记录错误日志

优先级：P1

```gherkin
Scenario: 签名错误请求被拒绝后写入错误日志
  Given 系统中存在启用应用 A
  When 调用方使用错误签名请求 GET /gateway/orders
  Then 网关应返回 AUTH_SIGNATURE_INVALID
  And 系统应记录错误日志
  And 错误日志应包含 trace_id 和错误码
  And 错误日志不应记录完整 X-Signature
```

### BDD-S-024 管理操作应记录操作日志

优先级：P1

```gherkin
Scenario: 管理员禁用应用后写入管理日志
  Given 管理员已通过管理接口认证
  And 系统中存在启用应用 A
  When 管理员调用 PATCH /admin/apps/{id}/status 将应用 A 禁用
  Then 系统应记录管理操作日志
  And 日志应包含 operator、action、target_type、target_id、change_summary、request_ip
```

## 10. Feature: 健康检查

关联：`FR-015`、`NFR-004`、`TASK-704`

### BDD-S-025 依赖正常时健康检查通过

优先级：P1

```gherkin
Scenario: MySQL 和 Redis 均可用
  Given 网关服务已启动
  And MySQL 连接可用
  And Redis 连接可用
  When 运维人员调用 GET /health
  Then 系统应返回 200
  And 响应 code 应为 OK
  And 响应 data 应显示 MySQL 状态为 healthy
  And 响应 data 应显示 Redis 状态为 healthy
```

### BDD-S-026 Redis 不可用时健康检查返回异常状态

优先级：P1

```gherkin
Scenario: Redis 连接不可用
  Given 网关服务已启动
  And MySQL 连接可用
  And Redis 连接不可用
  When 运维人员调用 GET /health
  Then 系统应返回 503
  And 响应 code 应为 DEPENDENCY_UNAVAILABLE
  And 响应不应包含 Redis 密码或连接串
```

## 11. Feature: MVP 端到端主链路

关联：`PG-001` 至 `PG-007`、`FR-001` 至 `FR-016`、`TASK-805`

### BDD-S-027 新应用在 5 分钟内完成首个路由调用

优先级：P0

```gherkin
Scenario: 管理员完成新应用接入并成功调用上游
  Given 管理员已通过管理接口认证
  And 上游订单服务可用
  When 管理员创建应用 "订单系统"
  And 管理员创建 GET /gateway/orders 到订单服务的启用路由
  And 调用方使用新应用密钥生成合法签名
  And 调用方请求 GET /gateway/orders
  Then 网关应通过鉴权
  And 网关应通过限流
  And 网关应匹配订单路由
  And 网关应转发请求到订单服务
  And 网关应返回订单服务响应
  And 系统应记录包含 trace_id 的请求日志
```

## 12. 场景状态表

| 场景 | 优先级 | 当前状态 | 验证方式 |
| --- | --- | --- | --- |
| BDD-S-001 至 BDD-S-006 | P0 | 待实现 | 管理接口测试 |
| BDD-S-007 至 BDD-S-010 | P0/P1 | 待实现 | 路由管理和匹配测试 |
| BDD-S-011 至 BDD-S-015 | P0 | 待实现 | 鉴权单元测试和接口测试 |
| BDD-S-016 至 BDD-S-018 | P0 | 待实现 | Redis 集成测试 |
| BDD-S-019 至 BDD-S-021 | P0 | 待实现 | 上游 Mock 接口测试 |
| BDD-S-022 至 BDD-S-024 | P1 | 待实现 | 日志字段和脱敏检查 |
| BDD-S-025 至 BDD-S-026 | P1 | 待实现 | 健康检查接口测试 |
| BDD-S-027 | P0 | 待实现 | MVP 端到端验收 |

