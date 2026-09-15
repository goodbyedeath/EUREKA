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

## 2a. The team — set up once, then show its score

### Team setup happens exactly once — operator rule, 14 Sep

*"Team member setup in the APK happens only once, at the first login. After that it cannot be done
again."* The server enforces it:

- After login, `GET /team`. **`404 no_team`** → the **Team setup** screen, before anything else. It
  cannot be skipped or dismissed: no back, no dashboard, no scanner. The same 404 carries
  `participant_directory: true|false` — whether the "already registered" option is available.
- **Two ways to add each member** (operator, 15 Sep — both stay):
  - **Sudah terdaftar** (only when `participant_directory` is true): a search box →
    `GET /participants?q=<name or e-mail, ≥ 2 chars>` →
    `{results: [{id, name, email (masked), avatar, available, team_name}]}`. Show up to 20; a row with
    `available: false` is already in `team_name` — show it greyed with that name, not selectable.
    Picking one adds `{ "participant_id": <id> }`; the server fills in the name and e-mail.
  - **Manual**: type `name` + `email` (`phone`, `position` optional), exactly as before.
  Mix both freely in one team. Each member has a **Ketua** toggle; at most one leader. If none is
  chosen, the server makes the logged-in account's e-mail, or the first member, the leader.
- One call does everything: `POST /team` with `name` (3–100) and `members` (1–20). Each member is
  either `{participant_id, is_leader?}` or `{name, email, phone?, position?, is_leader?}`; e-mails unique.
- Before sending, a confirm dialog: *"Nama tim dan anggota tidak bisa diubah lagi setelah ini."*
- `201` → dashboard. **Never show add, edit or remove controls again.** The member list is
  read-only everywhere in the app.
- `POST /team/members` and `DELETE /team/members/{id}` now **always** return `403 team_locked`.
  Remove every call to them.
- `409 team_exists` on `POST /team` means setup already happened (another device, a retry after a
  lost response) — go to the dashboard, not an error.
- A mistake in the names is fixed by the event admin on the website, not in the app.

Setup refusals:

| `error` | HTTP | Show |
|---|---|---|
| `participant_taken` | 409 | "{name} sudah terdaftar di tim {team_name}" (both in the body); remove that member |
| `directory_disabled` | 409 | The list was switched off meanwhile — switch those members to Manual |
| `participant_not_found` | 404 | Remove that member (`participant_id` in the body) and search again |
| `duplicate_participants` | 422 | The same person was added twice |
| `one_leader_only` | 422 | More than one Ketua |
| `duplicate_emails` | 422 | Two members share an e-mail |

### The team score card on the dashboard

The team has a right to see what it has achieved. `GET /team` → `team.score`:

```json
"score": { "total": 1080, "base_points": 1000, "earned_points": 100,
           "assessment_points": -20, "attempts_completed": 1 }
```

Show **`total`** large, and under it the breakdown:

| Line | Field | Note |
|---|---|---|
| Poin awal | `base_points` | the starting balance every team gets |
| Poin kuis | `earned_points` | correct answers |
| Poin game | `assessment_points` | facilitator scores minus penalties — **can be negative**, show the sign |

**No rank, no other teams** — operator decision. `rank` and `total_teams` are gone from the response.

The formula, so the labels are right — **never compute it in the app**, always show what the server
returns:

```
total = base_points
      + Σ points_earned of correct answers   in SUBMITTED questionnaires of this team
      + Σ (additional − penalty) of scored games in SUBMITTED questionnaires of this team
```

It is the same function (`PointsCalculationService::teamScore`) behind `/admin/user-progress` and the
kiosk leaderboard, so the team sees exactly the number the crew and the big screen see.

Two consequences worth designing for:

- A game counts **after the questionnaire is handed in** (the Finish screen), not at the moment the
  facilitator saves the score. On the scoring screen show `team_gain` as immediate feedback; the
  card moves after submit.
- `points` (= `score.total`) and `earned` (= total − base) are still in the response for older builds.
  Prefer `score`. Ignore `team_points` on the scoring response — it is a raw ledger value.

Refresh the card when the dashboard opens or resumes, and after Results. **Do not poll it** (§6).

---
## 2b. The game has ended — operator 14 Sep

When an event is over the admin **archives** it: the final leaderboard, teams, per-post results and
photos are saved on the server, and every account that played is removed from the live system. From
that moment the server answers that APK — on **any** endpoint, **login included** — with:

```json
403 { "success": false, "error": "game_ended", "game_ended": true,
      "message": "Game sudah berakhir. Terima kasih telah bermain — silakan uninstall aplikasi ini.",
      "archive": { "name": "Questerra Batch 3", "ended_at": "2026-09-20T17:04:11+07:00" } }
```

The operator's reason is security: an APK left installed after an event must not keep talking to
the running system or the venue network. So the app's job is to go **silent**.

On the **first** `game_ended` from anywhere — a screen, login, the tracking worker, the offline-queue
replay:

1. Show the **Game ended** screen, full-screen over everything: the server's `message`,
   `archive.name`, and one button, **Uninstall**. No back, no retry, no login form, no dashboard.
2. **Wipe** local state: token, stored attempt and assessment ids, the offline write queue (discard —
   never replay it), cached offline manifest, map tiles, AR models, guidance, branding, and any photo
   still on disk.
3. **Stop** everything that makes requests: location tracking and its foreground service, WorkManager
   jobs, alarms, polling timers.
4. Persist one flag, `game_ended` (with the message and name). On every later launch check it
   **before any network call** and show the Game ended screen directly — from then on the app makes
   **zero** requests.
5. **Uninstall** →
   `startActivity(Intent(Intent.ACTION_DELETE, Uri.parse("package:$packageName")))`. Android shows its
   own confirmation; no app can uninstall itself silently. Declare
   `<uses-permission android:name="android.permission.REQUEST_DELETE_PACKAGES"/>` (API 28+).

- The flag is cleared only by reinstalling. There is no way to dismiss the screen.
- A plain `401` still just means "log in again". Only `error: "game_ended"` ends the app.
- The next event uses new accounts; a fresh install logging in with a live account works normally.

---
## 2c. Emergency race stop — operator 14 Sep

The admin can stop the race for **every team at once**. It puts the session back to before the race:
race clocks, points, visited posts (scans, check-ins, opened indoor posts), and every answered
question and game score are wiped. **Teams, members, accounts and tokens stay** — the player is still
logged in, and the team starts again by scanning **START**.

Any call that names an attempt or assessment wiped by the stop answers:

```json
409 { "success": false, "error": "race_reset", "race_reset": true,
      "message": "Race dihentikan oleh panitia. Semua poin dan progres direset — scan QR START untuk memulai lagi.",
      "reset_at": "2026-09-20T10:14:03+07:00" }
```

It comes from `quiz/continue`, `quiz/timer`, `quiz/save-answer`, `quiz/complete-game`, `quiz/submit`,
`quiz/assessments/{id}` and `…/verify-pin` — exactly the calls an app mid-session is making, or makes
when it reopens into a stored session (§4, locked room).

On `race_reset`:

1. Show the server's `message` once, as a dialog the player dismisses. It is not an error.
2. **Drop local session state:** the stored attempt id, the pending assessment, PIN and photo held in
   memory, cached answers, the running race clock, and any offline-queue entries for the old attempt
   (discard — replaying them would only earn more `race_reset`).
3. **Leave the locked room.** This is the one case where the app exits a question session without
   finishing it — the server ended it.
4. Go to the dashboard and refresh `GET /team` (score back to base) and `GET /race/status`
   (`race: null` → show "Scan START").
5. **Do not log out.** The token is still valid.

A team sitting on the dashboard will not get `race_reset` (it names no attempt). `GET /race/status`
returning `race: null` where the app had a running clock means the same thing — clear the clock and
show "Scan START".

---
## 2d. Login cards — scan instead of typing (your #16, operator-approved 15 Sep)

The admin generates team accounts in bulk and prints one card each. The card's QR carries
`EUREKA-LOGIN:<code>`; e-mail and password are printed underneath as a fallback.

```
POST /api/v1/auth/login-code        (public, throttle: 60/min per address)
{ "code": "EUREKA-LOGIN:Qm9…", "device_name": "pixel-8-a1b2" }
```

- Send the scanned string as-is; the bare code is accepted too.
- The 200 body is **exactly** `POST /auth/login`'s: `{success, token, expires_at, user}`. The access window
  starts on first use, one token per `device_name`.
- `401 invalid_login_code` — unknown, replaced ("Ganti kartu") or revoked card. The server does not say
  which. Show the message; offer e-mail + password.
- `403 access_window_expired`, `403 account_inactive`, `403 game_ended` — as for login.
- Reusable: a team whose phone is replaced logs in again with the same card.

Login screen: a **Scan kartu login** button using the scanner you already ship. Accept only strings starting
with `EUREKA-LOGIN:`; anything else → "Ini bukan kartu login". After a 200, go straight to team setup
(§2a). `qr/lookup` never accepts a login card (`404 unknown_code`), so never send one there.

`POST /auth/login` refusals gained `error` keys too: `account_inactive`, `access_window_expired`.

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
         POST /quiz/complete-game         finish a fun_game question → assessment_id
         GET  /quiz/assessments/{id}      facilitator scoring screen (team's phone)
         POST /quiz/assessments/{id}/verify-pin   facilitator PIN pre-check
         POST /quiz/assessments/{id}      PIN + silent photo + score; next = continue | submit
         POST /quiz/submit                hand it in
```

---

## 4. Quiz runtime

### From scan to the questions screen — the core loop of every outpost

Reported by the operator, 14 Sep: **after scanning a questionnaire QR, the questions do not
appear.** The server was re-tested the same day against all 8 active codes: every lookup is 200 and
every `/quiz/start` returns its questions. So the break is between the scan and the screen. This is
the flow, exactly:

```
 QR decoded
   │
   ▼
 POST /qr/lookup { qr_code }
   ├─ 200 type = "race_start"     → race flow (§8), not a quiz
   ├─ 200 type = "questionnaire"  → take questionnaire.id  ─────────────┐
   ├─ 404 unknown_code            → "This code is not part of the event" │
   ├─ 403 station_not_linked      → "Pos belum disiapkan — hubungi panitia"│
   ├─ 403 race_not_started        → "Scan QR START dulu" → open the scanner│
   ├─ 403 checkin_required        → post {type, id, name}: outdoor → open  │
   │                                 the map at that post; indoor → "wait  │
   │                                 for the crew"                          │
   ├─ 403 not_available           → reason "inactive": "Station is off — │
   │                                 ask the crew"                       │
   ├─ 409 session_in_progress     → another session is live: open its     │
   │                                 attempt_id instead                    │
   └─ 403 max_attempts_reached    → "No attempts left at this station"   │
                                                                         ▼
 Navigate to the QUESTIONS screen immediately (no extra tap), which on open calls
 GET /quiz/start/{questionnaire.id}
   ├─ 200 → render `questions` (below). Persist attempt.id for resume.
   ├─ 409 session_in_progress  → open that attempt_id instead
   ├─ 403 scan_required        → the scan was not recorded; go back to the scanner
   ├─ 403 not_available        → reason inactive | not_open_yet | window_closed (+ opens_at/closes_at)
   ├─ 403 max_attempts_reached → no attempts left
   ├─ 404 questionnaire_not_found
   └─ 429 too_many_starts      → wait retry_after seconds; never loop
```

`/quiz/start` is **idempotent**: calling it again returns the same live attempt with its saved
answers, so it is safe on screen re-entry. The lookup is what records the scan, and `/quiz/start`
refuses (`scan_required`) without it — never call start from a local decode alone.

**The station gate — operator, 15 Sep.** A questionnaire opens only after (1) the team scanned **START**
and (2) checked in at the **post** the admin linked it to: outdoor = GPS check-in at that Quest
Location; indoor = the crew opened that Game Location for the team. An unlinked questionnaire cannot
be scanned. `qr/lookup` refuses before recording the scan (no attempt burned), and `quiz/start`
refuses a **new** attempt the same way; resuming an attempt already open is never blocked.
All three refusals are `403` with the `message` to show:

```json
{ "success": false, "error": "checkin_required",
  "message": "Check-in di pos Pos Demo terlebih dahulu.",
  "post": { "type": "outdoor", "id": 30, "name": "Pos Demo" } }
```

`race_not_started` and `station_not_linked` carry no `post`. After an emergency race stop every team is
back at `race_not_started`.

**The 200 body, verified live (Pos Merah):**

```json
{ "success": true,
  "attempt": { "id": 504, "started_at": "2026-09-14T07:14:29.000000Z", "status": "started" },
  "questionnaire": { "id": 13, "title": "Field Bomb (Pos Merah)", "description": "…", "time_limit": 30 },
  "questions": [
    { "id": 18, "type": "fun_game", "question": "Field Bomb (Pos Merah)",
      "game_name": "Field Bomb (Pos Merah)", "description": "Tantangan tim di pos ini …",
      "options": null, "points": 100, "order": 1,
      "images": ["games/question-images/yLWQ….jpg"],
      "image_urls": ["https://questerra-series.com/storage/games/question-images/yLWQ….jpg"] }
  ],
  "answers": {},
  "totalPoints": 0,
  "timeRemaining": 1799 }
```

- `answers` is always an **object** keyed by question id (as a string) → the saved answer.
- `totalPoints` and `timeRemaining` are **camelCase** here, unlike the rest of the API.
  `timeRemaining` is seconds; `null` means untimed.
- `images` are disk-relative paths. **Show `image_urls`** — they are absolute and load directly.

**What every station looks like today:** all 8 active questionnaires are **one `fun_game` question**
with one image. A questions screen that renders only answerable types (`text`, `multiple_choice`,
`true_false`) shows **nothing** at every station — the exact symptom reported. Render every type:

| `type` | Show | Input → call |
|---|---|---|
| `fun_game` | `game_name` as heading, `description` (multi-line, keep line breaks), `image_urls` | a **Complete** button → `POST /quiz/complete-game` → facilitator flow (below) |
| `multiple_choice` | `question`, `description`, `image_urls`, one choice per `options[]` string | send the option **text**, not its index → `save-answer` |
| `true_false` | `question`, `description`, `image_urls` | two buttons, send `"true"` / `"false"` → `save-answer` |
| `text` | `question`, `description`, `image_urls` | text field → `save-answer` (debounced, and on leaving the field) |
| `brief` | `question`, `description` | optional feedback text → `save-answer`; never blocks submit |

Order by `order`. If a 200 ever arrives with an empty `questions` list, do not show a blank screen:
send `POST /contract/feedback` (`kind: "bug"`) with the questionnaire id.

**Debugging this on a device:** if a scan still does not reach the questions, report both calls —
lookup status + body and start status + body — as `kind: "bug"`. Those two bodies decide it at once.

### The question session is a locked room — operator rule, 14 Sep

Once `/quiz/start` returns 200 the team is **inside a session**. There are exactly two ways out:

1. **Every question finished** — `text` / `multiple_choice` / `true_false` answered, and every
   `fun_game` completed **and scored by the facilitator**. `brief` never blocks.
2. **The clock runs out.** Whatever is unfinished scores 0.

Why: a team that could leave early went back to the dashboard, scanned again and got a fresh timer.
The server enforces it, so the app is never the weak point:

- `POST /quiz/submit` → **`409 questions_incomplete`** with `pending`, while anything is unfinished
  and the clock still runs. After the clock runs out, submit is always accepted.
- `POST /qr/lookup` or `GET /quiz/start` for **another** station → **`409 session_in_progress`**
  with `attempt_id` and `questionnaire`. The scan is not recorded, so it burns no attempt.
  Rescanning the **same** station returns the same attempt; the timer does not reset.
- After the clock runs out, `complete-game`, `verify-pin` and the scoring POST → `403 time_expired`.
  A game the facilitator had not scored by then stays at 0.

`completion` — on `/quiz/start`, `/quiz/continue` and the scoring POST — says where the team stands:

```json
"completion": { "can_submit": false, "time_expired": false,
  "pending": [ { "question_id": 18, "type": "fun_game", "reason": "awaiting_assessment" } ] }
```

`reason` is `unanswered` | `game_not_completed` | `awaiting_assessment`. After each `save-answer`,
recompute it locally with the same rules; the server's answer on submit is final. A `save-answer`
that comes back 422 changed nothing — the previously saved answer still stands and still counts.

**In the app:**

- While `can_submit` is false: **no Submit button, no back arrow, no bottom navigation, no route to
  the dashboard or scanner.** Consume Android back (`BackHandler`) and show
  "Selesaikan semua pertanyaan untuk keluar".
- When `can_submit` becomes true, go straight to the **Finish** screen: team verification photo →
  `POST /quiz/submit` → Results. It has no cancel. It is the only exit.
- A `fun_game` after **Complete** stays on "Menunggu penilaian fasilitator" → hand-over → PIN →
  scoring. There is no way back to the list and no way out until the score is saved.
- When `timeRemaining` reaches 0, or any call answers `time_expired`: close every input, show
  "Waktu habis", then the same Finish screen. Submit is accepted however much is pending.
- The Home button and recents cannot be blocked — do not try (no lock-task, no overlays). Instead
  **the app always reopens into the session**: persist `attempt_id`; on launch and on every resume,
  if one is stored (or `GET /quiz/attempts?limit=1` shows `status: "started"`), open
  `GET /quiz/continue/{id}` before anything else. `continue` answering `403 time_expired` → Finish
  screen; `attempt_submitted` → clear it and go on.
- If the server answers `session_in_progress` anywhere, open that `attempt_id` — never show it as
  an error.
### Runtime

This is the part that must survive the app being backgrounded, killed, or losing signal.

```
GET  /quiz/start/{questionnaireId}   → creates an attempt, returns questions + time limit
GET  /quiz/continue/{attemptId}      → resume after a restart; refuses an expired attempt
GET  /quiz/timer/{attemptId}         → authoritative seconds remaining
POST /quiz/save-answer               → { attempt_id, question_id, answer }
POST /quiz/complete-game             → { attempt_id, question_id }   fun_game only
GET  /quiz/assessments/{id}          → the facilitator screen's data
POST /quiz/assessments/{id}/verify-pin → { facilitator_pin }
POST /quiz/assessments/{id}          → { facilitator_pin, facilitator_photo, additional_points, penalty, notes }
POST /quiz/submit                    → { attempt_id, verification_photo }
```

**The server owns the clock.** Never count down locally and decide on your own that time is up:
read `/quiz/timer/{attemptId}` and treat its number as truth. A device clock that is wrong, or an
app that was asleep for ten minutes, will otherwise disagree with the scoreboard.

Two rules that are deliberate and not symmetrical:

- `save-answer` **refuses** after the time limit — `403 error: time_expired`. Stop writing.
- `submit` is **refused** while questions are unfinished and the clock still runs
  (`409 questions_incomplete`), and **always accepted** once the clock has run out. So on
  `time_expired`, go straight to the Finish screen.

Clearing an answer is **refused**, not accepted: `save-answer` with `answer: null` or `""` on a
`multiple_choice` question returns `422 "Invalid option selected"` and the previous answer stays saved
(verified 14 Sep). Do not offer "deselect" as a way to undo; let the player pick a different option.

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
| `fun_game` | `game_name`, `description`, `image_urls`, a Complete button; a facilitator scores it on the team's phone | **no, at answer time** |
| `brief` | optional feedback text | no |

`fun_game` is the one that catches clients out. Its answer is stored **correct with zero points**,
because a facilitator awards the score afterwards. Call `complete-game` for it, not `save-answer`
alone. And never sum question points locally to show a score — you will double-count every game.
Display what the server returns.

### The facilitator scores on the team's phone

Operator decision: there is no separate facilitator device. After the team plays, the facilitator
takes the team's phone and enters the score. So the flow for a `fun_game` question is:

1. Show the game (`game_name`, `description`, images). The team plays it off-screen.
2. **Complete** → `POST /quiz/complete-game` → keep `assessment_id`. Ignore `redirect`.
3. Open a **Facilitator scoring** screen: `GET /quiz/assessments/{id}`. Make the hand-over
   obvious — a full-screen "Hand this phone to the facilitator" step before anything else.
4. **PIN gate.** Numeric keypad, masked, 4–8 digits → `POST /quiz/assessments/{id}/verify-pin`.
   - `invalid_facilitator_pin` (403) → "Wrong PIN — `attempts_left` tries left".
   - `facilitator_pin_locked` (429) → disable the keypad and count down `retry_after` seconds.
   - `facilitator_pin_not_set` (409) → "The admin has not set a facilitator PIN". Nothing to retry.
   - If `GET` already says `facilitator_pin_set: false`, show that instead of the keypad.
   The score POST sends the PIN again. Keep it in memory while online; if the POST has to be queued
   (below), it may be stored only inside the sealed queue row — never in plain storage or logs.
5. When the PIN is accepted, **arm the silent camera** (below).
6. Inputs: **Additional points** (number, 0…`max_additional_points`, default
   `max_additional_points`), **Penalty** (number, default 0, max `max_penalty`), **Notes**
   (optional). Show the live result `additional − penalty` as "Team gains N" (may be negative).
   Never show or add the team's starting balance — it is not part of the gain.
7. Confirm dialog (it is one shot). On **Confirm**, capture the photo, then
   `POST /quiz/assessments/{id}` with `facilitator_pin`, `facilitator_photo`, `additional_points`,
   `penalty`, `notes`.
8. Show `team_gain` (not `team_points`), then follow `next`: `continue` → back to the quiz at the
   next question; `submit` → "Serahkan HP kembali ke tim" → the Finish screen (team photo →
   `POST /quiz/submit`). Never submit from the scoring screen: the facilitator is holding the phone,
   and the verification photo must show the team.

#### Silent facilitator photo

An audit record of who scored each game: the admin sees it next to the score. It is **required** —
a POST without it is 422, so there is no score without a photo.

- **Front camera**, CameraX `ImageCapture` bound **without a `Preview`** use case. Bind it when the
  PIN is accepted so it is warm by the time the facilitator confirms.
- **Capture on the Confirm tap** — the facilitator is looking at the screen. `takePicture` into
  memory; no preview, no thumbnail, no flash, no shutter sound, no toast.
- Apply EXIF rotation, downscale the longest side to ≤ 1024 px, JPEG quality ~80, base64 without
  line breaks (`data:image/jpeg;base64,` prefix accepted). Server limit: JPEG or PNG, ≤ 3 MB decoded.
- **Unbind right after capture and whenever the screen closes.** The one-camera-at-a-time rule and
  the CameraX Activity-lifecycle trap from §9 apply here exactly as for AR and the QR scanner.
- Do not try to hide Android's camera-in-use privacy indicator. It cannot be hidden and must not be.
- CAMERA permission is already needed for AR and QR. If it is missing, request it at the PIN step.
- If capture fails (no front camera, error, permission denied): retry once, then fall back to a
  **visible** front-camera shot the facilitator takes. Never send the score without a photo.
- **Weak signal — the score may be queued** (operator, 15 Sep). Rules, all server-enforced where they
  can be:
  - `verify-pin` must succeed **online** first. That is when the server records `pin_verified_at`.
  - Only the scoring POST is queued, only after a transport failure. Seal the whole body (PIN, photo,
    score) with a non-exportable Android Keystore key before writing it; delete it on delivery, on
    any 4xx, and on `game_ended` / `race_reset`.
  - **Late delivery:** if the POST arrives after the clock ran out, the server accepts it when the PIN
    was verified **before** the deadline and the POST lands within **15 minutes** after it; the reply
    carries `accepted_late: true`. Otherwise `403 time_expired` and the game stays 0. There is no
    device-time field: the server judges on its own clock.
  - A copy that had already landed answers `409 game_already_assessed` — drop it.
  - Tell facilitators: the PIN must pass while there is signal; the score may follow within 15 minutes
    of time-out.
Resuming: if the app died between steps 2 and 7, `complete-game` again returns the **same**
`assessment_id` while it is unscored, so repeat step 3. `409 game_already_assessed` on either
call means it was already scored — move on, do not show an error.

The full request/response shapes are in `API-V1-CONTRACT.md` → *Facilitator assessment*.

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

What each location carries, as the admin fills it in on Quest Locations:

| Field | Use |
|---|---|
| `name`, `description` | card title and text |
| `what_to_do` | the instruction for the team at that spot — show it on the location sheet |
| `latitude`, `longitude`, `radius` (m) | map pin and the check-in circle; lat/long arrive as **strings** |
| `marker_color` | pin colour, `#RRGGBB` |
| `image_url`, `map_image_url` | absolute URLs, or null. Ignore `image_path` / `map_image_path` (disk-relative) |
| `google_map_embed_url` | optional; open externally if present |
| `max_check_ins_per_user` | informational only — see below |
| `quest_points` | **not awarded** in this event: do not show it as points the team earns |
| `checked_in`, `check_ins_count`, `last_checked_at` | this account's progress |

Response top level also has `total_points` — ignore it; the team score is `GET /team` → `team.score`.

### Check-in

```
POST /api/v1/quest-locations/checkin
{ "location_id": 30, "user_latitude": -6.41697, "user_longitude": 106.82418 }
```

**One check-in per account per location**, whatever `max_check_ins_per_user` says. Replies:

| HTTP | `error` | Show |
|---|---|---|
| 200 | — | checked in |
| 403 | `out_of_range` (+ `distance`, `radius`) | "{distance} m away — get within {radius} m" |
| 409 | `already_checked_in` (+ `last_checked_at`) | already done; mark the location visited |
| 409 | `max_attempts_reached` (+ `limit`) | limit reached |
| 500 | `checkin_failed` | a real fault: report it, do not loop |

A repeat check-in used to return HTTP 200 with `success: false` and no key. It is 409 now.

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

**Which flags the app reads** — everything else in the list is for the retired web dashboard, the kiosk
or the server; ignore it:

| Flag | App behaviour when **off** |
|---|---|
| `quiz_system` | hide the Scan / quiz entry |
| `quest_locations` | hide the outdoor map and check-in |
| `gps_tracking` | do not run background position sending (`POST /tracking/position`) |

Not flag-gated, ever: team setup, the dashboard score card, the facilitator scoring screen, Game ended
and race reset handling. `leaderboard`, `team_management`, `notifications`, `game_dashboard`,
`workflow_timers` and every `dashboard_*` / `user_dashboard_*` flag have no effect in the app.

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

`is_open` says whether that spot's post is open for this team. `game_location_id` links a spot to an
AR outpost when it has one.

**A correct START clue opens every post on the plan** (operator, 15 Sep — your #21). When
`POST /race/clue/{map}` returns `correct: true, already_solved: false`, the server has written an
open unlock for this team on every active spot of that plan that has a `game_location_id`. It is the
same row the crew's Outpost Access panel writes, so `is_open`, the station gate (`checkin_required`,
indoor) and the AR gate (`awaiting_unlock`) all agree at once. **The crew can still close a post** for
a team afterwards, and answering the clue again (`already_solved: true`) does not reopen it.
`is_open` stays the only field to read — there is no `unlocked_by`. Re-read the plan after a correct
answer. A plan with no clue opens nothing by itself: the crew opens its posts.

The clue gates nothing else on the server: `/indoor-map` and `qr/lookup` do not check
`clue_solved`. Until it is solved no post is open, so the station gate already refuses indoor scans.

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

**No pre-entry gate.** Our web preview has one; yours must not — see the section below. The
camera opens straight into the scene.

**Object sheet** — slides up from the bottom on tap: title as a heading, description, the points
row, then at most one extra (image **or** link; a link opens externally), and a Close button. The
scene stays live behind it; closing returns to the same pose rather than restarting.

### No QR is involved. Open the camera and show the objects.

**This corrects the previous revision of this section, which told you to keep a Start gate. That
answer was wrong, and if you have already built to it, stop.**

The 3D camera has nothing to do with QR scanning. It has one job: show the objects an admin
deployed at *this* outpost, and let a player shoot them with the centre crosshair. Nothing is
decoded, nothing is matched. The outpost is already identified by the `{id}` in the request.

**Why the earlier answer was wrong.** I traced `yaw` to a frame captured when the admin taps Start
— which is true — and concluded the gate had to stay or "every object rotates". I never asked
whether that rotation matters. It does not. Checked: `hotspots` has **no latitude, longitude,
anchor or landmark column**. An object exists only as a bearing, pitch and distance from the
outpost's viewing position. So rotating the whole scene preserves every object's position
*relative to the others* — which is the only spatial relationship the data expresses. A player
still finds all of them by turning around, and the direction guide still points correctly.

Nothing is authored to line up with a physical thing, so absolute orientation is cosmetic.

**What this means for your build:**

- **Drop the Start gate.** Open the camera straight into the scene.
- Take the camera pose at entry as the origin and place objects camera-local from it.
- No QR step, no calibration step, no plane hit-test.
- Keep **Re-centre**: still useful when tracking is lost and regained, or when a player wanders off
  and returns. It just re-zeroes forward to wherever they are pointing now.

The web build keeps its gate for a reason that does not apply to you: `deviceorientation` has no
absolute forward at all, so it needs *a* moment to define one, and aiming at a fixed landmark makes
that repeatable between the admin and the player. ARCore establishes its own frame on start, so the
step buys you nothing and costs a confusing screen.

### The interaction, which is the whole feature

```
camera opens  →  objects are there  →  centre crosshair over one  →  tap  →  sheet
```

The sheet shows `title` + `description`, and then **one** extra decided by `media_type`:

| `media_type` | Sheet shows |
|---|---|
| absent / other | text only |
| `"image"` | text + the `image` |
| `"link"` | text + the `link` as a button, opening externally |

Plus the points row when `points` is non-zero. That is the entire interaction — the same as our
preview does.

### Opening the feature: the state machine

One endpoint decides everything. `GET /ar/locations/{id}[?lat=&lng=]` runs four checks **in this
order**, and the client mirrors them as states rather than guessing from HTTP status:

```
                        ┌─────────────────────────────┐
                        │ CHECKING                    │
                        │ GET /ar/locations/{id}       │
                        └──────────────┬──────────────┘
                                       │
        ┌──────────────────────────────┼──────────────────────────────┐
        │                              │                              │
   403 awaiting_unlock         403 location_required           403 out_of_range
        │                              │                         (distance,radius)
        ▼                              ▼                              ▼
┌───────────────────┐        ┌───────────────────┐        ┌───────────────────────┐
│ WAITING_UNLOCK    │        │ NEED_LOCATION     │        │ OUT_OF_RANGE          │
│ INDOOR            │        │ ask permission,   │        │ "You are 84 m away.   │
│ "Waiting for the  │        │ get a fix, retry  │        │  Get within 30 m."    │
│  crew…"  poll 15s │        │ with ?lat=&lng=   │        │ re-check on movement  │
└─────────┬─────────┘        └─────────┬─────────┘        └───────────┬───────────┘
          │ 200                        │ 200                          │ 200
          └──────────────┬─────────────┴──────────────────────────────┘
                         ▼
              ┌──────────────────────┐      models missing from cache
              │ PREPARING            │──────────────► download, or fail with
              │ resolve .glb from    │                "content not downloaded"
              │ the offline cache    │
              └──────────┬───────────┘
                         ▼
              ┌──────────────────────┐
              │ SCENE_LIVE           │  camera + crosshair + counter + guide
              │ crosshair hot/idle   │  (no gate, no QR, no plane test)
              └──────────┬───────────┘
                         │ tap while crosshair is hot
                         ▼
              ┌──────────────────────┐
              │ SHEET_OPEN           │  text | text+image | text+url
              │ scene stays live     │  close → back to SCENE_LIVE, same pose
              └──────────────────────┘
```

**Indoor and outdoor differ only in which gate fires.** `access_mode` in the success payload tells
you which regime you are in: `"manual"` means a crew member opens it (GPS cannot satisfy a radius
through a roof), `"geofence"` means the radius decides. Do not branch on anything else — not on
whether coordinates are null, not on a screen the player came from.

**Admins are exempt from both gates** so they can inspect from a desk. Never assume a 200 means the
player is on site.

### Admin configuration, as the API actually returns it

This is the live shape, not an illustration:

```json
{
  "success": true,
  "location": {
    "id": 39,
    "name": "Test - Demo",
    "model": "https://…/models/base.glb",
    "latitude": null,            // null = never bound to a place; no geofence at all
    "longitude": null,
    "radius": 50,
    "coordinate_source": "none", // none | own | quest_location
    "quest_location_id": null,
    "access_mode": "geofence"    // geofence | manual
  },
  "objects": [
    {
      "id": 26,
      "title": "The Cage",
      "description": "Free your team-mate.",
      "points": 10,

      "media_type": "image",     // image | link | null  → decides the sheet
      "image": "https://…/hotspot-images/x.jpg",
      "link": null,

      "model": "https://…/models/cage.glb",
      "model_id": 3,
      "scale": 1.0,
      "distance": 2.5,
      "position": { "x": 1.77, "y": 0.43, "z": -1.72 },
      "rotation": { "x": 0, "y": 180, "z": 0 },
      "animations": [ { "type": "spin", "speed": 1, "range": 1 } ]
    }
  ]
}
```

`position` is already metres, camera-local, `−z` forward. Place as given. `media_type` alone decides
which of the three sheet shapes to render — do not infer it from which fields are non-null.

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
motions applied locally rather than synced.

**Rotation order is Y, then X, then Z — yaw outermost** (three.js Euler order `'YXZ'`; as a matrix
`R = Ry(y) · Rx(x) · Rz(z)`, applied to the model's own points). So `y` is the heading the object faces
(a glTF model's front is its +Z), `x` tilts it and `z` rolls it. **Spin adds its angle to `y`**:
`Ry(y + spin) · Rx(x) · Rz(z)`, which turns the object about the vertical through its own position
after any tilt — a flat coin authored with `x: 90` stands up and turns like a top. Changed 15 Sep
from XYZ, where spin turned a tilted object inside its own plane; every object placed before then
has `x = z = 0`, where the two orders are identical. If your engine composes Euler angles in
another order, build the quaternion from the three axis rotations in this order yourself.

The motion formulas in `ArExperienceController` (copied by `animateObjects()` in
`resources/views/ar/view.blade.php`) are the whole spec: spin `t·speed·30°`, bob
`sin(2π·t·speed/4)·range` m, orbit `t·speed·12°` (orbit ignores `range`), sway
`sin(2π·t·speed/6)·range`°; orbit and sway add into one angle that rotates the authored `x`/`z`
about the player. Also check your renderer's far plane: `distance` goes up to 50 m, and some
defaults stop drawing at 30 m (your #18). `model` is a `.glb` URL: pre-download every one from
`/offline/manifest` before the event, or AR stalls in the field.

A 200 with an empty `objects` array means the outpost has a model but nothing placed on it yet.

---

## 10. Offline

```
GET /api/v1/offline/manifest
{ "bounds": { "north", "south", "east", "west" },
  "map":    { "tiles": ["https://…/{z}/{x}/{y}.png"], "attribution": "…", "max_zoom": 19 },
  "quest_locations": [ { id, name, latitude, longitude, radius, marker_color } ],
  "game_locations":  [ { id, name, experience_type, model, uses_ar,
                         latitude, longitude, radius, coordinate_source, quest_location_id } ],
  "images": [...], "models": [...], "pages": [...],
  "counts": { … } }
```

Fetch this once the team is registered and **pre-download everything on it while you still have
signal** — venue WiFi and mobile data are both unreliable mid-game. `bounds` is the area worth
pre-caching map tiles for. `models` are the 3D assets; AR will stall without them.

**`map` is the basemap for the outdoor map** (your #20). Raster XYZ tile templates from the server's
`config/maps.php` — the same source every web map uses, so the operator switches provider in one
place and the app follows. Never hardcode a tile host. Show `attribution` on the map. Pre-download
`bounds` at zoom 14–18 during Sync (cap the count; the web caps at 1500 tiles) and use the cache
first, so the map works without signal. The default template is OpenStreetMap's public server, which
is for light use only: download the venue once, never tile-by-tile for every team during play.

Queue player actions taken offline (`save-answer`, `checkin`) and replay them when signal returns,
**with backoff** — and drop a queued item on a `4xx` rather than retrying it forever, because a
rejected answer will be rejected again.

---

## 11. Traps — things that have already bitten someone

| Trap | Detail |
|---|---|
| **An empty PHP array encodes as `[]`, not `{}`** | Reported 14 Sep. A field built as a map keyed by id is `{}` when it has entries and `[]` when it has none, so the **type** changes with history. `answers` on `/quiz/start` and `/quiz/continue`, and `flags` on `/features`, all did this; a client modelling them as maps rejected every fresh attempt, and it surfaced as a network error because the reply could not be parsed. **All three now always return an object.** If you find another field that flips, report it — the fix belongs on the server. |
| **GLB models render solid black** | Reported 14 Sep, verified by the client team in the SceneView 2.2.1 AAR. With `Config.LightEstimationMode.AMBIENT_INTENSITY`, `LightEstimator` has no indirect-light path, so PBR materials — metallic ones especially — have nothing to reflect and render black. Fix: `ENVIRONMENTAL_HDR`, the neutral IBL shipped inside the AAR (`environments/neutral/neutral_ibl.ktx`) as the scene environment **with the skybox dropped** (in AR the camera feed is the background and a skybox paints over it), plus an explicit main light. It looks exactly like a broken model export, so the `.glb` gets blamed first. |
| **Two names for a coordinate** | `checkin` wants `user_latitude` / `user_longitude`. `tracking/position` wants `latitude` / `longitude`. Same concept, different keys. |
| **Lat/long type is inconsistent** | `/quest-locations` returns them as **strings** (`"-6.41697690"`); `/offline/manifest` returns them as **numbers** (`-6.4169769`). Parse defensively. |
| `spots` nesting | Top level in `/indoor-map`, not under `map`. |
| `distance` is null | Unless you pass `user_latitude` + `user_longitude` as query params (§5). |
| `race: null` | `/race/status` returns `race: null` before the START code is scanned — not an error. |
| `fun_game` scores 0 | At answer time; the facilitator's `POST /quiz/assessments/{id}` pays it. Never total points client-side. |
| `complete-game` returns `redirect` | A web URL. Ignore it — open the native Facilitator scoring screen with `assessment_id`. |
| `awaiting_unlock` | A normal waiting state on AR outposts, not a failure. |
| Nulls are normal | `team_id`, `access_window_ends_at`, `image_path`, `google_map_embed_url` are all legitimately `null`. |
| CameraX binds to the **Activity** lifecycle, not the composable | Reported by the client team, 11 Sep. Navigating away from the QR scanner does not release the camera, so ARCore then opens to a black frame. "Two cameras, never both alive" is easy to satisfy by accident and hard to satisfy on purpose: unbind on dispose **and** release any live provider immediately before starting the AR Activity, with one launcher as the only route in. This was the real cause of an earlier "no 3D camera" report; a missing CAMERA permission was a second, separate cause. |
| Don't trust the device clock | Read `/quiz/timer/{attemptId}`. |

### Guidance and attempt history — both now exist

Reported as blocking, and correctly: both lived only as Livewire pages, so a team holding just the
app could never reach them. The briefing was the worse of the two — instructions a team is told to
read, that they had no way to read.

```
GET /api/v1/guidance          { success, count, guidances[] }
GET /api/v1/guidance/{id}     { success, guidance }
GET /api/v1/quiz/attempts?limit=20
                              { success, total_attempts, attempts[] }
```

`guidances[]` carries `{ id, title, description, images[], sort_order, updated_at }` — the shape you
asked for, plus `updated_at` so you can cache on it.

Three things done as you asked, and verified rather than assumed:

- **`active()` → `forUser()` → `ordered()`**, mirroring `GuidanceView` exactly. `forUser()` is the
  one that matters: a briefing aimed at one team must not reach another. Tested with two accounts
  and a targeted row — A sees 2, B sees 1.
- **`images` are absolute URLs.** Stored relative; resolved through the public disk on the way out,
  because you cache for offline and cannot resolve a relative path from a cold start.
- **Inactive rows are filtered**, confirmed by a deliberately inactive row not appearing.

`GET /guidance/{id}` answers `404 guidance_not_found` both for a row that does not exist and for one
that is not yours — deliberately the same, since a distinct "exists but not for you" would tell one
team that another team has a briefing it cannot see.

**`attempts[]` row shape — confirmed field by field against the controller:**

```json
{
  "id": 412,
  "questionnaire": { "id": 13, "title": "Field Bomb (Pos Merah)" },   // null if the questionnaire was deleted
  "status": "completed",            // started | completed | abandoned — exactly these three
  "total_score": 80,                // a JSON number, never a string
  "total_time_seconds": 214,        // a JSON integer, never a string
  "created_at":   "2026-09-14T09:12:03+00:00",   // ISO-8601, always
  "completed_at": "2026-09-14T09:15:37+00:00"    // ISO-8601, or null while status is "started"
}
```

Your field names are right, and so is your status mapping. Two things you can tighten: both dates are
always ISO-8601 — there is no `Y-m-d H:i:s` variant on this endpoint — and both numbers are always
JSON numbers, never strings.

**A `started` attempt reports `total_score: 0` and `total_time_seconds: 0`, not `null`.** Verified
against a live row. So `0` does not mean "scored zero" — it can equally mean "not finished". Tell
the two apart with `status` (or `completed_at` being null), never with the number. Leniency does no harm, but do not rely on it elsewhere:
`/quest-locations` still sends lat/long as strings.

`total_attempts` counts everything, not the page: a team that has done thirty outposts sees thirty
even when listing twenty. `limit` is 1–100; above that is a 422.

Still not built, and still not blocking by your own reckoning: score breakdown, dashboard stats,
the player's own GPS map. (The game assessment form now has endpoints — §4.) Ask again if any becomes blocking.

### Edge cases, and what to do about each

**The player walks out of the radius while the camera is open.** The server checks the geofence
**once, at open**, and has no idea afterwards. This is the client's decision, and the obvious answer
is wrong: do not close the camera the moment they drift past the line. A team that stepped five
metres out of a thirty-metre radius while turning to look at an object has not cheated, and a camera
that snatches itself away mid-puzzle gets blamed on the app, loudly, at a live event.

Use hysteresis:

| Distance | Behaviour |
|---|---|
| inside `radius` | normal |
| `radius` … `radius + 50 m` | keep the scene, show a banner: "Move back toward the post" |
| beyond `radius + 50 m` | close, return to the map, explain why |

Sample position at the interval you already use (10 s is plenty — it is also the cap on position
posts). Never act on a single fix: GPS jumps, and one bad reading should not end a session.

**Your out-of-radius decision is accepted as-is — no number from us.** A blocking layer that swallows
gestures while the scene stays alive and tracking is better than anything proposed here: it keeps
found objects, resumes where they left off, and leaves Back reachable. Your `radius` → `radius × 0.9`
hysteresis band is the right shape, and a ratio beats the fixed `+50 m` suggested earlier — it scales
with the outpost instead of being generous at a 10 m post and meaningless at a 200 m one. Computing
it locally from the `radius` and coordinates already in the payload is also right: the authoritative
gate stays on every action, and it costs nothing from the budget.

**The crew revokes an indoor unlock while the camera is open.** Same shape, simpler answer: nothing
tells you until your next call. Re-check `GET /ar/locations/{id}` when the app returns to the
foreground, and on a long session roughly every 60 s. On `awaiting_unlock`, close back to
WAITING_UNLOCK — a revoked unlock is deliberate, unlike a GPS wobble.

**The access window expires mid-session.** Every authenticated call starts answering `403
access_window_expired`. That is terminal: stop, do not retry, send the player to the waiting screen.
It will most likely surface on an object-open or a position post rather than on entry.

**Camera permission refused.** A dead end the player can fix, so say so plainly and offer the
settings route. Never open SCENE_LIVE with no preview — a black screen with a working crosshair
looks like a broken outpost.

**ARCore missing or unsupported.** Check at launch, not at the outpost: a team discovering this
while standing at a post has already lost the time. Surface it on the dashboard as "this device
cannot run the 3D camera" and keep every other feature working.

**Models not downloaded.** `.glb` files come from `/offline/manifest` and must be fetched while
signal exists. If one is missing at open, say "content not downloaded" and offer a retry — do not
render an empty scene, which is indistinguishable from an outpost with no objects.

**An outpost with zero objects.** A legitimate 200 with `objects: []`: the admin made the outpost but
has not placed anything. Say so. Do not show the crosshair over an empty room and let the player
hunt for nothing.

**Double-counting.** The found counter increments the **first** time each object is opened. Flag the
object; never count taps. Re-opening the same object must not move the number.

**Offline, scene already loaded.** Everything needed is local: the objects came with the response and
the models are cached. The camera, the crosshair and the sheets must all keep working with no
signal — that is the whole reason the manifest exists. Only the found-counter sync waits.

**Tap with an idle crosshair.** Do nothing. No toast, no flash. A player sweeping the room taps
constantly, and feedback on every miss reads as malfunction.

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
| `invalid_login_code` | 401 | Login card unknown, replaced or revoked — offer e-mail + password (§2d) |
| `account_inactive` | 403 | Login: the account is switched off |
| `game_ended` | 403 | Session archived — Game ended screen, wipe, stop every request for good (§2b) |
| `race_reset` | 409 | The admin stopped the race — show the message, drop session state, dashboard, stay logged in (§2c) |
| `team_locked` | 403 | Member add/remove after setup — remove the call; members are fixed |
| `race_not_started` | 403 | Questionnaire scan before START — send the team to scan START |
| `checkin_required` | 403 | Not checked in at this questionnaire's `post` (outdoor: GPS check-in; indoor: crew opens it) |
| `station_not_linked` | 403 | The admin has not linked a post — nothing the team can do |
| `questions_incomplete` | 409 | Submit before every question is done; body has `pending` — stay in the session |
| `session_in_progress` | 409 | Another session is live; body has `attempt_id` — open it |
| `assessment_not_found` | 404 | No such assessment, or another team's |
| `additional_out_of_range` | 422 | Additional points above the game's points — clamp the input |
| `penalty_out_of_range` | 422 | Penalty above `max_penalty` — clamp the input |
| `facilitator_pin_not_set` | 409 | Admin has not set a PIN; show that, nothing to retry |
| `invalid_facilitator_pin` | 403 | Wrong PIN; show `attempts_left` |
| `facilitator_pin_locked` | 429 | Locked; count down `retry_after`, keypad disabled |
| `invalid_facilitator_photo` | 422 | Re-capture and downscale |

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
- [ ] `game_ended` from any call (login too) → Game ended screen, local data wiped, workers stopped, flag checked before any request on relaunch, Uninstall button
- [ ] `race_reset` from any quiz call → message, local session state dropped, out of the locked session, dashboard, still logged in; `race: null` clears a cached clock
- [ ] Login screen: Scan kartu login (only `EUREKA-LOGIN:` strings) → `auth/login-code` → team setup; e-mail + password still there
- [ ] Team setup shown once when `GET /team` is `no_team`; no member editing anywhere afterwards
- [ ] Team setup offers "Sudah terdaftar" (search) only when `participant_directory` is true, and Manual always; one Ketua toggle
- [ ] Dashboard score card from `team.score` (total + three lines, negative game points signed), no rank
- [ ] A scan of any active station code lands on its questions screen, every question type rendered, images from `image_urls`
- [ ] Inside a session: no Submit, no back, no dashboard until `can_submit`; killing the app reopens the session; time-out goes to Finish
- [ ] `fun_game` uses `complete-game`, then the native Facilitator scoring screen; no client-side point totals anywhere
- [ ] Facilitator scoring: PIN gate with lockout countdown; silent front-camera photo on Confirm; a queued score is Keystore-sealed and dropped on 4xx / game_ended / race_reset
- [ ] App name, logo and theme come from `/branding`, with nothing hardcoded
- [ ] Hidden menus follow `/features`, and you never rely on that for security
- [ ] Indoor spots positioned from `x`/`y` as percentages
- [ ] Offline manifest pre-downloaded while online; queued actions replay with backoff
- [ ] Both an indoor event and an outdoor event tested end to end
