#!/usr/bin/env bash
# Wait for MySQL, then hand over. Migrations are NOT run here: with two app containers that would be two
# processes migrating the same database at once. `./deploy.sh migrate` does it once, on purpose.
set -euo pipefail

role="${CONTAINER_ROLE:-app}"

if [ "${WAIT_FOR_DB:-1}" = "1" ]; then
  for i in $(seq 1 60); do
    if mysqladmin ping -h"${DB_HOST:-mysql}" -P"${DB_PORT:-3306}" --silent 2>/dev/null; then
      break
    fi
    [ "$i" = "60" ] && echo "entrypoint: MySQL never answered on ${DB_HOST:-mysql}:${DB_PORT:-3306}" >&2 && exit 1
    sleep 2
  done
fi

# Caches are built into the image's filesystem at start rather than baked, so a changed .env takes effect
# on restart. Cleared one by one on purpose: `optimize:clear` also runs `cache:clear`, and with Redis that
# empties the shared application cache — so a single container restarting mid-event would throw away the
# leaderboard, the offline manifest and the seal's PBKDF2 keys for everybody. Only the compiled
# config/routes/views belong to this container, and a stale config from a previous env is the trap
# documented in CLAUDE.md.
if [ "${SKIP_OPTIMIZE:-0}" != "1" ]; then
  for c in config:clear route:clear view:clear event:clear; do
    php artisan "$c" >/dev/null 2>&1 || true
  done
  php artisan optimize >/dev/null 2>&1 || true
fi

case "$role" in
  worker)
    exec php artisan queue:work --sleep=1 --tries=3 --max-time=3600 --queue="${QUEUE_NAMES:-default}"
    ;;
  scheduler)
    # This is what ends the hPanel cron problem: the PHP here is the image's, and no panel rewrites it.
    exec php artisan schedule:work
    ;;
  *)
    # Build what a cold cache makes expensive — the seal's key derivation above all — so the first team
    # to press Sync is not the one paying for it. Takes about two seconds on an empty cache and nothing
    # at all on a warm one; `eureka:warm` takes a lock, so both app containers starting together do the
    # work once. Never fatal: a warm cache is an optimisation, not a requirement.
    if [ "${SKIP_WARM:-0}" != "1" ]; then
      php artisan eureka:warm 2>&1 || true
    fi
    exec "$@"
    ;;
esac
