# API 网关技术设计

## 0. 设计基线

本设计承接 [API 网关系统 PRD](../../prd.md) 和 [API 网关需求规格](requirements.md)，目标是支撑 MVP 主链路：应用接入、路由配置、签名鉴权、防重放、限流、请求转发、日志审计和健康检查。

关键设计决策：

| 编号 | 决策 | 覆盖需求 |
| --- | --- | --- |
| DD-001 | 使用 ThinkPHP 中间件串联网关主链路。 | FR-007 至 FR-014 |
| DD-002 | 使用 HMAC-SHA256 实现调用方签名认证。 | FR-008 |
| DD-003 | 使用 Redis 保存 nonce 和固定窗口限流计数。 | FR-009 至 FR-011 |
| DD-004 | 使用 MySQL 保存应用、路由、限流策略和审计日志。 | FR-001 至 FR-006、FR-014 |
| DD-005 | 管理接口和网关转发入口分离。 | FR-016、NFR-001 |
| DD-006 | 上游转发必须设置超时、Header 过滤和上游地址约束。 | FR-012、FR-013、NFR-003 |

## 1. 总体设计

系统采用 ThinkPHP 8 的 HTTP 请求生命周期，通过中间件组成网关处理链路：

```text
请求进入 -> TraceId -> 基础校验 -> 鉴权 -> 限流 -> 路由匹配 -> 请求转发 -> 响应封装 -> 日志记录
```

管理接口和网关转发入口分离：

- `/admin/*`：管理应用、路由和策略。
- `/gateway/*`：承接外部调用并转发到上游。
- `/health`：健康检查。

## 2. 模块职责

| 模块 | 职责 | 覆盖需求 |
| --- | --- | --- |
| AuthService | 解析 app_key、校验签名、校验时间戳和 nonce | FR-008、FR-009 |
| RateLimitService | 使用 Redis 判断请求是否超过限流阈值 | FR-010、FR-011 |
| RouteMatchService | 根据方法、路径、状态和优先级匹配路由 | FR-007 |
| UpstreamClient | 转发 HTTP 请求到上游并处理超时、失败和 Header 过滤 | FR-012、FR-013 |
| GatewayLogService | 记录请求日志、错误日志、管理日志和耗时 | FR-014 |
| AppService | 管理调用方应用、状态和密钥生命周期 | FR-001 至 FR-004 |
| AdminRouteService | 管理路由配置和状态 | FR-005、FR-006 |
| RateLimitPolicyService | 管理应用级和路由级限流策略 | FR-010、FR-011、FR-016 |
| HealthService | 检查 MySQL、Redis 和应用状态 | FR-015 |

## 3. 请求处理链路

1. 创建或读取 `trace_id`，写入请求上下文。
2. 校验基础 Header：`X-App-Key`、`X-Timestamp`、`X-Nonce`、`X-Signature`。
3. 读取应用信息，确认应用存在且启用。
4. 校验时间戳窗口和 nonce 去重。
5. 计算服务端签名并与请求签名做恒定时间比较。
6. 根据应用、方法、路径匹配限流策略。
7. 使用 Redis 执行限流计数。
8. 匹配启用状态的路由。
9. 组装上游请求并发起 HTTP 调用。
10. 返回上游响应或网关错误响应。
11. 记录请求日志。

## 4. 签名设计

推荐请求 Header：

| Header | 说明 |
| --- | --- |
| `X-App-Key` | 调用方应用标识 |
| `X-Timestamp` | Unix 秒级时间戳 |
| `X-Nonce` | 随机字符串 |
| `X-Signature` | HMAC-SHA256 签名 |
| `X-Trace-Id` | 可选，调用方传入的链路 ID |

推荐签名原文：

```text
HTTP_METHOD + "\n" +
REQUEST_PATH + "\n" +
QUERY_STRING + "\n" +
BODY_SHA256 + "\n" +
X_TIMESTAMP + "\n" +
X_NONCE
```

签名算法：

```text
base64_encode(hash_hmac('sha256', canonical_string, app_secret, true))
```

注意事项：

- `QUERY_STRING` 需要按参数名升序规范化。
- 空请求体的 `BODY_SHA256` 使用空字符串的 SHA256。
- 比较签名时使用 `hash_equals`。
- nonce 使用 Redis 保存，TTL 与时间戳窗口一致。

## 5. 限流设计

第一阶段推荐固定窗口计数，简单可靠，后续可升级滑动窗口或令牌桶。

Redis Key 设计：

```text
rate_limit:app:{app_key}:{yyyyMMddHHmm}
rate_limit:route:{app_key}:{route_id}:{yyyyMMddHHmm}
```

处理规则：

- 首次请求执行 `INCR` 并设置过期时间。
- 计数超过阈值返回 `429 Too Many Requests`。
- 接口级策略存在时优先使用接口级策略。
- Redis 异常时第一阶段建议失败关闭，返回 `503`，避免流量绕过保护。

## 6. 路由设计

路由字段建议：

- HTTP 方法：`GET`、`POST`、`PUT`、`PATCH`、`DELETE`、`ANY`
- 网关路径：如 `/gateway/users`
- 上游地址：如 `http://user-service.internal/users`
- 超时时间：连接超时、读取超时
- 状态：启用、禁用

匹配策略：

- 精确路径优先。
- 再考虑前缀匹配。
- 同等条件下按优先级字段排序。
- 禁用路由不参与匹配。

## 7. 错误响应

统一 JSON 响应：

```json
{
  "code": "AUTH_SIGNATURE_INVALID",
  "message": "签名无效",
  "trace_id": "01HYEXAMPLE",
  "data": null
}
```

错误码建议：

| HTTP 状态 | 错误码 | 说明 |
| --- | --- | --- |
| 400 | `BAD_REQUEST` | 请求参数错误 |
| 401 | `AUTH_REQUIRED` | 缺少认证信息 |
| 401 | `AUTH_SIGNATURE_INVALID` | 签名无效 |
| 401 | `AUTH_TIMESTAMP_EXPIRED` | 时间戳过期 |
| 401 | `AUTH_NONCE_REPLAYED` | nonce 重放 |
| 403 | `APP_DISABLED` | 应用被禁用 |
| 404 | `ROUTE_NOT_FOUND` | 未匹配路由 |
| 429 | `RATE_LIMITED` | 请求过于频繁 |
| 502 | `UPSTREAM_ERROR` | 上游响应异常 |
| 504 | `UPSTREAM_TIMEOUT` | 上游超时 |
| 503 | `DEPENDENCY_UNAVAILABLE` | 依赖不可用 |

## 8. 可观测性

- 每个请求必须有 `trace_id`。
- 日志记录请求方法、路径、app_key、route_id、状态码、耗时和错误码。
- 上游错误和网关内部错误分开记录。
- 管理操作应记录操作者、操作对象和变更摘要。

## 9. 风险与取舍

| 风险 | 说明 | 应对 |
| --- | --- | --- |
| 转发目标被滥用 | 错误配置可能导致 SSRF | 上游地址白名单和内网域名限制 |
| Redis 不可用 | 限流和 nonce 校验受影响 | 明确失败策略，优先保护上游 |
| 日志写入影响性能 | 同步写库可能拖慢请求 | 后续可改为队列或异步日志 |
| 固定窗口限流不够平滑 | 边界时刻可能出现突刺 | 后续升级滑动窗口或令牌桶 |

## 10. 设计追踪矩阵

| 需求 | 设计决策 | 主要模块 | 验证重点 |
| --- | --- | --- | --- |
| FR-001 至 FR-004 | DD-004、DD-005 | AppService | 密钥只返回一次、禁用后鉴权失败 |
| FR-005 至 FR-007 | DD-004、DD-005 | AdminRouteService、RouteMatchService | 精确匹配、前缀匹配、优先级 |
| FR-008、FR-009 | DD-001、DD-002、DD-003 | AuthService | 签名、时间戳、nonce 防重放 |
| FR-010、FR-011 | DD-003、DD-004 | RateLimitService、RateLimitPolicyService | 应用级和路由级限流优先级 |
| FR-012、FR-013 | DD-001、DD-006 | UpstreamClient | Header 过滤、超时、上游错误 |
| FR-014 | DD-004 | GatewayLogService | trace_id、错误码、日志脱敏 |
| FR-015 | DD-003、DD-004 | HealthService | MySQL 和 Redis 状态 |
| FR-016、NFR-001 | DD-005 | 管理控制器和管理中间件 | 管理令牌、访问来源限制 |
