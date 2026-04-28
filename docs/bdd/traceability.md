# BDD 追踪矩阵

本文档用于追踪 PRD、SDD、BDD 场景和开发任务之间的关系，确保每个 MVP 能力都有可验收行为。

## 1. 总览

| PRD MVP 能力 | SDD 需求 | BDD 场景 | 开发任务 |
| --- | --- | --- | --- |
| 基础管理 API | FR-016、NFR-001、BR-010 | BDD-S-001、BDD-S-002 | TASK-301 |
| 应用创建、查询、启停、重置密钥 | FR-001 至 FR-004、BR-001、BR-002 | BDD-S-003 至 BDD-S-006 | TASK-201、TASK-302 至 TASK-305 |
| 路由创建、启停和请求匹配 | FR-005 至 FR-007、BR-005、BR-006 | BDD-S-007 至 BDD-S-010 | TASK-202、TASK-306、TASK-307、TASK-601、TASK-602 |
| HMAC-SHA256 签名鉴权 | FR-008 | BDD-S-011 至 BDD-S-013 | TASK-401、TASK-402、TASK-405 |
| timestamp + nonce 防重放 | FR-009、BR-003、BR-004 | BDD-S-014、BDD-S-015 | TASK-403、TASK-404 |
| Redis 固定窗口限流 | FR-010、FR-011、BR-007 | BDD-S-016 至 BDD-S-018 | TASK-203、TASK-308、TASK-501 至 TASK-504 |
| HTTP 请求转发 | FR-012、FR-013、BR-008、BR-011 | BDD-S-019 至 BDD-S-021 | TASK-603 至 TASK-605 |
| 请求日志和错误日志 | FR-014、NFR-002、NFR-005、BR-009 | BDD-S-022 至 BDD-S-024 | TASK-204、TASK-205、TASK-701 至 TASK-705 |
| 健康检查 | FR-015、NFR-004 | BDD-S-025、BDD-S-026 | TASK-704 |
| MVP 端到端验收 | PG-001 至 PG-007 | BDD-S-027 | TASK-805 |

## 2. P0 场景清单

发布 MVP 前必须通过：

| 场景 | 行为目标 | 失败风险 |
| --- | --- | --- |
| BDD-S-001 | 管理接口拒绝未授权访问 | 管理接口裸露 |
| BDD-S-003 | 创建应用并返回一次性密钥 | 调用方无法接入 |
| BDD-S-005 | 禁用应用后不可访问网关 | 禁用策略失效 |
| BDD-S-006 | 重置密钥后旧密钥失效 | 密钥泄露后无法阻断 |
| BDD-S-007 | 创建路由后可匹配 | 主链路无法转发 |
| BDD-S-008 | 禁用路由不参与匹配 | 下线接口仍可访问 |
| BDD-S-009 | 精确匹配优先 | 路由选择错误 |
| BDD-S-011 | 正确签名通过鉴权 | 合法调用失败 |
| BDD-S-012 | 缺少 Header 被拒绝 | 鉴权绕过 |
| BDD-S-013 | 错误签名被拒绝 | 签名伪造风险 |
| BDD-S-014 | 过期 timestamp 被拒绝 | 重放攻击风险 |
| BDD-S-015 | 重复 nonce 被拒绝 | 重放攻击风险 |
| BDD-S-016 | 未超限请求继续处理 | 正常请求误杀 |
| BDD-S-017 | 超限请求返回 429 | 上游缺少保护 |
| BDD-S-018 | 路由级限流优先 | 高风险接口保护失效 |
| BDD-S-019 | 成功转发请求到上游 | 网关核心价值缺失 |
| BDD-S-020 | 上游超时返回稳定错误 | 调用方无法判断故障 |
| BDD-S-021 | 拒绝不安全上游地址 | SSRF 风险 |
| BDD-S-027 | 新应用完成端到端调用 | MVP 主链路不可用 |

## 3. P1 场景清单

MVP 应覆盖，允许在实现阶段分批完善：

| 场景 | 行为目标 |
| --- | --- |
| BDD-S-002 | 有效管理令牌允许访问 |
| BDD-S-004 | 查询应用不返回明文密钥 |
| BDD-S-010 | ANY 方法匹配多个方法 |
| BDD-S-022 | 成功请求记录日志 |
| BDD-S-023 | 鉴权失败记录错误日志 |
| BDD-S-024 | 管理操作记录操作日志 |
| BDD-S-025 | 依赖正常时健康检查通过 |
| BDD-S-026 | Redis 不可用时健康检查返回异常状态 |

## 4. 待补充自动化绑定

代码实现后，需要为每个 BDD 场景补充对应测试入口：

| BDD 场景范围 | 建议测试类型 | 预计位置 |
| --- | --- | --- |
| BDD-S-001 至 BDD-S-010 | HTTP 接口测试 | `tests/Feature/Admin` |
| BDD-S-011 至 BDD-S-015 | 单元测试 + HTTP 接口测试 | `tests/Unit/Auth`、`tests/Feature/Gateway` |
| BDD-S-016 至 BDD-S-018 | Redis 集成测试 | `tests/Feature/RateLimit` |
| BDD-S-019 至 BDD-S-021 | 上游 Mock 接口测试 | `tests/Feature/Forwarding` |
| BDD-S-022 至 BDD-S-024 | 日志断言测试 | `tests/Feature/Logging` |
| BDD-S-025 至 BDD-S-026 | 健康检查接口测试 | `tests/Feature/Health` |
| BDD-S-027 | 端到端测试 | `tests/E2E/GatewayOnboarding` |

