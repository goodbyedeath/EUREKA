# Questerra — Android client build guide

Companion to `API-V1-CONTRACT.md` (the endpoint reference). This one is about **how to build the
app**: the order screens happen in, which call feeds each, and the specific places the API will
surprise you.

Every shape below was read off the live server, not written from memory.

- Base URL: `https://questerra-series.com`
- All player endpoints live under `/api/v1/`
- Auth: Sanctum bearer token, `Authorization: Bearer <token>`

---

## 1. What you are building

A live team treasure hunt run at a physical venue. Teams move between **outposts**, scan a QR code
to unlock each one, answer a questionnaire or play a facilitator-scored game, and accumulate
points. A big screen shows the leaderboard. Events run **indoors** (a floor plan, no GPS) or
**outdoors** (real coordinates, geofenced check-ins) — your app must handle both.

The website is now admin-and-kiosk only. **The app is the entire player experience**, so anything
a player does has to go through `/api/v1/`.

---

## 2. Start here: auth and the access window

```
POST /api/v1/auth/login
{ "email": "...", "password": "...", "device_name": "Pixel 8" }

200 { "success": true,
      "token": "1|abc…",
      "expires_at": "2026-09-10T18:00:00+00:00",   // null if the team has no window
      "user": { "id": 22, "name": "Test", "role": "user",
                "team_id": null, "locale": "en" } }
```

Two things about this token that are easy to get wrong:

- **It expires when the access window does.** The server creates it with
  `expiresAt = accessWindowEndsAt()`, and `expires_at` in the response tells you when. After
  that the token is dead, not merely the window — you must log in again, so keep the
  credentials or be ready to ask for them. If `expires_at` is `null` the token does not expire
  on its own.
- **`device_name` is a slot, not a label.** Logging in again with the same `device_name` deletes
  the previous token for that name. Send a stable string per install (not a random value per
  launch) and the old session is cleaned up for you; send a different one each time and you
  accumulate tokens nobody can revoke.

`POST /api/v1/auth/logout` revokes the current token.

Two login refusals have no `error` key, only `message` — handle them by status:
`This account is not active.` for a disabled account, and the access-window message (with
`access_window_expired: true`) for an expired one.

**Then respect the access window.** An admin grants each team a time window. Outside it, *every*
authenticated endpoint answers **403**:

```json
{ "success": false, "expired": true, "access_window_expired": true,
  "error": "access_window_expired",
  "message": "Your access period has ended. …" }
```

This is not an error to retry — it means the session is over. Send the player to a "waiting for
the crew" screen. `GET /api/v1/auth/me` returns `access_window_ends_at` (ISO 8601, or `null` if no
window is set) so you can show a countdown and pre-empt the 403.

Login has one more case to handle: an account whose window has **already** expired is refused at
login, but only **after** the password is correct. Do not treat that message as "wrong password".

---

## 3. The player journey

```
  login
    │
    ├─ GET /branding          ─── app name, logo, theme colour (cache, see §7)
    ├─ GET /features          ─── which menus to show at all
    ├─ GET /auth/me           ─── team, access window
    │
    ├─ INDOOR event ───────────────────────────────────────────────┐
    │    POST /race/start          scan the START code, clock on   │
    │    GET  /race/status         elapsed time, current clue      │
    │    GET  /indoor-map          floor plan + spots              │
    │    POST /race/clue/{map}     answer the clue to advance      │
    │                                                              │
    ├─ OUTDOOR event ──────────────────────────────────────────────┤
    │    GET  /quest-locations     outposts + distance to each     │
    │    POST /quest-locations/checkin   geofenced arrival         │
    │    POST /tracking/position   background position (§6)        │
    │                                                              │
    └─ AT AN OUTPOST (both modes) ─────────────────────────────────┘
         POST /qr/lookup                  { "qr_code": "<scanned string>" }
         GET  /ar/locations/{id}          3D/AR scene, if it has one
         GET  /quiz/start/{id}            begin the questionnaire
         POST /quiz/save-answer           one call per answer
         POST /quiz/complete-game         finish a fun_game question
         POST /quiz/submit                hand it in
```

---

## 4. Quiz runtime

This is the part that must survive the app being backgrounded, killed, or losing signal.

```
GET  /quiz/start/{questionnaireId}   → creates an attempt, returns questions + time limit
GET  /quiz/continue/{attemptId}      → resume after a restart; refuses an expired attempt
GET  /quiz/timer/{attemptId}         → authoritative seconds remaining
POST /quiz/save-answer               → { attempt_id, question_id, answer }
POST /quiz/complete-game             → { attempt_id, question_id }   fun_game only
POST /quiz/submit                    → { attempt_id, verification_photo }
```

**The server owns the clock.** Never count down locally and decide on your own that time is up:
read `/quiz/timer/{attemptId}` and treat its number as truth. A device clock that is wrong, or an
app that was asleep for ten minutes, will otherwise disagree with the scoreboard.

Two rules that are deliberate and not symmetrical:

- `save-answer` **refuses** after the time limit — `403 error: time_expired`. Stop writing.
- `submit` **always** accepts. Refusing it would strand an attempt whose clock ran out mid-request
  and lose the team's work. So on `time_expired`, go straight to `submit`.

`answer` may be `null` (the player cleared a field). That is accepted.

### `submit` requires a verification photo

`verification_photo` is **required** on `POST /quiz/submit` — `required|string`, a base64-encoded
image. Omit it and the submission fails validation with 422; there is no fallback path. Capture it
as part of handing in, not as an optional extra, or a team finishes the questionnaire and cannot
submit it.

The column behind it is `longtext`, and the server's `post_max_size` is generous, so size is not
the constraint the database imposes — but the venue's connection is. Downscale before encoding:
a full-resolution photo becomes roughly a third larger again as base64, and that upload happens at
the worst possible moment, with a team waiting on it. There is no server-side maximum today, so
the client is the only thing deciding how big this gets.

The server also records `photo_captured_at` when the field is present.

### Question types

`App\Enums\QuestionType` — these five, exactly:

| `type` | Render as | Scored |
|---|---|---|
| `text` | free text box | yes |
| `multiple_choice` | options from the `options` array | yes |
| `true_false` | two buttons | yes |
| `fun_game` | instructions + photo upload; a facilitator scores it later | **no, at answer time** |
| `brief` | free text, informational | no |

`fun_game` is the one that catches clients out. Its answer is stored **correct with zero points**,
because a facilitator awards the score afterwards. Call `complete-game` for it, not `save-answer`
alone. And never sum question points locally to show a score — you will double-count every game.
Display what the server returns.

---

## 5. Outposts, check-in and the unlock gate

### Distance is only computed if you ask for it

```
GET /api/v1/quest-locations?user_latitude=-6.41697&user_longitude=106.82418
```

Without those two query parameters, every location comes back with `distance: null` and
`within_radius: false` — so the app cannot tell whether check-in will succeed. **With** them:

```json
{ "id": 30, "name": "Pos Demo", "radius": 20,
  "distance": 1.066192234875042,      // metres
  "within_radius": true,
  "checked_in": false, "check_ins_count": 0, "last_checked_at": null }
```

Also accepts `search=` and `filter_status=visited|not_visited`, and paginates
(`pagination: { current_page, last_page, per_page, total }`).

### Check-in

```
POST /api/v1/quest-locations/checkin
{ "location_id": 30, "user_latitude": -6.41697, "user_longitude": 106.82418 }
```

### The unlock gate

An AR outpost is not readable until the crew opens it for that team:

```
GET /api/v1/ar/locations/39
403 { "success": false, "error": "awaiting_unlock",
      "message": "Waiting for the crew to open this outpost for your team." }
```

This is a normal state, not a failure — show "waiting for the crew" and poll sparingly (§6).

---

## 6. Polling and rate limits — read this before writing any timer

Repeated production outages on this install were HTTP 429. The limits key on the **authenticated
user**, so twenty phones on one venue WiFi get twenty separate budgets — but the hosting edge
still counts every request from that one address, so restraint matters.

| Limiter | Budget | Applies to |
|---|---|---|
| `api` | 120/min per user | everything below except the two named |
| `tracking` | **30/min per user** | `POST /tracking/position` |
| `auth` | 5/min per account, 60/min per IP | `POST /auth/login` |

```
POST /api/v1/tracking/position
{ "latitude": -6.2, "longitude": 106.8, "accuracy": 12.5, "device_info": "Pixel 8 / Android 15" }
```

**Do not post on every GPS callback.** `watchPosition`-style updates arrive about once a second
while walking; at that rate you will exhaust 30/min in two minutes. Keep the marker smooth on
screen locally and send **at most one fix every 10 seconds**. The web client was changed to do
exactly this.

`accuracy` (metres) and `device_info` are optional but please send them — without accuracy a
5-metre fix and a 500-metre fix draw the same dot on the admin map.

Suggested intervals for everything else:

| Call | Interval |
|---|---|
| `/tracking/position` | 10s minimum between sends |
| `/race/status` | 10s while a race screen is open, stop when backgrounded |
| `/quiz/timer/{id}` | 10s, or on resume — not every second |
| `/ar/locations/{id}` while `awaiting_unlock` | 15s |
| `/branding`, `/features` | once per launch (§7) |

A 429 from us carries `Retry-After`. Honour it, and back off rather than retrying in a loop —
a queue replayed into a rate-limited server is what keeps it rate-limited.

---

## 7. Branding and feature flags are server-driven

The operator renames and re-skins the app from the admin panel. Do not hardcode "Questerra" or
the logo.

```
GET /api/v1/branding          (public, no token)
{ "success": true, "branding": {
    "app_name": "Questerra", "tagline": "FEXDI X IFSE 2026",
    "logo_wide": "https://…", "logo_icon": "https://…",
    "theme_color": "#6777ef", "version": "1788938600" }}
```

`version` changes whenever branding changes — cache on it and re-fetch when it differs.

```
GET /api/v1/features
{ "success": true,
  "flags": { "quiz_system": true, "quest_locations": true, "leaderboard": false, … },
  "features": [ { "key", "name", "description", "enabled", "sort_order" } ] }
```

`flags` is the quick map; `features` carries labels and ordering if you want to render a menu from
it. **Flags control visibility only, never permission** — the server enforces access with
middleware regardless, so hiding a menu is a UX decision, not a security one. Most flags are off
in a fresh event; an empty dashboard is expected, not a bug.

`GET /api/v1/hero-slides` (public) returns the launch carousel: `title`, `subtitle`,
`background_image`, `background_gradient`, `text_color`, `button_color`, `button_style`,
`primary_button`, `secondary_button`, `icon_svg`, ordered by `order`.

---

## 8. Indoor mode

```
GET /api/v1/indoor-map            → the active plan
GET /api/v1/indoor-map/{id}       → a specific one
```

```json
{ "success": true,
  "map":   { "id": 3, "name": "…", "description": "…", "image": "https://…" },
  "spots": [ { "id": 26, "name": "test", "x": 88.54, "y": 12.689,
               "shape": "pin", "color": "#705757", "size": 28,
               "content": "", "image": "https://…",
               "game_location_id": null, "is_open": false } ] }
```

Two things to get right:

- **`spots` is top level, not inside `map`.** Easy to mis-nest.
- **`x` and `y` are percentages, not pixels** (0–100). Multiply by your rendered image size, so the
  plan can be displayed at any width.

`is_open` says whether the crew has opened that spot for this team. `game_location_id` links a
spot to an AR outpost when it has one.

---

## 9. AR and the 3D camera

### Two cameras, never both alive

| | Stack |
|---|---|
| QR scanner | CameraX + ML Kit, flat 2D preview, no GL, no ARCore session |
| AR viewer | ARCore session + SceneView, its own Activity, launched by location id |

ARCore takes **exclusive** control of the camera. Opening the AR Activity while the scanner
preview is still bound throws; the reverse leaves a black frame. Release one fully before
starting the other — they cannot share a surface or a session. (Sceneform is archived and will
not build against current Gradle.)

### The reticle is the interaction model, not decoration

The web build draws a circle at the dead centre of the screen. **Without it a player cannot tell
what is interactive or where to aim, and objects read as scenery.** These values are taken from
`resources/views/ar/view.blade.php` on the server:

| State | Appearance |
|---|---|
| idle | 44 × 44 circle, exact screen centre, 2px border `#ffffff88`, no fill, **not touchable** |
| hot | border `#4ade80`, 6px glow at `#4ade8033`, scaled to 1.15× |

Every rendered frame: cast a ray from the camera through normalised screen centre `(0,0)`, test
against the placed objects, walk up to the object's root, and set hot/idle from whether anything
was hit.

**Selection is aim-then-tap, not tap-the-object.** A tap anywhere opens whatever the reticle is
currently on; when the reticle is idle the tap does nothing. Do not hit-test from the touch
point — a player holding a phone at arm's length cannot reliably poke a small object, which is
the whole reason the reticle exists.

### What opens on tap

A bottom sheet built from the object's own fields:

- `title` as the heading, `description` as the body
- `points` shown as `+N points`; hide the row entirely when 0 or absent
- **one** extra, never two: switch on `media_type` — `"image"` renders `image`, `"link"` renders
  `link` as a button, anything else shows no extra

### The screen, element by element

Build the same screen we do. Every value below is read from
`resources/views/ar/view.blade.php`, and each element exists because something failed without it.

**Top bar** — fixed to the top, above the camera, `z-36`. Background is a gradient from `#000c` to
transparent so white text stays readable against any scene, with top padding for the status bar /
notch inset.

| | |
|---|---|
| `← Back` | leaves the outpost. Always reachable — a player who cannot find an object must be able to get out. |
| outpost name | an `h1`. The player needs to know which outpost they are standing at. |
| found counter | pill, `0/N`, 13 sp bold, green `#4ade80` on `#0008`, fully rounded. |

The counter is `found / total`. It increments the **first** time an object is opened and never
again — mark the object, do not count taps. When `found == total`, the hint line below changes to
**"All objects found!"**; that is the only completion signal the screen gives.

**Centre** — the reticle, specified above.

**Bottom, stacked upward from the safe-area inset:**

| Element | Offset | Purpose |
|---|---|---|
| hint | `inset + 24`, full width, centred 13 sp | "Look around to find the objects" → "All objects found!" |
| direction guide | `inset + 52`, centred pill `#0f172ad9`, 13 sp bold, **not touchable** | which way to turn |
| Re-centre | `inset + 70`, left 16 | re-zero forward |

**The direction guide is the element most likely to be left out, and the one players need most.**
Objects are authored at any bearing, so a player who opens the camera facing the wrong way sees an
empty room and concludes the outpost is broken. It shows:

```
▶ 47°        nearest object is 47° to the right
◀ 47°        … to the left
↺ Turn around    when the nearest is more than 150° away
```

Hidden entirely when **any** object is within `0.42 rad` (~24°) of where the phone is pointing —
that is "roughly on screen", so the guide disappears exactly when it stops being needed. Our build
recomputes it every tenth frame, which is far more often than a person can turn; once every few
frames is plenty and keeps it off the hot path.

**Re-centre** exists because a gyro heading drifts: objects creep sideways over several minutes. The
button re-aims the frame the same way the Start gate does — point at the QR again, tap, forward is
re-zeroed. **Keep it even though ARCore drifts far less**, because it is also the recovery when a
player walks off and comes back, or when tracking is lost and regained.

**Pre-entry gate** — full screen, `z-50`, solid `#0f172a`, centred:

> **Point at the QR code**
> Stand where the QR code is and point your phone straight at it, then tap **Start**. Objects are
> placed relative to this direction.
>
> `[ Start ]`

Keep that wording, or wording that says the same thing. A player who taps Start while facing
anywhere else has silently misaligned the whole scene, and nothing afterwards will look wrong
enough to tell them.

**Object sheet** — slides up from the bottom on tap: title as a heading, description, the points
row, then at most one extra (image **or** link; a link opens externally), and a Close button. The
scene stays live behind it; closing returns to the same pose rather than restarting.

### The frame origin: keep the Start gate

**Answering the v17 question directly: yes, the gate stays. `position` is not absolute.**

`§9` previously said "place it as given" without saying what *given* is relative to. That was an
omission on this side, and it is the reason your build and your operator disagree.

Checked in `resources/views/ar/view.blade.php`:

```
line 853   referenceYaw = null;            // this direction becomes "forward"
line 596   euler.set(beta, alpha - referenceYaw, -gamma, 'YXZ')
line 1346  const yaw = Math.atan2(aimDir.x, -aimDir.z)
```

`referenceYaw` is captured the instant the admin taps **Start while pointing at the QR**. Every
camera angle after that is measured as `alpha − referenceYaw`, and the `yaw` written when an object
is placed comes from the camera inside that same relative frame.

**So the stored `yaw` means "this many radians from the QR direction". Nothing anywhere records an
absolute compass heading.** Open the camera and adopt whatever pose the player happens to hold, and
forward becomes arbitrary: every object at that outpost rotates by the difference between where the
player was pointing and where the admin was. Your own words for it are right — plausible rather
than broken, which is the worst kind of wrong to ship.

ARCore's ability to anchor immediately does not help, because the missing information is not
tracking quality. It is *which way the admin was facing*, and that was never stored.

**Keep the gate, and keep it for this reason rather than the old one.** The web needs it because
`deviceorientation` has no absolute forward; you need it because the authored layout is expressed
relative to the QR. Same gate, different justification — so do not remove it when you later
improve tracking.

Once Start is tapped, treat the camera pose at that instant as the origin and place objects
camera-local from it, exactly as the response describes. ARCore should then hold that frame far
better than the web's complementary filter does, which is the real win available to you.

> If an absolute heading were stored at authoring time, the gate could go. It is not, and adding it
> would mean re-walking every outpost to re-author what exists. Worth doing before a large event,
> not worth doing mid-build. Say the word and it goes on the list.

### Do not require a plane hit-test

**Drop it — your instinct is right, and the guide should have said so.**

Objects are positioned at a bearing, pitch and distance **from the camera**. They are not on the
ground and never were: `y = distance · sin(pitch)` puts them wherever the admin aimed, commonly
above eye level. A plane requirement therefore gates entry on something the scene does not use, and
on dark or featureless ground it blocks the outpost entirely — a team standing in the right place,
unable to start, for a reason nobody at the venue can diagnose.

Anchor to the camera pose at Start and place from there. No plane detection, no hit-test.

### Response shape

```
GET /api/v1/ar/locations/{id}[?lat=&lng=]

location { id, name, model, latitude, longitude, radius,
           coordinate_source, quest_location_id, access_mode }

objects[] { id, title, description, points, media_type, link, image,
            model, model_id, scale, distance,
            position { x, y, z }, rotation { x, y, z },
            animations [ { type, speed, range } ] }
```

`access_mode` is `"geofence"` (the radius decides entry) or `"manual"` (a crew member does — show
a waiting state rather than a distance). `latitude`/`longitude` null means the outpost was never
bound to a place: render with no geofence at all.

**`position` is already resolved to metres** in a right-handed camera-local frame (−z forward),
computed server-side from the bearing, pitch and distance an admin authored while standing at the
outpost. Place it as given. Never recompute anchors from lat/long and never re-derive the bearing
from the compass — the server's maths is the authority and the two will not agree.

`scale` multiplies the model uniformly, `rotation` is in degrees, and `animations` are looped idle
motions applied locally rather than synced. `model` is a `.glb` URL: pre-download every one from
`/offline/manifest` before the event, or AR stalls in the field.

A 200 with an empty `objects` array means the outpost has a model but nothing placed on it yet.

---

## 10. Offline

```
GET /api/v1/offline/manifest
{ "bounds": { "north", "south", "east", "west" },
  "quest_locations": [ { id, name, latitude, longitude, radius, marker_color } ],
  "game_locations":  [ { id, name, experience_type, model, uses_ar,
                         latitude, longitude, radius, coordinate_source, quest_location_id } ],
  "images": [...], "models": [...], "pages": [...],
  "counts": { … } }
```

Fetch this once the team is registered and **pre-download everything on it while you still have
signal** — venue WiFi and mobile data are both unreliable mid-game. `bounds` is the area worth
pre-caching map tiles for. `models` are the 3D assets; AR will stall without them.

Queue player actions taken offline (`save-answer`, `checkin`) and replay them when signal returns,
**with backoff** — and drop a queued item on a `4xx` rather than retrying it forever, because a
rejected answer will be rejected again.

---

## 11. Traps — things that have already bitten someone

| Trap | Detail |
|---|---|
| **Two names for a coordinate** | `checkin` wants `user_latitude` / `user_longitude`. `tracking/position` wants `latitude` / `longitude`. Same concept, different keys. |
| **Lat/long type is inconsistent** | `/quest-locations` returns them as **strings** (`"-6.41697690"`); `/offline/manifest` returns them as **numbers** (`-6.4169769`). Parse defensively. |
| `spots` nesting | Top level in `/indoor-map`, not under `map`. |
| `distance` is null | Unless you pass `user_latitude` + `user_longitude` as query params (§5). |
| `race: null` | `/race/status` returns `race: null` before the START code is scanned — not an error. |
| `fun_game` scores 0 | At answer time. Never total points client-side. |
| `awaiting_unlock` | A normal waiting state on AR outposts, not a failure. |
| Nulls are normal | `team_id`, `access_window_ends_at`, `image_path`, `google_map_embed_url` are all legitimately `null`. |
| Don't trust the device clock | Read `/quiz/timer/{attemptId}`. |

### Error handling

Every refusal carries a stable `error` string — switch on that, never on `message`, which is
human-facing prose and is translated.

| `error` | HTTP | Meaning |
|---|---|---|
| `access_window_expired` | 403 | Session over; stop, show the waiting screen |
| `awaiting_unlock` | 403 | Crew has not opened this outpost yet |
| `time_expired` | 403 | Clock ran out; stop saving, call `submit` |
| `attempt_not_found` | 404 | No such attempt, or it is not yours |
| `question_not_found` | 404 | Question is not in this questionnaire |
| `attempt_submitted` | 409 | Already handed in |
| `game_already_assessed` | 409 | A facilitator already scored it |
| `not_a_game_question` | 422 | `complete-game` on a normal question |

A `422` with a Laravel `errors` object is ordinary validation — show it against the field.
A `500` is a real fault: report it, do not retry in a loop.

---

## 12. Before you ship — checklist

- [ ] Token survives an app restart; `401` sends the player back to login
- [ ] `expires_at` is respected — the token dies with the access window, so re-login is a
      normal path, not an error state
- [ ] `device_name` is stable per install
- [ ] `access_window_expired` handled on **every** call, not just login
- [ ] `/tracking/position` sends at most one fix per 10s, even while walking
- [ ] `429` honours `Retry-After` and backs off; no retry loops
- [ ] Quiz resumes correctly after the app is killed mid-attempt (`/quiz/continue`)
- [ ] `time_expired` on `save-answer` goes straight to `submit`
- [ ] `submit` always carries `verification_photo`, downscaled before encoding
- [ ] `fun_game` uses `complete-game`; no client-side point totals anywhere
- [ ] App name, logo and theme come from `/branding`, with nothing hardcoded
- [ ] Hidden menus follow `/features`, and you never rely on that for security
- [ ] Indoor spots positioned from `x`/`y` as percentages
- [ ] Offline manifest pre-downloaded while online; queued actions replay with backoff
- [ ] Both an indoor event and an outdoor event tested end to end
