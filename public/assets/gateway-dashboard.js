const healthDot = document.querySelector("#healthDot");
const healthSummary = document.querySelector("#healthSummary");
const serviceName = document.querySelector("#serviceName");
const mysqlStatus = document.querySelector("#mysqlStatus");
const redisStatus = document.querySelector("#redisStatus");
const traceId = document.querySelector("#traceId");
const refreshHealth = document.querySelector("#refreshHealth");
const requestForm = document.querySelector("#requestForm");
const requestMethod = document.querySelector("#requestMethod");
const requestPath = document.querySelector("#requestPath");
const requestBody = document.querySelector("#requestBody");
const responseMeta = document.querySelector("#responseMeta");
const responseBody = document.querySelector("#responseBody");

function setHealthState(state, message) {
  healthDot.classList.remove("ok", "error");
  if (state) {
    healthDot.classList.add(state);
  }
  healthSummary.textContent = message;
}

function formatBody(text) {
  try {
    return JSON.stringify(JSON.parse(text), null, 2);
  } catch (_error) {
    return text || "(空响应)";
  }
}

async function loadHealth() {
  setHealthState("", "检测中...");

  try {
    const response = await fetch("/health", {
      headers: {
        Accept: "application/json"
      }
    });
    const payload = await response.json();
    const dependencies = payload.data?.dependencies ?? {};

    serviceName.textContent = payload.data?.service ?? "-";
    mysqlStatus.textContent = dependencies.mysql ?? "-";
    redisStatus.textContent = dependencies.redis ?? "-";
    traceId.textContent = payload.trace_id ?? response.headers.get("X-Trace-Id") ?? "-";
    setHealthState(response.ok ? "ok" : "error", response.ok ? "网关运行正常" : `健康检查异常：${response.status}`);
  } catch (error) {
    serviceName.textContent = "-";
    mysqlStatus.textContent = "-";
    redisStatus.textContent = "-";
    traceId.textContent = "-";
    setHealthState("error", `无法访问 /health：${error.message}`);
  }
}

async function sendGatewayRequest(event) {
  event.preventDefault();

  const method = requestMethod.value;
  const path = requestPath.value.trim() || "/gateway/hello.json";
  const headers = {
    Accept: "application/json"
  };
  const options = {
    method,
    headers
  };

  if (!["GET", "HEAD"].includes(method)) {
    headers["Content-Type"] = "application/json";
    options.body = requestBody.value.trim();
  }

  responseMeta.textContent = "请求中...";
  responseBody.textContent = "";

  try {
    const startedAt = performance.now();
    const response = await fetch(path, options);
    const elapsed = Math.round(performance.now() - startedAt);
    const text = await response.text();
    const trace = response.headers.get("X-Trace-Id") ?? "-";

    responseMeta.textContent = `${method} ${path} -> ${response.status}，${elapsed}ms，trace_id=${trace}`;
    responseBody.textContent = formatBody(text);
  } catch (error) {
    responseMeta.textContent = "请求失败";
    responseBody.textContent = error.message;
  }
}

refreshHealth.addEventListener("click", loadHealth);
requestForm.addEventListener("submit", sendGatewayRequest);

loadHealth();
