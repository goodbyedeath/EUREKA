# Scaling to 5,000 players — plan of record

Written 28 Sep 2026 from a design discussion with the operator. Nothing here is built yet: this is the
agreed direction, the measurements it rests on, and the questions still open. Read it before starting any
infrastructure work, and update it when a number or a decision changes.

The target: **5,000 accounts playing at once**, on hardware the operator owns — a workstation on the venue
premises, with the players on the venue's own WiFi (no internet dependency for gameplay).

---

## 1. What "5,000 playing" means in requests

Derived from the polling intervals this project already promises the Android client
(`APK-BUILD-GUIDE.md` §6), not from a guess:

| Call | Interval | 5,000 phones |
|---|---|---|
| `/race/status` | 10 s while a race screen is open | **500 req/s** |
| `/tracking/position` | 10 s minimum (outdoor) | 500 req/s |
| `/ar/locations/{id}` while `awaiting_unlock` | 15 s | ≤ 333 req/s |
| `/quiz/timer/{id}` | 10 s inside a quiz | ≤ 500 req/s |
| `/offline/manifest` | once per Sync | 5,000 in the opening burst |

**Design for 500–1,200 req/s sustained**, plus one storm in the first ten minutes (login + Sync + START
scans together). The storm, not the steady state, is what breaks events.

## 2. Measured baseline (27 Sep 2026, current shared host, empty database)

In-process HTTP kernel requests, PHP 8.3, 12 samples each:

| Endpoint | p50 | body | queries |
|---|---|---|---|
| `/api/v1/race/status` | 7.0 ms | 514 B | **21** |
| `/api/v1/team` | 6.2 ms | 382 B | 16 |
| `/api/v1/indoor-map` | 10.4 ms | 4.9 KB | 19 |
| `/api/v1/offline/manifest` | 54.3 ms | 60 KB | **100** |
| `/api/kiosk/leaderboard` | 6.6 ms | 457 B | 20 (empty DB — see §3b) |

Reading: 500 req/s × 21 queries ≈ **10,000 queries/s** for status polling alone, and 7 ms × 500 = **~4 CPU
cores** before a single quiz, photo or kiosk screen.

Also measured, and decisive: `CACHE_STORE=database`, `SESSION_DRIVER=database`,
`QUEUE_CONNECTION=database`. Laravel's rate limiter uses the cache store, so **every player request is a
read *and* a write on the `cache` table**.

## 3. The five application blockers — ranked. No hardware fixes these.

**a. Cache, session, queue and rate limiter all live in MySQL.** At 500 req/s that is ~1,000 writes/s on
one InnoDB table. → Redis for all four. The `redis` extension is already present.

**b. The kiosk leaderboard is recomputed per request.** `KioskController::leaderboard()` calls
`PointsCalculationService::teamScore()` for every team, and each of those is several queries; with 500
teams that is thousands of queries per poll, multiplied by every screen. → compute once per 10 s, cache,
serve every screen from the same value.

**c. `/offline/manifest` is 100 queries and 60 KB, identical for every team on a plan.** 5,000 Syncs
would be 500,000 queries. → cache per `(indoor_map_id, content_updated_at)` with an ETag; on a LAN, serve
the heavy assets (`.glb`, tiles, images) as static files through nginx, never through PHP.

**d. Verification photos are base64 inside `quiz_attempts`.** Measured earlier at ~48 KB per attempt:
5,000 players × 15 posts ≈ **3.6 GB of BLOBs inside MySQL rows**, written and read through PHP. → files on
disk (or object storage), thumbnailing in a queue job, not in the request.

**e. Per-IP rate limits will block the venue itself.** `api` and `tracking` key on the user (fine), but
`login-code` is 60/min per IP and `auth` 60/min per IP. 5,000 phones behind one NAT means ~500 logins/min
from one address in the opening ten minutes. → key on IP+device, or allow-list the venue ranges, or raise
the login-code ceiling for the event. **This will happen in the first minute of the first 5,000-player
event if it is not changed.**

## 4. Infrastructure: Docker Compose on one workstation. Not Kubernetes.

```
caddy|nginx   TLS, HTTP/2, brotli, static assets (tiles, .glb, images)
app           php-fpm 8.3, opcache on, 200–400 workers        ×2–4
redis         cache + session + queue + rate limiter
mysql 8       innodb_buffer_pool ≈ 50% RAM, slow query log on
worker        php artisan queue:work                          ×2–4
scheduler     php artisan schedule:work
```

**Why not Kubernetes.** One venue, one machine, an event lasting hours. K8s solves scheduling across a
fleet and operations by a team; here it would add failure modes (etcd, CNI, ingress, webhook certificates)
to the morning of an event run by one person. What is actually wanted from it — restart on crash, health
checks, scaling the worker count — Compose gives with `restart: unless-stopped`, `healthcheck` and
`--scale`. Revisit when **many venues run at once**, when an existing IT team already operates a cluster,
or when this becomes multi-tenant SaaS; single-node k3s is then the lightest honest step.

`schedule:work` in a container also ends the hPanel cron problem documented in `CLAUDE.md`: no panel
rewrites the command, and the PHP version is the image's.

**Habits that keep a later move to K8s mechanical** — and one of them is a real change from how this repo
is operated today:

1. All configuration from the environment; nothing per-venue hardcoded.
2. **Stateless app containers: stop editing files on the server.** Build images from git with tags; fix by
   rebuilding. (Today this codebase is patched in place on the live host.)
3. Logs to stdout, not to files inside the container.
4. A health endpoint (`/up`).
5. Migrations as their own step before the app starts, never in an entrypoint that has replicas.

**The box is known (operator, 28 Sep): 16 cores, 64 GB RAM, 2 TB NVMe.** That is comfortable for 5,000
phones *provided §3 is done* — without it no core count helps, because it stalls on the `cache` table.

Allocation to start from, and to correct with the harness rather than by feel:

| | Setting | Why |
|---|---|---|
| php-fpm | 2 containers × 100 children (~12 GB) | Little's law: 1,000 req/s × 20 ms p95 ≈ 20 busy workers. 200 is burst headroom, and two containers allow a rolling restart |
| MySQL | `innodb_buffer_pool_size = 16G` | The whole event's rows are a few GB once photos are out of the DB (§3d); 16 GB holds it all in memory |
| Redis | `maxmemory 4gb`, cache LRU — **queue in a separate instance or DB with no eviction** | Rate-limit and cache keys are small; a queued job must never be evicted to make room for a cache entry |
| OS, nginx, workers | ~4 GB | |
| **Total** | **~40 GB of 64 GB** | leaves room for a second MySQL for a restore rehearsal, and for being wrong |

CPU at peak: ~3 cores for the app (1,000 req/s × 3 ms after §3), ~4 for MySQL, ~1 for thumbnailing in the
queue (75,000 photos over two hours ≈ 10/s). **Roughly 8 of 16 cores** — about 2× headroom, which is the
right amount for an event that cannot be paused.

Disk is not a constraint: photos are ~3.7 GB per 5,000-player event, archives ~80 MB each, MySQL a few GB.
2 TB is dozens of events. Plan retention and backups for tidiness, not for space.

The real ceiling on this machine is MySQL write concurrency and the venue WiFi — not cores or RAM.

## 5. LAN specifics

- **Split-horizon DNS is the elegant answer to TLS.** Answer `questerra-series.com` on the venue LAN with
  the server's local IP and hold a valid certificate for that name (Let's Encrypt via DNS-01, fetched once
  while online). **The APK then needs no change** — it already points at that host. The alternatives cost
  more: a configurable base URL (new APK release + contract change), a private CA (installing it on 5,000
  phones), or plain HTTP (Android blocks cleartext by default and Sanctum tokens would travel in clear).
- **The venue has an uplink (operator, 28 Sep).** That makes certificate renewal automatic, lets
  `tracker:sync-map`, the partner connector and APK distribution work live, gives NTP (which the two race
  clocks and the LoRa nodes both need), and — most valuable — allows **offsite backup while the event is
  running**, so a dead box does not take the event's data with it. Treat all of it as convenience, never as
  dependency: gameplay must still stand with the uplink cut.
- **The hazard the uplink introduces: split-brain data.** `questerra-series.com` resolves publicly to the
  shared host. A phone that bypasses the venue resolver — Android *Private DNS* (DoT) and DoH do exactly
  that — would reach the **old production server** and play there: some teams' attempts on the workstation,
  some on Hostinger, and no way to merge them afterwards. This is worse than an outage. The answer is not
  DNS trickery but routing: **the player VLAN gets no route to the internet at all.** Then the public
  address is unreachable, DoT/DoH fail and Android falls back to the DHCP resolver, and the only server a
  phone can reach is the local one. The crew and server VLANs keep the uplink.
  A cleaner long-term fix is a configurable server address in the APK (a contract and release item), which
  would remove the reliance on resolver behaviour entirely.
- **Network shape:** 5,000 clients cannot sit in one broadcast domain. VLAN per zone (300–500 clients
  each), L3 to the server, client isolation on, short DHCP leases. And **70–100 access points** — 5,000
  phones is an AP capacity problem before it is a server problem.
- **One box is one point of failure for the whole event.** What softens it: the offline-first design
  already caches the manifest, scenes, tiles and sealed questionnaires on the phones, so a server outage
  stops the *gates* (START, clue, `qr/lookup`, `quiz/start`), not the whole game. Still required: UPS, a
  spare box able to take the IP, and a restore that has been rehearsed rather than written down.

## 6. Presence at a post: ESP32 + RFID over LoRa

**The problem it solves is not load, it is the human in the critical path.** Today a post opens when a
facilitator radios the admin, who clicks in Outpost Access. At 5,000 players that is ~500 teams × 15 posts
= **7,500 manual unlocks**. It also removes up to 333 req/s of `awaiting_unlock` polling, and it is better
proof of presence than the two mechanisms in place: GPS does not work under a roof (which is why indoor
unlocks are manual), and a paper QR **can be photographed and shared** — an open cheat today.

**Shape agreed:**

- **One tag per team, not per player.** Every unit in this domain is the team — unlocks, scores, sessions —
  and a team plays on the leader's phone. 500 tags, not 5,000: a tenth of the cost and the logistics.
- **A tap writes the same `game_location_unlocks` row Outpost Access writes today.** The rest of the
  system needs no change: the AR outpost opens, the station gate passes, the questionnaire opens. The
  reader becomes another way to produce a fact that already exists.
- Server side: `POST /api/v1/device/tap {device_id, tag_uid, ts, nonce, hmac}` behind a **device-token
  guard** (not a player's Sanctum token) and its own limiter; tables `rfid_tags` (uid → team, active
  window) and `post_arrivals` (audit, and real per-booth queue data). Load: 7,500 taps over two hours ≈
  **1 req/s**.
- **Outpost Access stays** as the manual override — the fallback when a reader dies mid-event.

**Radio: LoRa point-to-point (AS923, 920–923 MHz), 15 nodes → 2 wired gateways at the server rack.**
Cabling to the booths was rejected on cost and aesthetics (15 posts × ~50 m); a router per post was
rejected because each still needs booth power and a backhaul that competes in the same 2.4/5 GHz air as the
players. LoRa leaves the contested band entirely, 50 m indoors is trivial for it, and the only device
needing the network is the gateway — which sits beside the server, so its cable is one metre. Raw LoRa, not
LoRaWAN: a network server, join procedures and key management are overkill for 15 nodes in one building.

Numbers: a tap is 40–60 bytes; airtime at SF7/BW125 ≈ 60–80 ms; the whole venue averages ~1 message/s, so
total airtime stays under ~10%.

**Hardware, chosen by the operator 28 Sep:**

| | Part | Why this one |
|---|---|---|
| Radio | **Ebyte E22-900T22D** (SX1262, UART, 22 dBm, 850–930 MHz) | UART and AT-style configuration instead of an SPI driver and a radio stack — the least RF work for a team without RF experience. SX1262 rather than SX1276/RFM95 for the lower receive current, which is what decides whether a powerbank lasts the day |
| Reader | **PN532** over I²C | ~5–7 cm read range against RC522's 2–4 cm. A team in a hurry taps once instead of three times, and that difference is the whole point of removing the crew |
| Tags | MIFARE Classic 1K, **one per team** (~500) | UID-only is enough: the server maps UID → team, and a tap only opens a post *for that team*, so a cloned card buys nothing worth having |
| Gateway | Same board ×2, **read over USB serial** at the rack | No IP, no WiFi, no TLS, nothing to knock off the air; two of them dedupe by `(node_id, seq)` |

Assembly, bill of materials, provisioning and the bench tests: **`TAP-NODE-BUILD.md`**. The firmware and the
byte-level protocol are commissioned from the client agent (it has PlatformIO) in **`APK-WORK-ORDER.md`
§46**, which is authoritative for the pin map.

Radio settings to start from: channel **73** (923.125 MHz), air rate **9.6 kbps** — range is not the problem
at 50 m, so the faster rate is chosen to shorten airtime and cut collisions between fifteen booths — fixed
transmission to gateway address `0x0001`, and transmit power as low as passes the walk test.

**Protocol requirements** (LoRa does not guarantee delivery):

1. Gateway ACK; the node retries with random jitter so two simultaneous booths do not collide repeatedly.
2. Sequence number + HMAC — a retry must not become two arrivals, and without HMAC anyone with a LoRa
   module could claim "Team 7 reached Post 3".
3. A queue in flash, flushed later **with the original timestamp** — not the time the server received it.
4. Time sync from the gateway at boot and periodically; this race has two official clocks and a 40-second
   drift is a defect, not a detail.
5. Buzzer and LED locally, immediately, without waiting for the ACK. The phone learns on its next
   `/race/status` (already 10 s); MQTT on the LAN is the natural upgrade if instant feedback is wanted, but
   pushing to the phone is an APK change.
6. **Two gateways**, deduplicated server-side by sequence number. One gateway is one device that can
   silence fifteen booths.

**Power and aesthetics:** LoRa plus deep sleep (wake on the reader's interrupt) runs a day on a powerbank,
so a booth has a small box and **no cable at all** — which was the reason cabling was rejected.

### Hardware chosen (operator, 28 Sep)

| Part | Chosen | Why this one |
|---|---|---|
| Radio | **Ebyte E22-900T22D** | SX1262 over UART — no SPI driver to get wrong — 850.125–930.125 MHz covers the 920–923 plan, 22 dBm is more than 50 m needs. SX1262 rather than SX1276/RFM95 for receive current and sensitivity, which is what decides whether a powerbank lasts the day |
| Reader | **PN532** (I²C) | ~5–7 cm read range against RC522's 2–4 cm: a team in a hurry taps once instead of three times |
| Tags | MIFARE Classic 1K, **one per team** | UID-only is enough — the server maps UID → team and a tap only opens a post *for that team*, so cloning buys nothing worth having. 500 cards, not 5,000 |
| Controller | ESP32-WROOM | |
| Gateway | Same radio on an ESP32, **read over USB serial** at the server | No IP, no WiFi, no TLS, nothing to knock off the air. Two of them on two ports, deduplicated server-side by sequence number |

Radio settings: channel **73** (923.125 MHz — still to be confirmed against Kominfo), **9.6 kbps** air rate
(range is not our problem at 50 m; shorter airtime means fewer collisions between fifteen booths), fixed
transmission to gateway address `0x0001`.

Two documents carry the rest: **`TAP-NODE-BUILD.md`** for the bill of materials, wiring, radio configuration,
per-node provisioning and the bench tests; **`APK-WORK-ORDER.md` §46** commissions the firmware from the
client agent — it has PlatformIO already — and holds the authoritative pin map and the 26-byte wire protocol
(sequence numbers, truncated HMAC, ACK, NVS queue, time sync).

**The cheaper alternative, if the WiFi is ours to configure:** put the player SSID on **5 GHz only** and
give the IoT SSID the 2.4 GHz band alone. No new hardware. It depends on every participant phone
supporting 5 GHz (some low-end handsets do not) and on the WiFi operator agreeing to that split.

**Open product decision:** does RFID *replace* the paper QR, or only the unlock?
- Only the unlock (recommended): tap → outpost opens → 3D object gives the clue → paper QR → quiz. The
  hunt, which is the product, survives.
- Replacing the QR: faster and fewer failure points, but the hunt disappears, and at posts whose camera is
  off it becomes very flat.
- Middle path: RFID unlocks everywhere; at camera-off posts the tap also opens the quiz, because there is
  no 3D object to carry a clue.

## 7. Phasing, with acceptance criteria

| Phase | Work | Done when |
|---|---|---|
| 0 | Load-test harness (k6): 500 `/race/status`/s + 5,000-Sync burst + login storm | A run produces p95 and error rate per endpoint, repeatably |
| 1 | Compose stack on the workstation (nginx, app, redis, mysql, worker, scheduler) | The app serves the harness at all, from images built out of git |
| 2 | §3a Redis for cache/session/queue/limiter | Harness: `cache` table writes gone; p95 of `/race/status` down |
| 3 | §3b leaderboard cache, §3c manifest cache + ETag + static assets | 5,000-Sync burst costs one computation per plan |
| 4 | §3d photos out of MySQL, thumbnails in a queue job | `quiz_attempts` holds no base64; submit latency independent of photo size |
| 5 | §3e login-storm limits | 5,000 logins in ten minutes from one NAT address, zero 429s |
| 6 | RFID presence: device endpoint, tags, one booth end to end on LoRa | A tap opens the post, a replayed tap does not double, a dead gateway loses nothing |
| 7 | Rehearsal at the venue: walk test, gateway pulled mid-run, restore from backup | The crew has run the failure, not read about it |

## 8. Open questions that change the design

1. ~~Workstation spec~~ — **answered 28 Sep: 16 cores, 64 GB, 2 TB NVMe.** Allocation in §4.
2. ~~Venue internet uplink~~ — **answered 28 Sep: yes.** Certificates, NTP, tracker sync and offsite
   backup all become straightforward; §5 now carries the split-brain hazard it introduces and the routing
   rule that closes it (player VLAN without internet).
3. **One venue of 5,000, or several venues at once?** Several favours one instance per venue over
   multi-tenancy.
4. **Is HA required?** If a five-minute outage ends the event, the spare box stops being optional.
5. **Do all participant phones support 5 GHz?** Decides whether the spectrum split in §6 is available.
6. **Mains power at each booth?** Decides whether deep sleep on a powerbank is required or merely tidy.
7. **RFID: replace the QR or not?** The product decision in §6.

## 9. Explicitly rejected, and why

- **Kubernetes now** — solves fleet scheduling, which we do not have; adds event-morning failure modes.
- **Cable to each booth** — cost and booth aesthetics (15 × ~50 m).
- **A router per post** — still needs booth power and a backhaul in the players' own spectrum.
- **A tag per player** — the domain is per team; 5,000 tags is ten times the cost and logistics for no gain.
- **LoRaWAN** — a network server and join management for 15 nodes in one hall.

## 10. Unverified assumptions

- opcache was **not** loaded in the CLI binary checked; the web SAPI was not verified. Confirm on the new
  stack, where it is ours to set.
- Kominfo limits for 920–923 MHz (power, duty) were not checked. The chosen E22-900T22D covers
  850.125–930.125 MHz, so the band is a **configuration** choice (channel 73 = 923.125 MHz) rather than a
  purchase mistake waiting to happen — but confirm the allocation and the power limit before the walk test,
  and confirm the channel arithmetic against the datasheet revision that arrives. Order the **900** variant,
  not the 400.
- The AP count (70–100) is an industry rule of thumb at 50–80 active clients per AP, not a measurement of
  this venue.
- Every timing in §2 came from an **empty** database on shared hosting. They are a floor, not a forecast:
  re-measure with realistic data before trusting phase gates.
