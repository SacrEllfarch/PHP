# API 网关框架图

本文用 Mermaid 描述当前 API 网关的项目框架、请求链路和后续目标架构。当前代码仍处于最小 MVP 阶段，图中会明确区分“已实现”和“规划中”能力。

## 1. 当前 MVP 框架图

```mermaid
flowchart LR
    client[调用方 / 浏览器 / curl]
    nginx[Nginx / Apache / ThinkPHP 内置服务器]
    public[public/index.php]
    think[ThinkPHP 8 应用]
    route[route/app.php]
    middleware[全局中间件链<br/>TraceId / AccessLog / Cors]
    health[HealthController<br/>/health]
    gateway[GatewayController<br/>/gateway/*]
    response[ApiResponse<br/>统一 JSON 响应]
    trace[TraceContext<br/>trace_id 上下文]
    config[config/gateway.php<br/>上游 / 超时 / CORS 配置]
    guzzle[Guzzle HTTP Client]
    upstream[上游 HTTP 服务]
    log[ThinkPHP Log<br/>访问日志]

    client --> nginx --> public --> think --> route --> middleware
    middleware --> health --> response --> client
    middleware --> gateway
    middleware --> trace
    middleware --> log
    gateway --> config
    gateway --> guzzle --> upstream
    upstream --> gateway --> client
```

当前已落地的核心能力：

- `TraceId` 中间件负责生成或透传 `X-Trace-Id`。
- `AccessLog` 中间件负责记录请求路径、状态码、耗时和客户端 IP。
- `Cors` 中间件负责跨域响应头和 `OPTIONS` 预检。
- `HealthController` 提供 `/health` 健康检查入口。
- `GatewayController` 基于 `GATEWAY_UPSTREAM_BASE_URL` 做简单 HTTP 转发。
- `ApiResponse` 负责健康检查、路由不存在、上游异常等统一 JSON 响应。

## 2. 目标分层架构图

```mermaid
flowchart TB
    subgraph entry[入口层]
        caller[调用方应用]
        admin[管理员 / 运维]
        web[Web Server<br/>public/]
        router[ThinkPHP Route]
    end

    subgraph middleware[中间件层]
        trace[TraceId]
        cors[CORS]
        auth[签名鉴权<br/>规划中]
        replay[Timestamp + Nonce 防重放<br/>规划中]
        rate[Redis 限流<br/>规划中]
        access[访问日志]
    end

    subgraph controller[控制器层]
        health[HealthController]
        gateway[GatewayController]
        appAdmin[Admin/AppController<br/>规划中]
        routeAdmin[Admin/RouteController<br/>规划中]
        policyAdmin[Admin/RateLimitController<br/>规划中]
    end

    subgraph service[业务服务层]
        appService[AppService<br/>应用与密钥]
        authService[AuthService<br/>签名校验]
        routeService[RouteMatchService<br/>路由匹配]
        rateService[RateLimitService<br/>限流策略]
        proxyService[GatewayProxyService<br/>请求转发]
        logService[GatewayLogService<br/>日志落库]
    end

    subgraph data[数据与基础设施层]
        mysql[(MySQL 8<br/>应用 / 路由 / 策略 / 日志)]
        redis[(Redis<br/>nonce / 限流 / 路由缓存)]
        upstream[上游业务服务]
        config[ThinkPHP Config / .env]
        logger[ThinkPHP Log]
    end

    caller --> web --> router --> trace --> cors --> auth --> replay --> rate --> access --> gateway
    admin --> web --> router --> trace --> cors --> appAdmin
    admin --> routeAdmin
    admin --> policyAdmin

    health --> config
    gateway --> proxyService
    gateway --> routeService
    gateway --> logService
    appAdmin --> appService
    routeAdmin --> routeService
    policyAdmin --> rateService

    auth --> authService --> mysql
    replay --> redis
    rate --> rateService --> redis
    appService --> mysql
    routeService --> mysql
    routeService --> redis
    proxyService --> config
    proxyService --> upstream
    logService --> mysql
    access --> logger
```

目标分层原则：

- 控制器只负责接收请求、参数编排和响应封装。
- 鉴权、限流、防重放优先放在中间件和服务层，不堆在控制器里。
- MySQL 保存长期业务数据，Redis 保存短生命周期运行状态。
- 上游转发必须受协议、Host 白名单、超时和请求体大小约束。

## 3. 网关请求时序图

```mermaid
sequenceDiagram
    autonumber
    participant C as 调用方
    participant W as Web Server
    participant T as ThinkPHP Route
    participant M as 中间件链
    participant G as GatewayController
    participant R as 路由匹配服务
    participant A as 鉴权服务
    participant L as 限流服务
    participant U as 上游服务
    participant DB as MySQL
    participant RS as Redis

    C->>W: 请求 /gateway/*
    W->>T: 转发到 public/index.php
    T->>M: 匹配网关路由
    M->>M: 生成 trace_id / CORS / 访问日志
    M-->>A: 校验签名（规划中）
    A-->>DB: 查询应用和密钥（规划中）
    M-->>RS: 校验 nonce（规划中）
    M-->>L: 执行限流（规划中）
    L-->>RS: 读写限流计数（规划中）
    M->>G: 进入网关控制器
    G-->>R: 匹配上游路由（规划中）
    R-->>DB: 读取启用路由（规划中）
    G->>U: 当前 MVP 直接按基础地址转发
    U-->>G: 返回上游响应
    G-->>C: 透传允许的响应头和响应体
```

## 4. 目录职责图

```mermaid
flowchart TB
    root[项目根目录]
    app[app/<br/>应用代码]
    controller[app/controller/<br/>HTTP 控制器]
    middleware[app/middleware/<br/>全局中间件]
    support[app/support/<br/>通用响应与上下文]
    config[config/<br/>框架与网关配置]
    route[route/<br/>路由定义]
    public[public/<br/>Web 入口和伪静态]
    docs[docs/<br/>SDD / 架构 / 安全 / 数据库文档]
    database[database/<br/>迁移和种子数据，待补]
    tests[tests/<br/>自动化测试，待补]

    root --> app
    app --> controller
    app --> middleware
    app --> support
    root --> config
    root --> route
    root --> public
    root --> docs
    root --> database
    root --> tests
```

## 5. 现状与下一步

当前项目已经具备最小请求链路，后续建议按以下顺序扩展：

1. 补齐 MySQL 迁移和基础模型。
2. 抽出 `GatewayProxyService`，让控制器继续保持薄层。
3. 实现应用管理、路由管理和限流策略管理接口。
4. 实现 HMAC-SHA256 签名鉴权、timestamp 窗口校验和 Redis nonce 防重放。
5. 实现 Redis 限流和请求日志落库。
6. 补齐核心服务单元测试和网关主链路接口测试。
