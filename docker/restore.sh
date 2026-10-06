#!/usr/bin/env bash
# The other half of §5's "a restore that has been rehearsed rather than written down".
#
# Deliberately awkward to run by accident and deliberately not awkward to run in a drill: it names
# what it is about to overwrite, counts it, and makes you type the word. The point of phase 7 is that
# the crew has done this once, on the real box, with the real backup — not read about it.
#
#   ./restore.sh backups/eureka-20261006-0930.sql.gz
#   ./restore.sh backups/eureka-20261006-0930.sql.gz --with-storage
set -euo pipefail
cd "$(dirname "$0")"

SQL="${1:?./restore.sh backups/eureka-YYYYmmdd-HHMMSS.sql.gz [--with-storage]}"
WITH_STORAGE="${2:-}"

[ -f "$SQL" ] || { echo "No such dump: $SQL" >&2; exit 1; }
[ -f .env ] || { echo "No .env here." >&2; exit 1; }
# shellcheck disable=SC1091
set -a; . ./.env; set +a

DB="${DB_DATABASE:-eureka}"
ROOT_PW="${MYSQL_ROOT_PASSWORD:?MYSQL_ROOT_PASSWORD must be set in .env}"
TGZ="${SQL%.sql.gz}-storage.tgz"

compose() { docker compose "$@"; }
mysql_root() { compose exec -T mysql mysql -u root -p"$ROOT_PW" "$@"; }

echo "About to replace the contents of database '$DB' with $SQL"
echo
echo "What is in there now:"
for t in teams users quiz_attempts post_arrivals game_archives; do
  printf '  %-16s %s\n' "$t" "$(mysql_root -N -B -e "SELECT COUNT(*) FROM \`$t\`" "$DB" 2>/dev/null || echo '-')"
done
echo
[ "$WITH_STORAGE" = "--with-storage" ] && echo "storage/app will also be replaced from $TGZ" && echo

read -r -p "Type RESTORE to continue: " typed
[ "$typed" = "RESTORE" ] || { echo "Nothing done."; exit 1; }

echo "→ closing the door"
compose exec -T app php artisan down --render=errors::503 >/dev/null 2>&1 || true

echo "→ restoring the database"
# Dropped and recreated rather than imported over the top: a dump loaded onto existing rows leaves
# whatever the dump did not mention, which is the worst of both.
mysql_root -e "DROP DATABASE IF EXISTS \`$DB\`; CREATE DATABASE \`$DB\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
gunzip -c "$SQL" | mysql_root --default-character-set=utf8mb4 "$DB"

if [ "$WITH_STORAGE" = "--with-storage" ]; then
  [ -f "$TGZ" ] || { echo "No storage archive beside that dump: $TGZ" >&2; exit 1; }
  echo "→ restoring storage/app"
  compose exec -T app rm -rf /var/www/html/storage/app.restoring
  compose exec -T app mkdir -p /var/www/html/storage/app.restoring
  # Unpacked beside the live one and swapped, so a half-finished extract never serves.
  compose exec -T app tar xzf - -C /var/www/html/storage/app.restoring < "$TGZ"
  compose exec -T app sh -c 'rm -rf /var/www/html/storage/app.old && mv /var/www/html/storage/app /var/www/html/storage/app.old && mv /var/www/html/storage/app.restoring/storage/app /var/www/html/storage/app && rm -rf /var/www/html/storage/app.restoring'
  compose exec -T app chown -R www-data:www-data /var/www/html/storage
  echo "  the previous storage/app is kept as storage/app.old until you delete it"
fi

echo "→ migrating anything the dump predates"
compose exec -T app php artisan migrate --force

echo "→ rebuilding caches"
compose exec -T app php artisan optimize >/dev/null
compose exec -T app php artisan eureka:warm

echo "→ opening up"
compose exec -T app php artisan up >/dev/null

echo
./deploy.sh doctor
echo
echo "Restored. Check /admin/kesiapan and open one team's floor plan before trusting it."
