/**
 * The steady state: what 5,000 phones do while a race is running.
 *
 * Intervals are the ones the project promises the client (APK-BUILD-GUIDE §6), not invented load:
 *   /race/status      every 10 s
 *   /ar/locations/{id} every 15 s while a post is awaiting_unlock
 *   /quiz/timer/{id}  every 10 s, for the fraction of teams inside a quiz
 *   /tracking/position every 10 s, outdoor only (OUTDOOR=1 to include it)
 *
 * TARGET=http://10.0.0.10 PHONES=5000 k6 run race-steady.js
 */
import http from 'k6/http';
import { sleep } from 'k6';
import { TARGET, loadAccounts, accountFor, authHeaders } from './lib/env.js';
import { record, handleSummary as summary } from './lib/report.js';

const accounts = loadAccounts();
const PHONES = Number(__ENV.PHONES || 500);
const MINUTES = Number(__ENV.MINUTES || 5);
const OUTDOOR = __ENV.OUTDOOR === '1';
const IN_QUIZ = Number(__ENV.IN_QUIZ || 0.2);        // share of teams inside a quiz at any moment
const AWAITING = Number(__ENV.AWAITING || 0.3);      // share standing at a post, not yet opened

export const options = {
  scenarios: {
    steady: {
      executor: 'constant-vus',
      vus: PHONES,
      duration: `${MINUTES}m`,
      gracefulStop: '15s',
    },
  },
  thresholds: {
    // The gate for phase 0 is "we can measure", but these are the numbers later phases must beat.
    'http_req_failed': ['rate<0.01'],
    'eureka_latency{endpoint:race/status}': ['p(95)<200'],
    'eureka_failed': ['count<1'],
  },
};

export default function () {
  const acct = accountFor(accounts, __VU);
  const h = authHeaders(acct.token);

  record('race/status', http.get(`${TARGET}/api/v1/race/status`, h));

  if (acct.post_id && __VU % 100 < AWAITING * 100) {
    // 403 awaiting_unlock is the expected answer here, and it is the cheap path we want measured.
    const res = http.get(`${TARGET}/api/v1/ar/locations/${acct.post_id}`, h);
    record('ar/locations (awaiting)', res, res.status === 403 ? 403 : 200);
  }

  if (acct.attempt_id && __VU % 100 < IN_QUIZ * 100) {
    record('quiz/timer', http.get(`${TARGET}/api/v1/quiz/timer/${acct.attempt_id}`, h));
  }

  if (OUTDOOR) {
    record('tracking/position', http.post(`${TARGET}/api/v1/tracking/position`, JSON.stringify({
      latitude: -6.2 + (__VU % 100) / 10000,
      longitude: 106.8 + (__VU % 100) / 10000,
      accuracy: 12.5,
      device_info: 'k6',
    }), h));
  }

  sleep(10);   // the promised interval; every VU is one phone, not one request
}

export const handleSummary = summary;
