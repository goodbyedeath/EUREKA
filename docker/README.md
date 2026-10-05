# Deploying EUREKA on the workstation — phase 1

One machine, Docker Compose, no Kubernetes (`SCALING-PLAN.md` §4 says why). Nine containers: nginx, two
app containers, two queue workers, a scheduler, MySQL, and two Redis instances.

Nothing here has been run yet — there is no Docker on the shared host, by design. Treat the first bring-up
as the test of this file.

## What you need on the workstation

- Docker Engine + the compose plugin (Docker Desktop also works).
- This repository, including `docker/`.
- Ports 80 and 443 free.

## Bring it up

```bash
cd docker
cp .env.docker.example .env
./deploy.sh key          # prints an APP_KEY; paste it into .env
nano .env                # APP_KEY, DB_PASSWORD, MYSQL_ROOT_PASSWORD
./deploy.sh up           # build, start, migrate, cache, storage:link
```

`./deploy.sh up` ends by printing the wiring table and telling you to open `/up` — Laravel's own health
endpoint. If that answers, the chain nginx → php-fpm → MySQL → Redis is intact.

```bash
./deploy.sh doctor       # what the app is ACTUALLY using: drivers, both Redis instances, opcache, MySQL
```

Read that table rather than trusting `.env`: the two are different claims whenever a config cache is stale,
which is the most common thing to go wrong here. It exits non-zero only on a real fault, so it can go in a
health check or a cron.

Day to day:

```bash
./deploy.sh logs         # everything, following
./deploy.sh ps
./deploy.sh doctor       # drivers, Redis, opcache, what still writes to MySQL
./deploy.sh optimize     # after any .env change — required, see CLAUDE.md
./deploy.sh down         # stop; data survives in named volumes
./deploy.sh nuke         # stop AND delete the data. There is no undo
```

## Moving the live data onto it

The event data lives on the shared host today. Export there, import here:

```bash
# on the shared host
/opt/alt/php83/usr/bin/php artisan down
mysqldump -u USER -p DATABASE | gzip > eureka.sql.gz
tar czf storage.tgz storage/app

# on the workstation, with the stack up
gunzip -c eureka.sql.gz | docker compose exec -T mysql mysql -u root -p"$MYSQL_ROOT_PASSWORD" eureka
docker compose cp storage.tgz app:/tmp/ && docker compose exec app tar xzf /tmp/storage.tgz -C /var/www/html
./deploy.sh optimize
```

Check the row counts on both sides before pointing a single phone at it.

## The discipline this stack asks for

**Stop editing files on the server.** `opcache.validate_timestamps=0` means a running container will never
notice an edited file, and that is the point: a fix is a rebuild with a new tag, not a patch in place. It is
a real change from how the Hostinger deployment is operated today, and it is what makes two app containers
and a later move to anything else possible.

What survives a rebuild: MySQL data, the queue's append-only file, and `storage/` (photos, archive ZIPs,
sealed questionnaires). What does not: the application, its caches, its logs. Keep nothing you care about
anywhere else.

## TLS and the venue LAN

From `SCALING-PLAN.md` §5: the phones resolve `questerra-series.com` to this box, so the certificate must be
valid for **that** name, and the player VLAN must have **no route to the internet** — otherwise a handset
using Private DNS or DoH reaches the old production server and half the event's data lands on the wrong
machine.

1. Bring the stack up on port 80 and prove it works.
2. Get a certificate for the hostname by DNS-01 (the venue has an uplink; no inbound needed):
   `certbot certonly --manual --preferred-challenges dns -d questerra-series.com`
3. Put `fullchain.pem` and `privkey.pem` in `docker/nginx/certs/`, uncomment the TLS block in
   `nginx/default.conf`, set `SESSION_SECURE_COOKIE=true` in `.env`, then `docker compose restart nginx`
   and `./deploy.sh optimize`.

## Running the load harness against it

```bash
./deploy.sh seed-loadtest 500 5000     # creates LT_ teams/accounts/cards, copies accounts.json out
# then, from another machine, in tools/loadtest/k6:
TARGET=http://<workstation-ip> PHONES=200 k6 run all.js    # start small
./deploy.sh purge-loadtest
```

The harness refuses to point at the live host, and the seeder refuses to run without `LOADTEST=1`.
`tools/loadtest/README.md` has the rest.

## What phase 1 does and does not change

This stack **is** phase 2 as far as configuration goes: `.env.docker.example` already sets
`CACHE_STORE=redis`, `SESSION_DRIVER=redis` and `QUEUE_CONNECTION=redis`. That removes the first blocker —
every player request writing the `cache` table — with no application change, because the rate limiter
follows the cache store by itself.

Two Redis instances, and which data goes where is not arbitrary:

| | `redis-cache` | `redis-queue` |
|---|---|---|
| policy | `allkeys-lru`, 4 GB, no persistence | `noeviction`, 1 GB, AOF on |
| holds | cached leaderboards, manifests, rate-limit counters | queued jobs (db 0) **and sessions** (db 2) |
| if it is lost | the next request recomputes | work and logins are gone |

Sessions sit on the non-evicting instance (`SESSION_CONNECTION=persistent`) because `allkeys-lru` would
drop an admin session as readily as a cached leaderboard, and `volatile-lru` is no protection when the
thing worth keeping is exactly the thing with a TTL. `./deploy.sh doctor` fails if either instance is
reachable but evicting.

Phase 2 is only *configured*, not yet *measured*. `tools/loadtest/README.md` has the three-step procedure —
doctor, the `cache` row count, then the same k6 scenario on both sets of drivers — and the empty table to
fill in.

Still untouched, and still in the plan: the per-request kiosk leaderboard (§3b), the 100-query manifest
(§3c), base64 photos inside the attempt row (§3d), and the per-IP login limits that will block the venue's
own NAT (§3e). **Run the baseline first** (`tools/loadtest`) so those four can be proved rather than
asserted.

## If it does not come up

| Symptom | Where to look |
|---|---|
| `/up` times out | `./deploy.sh logs nginx app` — usually `.env` missing `APP_KEY` |
| MySQL restarts in a loop | `innodb_buffer_pool_size = 16G` in `mysql/my.cnf` is larger than the machine |
| 500 on every page | `./deploy.sh optimize`; a config cache from a previous `.env` is the trap in CLAUDE.md |
| Assets 404 | the `public` volume kept an old build: `docker compose down && ./deploy.sh up` |
| Queue does nothing | `./deploy.sh logs worker`; check `REDIS_QUEUE_HOST=redis-queue` |
| Admins logged out at random | `./deploy.sh doctor` — sessions on an evicting Redis; `SESSION_CONNECTION=persistent` |
| `doctor` says `database` | the config cache predates the `.env`: `./deploy.sh optimize` |
