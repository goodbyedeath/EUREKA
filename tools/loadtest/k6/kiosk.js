/**
 * The big screens. Four of them poll every 10 s, and the leaderboard recomputes every team's score on
 * every request (SCALING-PLAN §3b) — with 500 teams that is thousands of queries per poll, multiplied by
 * every screen. This scenario is small on purpose: it should be cheap, and today it is not.
 *
 * TARGET=http://10.0.0.10 SCREENS=4 k6 run kiosk.js
 */
import http from 'k6/http';
import { sleep } from 'k6';
import { TARGET, jsonHeaders } from './lib/env.js';
import { record, handleSummary as summary } from './lib/report.js';

export const options = {
  scenarios: {
    screens: {
      executor: 'constant-vus',
      vus: Number(__ENV.SCREENS || 4),
      duration: `${Number(__ENV.MINUTES || 5)}m`,
    },
  },
  thresholds: {
    'eureka_latency{endpoint:kiosk/leaderboard}': ['p(95)<500'],
    'eureka_failed': ['count<1'],
  },
};

export default function () {
  record('kiosk/leaderboard', http.get(`${TARGET}/api/kiosk/leaderboard`, jsonHeaders));
  sleep(10);
}

export const handleSummary = summary;
