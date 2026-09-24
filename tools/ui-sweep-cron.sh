#!/bin/bash
#
# Run the browser sweep unattended, the way a cron entry has to.
#
# hPanel owns cron on this host — the shell's `crontab` is a read-only shim — so this is the file an
# hPanel entry points at. Schedule it with the server clock, which is UTC:
#
#   0 2 * * *   /bin/bash ~/domains/questerra-series.com/public_html/EUREKA_APP/tools/ui-sweep-cron.sh
#   ^ 02:00 UTC = 09:00 WIB
#
# One run at a time: a sweep takes about fifteen minutes and two at once would exhaust this host's
# process limit, which is what killed the first attempt. The lock is released even if the run dies.
#
# The log is kept per day so a bad morning can be compared with a good one, and the previous week is
# discarded — screenshots and logs are for the crew that day, not an archive.

set -u

APP="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
STAMP="$(date -u '+%Y-%m-%d')"
LOG="$APP/storage/app/ui-sweep-$STAMP.log"
SHOTS="$APP/storage/app/ui-sweep/$STAMP"
LOCK="$APP/storage/app/ui-sweep.lock"

cd "$APP" || exit 1

exec 9>"$LOCK"
if ! flock -n 9; then
    echo "$(date -u '+%F %T') another sweep is still running; skipped" >> "$LOG"
    exit 0
fi

mkdir -p "$SHOTS"

{
    echo "=== ui-sweep $(date -u '+%F %T') UTC  ($(TZ=Asia/Jakarta date '+%F %T') WIB)"
} >> "$LOG"

# node is installed through nvm, which only exists in a login shell — cron has neither, so the path
# is named outright. If the node version is ever upgraded, this line is the one to change.
NODE="$HOME/.nvm/versions/node/v22.22.3/bin/node"
[ -x "$NODE" ] || NODE="$(command -v node)"
if [ -z "${NODE:-}" ] || [ ! -x "$NODE" ]; then
    echo "$(date -u '+%F %T') no usable node; sweep skipped" >> "$LOG"
    exit 1
fi

"$NODE" tools/ui-sweep.cjs --log="$LOG" --shots="$SHOTS" >> "$LOG" 2>&1
STATUS=$?

{
    echo "=== finished with status $STATUS at $(date -u '+%F %T') UTC"
    echo
} >> "$LOG"

# Keep a week. `-mtime +7` leaves today's alone even when a run spans midnight.
find "$APP/storage/app" -maxdepth 1 -name 'ui-sweep-*.log' -mtime +7 -delete 2>/dev/null
find "$APP/storage/app/ui-sweep" -maxdepth 1 -type d -name '20*' -mtime +7 -exec rm -rf {} + 2>/dev/null

exit "$STATUS"
