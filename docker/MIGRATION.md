# Moving EUREKA to the workstation

Two paths, and **the operator chose the first (5 Oct): start clean.** Nothing is carried over from
Hostinger — no database, no images, no login cards, no indoor maps.

| | [Clean start](#clean-start-the-chosen-path) | [Migration](#migration-carrying-the-data-across) |
|---|---|---|
| What moves | nothing | ~5 MB of MySQL + 370 MB of `storage` |
| `APP_KEY` | **generate a new one** — correct and safe | **copy it**, or lose login cards for good |
| Takes | minutes | an hour, plus the checks |
| Costs | re-authoring the whole event | nothing |
| Risk | none that cannot be redone | two irreversible steps |

The migration half is kept below because the choice may change for a later event, and because the
`APP_KEY` rule in it is the single most expensive mistake available in this project.

---

## Clean start — the chosen path

### What a clean start actually costs

Worth reading once before typing anything, because this is authoring work, not data:

| Gone | Live count | Re-creatable? |
|---|---|---|
| Questionnaires and their questions | 15 + 15 | Yes, in the admin. Each questionnaire gets a **new QR code** on creation, so **every paper QR has to be reprinted**. |
| AR Outposts and their 3D objects | 15 + 46 | Yes — but the placement was done **on site**, standing at each booth. That is the expensive one. |
| Indoor maps and their spots | 10 + 160 | Yes. Tim 01 is authored by hand and the other nine are rotations of it. |
| `.glb` model library | 4 models | Re-upload the files. |
| Quest locations, map routes | 7 + routes | Yes; routes also re-import from the tracker app hourly. |
| Teams, accounts, login cards | 10 cards | Yes — **new cards must be printed**, the old ones stop working. |
| Finished-event archives | 7 (239 MB) | **No.** This is the only truly unrecoverable item: it is the history of events already run. |
| Branding/logo, Panduan, hero slides | — | Re-upload / retype. |

Two consequences that catch people out:

- **Every printed QR becomes wrong.** Questionnaire QR codes are UUIDs minted at creation
  (`QuestionnaireService::generateUniqueQrCode`), and the same code seals the offline copy. New
  questionnaire, new code, new print run — paper questionnaire QRs *and* login cards.
- **Every phone has to log in again.** New accounts mean new Sanctum tokens; the app will 401 and
  ask. It must also re-Sync, because the manifest it holds describes posts that no longer exist.

If what you wanted was a clean *event* rather than a clean *server* — no teams, no attempts, no
archives, but keep the questionnaires, the AR scenes and the floor plans — say so, because that is a
third path: import the content tables only and leave the player data behind. It saves the on-site AR
placement and the reprinting.

**Before you wipe anything: take the export anyway.** `mysqldump` plus a `tar` of `storage/app` is
370 MB and ten minutes, and it is the difference between "we decided to start clean" and "we cannot
get the archives back". Keep it somewhere that is not the workstation. The commands are in the
migration section below — run step 2 of it, then stop.

### The procedure

```bash
cd docker
cp .env.docker.example .env

./deploy.sh key            # a FRESH key: correct here, nothing encrypted exists yet
nano .env                  # paste it, set DB_PASSWORD and MYSQL_ROOT_PASSWORD

./deploy.sh up             # build, start, migrate, optimize, warm
./deploy.sh install        # defaults + an admin with a real password + the facilitator PIN
./deploy.sh doctor
```

`./deploy.sh install` is interactive on purpose — the admin password is typed, not left in shell
history. It does three things a clean database needs and nothing else provides:

1. **Seeds the defaults** — the feature rows and the hero slides.
2. **Creates one admin.** There is a `UserSeeder` in the repo that makes `admin@gmail.com` with the
   password `123`; it is not used here and must not be, on a box 5,000 phones can reach. Twelve
   characters minimum, asked twice.
3. **Sets the facilitator PIN.** `FacilitatorPin` **fails closed**: until it is set, every attempt to
   score a game answers `facilitator_pin_not_set`. Nobody discovers that in testing — they discover
   it at the first post, with a team waiting.

It refuses to run against a database that already holds an event, and it never deletes anything.
Verified on this host: it listed the live questionnaires, maps, cards and archives and exited 1
without touching them.

Then `doctor` should read: both Redis instances reachable, `redis:queue` and `redis:persistent` on
`[noeviction]`, and `APP_KEY vs encrypted data` → `no login cards to check against`. That last line is
the correct answer for a clean install; it changes to `matches` once the first login cards are
printed.

### Then author the event, in this order

`install` prints this list too, because the order matters — a questionnaire cannot be linked to a
post that does not exist yet:

| Where | What |
|---|---|
| `/admin/quest-locations` | Outdoor posts, if this event has any (coordinates + radius) |
| `/admin/games` | AR Outposts: the 3D scene per post, `access_mode = manual` indoors |
| `/admin/questionnaires` | The questions. Each gets its QR on creation — **that is the one you print** |
| `/admin/indoor-maps` | The floor plan per team, each spot linked to its AR Outpost |
| `/admin/race-start` | The START code and its time limit |
| `/admin/team-management` | Teams, then Login Cards to print the accounts |
| `/admin/panduan` + dashboard management | The in-app guide, branding and logo |
| `/admin/outpost-access` | Nothing to set up — this is the desk the crew runs the event from |

Then walk it once as a team, with one phone, before trusting it: scan START, answer the clue, have
the crew open a post in Outpost Access, tap the 3D object, scan the questionnaire QR, hand in, and
check the score lands on `/admin/user-progress` and on the LED board.

---

## Migration — carrying the data across

Not the chosen path for this move, kept for the next one.

### Before you start: the two things that cannot be undone

**1. `APP_KEY` must be copied, never generated.**

`docker/README.md` tells you to run `./deploy.sh key` — that is for a *fresh* install. For a
migration it is the worst possible move, because two things on this server are tied to that key:

| What | What a new key does |
|---|---|
| Login cards (`login_cards.code_encrypted`, `password_encrypted`) | **Permanent loss.** `Crypt::decryptString` fails with *"The MAC is invalid"*. The printed cards keep working — the login code is matched by hash — but you can never reprint or re-reveal one. Measured on the live data: 10 cards, all encrypted. |
| The offline seal's salt | Derived from the key (`OfflineQuestionnaireSeal::salt()`). A new key means a new salt, so every sealed questionnaire already cached on a phone becomes unopenable until that phone re-syncs. Verified: the salt changes from `gHYnE0tDSIc9glM7ZVSJYA==` to something else the moment the key does. |

So: copy `APP_KEY` out of the Hostinger `.env` verbatim, into `docker/.env`. Nothing else in that
file should be copied blindly — the database host, the Redis settings and the drivers are all
different on the stack.

After bring-up, **`./deploy.sh doctor` checks this for you**: the row `APP_KEY vs encrypted data`
decrypts a real login card and says `WRONG KEY` in red if the key does not match. Look at that row
before you point a phone at the box.

**2. Never let both servers be live for the same event.**

Same hostname, two databases, nothing to merge afterwards — SCALING-PLAN.md §5 calls this
split-brain and it is worse than an outage. The moment the workstation owns the data, Hostinger must
stop being an event server. Decide which box owns which event *in writing*, beforehand.

---

### What actually has to move

Measured on the live host, 5 Oct 2026:

| | Size | Notes |
|---|---|---|
| MySQL dump | **~5 MB** | The whole event database. `game_archives` is the biggest table at 1.5 MB. |
| `storage/app/private` | **245 MB** | 239 MB of it is `game-archives` — the finished-event ZIPs and photos. |
| `storage/app/public` | **122 MB** | The live assets: `.glb` models, photos, floor plans, uploads. Served by nginx. |
| `storage/app/ui-sweep` | 4.5 MB | Screenshots from an old tooling run. **Leave it behind.** |

So about 370 MB in total, and it compresses. This is a USB-stick-sized move, not an infrastructure
project — the reason it needs care is the key, not the volume.

**Not moving, and no action needed:**

- **Sanctum tokens survive.** They are stored hashed, not encrypted, so a team already logged in on
  a phone stays logged in after the move. 17 tokens on the live host today.
- **Sessions, cache and queue tables** — throwaway. Do not import them; the stack has Redis.
- **`vendor/`, `node_modules/`, `bootstrap/cache/`, `public/build/`** — all rebuilt by the image.

---

### The procedure

#### 1. On the workstation: bring the stack up empty and prove it works

```bash
cd docker
cp .env.docker.example .env
nano .env          # APP_KEY from Hostinger (see above), DB_PASSWORD, MYSQL_ROOT_PASSWORD
./deploy.sh up
./deploy.sh doctor
```

Expect `cache store: redis`, `session driver: redis (persistent)`, `queue connection: redis`, both
Redis instances reachable, `redis:queue` and `redis:persistent` on `[noeviction]`, and
`APP_KEY vs encrypted data` → `no login cards to check against` (the database is still empty; it
will say `matches` after the import).

Open `http://<workstation-ip>/up`. If that answers, nginx → php-fpm → MySQL → Redis is intact and
every later failure is about data, not about the stack.

#### 2. On Hostinger: close the door, then export

```bash
cd ~/domains/questerra-series.com/public_html/EUREKA_APP
PHP=/opt/alt/php83/usr/bin/php

$PHP artisan down                       # nobody writes while you copy

mysqldump -u DBUSER -p DBNAME --single-transaction --default-character-set=utf8mb4 \
  | gzip > ~/eureka-$(date +%F).sql.gz

tar czf ~/eureka-storage-$(date +%F).tgz \
  --exclude='storage/app/ui-sweep*' \
  --exclude='storage/app/livewire-tmp' \
  --exclude='storage/app/private/livewire-tmp' \
  --exclude='storage/app/temp' \
  storage/app

ls -lh ~/eureka-*
```

**Write down the row counts now** — you will compare them after the import:

```bash
$PHP artisan tinker --execute='foreach (["teams","users","questionnaires","questions","game_locations","hotspots","indoor_maps","indoor_map_spots","login_cards","game_archives","quiz_attempts","user_answers"] as $t) printf("%-20s %d\n", $t, DB::table($t)->count());'
```

Both files are in your home directory, which hPanel's File Manager can browse — download them from
there.

> Keep the app down only as long as the copy takes (seconds, at this size). `artisan up` afterwards
> if you still want Hostinger reachable; leave it down if the workstation is taking over now.

#### 3. On the workstation: import

```bash
cd docker

gunzip -c eureka-YYYY-MM-DD.sql.gz | docker compose exec -T mysql \
  mysql -u root -p"$MYSQL_ROOT_PASSWORD" --default-character-set=utf8mb4 eureka

docker compose cp eureka-storage-YYYY-MM-DD.tgz app:/tmp/storage.tgz
docker compose exec app tar xzf /tmp/storage.tgz -C /var/www/html
docker compose exec app chown -R www-data:www-data /var/www/html/storage
docker compose exec app php artisan storage:link

./deploy.sh migrate      # anything the dump predates
./deploy.sh optimize
./deploy.sh warm         # the seal's PBKDF2 keys: ~2 s here instead of on a player's first Sync
./deploy.sh doctor
```

#### 4. Check it before any phone sees it

```bash
./deploy.sh doctor
```

The row that matters most on this day: **`APP_KEY vs encrypted data` must say `matches`.** If it
says `WRONG KEY`, stop — put the Hostinger `APP_KEY` into `.env`, `./deploy.sh optimize`, and check
again. Do not "fix it later": re-encrypting is not possible, and every hour the crew spends creating
new cards against the wrong key is an hour of work to redo.

Then compare the counts against what you wrote down in step 2:

```bash
docker compose exec -T app php artisan tinker --execute='foreach (["teams","users","questionnaires","questions","game_locations","hotspots","indoor_maps","indoor_map_spots","login_cards","game_archives","quiz_attempts","user_answers"] as $t) printf("%-20s %d\n", $t, DB::table($t)->count());'
```

And walk the five things that break in different ways, in a browser, as the admin:

1. **Login** — proves sessions and `APP_KEY` basics.
2. **Team Management → reprint a login card** — proves the encrypted payload survived. This is the
   one that silently fails on a wrong key.
3. **Indoor Maps → open a plan** — proves `storage/app/public` arrived and `storage:link` is in place
   (the floor-plan image loads, the markers sit on it).
4. **Games → an AR Outpost → the editor** — proves the `.glb` models are there and nginx serves them.
5. **Game Archives → download a batch ZIP** — proves `storage/app/private` arrived, which is 239 MB
   of the move and the easiest to forget.

Then one API call as a player, with a token:

```bash
curl -s -o/dev/null -w '%{http_code} %{size_download}\n' \
  -H "Authorization: Bearer <token>" -H 'Accept: application/json' \
  http://<workstation-ip>/api/v1/offline/manifest
```

A 200 with ~60 KB means the whole sync path works: sealed questionnaires, scenes, floor plan, assets.

#### 5. Only then, point the phones at it

This is SCALING-PLAN.md §5, and it is a network job, not an application one: split-horizon DNS for
`questerra-series.com` on the venue LAN, a certificate valid for that name, and **no internet route
on the player VLAN** — that last one is what makes the Hostinger copy unreachable and split-brain
impossible.

Until the certificate is in place you can test over plain HTTP by IP from a laptop, but **not from
the APK**: Android blocks cleartext, and the token would travel in the clear anyway.

---

### If something is wrong

| Symptom | Cause |
|---|---|
| `doctor` says `WRONG KEY` | `APP_KEY` was generated instead of copied. Fix it before anything else. |
| Login cards cannot be reprinted, everything else fine | Same thing. If new cards were already created under the wrong key, those are the ones that work now and the old ones are gone. |
| Floor-plan and model images 404 | `storage:link` missing, or `storage/app/public` not extracted. |
| Archive ZIP download 404 | `storage/app/private` was left out of the tar. |
| Phones say the sealed questionnaire will not open | The salt moved: either a different `APP_KEY`, or they are holding a manifest from the other server. A Sync fixes the second. |
| First Sync takes two seconds | `./deploy.sh warm` was not run after a cache clear. Harmless, but do not let it happen during an event. |
| Admin logged out at random | Sessions on the evicting Redis. `doctor` fails on this; `SESSION_CONNECTION=persistent`. |

### Rolling back

Nothing in this procedure writes to Hostinger except `artisan down`. If the workstation is not
ready, `artisan up` there and you are exactly where you started — which is the reason to do the whole
thing once as a rehearsal, long before an event, and keep the export files.
