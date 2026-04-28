# BDD 行为规格说明

BDD（Behavior-Driven Development，行为驱动开发）用于把 PRD 和 SDD 中的需求转成可讨论、可验收、可自动化的业务行为场景。

本目录是 API 网关 MVP 的行为规格层，位置在 SDD 之后、测试实现之前：

```text
PRD 产品基线
  -> SDD 需求规格
    -> 技术设计和任务拆分
      -> BDD 行为场景
        -> 自动化测试和手工验收
```

## 1. 文档目标

- 用业务语言描述系统行为，方便产品、开发、测试、运维共同评审。
- 将 `FR/NFR/TASK` 转换为 `Feature/Rule/Scenario`。
- 为后续 Behat、Cucumber 或 PHPUnit 接口测试提供场景基线。
- 明确 MVP 验收边界，减少“实现了代码但不符合业务预期”的风险。

## 2. 文档结构

```text
docs/bdd/
  README.md
  api-gateway-mvp.md
  traceability.md
```

| 文件 | 说明 |
| --- | --- |
| [api-gateway-mvp.md](api-gateway-mvp.md) | API 网关 MVP 的 BDD 行为场景 |
| [traceability.md](traceability.md) | PRD、SDD、BDD、任务之间的追踪关系 |

## 3. 编写规则

BDD 场景使用 Gherkin 风格：

```gherkin
Feature: 功能名称
  Rule: 业务规则
    Scenario: 具体场景
      Given 前置条件
      When 用户行为或系统事件
      Then 期望结果
      And 附加结果
```

编写约束：

- `Feature` 对应一个完整业务能力，例如应用管理、签名鉴权、限流。
- `Rule` 对应业务规则或验收约束，例如“禁用应用不得通过鉴权”。
- `Scenario` 只描述一个可验证行为。
- `Given` 描述前置数据和系统状态，不描述实现细节。
- `When` 描述触发动作，通常是一次 HTTP 请求或管理操作。
- `Then` 描述可观察结果，例如状态码、错误码、日志、是否转发上游。
- 每个场景必须标注关联 `FR/NFR/BR/TASK`。

## 4. 编号规则

| 类型 | 前缀 | 示例 | 说明 |
| --- | --- | --- | --- |
| BDD 功能 | `BDD-F` | `BDD-F-001` | 一个行为功能集 |
| BDD 场景 | `BDD-S` | `BDD-S-001` | 一个可验收场景 |
| BDD 规则 | `BDD-R` | `BDD-R-001` | 一个业务行为规则 |

## 5. 场景优先级

| 优先级 | 说明 |
| --- | --- |
| P0 | MVP 主链路必须覆盖，发布前必须通过 |
| P1 | MVP 应覆盖，允许在实现阶段分批补齐 |
| P2 | 后续增强能力，当前只保留意图 |

## 6. 自动化建议

PHP 项目可选：

- 使用 Behat 执行 Gherkin 场景。
- 使用 PHPUnit 实现接口级行为测试。
- 使用 HTTP Mock Server 模拟上游服务。
- 使用 Redis 和 MySQL 测试容器验证集成场景。

MVP 阶段可以先让 BDD 文档作为手工验收脚本，等主链路实现稳定后再转为自动化测试。

## 7. 维护规则

- PRD 变更后，先判断是否影响 BDD 场景。
- `requirements.md` 的 `FR/NFR/BR` 变更后，必须同步 [traceability.md](traceability.md)。
- 新增 P0 功能时，必须补充至少一个成功场景和一个失败场景。
- 场景通过实现验证后，可在测试记录或任务说明中标记对应 `BDD-S`。

