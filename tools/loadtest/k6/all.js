/**
 * One run, every scenario, in the proportions a real event has — this is the number the plan's phase gates
 * are read from. The others exist to isolate a single endpoint while fixing it.
 *
 * TARGET=http://10.0.0.10 PHONES=5000 k6 run all.js
 *
 * Start small. PHONES=200 on a laptop proves the harness; PHONES=5000 needs a machine that can hold 5,000
 * VUs (k6 wants roughly 1–2 GB per 1,000 VUs) and a network that is not the thing under test.
 */
import http from 'k6/http';
import { sleep } from 'k6';
import { TARGET, loadAccounts, accountFor, authHeaders, jsonHeaders } from './lib/env.js';
import { record, handleSummary as summary } from './lib/report.js';

const accounts = loadAccounts();
const PHONES = Number(__ENV.PHONES || 500);
const MINUTES = Number(__ENV.MINUTES || 10);
const PHOTO_KB = Number(__ENV.PHOTO_KB || 50);
const PHOTO = 'data:image/jpeg;base64,' + 'A'.repeat(Math.max(1, PHOTO_KB) * 1024);

export const options = {
  scenarios: {
    // Doors open: the login storm, then it stops.
    doors: {
      executor: 'ramping-arrival-rate',
      exec: 'login',
      startRate: 1,
      timeUnit: '1s',
      preAllocatedVUs: 100,
      maxVUs: 400,
      stages: [{ target: Math.ceil(PHONES / 600), duration: '10m' }],
    },
    // Everyone syncs in the first ten minutes, then not again.
    sync: {
      executor: 'shared-iterations',
      exec: 'sync',
      vus: 100,
      iterations: PHONES,
      maxDuration: '12m',
    },
    // And the race runs for the whole window.
    race: {
      executor: 'constant-vus',
      exec: 'race',
      vus: PHONES,
      duration: `${MINUTES}m`,
      startTime: '60s',
    },
    posts: {
      executor: 'constant-arrival-rate',
      exec: 'post',
      rate: 1,
      timeUnit: '1s',
      duration: `${MINUTES}m`,
      preAllocatedVUs: 20,
      maxVUs: 100,
      startTime: '120s',
    },
    screens: {
      executor: 'constant-vus',
      exec: 'kiosk',
      vus: 4,
      duration: `${MINUTES}m`,
    },
  },
  thresholds: {
    'eureka_failed': ['count<1'],
    'eureka_latency{endpoint:race/status}': ['p(95)<200'],
    'eureka_latency{endpoint:kiosk/leaderboard}': ['p(95)<500'],
    'eureka_latency{endpoint:offline/manifest}': ['p(95)<3000'],
  },
};

export function login() {
  const acct = accountFor(accounts, __ITER + 1);
  const payload = acct.login_code
    ? { code: acct.login_code, device_name: 'k6' }
    : { email: acct.email, password: acct.password, device_name: 'k6' };
  const path = acct.login_code ? '/api/v1/auth/login-code' : '/api/v1/auth/login';
  record(acct.login_code ? 'auth/login-code' : 'auth/login', http.post(TARGET + path, JSON.stringify(payload), jsonHeaders));
}

export function sync() {
  const acct = accountFor(accounts, __VU + __ITER * 7919);
  record('offline/manifest', http.get(`${TARGET}/api/v1/offline/manifest`, authHeaders(acct.token)));
}

export function race() {
  const acct = accountFor(accounts, __VU);
  const h = authHeaders(acct.token);
  record('race/status', http.get(`${TARGET}/api/v1/race/status`, h));
  if (acct.post_id && __VU % 10 < 3) {
    const res = http.get(`${TARGET}/api/v1/ar/locations/${acct.post_id}`, h);
    record('ar/locations (awaiting)', res, res.status === 403 ? 403 : 200);
  }
  sleep(10);
}

export function kiosk() {
  record('kiosk/leaderboard', http.get(`${TARGET}/api/kiosk/leaderboard`, jsonHeaders));
  sleep(10);
}

export function post() {
  const acct = accountFor(accounts, __ITER + 1);
  const h = authHeaders(acct.token);
  if (!acct.qr_code) return;
  const lookup = http.post(`${TARGET}/api/v1/qr/lookup`, JSON.stringify({ qr_code: acct.qr_code }), h);
  record('qr/lookup', lookup, lookup.status === 403 ? 403 : 200);
  if (lookup.status !== 200) return;
  const qid = lookup.json('questionnaire.id');
  if (!qid) return;
  const start = http.get(`${TARGET}/api/v1/quiz/start/${qid}`, h);
  if (record('quiz/start', start) !== 'ok') return;
  const attempt = start.json('attempt.id') || start.json('attempt_id');
  const res = http.post(`${TARGET}/api/v1/quiz/submit`, JSON.stringify({
    attempt_id: attempt, verification_photo: PHOTO,
  }), h);
  record('quiz/submit', res, res.status === 409 ? 409 : 200);
}

export const handleSummary = summary;
