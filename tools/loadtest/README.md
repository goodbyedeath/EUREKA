# Load-test harness — phase 0

What it is for: **every later phase of `SCALING-PLAN.md` is a claim about a number, and this is where the
number comes from.** Without it, "the manifest cache helped" is a feeling.

## The one rule

**Never point this at `questerra-series.com`.** The shared host allows the whole account about 120 tasks and
starts failing around two dozen concurrent PHP processes, so a single scenario here would take the live
server down rather than measure it. `lib/env.js` refuses that hostname outright, and `TARGET` has no
default — but the rule matters more than the guard.

Run k6 **from a different machine than the target**, or the generator competes with the thing being
measured. k6 wants roughly 1–2 GB of RAM per 1,000 VUs, so 5,000 phones needs a real machine of its own.

## Install

k6 is a single binary, nothing to configure: <https://grafana.com/docs/k6/latest/set-up/install-k6/>

## Run

On the machine under test (the workstation), create the accounts — this writes thousands of rows, so it
refuses unless `LOADTEST=1` is in that machine's environment, and everything it makes is prefixed `LT_`:

```bash
LOADTEST=1 php artisan loadtest:seed --teams=500 --accounts=5000
#   → tools/loadtest/k6/accounts.json  (team, email, password, login_code, API token, post_id, qr_code)
```

Then, from `tools/loadtest/k6` on the generator machine (copy `accounts.json` across):

```bash
TARGET=http://10.0.0.10 PHONES=5000 k6 run all.js          # the whole event, in proportion
TARGET=http://10.0.0.10 PHONES=200  k6 run all.js          # start here: proves the harness, not the server

TARGET=… PHONES=5000 k6 run race-steady.js                 # the steady 500 req/s
TARGET=… PHONES=5000 k6 run sync-burst.js                  # the opening storm
TARGET=… PHONES=5000 k6 run login-storm.js                 # 5,000 cards at the door
TARGET=… k6 run kiosk.js                                   # four big screens
TARGET=… PER_SEC=1 PHOTO_KB=50 k6 run post-flow.js          # the write path, photo included
```

And afterwards, on the machine under test:

```bash
LOADTEST=1 php artisan loadtest:purge
```

Each run writes `summary.md` (a table you can paste into the plan) and `summary.json` (per-endpoint p95,
tagged by `endpoint`).

## What each scenario is actually measuring

| Scenario | Blocker it proves or disproves | Expected today |
|---|---|---|
| `race-steady.js` | §3a cache/session/limiter in MySQL — every request writes the `cache` table | the first wall; p95 climbs with VUs |
| `sync-burst.js` | §3c `/offline/manifest`: 100 queries, 60 KB, identical per plan | 5,000 syncs = 500,000 queries |
| `login-storm.js` | §3e per-IP login limits versus one venue NAT | **429s by design** — that is the measurement |
| `kiosk.js` | §3b leaderboard recomputed per request, per screen | cheap with few teams, expensive with 500 |
| `post-flow.js` | §3d base64 photos inside the attempt row | submit latency tracks `PHOTO_KB` |

**A 429 is a signal here, not a crash.** The harness counts them separately (`eureka_throttled`) from real
failures (`eureka_failed`: 5xx, timeouts, wrong shape). A clean `login-storm` run is the phase-5 gate; a
`login-storm` run full of 429s is phase 0 working correctly.

## The baseline to fill in

Run `all.js` once against the workstation **before** any of §3 is fixed, and paste the table here. Every
later phase is then an argument with this row rather than with a memory.

| Date | Target | PHONES | race/status p95 | manifest p95 | leaderboard p95 | submit p95 | 429s | failures |
|---|---|---|---|---|---|---|---|---|
| | | | | | | | | |

For reference, the single-request measurements taken on the shared host with an empty database
(`SCALING-PLAN.md` §2) were: `race/status` 7.0 ms / 21 queries, `/offline/manifest` 54.3 ms / 100 queries,
`kiosk/leaderboard` 6.6 ms. Those are a floor with one user, not a forecast under load.

## Proving phase 2 (Redis), not asserting it

Phase 2 changes nothing but configuration, so it is invisible unless it is measured. Three checks, in this
order — the first two take a minute and catch the usual mistake, which is an `.env` edit that `config:cache`
never picked up.

**1. Ask the app what it is wired to.**

```bash
./deploy.sh doctor
```

Every row that matters should read `ok`: `cache store: redis`, `session driver: redis (persistent)`,
`queue connection: redis`, `rate limiter: follows the cache store → redis`, and each `redis:*` row
reachable, with `redis:queue` and `redis:persistent` showing `[noeviction]`. The command exits non-zero
only on a real fault — an unreachable Redis the app depends on, an evicting queue or session instance, a
dead database — so it is safe in a health check. A row still saying `database` means the config cache is
stale: `./deploy.sh optimize`.

**2. Watch the `cache` table go quiet.** This is the blocker from `SCALING-PLAN.md` §3a, and it is a row
count, not an opinion:

```bash
./deploy.sh shell
php artisan eureka:doctor --json | grep -A2 '"mysql cache rows"'
```

Run a scenario, then look again. On MySQL drivers the number climbs with traffic — the rate limiter writes
a key per user per minute, and every throttled route touches it. On Redis it stays where it was, and
`mysql session rows` and `mysql queued jobs` stay flat with it.

**3. Compare p95 — same scenario, same `PHONES`, drivers flipped.**

```bash
TARGET=http://<workstation-ip> PHONES=200 k6 run race-steady.js   # before
# set CACHE_STORE/SESSION_DRIVER/QUEUE_CONNECTION in docker/.env, then ./deploy.sh optimize
TARGET=http://<workstation-ip> PHONES=200 k6 run race-steady.js   # after
```

`race/status` is the endpoint to judge on: it is the most frequent call in an event and does little work of
its own, so most of what it spends is the per-request overhead this phase removes. Fill in both rows. If
`after` is not lower, write that here rather than quietly moving to phase 3 — it would mean the bottleneck
is somewhere phase 2 does not reach.

| Date | PHONES | drivers | race/status p95 | `cache` rows after run | 429s | failures |
|---|---|---|---|---|---|---|
| | | database | | | | |
| | | redis | | | | |

What this phase does **not** fix, so do not expect it to move: the per-request leaderboard (§3b), the
100-query manifest (§3c) or submit latency (§3d). Redis makes each request cheaper to serve; it does not
make an expensive query cheap.

## Honest limits of this harness

- It drives the **API**, not the Android app: no ARCore, no camera, no real GPS, no APK caching behaviour.
  A phone that polls less often than the guide says will produce less load than this does.
- `post-flow.js` submits a synthetic photo of `PHOTO_KB` zeros; real JPEGs compress differently on the wire
  and not at all in the database.
- The scenarios assume the station gate is **closed** for most teams (403 `checkin_required`), which is the
  normal state — the expensive open path is exercised at a trickle, as it is in a real event.
- 5,000 VUs from one machine is a lot of sockets. If the generator is the bottleneck you will see it as
  `dial: i/o timeout` rather than as server latency; split across two machines before believing a bad
  number.
