#!/usr/bin/env bash
# The five commands this stack needs. Everything else is docker compose.
set -euo pipefail
cd "$(dirname "$0")"

compose() { docker compose "$@"; }

need_env() {
  [ -f .env ] || { echo "Copy .env.docker.example to .env and fill it in first." >&2; exit 1; }
}

case "${1:-help}" in
  up)
    need_env
    compose build
    compose up -d
    echo "Waiting for MySQL…"
    until compose exec -T mysql mysqladmin ping -h127.0.0.1 --silent >/dev/null 2>&1; do sleep 2; done
    # Migrations run once, here — not in the app entrypoint, where two containers would race.
    compose exec -T app php artisan migrate --force
    compose exec -T app php artisan optimize
    compose exec -T app php artisan storage:link || true
    echo
    # Say what the stack is actually wired to. "I set REDIS_HOST in .env" and "the app is using Redis"
    # are different claims, and only one of them is checkable.
    # The seal's PBKDF2 keys cost 1.7s to derive and a clear throws them away; pay that here rather
    # than leaving it to the first team that presses Sync.
    compose exec -T app php artisan eureka:warm
    compose exec -T app php artisan eureka:doctor
    echo "Up. Check http://localhost:${HTTP_PORT:-80}/up"
    ;;

  migrate)  need_env; compose exec -T app php artisan migrate --force ;;
  doctor)   need_env; shift; compose exec -T app php artisan eureka:doctor "$@" ;;
  warm)     need_env; compose exec -T app php artisan eureka:warm ;;
  optimize) need_env; compose exec -T app php artisan optimize:clear && compose exec -T app php artisan optimize ;;
  logs)     compose logs -f --tail=100 ;;
  ps)       compose ps ;;
  down)     compose down ;;          # data survives: it is in named volumes
  nuke)     compose down -v ;;       # data does not

  key)
    # Generate an APP_KEY without needing a running stack.
    docker run --rm eureka-app:${TAG:-latest} php artisan key:generate --show
    ;;

  shell)    need_env; compose exec app bash ;;

  seed-loadtest)
    need_env
    teams="${2:-500}"; accounts="${3:-5000}"
    compose exec -T -e LOADTEST=1 app php artisan loadtest:seed --teams="$teams" --accounts="$accounts" --no-interaction
    compose cp app:/var/www/html/tools/loadtest/k6/accounts.json ./accounts.json
    echo "accounts.json copied here — move it to the machine running k6."
    ;;

  purge-loadtest)
    need_env
    compose exec -T -e LOADTEST=1 app php artisan loadtest:purge --force
    ;;

  *)
    cat <<'EOF'
./deploy.sh up                     build, start, migrate, cache
./deploy.sh migrate | optimize     after a rebuild or an .env change
./deploy.sh doctor [--json]        what the stack is wired to; non-zero only on a real fault
./deploy.sh warm                   rebuild the caches a clear emptied (seal keys, manifest, board)
./deploy.sh logs | ps | down       day to day
./deploy.sh nuke                   stop AND delete the data volumes
./deploy.sh key                    print a fresh APP_KEY
./deploy.sh shell                  a shell in the app container
./deploy.sh seed-loadtest [teams] [accounts]
./deploy.sh purge-loadtest
EOF
    ;;
esac
