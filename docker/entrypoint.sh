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
# on restart. optimize:clear first: a cached config from a previous env is the trap documented in CLAUDE.md.
if [ "${SKIP_OPTIMIZE:-0}" != "1" ]; then
  php artisan optimize:clear >/dev/null 2>&1 || true
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
    exec "$@"
    ;;
esac
