# 文档索引

本目录保存 API 网关系统的规格、设计、开发和安全文档。开发前优先阅读规格文档，开发中同步维护设计和任务状态。

## 文档结构

```text
docs/
  README.md
  prd.md
  architecture.md
  architecture-diagram.md
  database.md
  development.md
  testing.md
  security.md
  sdd/
    README.md
    spec-template.md
  bdd/
    README.md
    api-gateway-mvp.md
    traceability.md
  specs/
    api-gateway/
      requirements.md
      design.md
      tasks.md
```

## 阅读顺序

1. [产品需求文档 PRD](prd.md)
2. [SDD 工作流](sdd/README.md)
3. [API 网关需求规格](specs/api-gateway/requirements.md)
4. [API 网关技术设计](specs/api-gateway/design.md)
5. [API 网关开发任务](specs/api-gateway/tasks.md)
6. [BDD 行为规格](bdd/README.md)
7. [API 网关 MVP 行为场景](bdd/api-gateway-mvp.md)
8. [系统架构说明](architecture.md)
9. [API 网关框架图](architecture-diagram.md)
10. [数据库设计](database.md)
11. [安全设计](security.md)
12. [开发指南](development.md)
13. [测试与运行验收](testing.md)

## 维护规则

- 产品目标、用户场景和范围变化先改 `prd.md`。
- 功能需求变化再改 `requirements.md`，并同步 `design.md` 和 `tasks.md`。
- 验收行为变化必须同步 `bdd/api-gateway-mvp.md` 和 `bdd/traceability.md`。
- 数据库字段变化必须同步 `database.md`。
- 涉及鉴权、签名、限流、日志脱敏的变化必须同步 `security.md`。
- 每个任务完成时更新 `tasks.md` 的状态和验证方式。
