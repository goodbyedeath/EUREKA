/**
 * The opening storm: every phone syncing at once.
 *
 * /offline/manifest was measured at 54 ms and 100 queries on an empty database (SCALING-PLAN §2), and it is
 * the same payload for every team on a plan — so this scenario is the one that proves the cache in phase 3.
 *
 * TARGET=http://10.0.0.10 PHONES=5000 OVER=600 k6 run sync-burst.js
 */
import http from 'k6/http';
import { TARGET, loadAccounts, accountFor, authHeaders } from './lib/env.js';
import { record, handleSummary as summary } from './lib/report.js';

const accounts = loadAccounts();
const PHONES = Number(__ENV.PHONES || 500);
const OVER = Number(__ENV.OVER || 600);        // seconds the crowd arrives over

export const options = {
  scenarios: {
    sync: {
      executor: 'shared-iterations',
      vus: Math.min(PHONES, 200),              // a sync is slow; 200 in flight is already brutal
      iterations: PHONES,
      maxDuration: `${OVER + 120}s`,
    },
  },
  thresholds: {
    'eureka_latency{endpoint:offline/manifest}': ['p(95)<3000'],
    'eureka_failed': ['count<1'],
  },
};

export default function () {
  const acct = accountFor(accounts, __VU + __ITER * 7919);   // spread accounts, not sequential
  const res = http.get(`${TARGET}/api/v1/offline/manifest`, authHeaders(acct.token));
  record('offline/manifest', res);

  // Worth knowing in the result: how big the thing we are caching actually is.
  if (__ITER === 0) {
    console.log(`manifest body: ${(res.body ? res.body.length : 0) / 1024} KB`);
  }
}

export const handleSummary = summary;
