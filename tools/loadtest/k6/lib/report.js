// One summary shape for every scenario, so phase 2 can be compared with phase 0 instead of remembered.
import { Counter, Trend } from 'k6/metrics';

export const rps = new Counter('eureka_requests');
export const throttled = new Counter('eureka_throttled');     // 429s are the signal, not a crash
export const failed = new Counter('eureka_failed');           // 5xx, timeouts, wrong shape
export const latency = new Trend('eureka_latency', true);

/**
 * Record one response. `name` is the endpoint as the plan names it, so the table reads like §2 of
 * SCALING-PLAN.md rather than like a URL list.
 */
export function record(name, res, expect = 200) {
  rps.add(1, { endpoint: name });
  latency.add(res.timings.duration, { endpoint: name });

  if (res.status === 429) {
    throttled.add(1, { endpoint: name });
    return 'throttled';
  }
  if (res.status !== expect) {
    failed.add(1, { endpoint: name, status: String(res.status) });
    return 'failed';
  }
  return 'ok';
}

/** A markdown table beside the raw JSON: the plan's phase gates are read by people. */
export function handleSummary(data) {
  const m = data.metrics;
  const line = (label, key) => {
    const v = m[key];
    if (!v) return `| ${label} | — | — | — |`;
    const p = v.values;
    return `| ${label} | ${(p['p(95)'] ?? 0).toFixed(1)} ms | ${(p.avg ?? 0).toFixed(1)} ms | ${p.count ?? '—'} |`;
  };

  const md = [
    `# Load test — ${new Date().toISOString()}`,
    '',
    `Target: \`${__ENV.TARGET}\`  ·  scenario: \`${__ENV.SCENARIO || 'all'}\``,
    '',
    '| Metric | p95 | avg | count |',
    '|---|---|---|---|',
    line('Request duration (all)', 'http_req_duration'),
    line('Waiting (server time)', 'http_req_waiting'),
    '',
    `- Requests: **${m.eureka_requests?.values?.count ?? 0}**`,
    `- Throttled (429): **${m.eureka_throttled?.values?.count ?? 0}**`,
    `- Failed (5xx / timeout / shape): **${m.eureka_failed?.values?.count ?? 0}**`,
    `- Checks failed: **${m.checks?.values?.fails ?? 0}**`,
    '',
    'Per-endpoint p95 is in `summary.json` under `eureka_latency` tagged by `endpoint`.',
    '',
  ].join('\n');

  return {
    'summary.json': JSON.stringify(data, null, 2),
    'summary.md': md,
    stdout: md,
  };
}
