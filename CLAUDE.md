# EUREKA

Laravel 12 + Livewire 3 app for team-based outdoor quest games (QR-unlocked quizzes, geofenced
check-ins, 360° panorama hotspots, facilitator-scored games, big-screen kiosk displays).

**Read `ARCHITECTURE.md` for the system design** — stack, request lifecycle, route map, domain
model, scoring rules, feature flags, timers, and known gaps.

## Environment constraints

This is a live Hostinger deployment, not a dev box. `questerra-series.com` serves it from
`~/domains/questerra-series.com/public_html/`, with the app in `EUREKA_APP/` *inside* the web root
and three `.htaccess` files controlling access.

**Always use PHP 8.3.** The web server runs 8.3.30; the SSH default `php` is 8.2.30 and cannot
install this lockfile.

```bash
PHP=/opt/alt/php83/usr/bin/php

$PHP artisan optimize          # config + events + routes + views
$PHP artisan optimize:clear    # required after any .env or config/ change
$PHP /usr/local/bin/composer2.phar install --no-dev --optimize-autoloader
#   ^ safe only because laravel/breeze, pail, sail and collision are in composer.json's
#     extra.laravel.dont-discover. All four are require-dev packages that register a service
#     provider; without that list, --no-dev drops them from the autoloader while
#     bootstrap/cache/packages.php still names them, and the app dies at boot with
#     "Class Laravel\Pail\PailServiceProvider not found". Recover with dump-autoload and NO
#     --no-dev flag. Never add a require-dev package that ships a provider without listing it.
node node_modules/vite/bin/vite.js build     # npm scripts assume executable bits

$PHP artisan livewire:publish --assets   # after ANY livewire/livewire version change
#   Livewire's JS is served statically from public/vendor/livewire/. It does not update with
#   the package, and the browser console says "The published Livewire assets are out of date"
#   when it drifts. Compare public/vendor/livewire/manifest.json against
#   vendor/livewire/livewire/dist/manifest.json — the hashes must match.
```

**Never create a `.env.production`.** `config:cache` bootstraps a second app instance with
`APP_ENV` already in the environment, so it loads `.env.<APP_ENV>` in preference to `.env` and
caches the wrong values. Runtime keeps looking correct, so the breakage appears only after
optimizing.

## Conventions worth following

- **`openapi.json` is the machine-readable contract for `/api/v1`, and the Android client is
  generated from it.** Never edit it by hand. After changing a v1 route, its middleware or a
  `validate()` rule, regenerate and commit it in the same change:
  `$PHP tools/openapi-gen.php --sample`, then `$PHP tools/contract-check.php` (exit 1 = drift).
- Two hand-written companions at the repo root carry what a schema cannot: `API-V1-CONTRACT.md`
  (endpoint reference) and `APK-BUILD-GUIDE.md` (screen order, polling budgets, the AR reticle,
  semantics like "submit stays open after time_expired"). **Behaviour changes go in the guide,
  not in a message to the operator** — the generator hashes both files into
  `info.x-behaviour-guides`, so a prose edit shows up in the contract the client agent already
  diffs, and `contract-check` names the file that moved. Relaying rules by hand is how the AR
  reticle got built without one.
- **Players are on the Android app, not this website.** Anything player-facing has to exist as a
  `/api/v1/*` endpoint (Sanctum token, `throttle:api`); a Livewire component is invisible to them.
  The web participant UI under `resources/views/user/` is retired but still routed — don't build
  on it, and check `ARCHITECTURE.md` §3 before deleting it.
- Admin and kiosk UI is Livewire, not JSON endpoints. Add to `/api/*` only for things Livewire
  can't do: scanner input, the reload-surviving quiz runtime, live positions, public kiosk screens.
- Anything that polls goes through `tools/traffic-audit.php` before it ships. Repeated production
  outages here were 429s. `/livewire/update` now has its own limiter (`throttle:livewire`, set in
  `AppServiceProvider` via `Livewire::setUpdateRoute`), so a flood fails as a Laravel 429 with
  Retry-After rather than an opaque edge 429 that kills the page.
- `App\Enums\QuestionType` owns each type's label, validation rules, defaults, and scored-ness.
  Add question types there, not in scattered `match` statements.
- Scoring belongs in `PointsCalculationService`. Read earned points from
  `user_answers.points_earned`, never `questions.points` — `fun_game` answers store 0 and are
  scored later by a facilitator.
- Feature flags gate UI visibility only, never authorization. Guard access with middleware.
- All 21 feature flags are currently OFF, so an empty user dashboard is expected, not a bug.

## Housekeeping

Routes live only in `routes/web.php`; `routes/auth.php` is unregistered dead code, so there are no
password-reset or email-verification routes — and the app sends no email anywhere. Translations
resolve from `resources/lang/` (Laravel prefers it when present), which makes the root `lang/`
directory dead weight.
