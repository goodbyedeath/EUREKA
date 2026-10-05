/**
 * The write path, which is the expensive one: scan the paper QR, open the questions, save answers, hand in
 * with a photograph.
 *
 * Rate rather than volume is what matters here. 500 teams × 15 posts over two hours is about one hand-in
 * per second for the whole venue — but each one carries a base64 photo, and photos live in the attempt row
 * until phase 4 moves them out (SCALING-PLAN §3d). PHOTO_KB is deliberately adjustable: run it at 50 to
 * match a real handset, and at 0 to see what the photo alone costs.
 *
 * TARGET=http://10.0.0.10 PER_SEC=1 MINUTES=5 PHOTO_KB=50 k6 run post-flow.js
 */
import http from 'k6/http';
import { sleep } from 'k6';
import { TARGET, loadAccounts, accountFor, authHeaders } from './lib/env.js';
import { record, handleSummary as summary } from './lib/report.js';

const accounts = loadAccounts();
const PHOTO_KB = Number(__ENV.PHOTO_KB || 50);
const PHOTO = PHOTO_KB > 0 ? 'data:image/jpeg;base64,' + 'A'.repeat(PHOTO_KB * 1024) : null;

export const options = {
  scenarios: {
    posts: {
      executor: 'constant-arrival-rate',
      rate: Number(__ENV.PER_SEC || 1),
      timeUnit: '1s',
      duration: `${Number(__ENV.MINUTES || 5)}m`,
      preAllocatedVUs: 20,
      maxVUs: 100,
    },
  },
  thresholds: {
    'eureka_latency{endpoint:quiz/submit}': ['p(95)<2000'],
    'eureka_failed': ['count<1'],
  },
};

export default function () {
  const acct = accountFor(accounts, __ITER + 1);
  const h = authHeaders(acct.token);
  if (!acct.qr_code) {
    return;
  }

  // The station gate answers 403 for a post the crew has not opened — an expected outcome, and the one
  // most teams will meet, so it is measured rather than treated as an error.
  const lookup = http.post(`${TARGET}/api/v1/qr/lookup`, JSON.stringify({ qr_code: acct.qr_code }), h);
  const gate = record('qr/lookup', lookup, lookup.status === 403 ? 403 : 200);
  if (gate !== 'ok' || lookup.status !== 200) {
    sleep(1);
    return;
  }

  const body = lookup.json();
  const qid = body && body.questionnaire ? body.questionnaire.id : null;
  if (!qid) {
    return;
  }

  const start = http.get(`${TARGET}/api/v1/quiz/start/${qid}`, h);
  if (record('quiz/start', start) !== 'ok') {
    return;
  }

  const attempt = start.json('attempt.id') || start.json('attempt_id');
  const questions = start.json('questions') || [];

  for (const q of questions.slice(0, 5)) {
    record('quiz/save-answer', http.post(`${TARGET}/api/v1/quiz/save-answer`, JSON.stringify({
      attempt_id: attempt,
      question_id: q.id,
      answer: 'k6',
    }), h));
  }

  const submit = {
    attempt_id: attempt,
    ...(PHOTO ? { verification_photo: PHOTO } : { photo_missing_reason: 'no_camera' }),
  };
  const res = http.post(`${TARGET}/api/v1/quiz/submit`, JSON.stringify(submit), h);
  // 409 questions_incomplete is a legitimate answer while the clock still runs.
  record('quiz/submit', res, res.status === 409 ? 409 : 200);
}

export const handleSummary = summary;
