#!/usr/bin/env bash
# A backup that has been restored, because one that has not is a rumour.
#
# SCALING-PLAN.md §5: one box is one point of failure for the whole event, and offsite backup while
# the event runs is the most valuable thing the venue uplink buys. It also says what was still
# required — "a restore that has been rehearsed rather than written down" — and this is the half that
# makes rehearsing possible.
#
# The important part is `verify`: every backup is restored into a scratch database inside the same
# MySQL container and its row counts compared against the live ones. A dump that cannot be loaded is
# found here, in the morning, instead of at the one moment it matters.
#
#   ./backup.sh                 take one, verify it, prune old ones
#   ./backup.sh --no-verify     faster; for a mid-event snapshot where seconds count
#   ./backup.sh list
#   ./backup.sh verify FILE     re-check an old one
#
# Mid-event, run it from cron on the host every ten minutes:
#   */10 * * * * cd /path/to/docker && ./backup.sh --no-verify >> backup.log 2>&1
set -euo pipefail
cd "$(dirname "$0")"

[ -f .env ] || { echo "No .env here. Copy .env.docker.example first." >&2; exit 1; }
# shellcheck disable=SC1091
set -a; . ./.env; set +a

DB="${DB_DATABASE:-eureka}"
ROOT_PW="${MYSQL_ROOT_PASSWORD:?MYSQL_ROOT_PASSWORD must be set in .env}"
DIR="${BACKUP_DIR:-./backups}"
KEEP="${BACKUP_KEEP:-24}"
# Where a copy goes so a dead box does not take the event with it. Anything rsync understands:
# user@host:/path, or a mounted drive. Empty means local only, which is not a backup.
OFFSITE="${BACKUP_OFFSITE:-}"

compose() { docker compose "$@"; }
mysql_root() { compose exec -T mysql mysql -u root -p"$ROOT_PW" "$@"; }

mkdir -p "$DIR"

# Tables whose contents are the event. Counted before and after a verify restore, so "it restored"
# is a comparison rather than an impression.
COUNTED=(teams users questionnaires questions game_locations hotspots indoor_maps indoor_map_spots \
         quiz_attempts user_answers game_assessments login_cards race_sessions game_archives \
         rfid_tags tap_nodes post_arrivals team_score_adjustments)

counts_from() {   # $1 = database name
  local db="$1" out=""
  for t in "${COUNTED[@]}"; do
    local n
    n=$(mysql_root -N -B -e "SELECT COUNT(*) FROM \`$t\`" "$db" 2>/dev/null || echo "-")
    out+="$t=$n "
  done
  echo "$out"
}

take() {
  local stamp sql tgz
  stamp=$(date +%Y%m%d-%H%M%S)
  sql="$DIR/eureka-$stamp.sql.gz"
  tgz="$DIR/eureka-$stamp-storage.tgz"

  echo "→ dumping $DB"
  # --single-transaction so the dump is consistent without locking the event out of its own database.
  compose exec -T mysql mysqldump -u root -p"$ROOT_PW" \
      --single-transaction --quick --default-character-set=utf8mb4 \
      --routines --events "$DB" | gzip -6 > "$sql"

  echo "→ dumping storage (photos, archives, sealed questionnaires)"
  # The caches are not backed up: livewire-tmp and ui-sweep are scratch, and the offline manifest
  # and seal keys rebuild with `eureka:warm`.
  compose exec -T app tar czf - \
      --exclude='storage/app/livewire-tmp' \
      --exclude='storage/app/private/livewire-tmp' \
      --exclude='storage/app/ui-sweep*' \
      --exclude='storage/app/temp' \
      -C /var/www/html storage/app > "$tgz"

  ls -lh "$sql" "$tgz" | awk '{ printf "  %-10s %s\n", $5, $9 }'

  if [ "${1:-}" != "--no-verify" ]; then
    verify "$sql"
  else
    echo "  not verified (--no-verify) — verify the next one"
  fi

  if [ -n "$OFFSITE" ]; then
    echo "→ copying offsite to $OFFSITE"
    rsync -a "$sql" "$tgz" "$OFFSITE/" && echo "  offsite ok" || echo "  OFFSITE COPY FAILED — the backup is only on this box" >&2
  else
    echo "  BACKUP_OFFSITE is empty: this copy lives on the same machine as the data it protects."
  fi

  prune
}

# Load the dump into a scratch database and compare. This is the whole point of the script.
verify() {
  local sql="${1:?which dump?}"
  local scratch="${DB}_verify"

  echo "→ verifying $sql by restoring it into $scratch"

  local before after
  before=$(counts_from "$DB")

  mysql_root -e "DROP DATABASE IF EXISTS \`$scratch\`; CREATE DATABASE \`$scratch\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
  gunzip -c "$sql" | mysql_root --default-character-set=utf8mb4 "$scratch"

  after=$(counts_from "$scratch")
  mysql_root -e "DROP DATABASE IF EXISTS \`$scratch\`;"

  if [ "$before" = "$after" ]; then
    echo "  restored and every table matches:"
    echo "    $before" | tr ' ' '\n' | grep -v '^$' | sed 's/^/    /' | paste -sd' ' -
    return 0
  fi

  echo "  MISMATCH — the dump does not restore to the same data." >&2
  echo "    live:     $before" >&2
  echo "    restored: $after" >&2
  return 1
}

prune() {
  local n
  n=$(ls -1t "$DIR"/eureka-*.sql.gz 2>/dev/null | wc -l)
  [ "$n" -le "$KEEP" ] && return 0

  echo "→ pruning to the newest $KEEP"
  ls -1t "$DIR"/eureka-*.sql.gz | tail -n +$((KEEP + 1)) | while read -r old; do
    rm -f "$old" "${old%.sql.gz}-storage.tgz"
    echo "  removed $(basename "$old")"
  done
}

case "${1:-take}" in
  take|--no-verify) take "${1:-}" ;;
  verify)           verify "${2:?./backup.sh verify FILE}" ;;
  list)
    ls -lht "$DIR"/eureka-*.sql.gz 2>/dev/null | awk '{ printf "  %-10s %s %s %s  %s\n", $5, $6, $7, $8, $9 }' \
      || echo "  no backups in $DIR"
    ;;
  *)
    cat <<'EOF'
./backup.sh                 take a backup, verify it restores, prune old ones
./backup.sh --no-verify     take one without the verify restore (mid-event snapshots)
./backup.sh verify FILE     restore an existing dump into a scratch DB and compare
./backup.sh list

Set in .env:  BACKUP_DIR (default ./backups)  BACKUP_KEEP (24)  BACKUP_OFFSITE (rsync target)
EOF
    ;;
esac
