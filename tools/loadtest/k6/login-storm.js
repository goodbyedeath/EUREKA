/**
 * The first ten minutes: 5,000 printed cards scanned at the door.
 *
 * This used to be the scenario that existed to **fail**: `login-code` was 60/min per address and `auth`
 * 60/min per address, and at a venue every phone may be behind one of them. Phase 5 changed that (5 Oct)
 * — the ceilings are sized from `config/venue.php`, the password cap is keyed per account, and a separate
 * counter watches failures per address — so **a clean run is now the gate**. A 429 here is a finding:
 * read its `error` key, because `too_many_attempts` is the harness reusing one account too fast and
 * `too_many_failed_logins` means the seeded credentials do not match the target.
 *
 * TARGET=http://10.0.0.10 PHONES=5000 OVER=600 k6 run login-storm.js
 */
import http from 'k6/http';
import { TARGET, loadAccounts, accountFor, jsonHeaders } from './lib/env.js';
import { record, handleSummary as summary } from './lib/report.js';

const accounts = loadAccounts();
const PHONES = Number(__ENV.PHONES || 500);
const OVER = Number(__ENV.OVER || 600);

export const options = {
  scenarios: {
    doors_open: {
      executor: 'ramping-arrival-rate',
      startRate: 1,
      timeUnit: '1s',
      preAllocatedVUs: 100,
      maxVUs: 400,
      stages: [
        { target: Math.ceil(PHONES / OVER), duration: '60s' },      // the queue builds
        { target: Math.ceil(PHONES / OVER), duration: `${OVER}s` }, // and then it is relentless
      ],
    },
  },
  thresholds: {
    // Deliberately not a pass/fail on 429 — the whole point is to count them.
    'eureka_failed': ['count<1'],
  },
};

export default function () {
  const acct = accountFor(accounts, __ITER + 1);
  if (acct.login_code) {
    record('auth/login-code', http.post(`${TARGET}/api/v1/auth/login-code`, JSON.stringify({
      code: acct.login_code,
      device_name: 'k6-storm',
    }), jsonHeaders));
    return;
  }

  record('auth/login', http.post(`${TARGET}/api/v1/auth/login`, JSON.stringify({
    email: acct.email,
    password: acct.password,
    device_name: 'k6-storm',
  }), jsonHeaders));
}

export const handleSummary = summary;
