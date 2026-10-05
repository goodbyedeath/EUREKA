// Shared setup for every scenario: where to point, how to authenticate, and the one rule.
import { fail } from 'k6';

/**
 * TARGET must be given explicitly. There is no default on purpose.
 *
 * The live site cannot survive this harness and must never be the target: the shared host allows the whole
 * account about 120 tasks and starts failing around two dozen concurrent PHP processes (CLAUDE.md), so a
 * single 500 req/s scenario would take the event server down rather than measure it. Point this at the
 * on-prem workstation.
 */
const BLOCKED = ['questerra-series.com', 'www.questerra-series.com'];

export const TARGET = (() => {
  const t = (__ENV.TARGET || '').replace(/\/+$/, '');
  if (!t) {
    fail('TARGET is required, e.g. TARGET=http://10.0.0.10 k6 run k6/all.js');
  }
  for (const host of BLOCKED) {
    if (t.includes(host)) {
      fail(`refusing to load-test ${host}: that is the live event server. Point TARGET at the workstation.`);
    }
  }
  return t;
})();

/** Accounts to drive. `php artisan loadtest:seed` writes this file. */
export function loadAccounts(path = __ENV.ACCOUNTS || 'accounts.json') {
  const raw = open(path);
  const rows = JSON.parse(raw);
  if (!Array.isArray(rows) || rows.length === 0) {
    fail(`${path} holds no accounts — run: php artisan loadtest:seed --teams=500 --accounts=5000`);
  }
  return rows;
}

/** One account per VU, wrapping round if there are more VUs than accounts. */
export function accountFor(accounts, vu) {
  return accounts[(vu - 1) % accounts.length];
}

export function authHeaders(token) {
  return {
    headers: {
      Authorization: `Bearer ${token}`,
      Accept: 'application/json',
      'Content-Type': 'application/json',
    },
    tags: { authed: 'yes' },
  };
}

export const jsonHeaders = {
  headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
};
