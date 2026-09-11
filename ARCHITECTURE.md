# EUREKA — System Architecture

EUREKA is a Laravel 12 web app that runs **team-based outdoor quest games**. Participants join a
team, walk to physical locations, scan QR codes to unlock questionnaires, answer timed quizzes,
play facilitator-assessed "fun games", and accumulate points. Big screens ("kiosks") show a live
leaderboard and map for spectators.

Live at **https://questerra-series.com** on Hostinger shared hosting.

**Two clients, one backend.** Participants play through an **Android app** (APK), which talks to
the token-authenticated `/api/v1/*` surface. This website is now the **admin, kiosk and authoring**
surface. The participant UI that ships in `resources/views/user/` and `livewire/user/` is
**retired but still routed** — see §3 and §10.

---

## 1. Stack

| Layer | Choice |
|---|---|
| Framework | Laravel 12 (PHP 8.3) |
| Interactivity | Livewire 3 + Alpine.js 3 (no SPA, no REST-first API) |
| Assets | Vite 6 + Tailwind 3 |
| Database | MariaDB 11.8 |
| Sessions / Cache / Queue | all `database` (no Redis on this host) |
| Long-running timers | `laravel-workflow` (durable workflows, queue-backed) |
| PDF export | `barryvdh/laravel-dompdf` |
| QR codes | `simplesoftwareio/simple-qrcode` (generate), `qr-scanner` JS (read) |
| 360° panorama | `pannellum` |
| PWA | `ladumor/laravel-pwa` + hand-written service workers |
| i18n | `laravel-lang/common`, English + Bahasa Indonesia |

### Rendering model

**Admin and kiosk** are Blade; anything interactive is a Livewire component posting to
`/livewire/update`. Add to `/api/*` only for what Livewire cannot do: the QR scanner, the quiz
runtime (which must survive page reloads), live GPS positions, and the public kiosk screens.

**Participants** are not on this stack at all. The APK holds the whole player loop and speaks
JSON to `/api/v1/*` with a Sanctum bearer token. Anything a player does must therefore exist as
a v1 endpoint — a Livewire component is invisible to them.

This split is also why player traffic no longer arrives as one IP: `throttle:api` keys on the
authenticated user, so twenty phones are twenty buckets of 120/min rather than twenty browsers
sharing one. The host and Cloudflare edge still count per address, so a venue is only as safe as
the APK's polling interval — a number that lives in the client, not here.

---

## 2. Request lifecycle

```
questerra-series.com/public_html/.htaccess
        │  rewrites everything into EUREKA_APP/public/
        ▼
EUREKA_APP/public/.htaccess ──► public/index.php ──► bootstrap/app.php
        │                                                   │
        │ hotlink protection, security headers,              │ middleware + aliases
        │ immutable caching for /build/assets/               ▼
        └──────────────────────────────────────────► routes/web.php
```

The application directory sits **inside** the domain's web root, so `EUREKA_APP/.htaccess`
denies direct access to everything and `public/.htaccess` re-grants it for that one subtree.
See §9 for why the layout is like this.

### Global middleware (appended to `web`)

- `SetLocale` — resolves locale from `?lang=`, then session, then config; persists to session.
- `TrustCloudflareProxies` — trusts CF ranges for real client IPs.

### Middleware aliases and groups (`bootstrap/app.php`)

| Alias | Class | Purpose |
|---|---|---|
| `admin` | `AdminMiddleware` | legacy admin gate |
| `user` | `UserMiddleware` | legacy user gate |
| `role` | `RoleBasedAccessMiddleware` | `role:admin` / `role:user` |
| `preventbackhistory` | `PreventBackHistory` | no-store headers + CSP + security headers |
| `team` | `EnsureTeamRegistration` | blocks users who have not registered a team |
| `session.timeout` | `UserSessionTimeout` | idle logout using per-user `session_timeout` |

Groups: `admin` = `role:admin` + `preventbackhistory`; `user` = `role:user` +
`preventbackhistory` + `session.timeout`; `quiz` and `auth_pages` = `preventbackhistory`.

### Rate limiters (`AppServiceProvider`)

| Limiter | Rate | Keyed by |
|---|---|---|
| `api` | 120/min authenticated, 60/min anonymous | **user id**, falling back to IP |
| `kiosk` | 300/min | IP (a venue's screens share one address) |
| `auth` | 5/min per account **and** 60/min per IP | `email + IP`, and IP |
| `tracking` | 30/min authenticated, 10/min anonymous | user id, falling back to IP |
| `livewire` | 200/min authenticated, 60/min anonymous | user id, falling back to IP |

Keying `api` on the user is what makes twenty phones behind one venue WiFi twenty separate
budgets. Keying `auth` on the account as well as the IP stops one venue's shared address from
locking every team out after five bad passwords. `AuthController` additionally runs its own
`RateLimiter` with a 5-minute decay (5 login attempts, 3 registration attempts).

`tracking` guards the position writes. `watchPosition` fires about once a second on a
moving phone and every fix used to be an unthrottled POST; the browser now sends at most one
per 10 seconds, and the endpoint refuses more than 30 a minute per player.

`livewire` covers `POST /livewire/update`, which Livewire registers itself — so the middleware
is attached via `Livewire::setUpdateRoute()` in `AppServiceProvider::boot()`, not in
`routes/web.php`. It ran on the bare `web` group until 2026-09-09.

The purpose is not to protect the server but to fail predictably. With no limiter of our own,
the first thing to refuse an admin was the host's edge, which answers with an empty-bodied 429
that kills the page and explains nothing. Laravel's 429 carries `Retry-After` and the Livewire
client backs off on it. Verified through the kernel: 429 on request 61 against the anonymous
limit of 60, with `Retry-After: 60`.

Order matters — `['web', 'throttle:livewire']`, not the reverse. The limiter keys on the
authenticated user, so the session has to be started before it runs.

---

## 3. Route map

Routes live entirely in `routes/web.php`. **`routes/auth.php` is dead code** — it is never
registered in `bootstrap/app.php`, so the Breeze/Volt password-reset and email-verification
routes it defines do not exist. See §10.

```
PUBLIC
  /                          landing page (dynamic hero slides); redirects if logged in
  /login  /register          AuthController, throttled
  /ping                      connectivity probe for the PWA
  /manifest.json             PWA manifest
  /kiosk/leaderboard         big-screen team ranking
  /kiosk/map                 big-screen map
  /kiosk/led                 big-screen LED ticker
  /api/kiosk/{leaderboard,locations,data}   throttle:kiosk
  /api/live/{position,positions}            live GPS positions
  /test-dark-mode /test-offline             ← dev leftovers, still public

AUTHENTICATED (auth + preventbackhistory)
  /dashboard                 role-based redirect
  /debug/*                   quest health, DB test (auth-gated, admin-only would be better)

  ADMIN (role:admin, prefix /admin)
    dashboard, users, quest-locations, games, user-progress (+ export/preview/download),
    hero-slides, team-management, gps-tracking, feature-management, dashboard-management
    panorama/{id}, panorama/{id}/hotspots  (+ add/delete hotspot)

  USER (role:user) — RETIRED as a player UI, still routed. 31 routes.
    /team-registration                     ← reachable without a team
    ── requires `team` middleware ──
    /user/dashboard
    /quiz/start|take|continue|results      /game/assessment/{id}
    /quest-locations  /quest-locations/dashboard  /gps-tracking
    /game-dashboard   /panorama/{id}       /ar/{id}  /indoor-map/{id?}
    /race/start/{code}  /race/clue/{map}
    POST /quest-locations/checkin
    /api/quest-locations (list, update-location, get-route)
    /api/qr-scanner/lookup
    /api/quiz/{start,continue,save-answer,submit,timer,complete-game}
    /api/{ar/locations/{id},indoor-map/{id?},offline/manifest}
```

**The APK is a shell; the server patches it.** Branding, feature flags, questionnaires,
outposts, indoor plans and AR scenes all arrive over `/api/v1`, so most changes reach players
without a new build. The half that was missing until 2026-09-10 is the version handshake: the
client sends `X-App-Version: <versionCode>`, `config/app_release.php` holds `minimum_code`, and
`EnforceAppVersion` answers **426 `update_required`** below it. `GET /api/v1/app/release` lets an
app check at launch. When armed, the header is required: a request without one is refused too,
because the alternative is a gate anyone can skip by omitting it. Arm it by
raising `APK_MINIMUM_CODE`; it is 0, blocking nothing, until someone decides otherwise before an
event rather than during one.

**`openapi.json` is the contract of record.** It is generated by `tools/openapi-gen.php` from
three sources, because no single one knows everything: the router (paths, auth, access window,
rate limit), the controllers' inline `validate()` calls (request bodies — this project has no
FormRequest classes, so off-the-shelf generators see nothing), and a live probe of GET
responses (Laravel builds responses as inline arrays, which cannot be inferred statically;
POST endpoints are never probed, because they have side effects and this runs against
production). `tools/contract-check.php` regenerates and diffs, exiting 1 on drift — it
compares only what is a function of the code, never the probed responses, which move with
the data.

Two documents are written for the Android team and kept at the repo root:

- **`API-V1-CONTRACT.md`** — the endpoint reference. Its table is generated from
  `artisan route:list --json`, so it cannot drift from the router.
- **`APK-BUILD-GUIDE.md`** — how to build the client: screen order, which call feeds each step,
  polling budgets, and a list of the places the API surprises people (two different key names
  for a coordinate, lat/long typed as string in one response and number in another, `spots`
  sitting beside `map` rather than inside it).

Both were written from responses sampled off the live server, not from the source alone. When an
endpoint changes, update them in the same commit.

### The APK surface (`routes/api.php`)

Players use these and nothing else. Sanctum bearer token, `access.window`, `throttle:api`.

```
PUBLIC   POST /api/v1/auth/login
         GET  /api/v1/branding          GET /api/v1/hero-slides     ← throttle:kiosk
AUTH     GET  /api/v1/auth/me           POST /api/v1/auth/logout
         GET  /api/v1/features
         POST /api/v1/qr/lookup
         GET  /api/v1/quest-locations   POST /api/v1/quest-locations/checkin
         POST /api/v1/tracking/position
         GET  /api/v1/quiz/{start/{id},continue/{id},timer/{id}}
         POST /api/v1/quiz/{save-answer,submit}
         POST /api/v1/race/start        GET /api/v1/race/status
         POST /api/v1/race/clue/{map}
         GET  /api/v1/ar/locations/{id} GET /api/v1/indoor-map/{id?}
         GET  /api/v1/offline/manifest
         POST /api/v1/quiz/complete-game
```

**Refusals carry a stable `error` key.** The quiz endpoints used to throw plain exceptions for
expected states and return them as HTTP 500 with the raw English message, so a client could only
string-match and could not tell a double-submit from an outage. `App\Exceptions\QuizRuleException`
now gives each one a key and a 4xx:

| key | status | meaning |
|---|---|---|
| `attempt_submitted` | 409 | the attempt is already submitted; stop editing |
| `question_not_found` | 404 | that question is not in this questionnaire |
| `time_expired` | 403 | the clock ran out — `save-answer` refuses, `submit` still succeeds |
| `game_already_assessed` | 409 | a facilitator has already scored this game |
| `not_a_game_question` | 422 | `complete-game` called on a normal question |

`submit` is deliberately **not** time-gated: refusing it would strand an attempt whose clock ran
out mid-request and lose the team's work. The gate sits on `save-answer`, so nothing new can be
written after time, and the submission itself always lands.

**The two surfaces are not the same code path.** `/api/*` in `routes/web.php` is session-cookie
authenticated through the `user` group and carries **no throttle**; `/api/v1/*` is token
authenticated and throttled per user. They duplicate the same game loop. Two endpoints exist only
on the retired side and have no v1 twin: `POST /api/quest-locations/get-route` and
`POST /api/quiz/complete-game` — check with the client team before deleting the web user side.

Everything unmatched falls through to `errors.404`.

---

## 4. Domain model

```
User ──┬── team_id ─────────► Team ──── members ────► TeamMember
       │                        │
       │                        └── points, initial_points
       ├── quizAttempts ──► QuizAttempt ──┬── UserAnswer ──► Question
       │                                  └── GameAssessment (facilitator-scored)
       ├── qrCodeScans ──► QrCodeScan ──► Questionnaire ──► Question
       └── checkpoints ──► UserQuestCheckpoint ──► QuestLocation

GameLocation ──► Hotspot (panorama pins: navigation | quiz | info)
HeroSlide      (landing page carousel, admin-editable)
FeatureSetting (global feature flags — see §7)
Guidance       (instructions targeted at all users or one user)
```

### Key tables

- **users** — `role` enum(`admin`,`user`), `is_active`, `login_history` (JSON), per-user
  `session_timeout`, `session_workflow_id`, `last_activity_at`.
- **teams** — `points` and `initial_points`; `initial_points` is the team's starting balance and
  feeds every user's "base points".
- **questionnaires** — `qr_code` (unique), `time_limit`, `max_attempts`, `pass_percentage`,
  `total_points`, active window via `start_date`/`end_date`.
- **questions** — `type` enum(`multiple_choice`,`text`,`true_false`,`fun_game`,`brief`),
  `options` and `images` as JSON, `points`, `order`.
- **quiz_attempts** — `status` enum(`started`,`completed`,`abandoned`), `timer_workflow_id`,
  `auto_submitted`, `submission_reason`, `verification_photo` (base64 selfie), `total_score`.
- **quest_locations** — lat/lng + `radius` for geofenced check-in, `quest_points`,
  `max_check_ins_per_user`, `marker_color`.
- **game_locations / hotspots** — 360° panorama scenes; hotspots carry `pitch`/`yaw` and either
  navigate to another location, open a quiz, or show info.
- **game_assessments** — facilitator scoring for `fun_game` questions: `deposit`, `penalty`,
  `additional_points`, `total_deposit`, `facilitator_photo`.

---

## 5. Question types

`App\Enums\QuestionType` is the single source of truth — it owns the label, description,
validation rules, defaults, and whether the type is scored.

| Type | Scored | Answer source |
|---|---|---|
| `multiple_choice` | yes | option match |
| `text` | yes | string comparison via `AnswerValidationService` |
| `true_false` | yes | `true` / `false` |
| `fun_game` | **manually** | facilitator fills a `GameAssessment`; auto-score is 0 |
| `brief` | **no** | free-text feedback, always 0 points |

---

## 6. Scoring

`App\Services\PointsCalculationService` is the authority. Two rules matter:

1. **Earned points come from `user_answers.points_earned`, never `questions.points`.** This is
   deliberate — `fun_game` answers store 0 and are scored later by a facilitator, so reading
   `questions.points` would double-count them.
2. **A manual `GameAssessment` overrides the computed score.** If an assessment exists with
   `is_assessed = true`, its `total_deposit` replaces base + earned for that attempt.

`calculateUserTotalPoints()` walks a user's completed attempts **chronologically**, counting only
the *first* completion of each questionnaire, starting from the team's `initial_points`
("base points"), and adding `max(0, total_deposit - base_points)` for each assessed game.

---

## 7. Feature flags

`feature_settings` is a global on/off switch table (21 rows) read through
`FeatureSetting::isEnabled()`, cached under the `enabled_features` key for 1 hour and invalidated
by `toggle()` / `enable()` / `disable()`.

Flags gate **UI surface only** — the dashboard tabs and cards in
`resources/views/livewire/user/dashboard-content.blade.php` and `DashboardTabs`. Routes stay
reachable, so a flag is a visibility control, not an authorization control.

Main flags: `quiz_system`, `quest_locations`, `game_dashboard`, `team_management`, `leaderboard`,
`gps_tracking`, `notifications`, `workflow_timers`, plus a dozen `dashboard_stat_*` toggles.
Admins manage them at `/admin/feature-management`.

> **All 21 flags are currently OFF** in the imported production data, so a logged-in participant
> sees an empty dashboard until an admin enables them.

---

## 8. Timers (`laravel-workflow`)

Two durable workflows back the timing features:

- `QuizTimerWorkflow` — started when an attempt begins, sleeps for the questionnaire's
  `time_limit`, emits `QuizTimeWarning` (5 min out for long quizzes, 2 min for short) then
  `QuizTimeExpired`, which auto-submits the attempt.
- `UserSessionTimeoutWorkflow` — per-user idle expiry.

`WorkflowTimerService` wraps both, and every entry point checks the `workflow_timers` flag first.
Clients also poll `GET /api/quiz/timer/{attemptId}` so the countdown survives a page reload —
the server is the clock, not the browser.

> **Operational consequence:** workflows execute as **queued jobs** on the `database` queue.
> With `workflow_timers` disabled, nothing is dispatched and no worker is needed. **Enabling that
> flag requires a running `php artisan queue:work`**, otherwise timers silently never fire.

---

## 9. Deployment layout

Hostinger serves `~/domains/questerra-series.com/public_html/` as the document root, and the
whole Laravel app lives in `EUREKA_APP/` *inside* it. Rather than move the app (which would break
tooling paths), access is controlled with three `.htaccess` files:

| File | Role |
|---|---|
| `public_html/.htaccess` | rewrites every request into `EUREKA_APP/public/`; denies dotfiles |
| `EUREKA_APP/.htaccess` | `Require all denied` — nothing in the app dir is directly servable |
| `EUREKA_APP/public/.htaccess` | `Require all granted` + front controller, hotlink protection, security headers, immutable asset caching |

Verified: `.env`, `composer.json`, `artisan`, and `storage/logs/` return errors, never source.

**PHP version matters.** The web server runs **PHP 8.3**; the SSH default `php` is 8.2, which
cannot even install this lockfile (`spatie/php-structure-discoverer` needs `^8.3`). Always use
`/opt/alt/php83/usr/bin/php` for `composer` and `artisan`.

**`.env.production` must not exist.** During `config:cache` Laravel bootstraps a second
application instance; by then `APP_ENV=production` is in the process environment, so it loads
`.env.<APP_ENV>` in preference to `.env` and bakes those values into the cache. A stale
`.env.production` silently poisons the cached config while runtime still looks correct.

---

## 10. Known gaps

These are real, verified, and worth deciding on — none of them block the site from running.

- **No email at all.** The app contains zero `Mail::`, `Notification::`, or `->notify()` calls,
  and `routes/auth.php` (which defines forgot-password / verify-email) is never registered.
  Configuring SMTP changes nothing until password reset is actually wired up.
- **GPS tracker: re-hosted, pending a subdomain.** EUREKA's GPS and LED screens read a *separate*
  Laravel app (see §13). Its old home `tracker.eureka-performa.com` no longer resolves; the four
  views that referenced it now point at `https://tracker.questerra-series.com`, which goes live as
  soon as that subdomain is created in hPanel. `admin.gps-tracking` (which iframes the tracker's
  live map) is orphaned — no route or controller references it; the served view is
  `admin.gps-tracking-direct`.
- EUREKA's own `gps_footprints` and `gps_tracking_sessions` tables are empty and unused — live
  position data comes from the tracker app, not from these.
- **`/api/test-submission` CSRF exemption is a no-op.** It calls
  `withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)`, but that class does not exist
  in this app (Laravel 12 uses `Illuminate\Foundation\Http\Middleware\VerifyCsrfToken`), so the
  string never matches anything in the stack.
- **Dev routes are public:** `/test-dark-mode`, `/test-offline`, and `/test-language`.
- **`/debug/*` is `auth`-gated, not `admin`-gated** — any logged-in participant can reach
  `/debug/test-database`.
- **The app's CSP never reaches the browser.** `PreventBackHistory` builds a detailed policy, but
  the Hostinger CDN replaces the header with `upgrade-insecure-requests`.
- **`resources/lang/` wins over `lang/`.** Laravel picks `resources/lang` when it exists, so the
  root `lang/` directory is dead weight. `resources/lang/id` also has four files with no English
  counterpart (`dashboard`, `forms`, `navigation`, `teams`).
- **`quest_locations.google_map_embed_url` is unused** by any view or controller.
- ~~`POST /api/live/position` was unthrottled and fed by an unthrottled `watchPosition`.~~
  Fixed 2026-09-09: `throttle:tracking` on both write routes, 10s minimum between sends in the
  browser. Found by code review, not by `tools/traffic-audit.php` — which only looked for
  `setInterval` and `wire:poll`, and now has an UNBOUNDED section for sources with no interval.
- **AR authoring stores no absolute heading.** `hotspots.yaw` is relative to the direction the admin
  faced when tapping Start at the outpost, so the web preview needs its QR-aiming gate to reproduce
  that frame. A native client does not: objects carry no real-world anchor, so scene rotation is
  cosmetic. Recording an absolute heading at authoring time would let the web gate go too, but it
  means re-walking every outpost to re-author what exists — worth doing before a large event, not
  mid-build. The Android team asked for this to be on the list (11 Sep) and is not blocked by it.
- **The retired web player UI is still fully reachable.** 31 routes behind `UserMiddleware`,
  including a session-authenticated `/api/*` game loop with **no rate limit**, duplicating the
  throttled `/api/v1/*` the APK uses. Nobody owns it now that players are on the APK. A phone
  that installed the PWA earlier still has `sw.js` registered and can still reach it.
- **`resources/js/network-monitor.js` and `resources/js/session-timeout.js` are dead.** No view
  includes them, no Vite entry imports them, and they do not appear in `public/build`. Their
  `/ping` and `/api/user/session-status` polls have never run. Worth knowing before optimising
  them: two rounds of "traffic reduction" were spent on these files before anyone checked
  whether they load.

---

## 11. Working on this codebase

```bash
PHP=/opt/alt/php83/usr/bin/php          # NOT the default 8.2 `php`

$PHP /usr/local/bin/composer2.phar install --no-dev --optimize-autoloader
node node_modules/vite/bin/vite.js build          # npm scripts assume executable bits
$PHP artisan optimize                             # config + events + routes + views
$PHP artisan optimize:clear                       # after any .env or config change
```

**Livewire's JS is published, not linked.** `public/vendor/livewire/` holds a copy of the
package's `dist/`, served statically so the asset costs no PHP request. It does not follow the
package: after any `livewire/livewire` version change, run `artisan livewire:publish --assets`,
or every page logs *"The published Livewire assets are out of date"*. The check is a hash
comparison between `public/vendor/livewire/manifest.json` and
`vendor/livewire/livewire/dist/manifest.json`. `public/vendor/maplibre/` and `public/vendor/three/`
are pinned the same way and have the same caveat.

**`--no-dev` needs `dont-discover`.** `laravel/breeze`, `laravel/pail`, `laravel/sail` and
`nunomaduro/collision` are all `require-dev` packages that register a service provider. With
`--no-dev` their classes leave the autoloader, but `bootstrap/cache/packages.php` still lists the
providers, so the app dies at boot: *Class "Laravel\Pail\PailServiceProvider" not found*, from
`ProviderRepository.php:205`. All four are now in `extra.laravel.dont-discover` in
`composer.json`, which makes the deploy command above safe. If it ever happens again, recover by
re-running `dump-autoload --optimize` **without** `--no-dev` — that restores the app in one step.

After a fresh upload from a zip, two things are always broken and must be repaired:
`chmod +x node_modules/.bin/* node_modules/@esbuild/linux-x64/bin/esbuild`, and
`public/storage` arrives as a real 85 MB copy instead of a symlink — delete it and
re-run `ln -s ../storage/app/public public/storage`.

Tests: PHPUnit under `tests/Feature` and `tests/Unit` (thin), plus six Playwright specs in
`tests/playwright` covering login, dashboard, quiz workflow, brief feedback, and teams.
`playwright.config.js` still points `baseURL` at the retired `eureka-performa.com`.

---

## 12. Request budget

Repeated "the site is down" incidents were all HTTP 429, and the two sources look nothing alike:

| | Laravel `RateLimiter` | Host / Cloudflare edge |
|---|---|---|
| Body | JSON, with `X-RateLimit-*` headers | **empty**, no `content-type` |
| Header | — | `cf-ray: …-CGK` |
| Scope | the keyed limiter that tripped | the whole site, from that address |

`tools/traffic-audit.php` measures what the front-end asks for. It walks every `setInterval` and
`wire:poll`, follows the call up to three hops into the function that really touches the network,
and prints a per-minute budget by audience.

```bash
/opt/alt/php83/usr/bin/php tools/traffic-audit.php --teams=25
```

Run it before an event and after adding anything that polls. Two things it enforces, both learned
by getting them wrong:

- **Code nothing loads makes no requests.** It resolves the Vite entry import graph and refuses to
  bill a file that is not in the bundle.
- **Reachability is not frequency.** A request behind an `if`, or in a callback that calls
  `clearInterval` first, is reported separately rather than charged once per tick.

It cannot see the APK. Player traffic has to come from the client team as a number.

---

## 13. Companion app: EUREKA GPS Tracker

EUREKA does not record GPS itself. A **second, independent Laravel 12 app** lives at
`~/domains/questerra-series.com/public_html/tracker/TRACKER/` and owns all GPS data. It has its
own database (`u840623113_tracker`), its own `APP_KEY`, and its own users — the two apps share
nothing but an HTTP contract.

**What EUREKA consumes:** exactly one endpoint, `GET /api/export/map-data`, called from browser
JavaScript in three views — `user/gps-tracking`, `admin/gps-tracking-direct`, and `kiosk/led`.

**The tracker's own surface**

```
PUBLIC   /api/export/{activities,activities/{id},users/{id}/stats,routes,leaderboard,
                     live-tracking,map-data}      ← no auth; only non-private activities
         /eureka-live-map.html  /api-demo.html    ← static pages, same-origin relative API calls
AUTH     /  (dashboard)  /profile  /activities/{id}  /track  (MapLibre recording UI)
         /api/gps/*      session start/pause/resume/stop, record-point, markers
```

Domain model: `User → GpsSession → GpsPoint`, plus `Activity` (a completed, shareable session
with `private` flag), `RouteMarker`, `ActivityKudo`, `ActivityComment`.

**Cross-origin contract.** The tracker runs on a different origin to EUREKA, so
`config/cors.php` allows `https://questerra-series.com` on `api/export/*` only. The authenticated
`api/gps/*` routes are deliberately excluded, so no other origin can drive GPS recording.
`public/.htaccess` sets `frame-ancestors 'self' https://questerra-series.com` to permit the admin
iframe — the old `X-Frame-Options: ALLOW-FROM` it used to send is obsolete and ignored by every
current browser.

**Operational notes**

- Same PHP 8.3 rule and the same `.env.<APP_ENV>` shadowing trap as EUREKA (§9). It arrived with a
  stale `bootstrap/cache/config.php` holding the previous host's credentials — always clear that
  before caching config.
- **`exec()` and `symlink()` are disabled in PHP on this host**, so `artisan storage:link` fails
  with `Call to undefined function exec()`. Create the link from the shell instead:
  `ln -s ../storage/app/public public/storage`.
- `public/build/assets/` is empty — the frontend was never built. This breaks nothing: the only
  views using `@vite` are `tracker.blade.php` and `welcome.blade.php`, and neither is routed
  (`/track` renders `tracker-maplibre`). Everything reachable is server-rendered Blade.
