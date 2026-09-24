# EUREKA / Questerra — API v1 for the Android client

Generated from the live router on questerra-series.com. Base URL `https://questerra-series.com`.

## What changed since the last handover

Act on these — several change responses your app already handles.

**Latest (24 Sep, second change): check ARCore at install, not at a post — client-side, no API
change.** Two of four trial teams were told "ARCore tidak support" **at their first booth**, with the
paper-QR clue locked inside the 3D scene; both phones were otherwise fine and the server answered 200
with three objects. The APK must run `ArCoreApk.checkAvailability()` on first launch after install (and
again when the 3D camera first opens), distinguish *needs Google Play Services for AR* (offer
`requestInstall()`) from *device can never* (tell them to see the crew before the race), and **never
block login or the rest of the game** over it. Nothing in this API changes. Build guide §9; work order
§39.

**Latest (24 Sep, sixth change): `counts.ar_posts`, so the Kamera 3D button can be locked.**
`GET /offline/manifest` `counts` gains **`ar_posts`** — how many active posts really open a camera
(`uses_ar` true). Zero means the crew ran this event with the 3D switched off everywhere: lock the
dashboard's Kamera 3D button, visibly disabled with a reason, rather than hiding it or opening an empty
screen (operator's instruction). **Only zero locks it** — one AR post out of fourteen leaves the button
live, because that post still needs the screen; the test is `== 0`, never a fraction. Re-read it at
each Sync — one post switched back on unlocks it. A camera-off post itself stays off the 3D list
and is reached from the floor plan, unchanged. Build guide
§9 and §10; work order §43.

**Latest (24 Sep, fifth change): the bell at the deadline is the crew's to choose, and it is sent to
you.** A new **`bell`** object rides on `GET /race/status` (only when a run exists) and on
`GET /offline/manifest`: `{ "tone": "bel" | "lonceng" | "sirine" | "bip" | "custom", "volume": 0-100,
"repeat": 1-5, "sound_url": null | "https://…" }`. The four named tones are **not files** — each end
draws its own, so a bell can ring with no signal; `custom` means the crew uploaded a sound and
`sound_url` is where it is, to be cached at Sync like any other asset. Cache the last `bell` you saw
and ring it at `deadline_at` even offline; if `custom` cannot be fetched or played, fall back to `bel`
rather than staying silent. A crew changing the tone mid-event reaches you on the next poll, without a
re-sync. Build guide §8; work order §42.

**Latest (24 Sep, fourth change): a race can end on time, and says which ending it was.** A START code
can now carry a **time limit in minutes**, so a run ends on whichever comes first: every counting post
cleared, or the clock running out. `GET /race/status` `race` gains **`deadline_at`** (ISO 8601 or null),
**`time_limit_minutes`** and **`finished_reason`** (`all_posts` | `time_limit` | null while running);
`POST /race/start` carries `deadline_at` and `time_limit_minutes` too, so a team sees its deadline the
moment it scans. `elapsed_seconds` never exceeds the limit, and `finished_at` is stamped **at the
deadline**, not when anyone noticed. After the deadline the station gate answers **`403 race_time_up`**
(with `deadline_at`, `finished_at`) for a **new** post — a questionnaire already open may still be
finished and handed in, deliberately. A code with no limit behaves exactly as before. Build guide §8;
work order §41.

**Latest (24 Sep, third change): a post can run without the camera.** `ar_enabled` is new in the
scene body — `location.ar_enabled` in `/ar/locations/{id}` and in the synced
`game_locations[].scene`. False means the crew switched the 3D camera off for that post: render the
`objects[]` as a **list** (title, description, points, `claimed`) instead of starting ARCore, and let a
row be tapped to claim through the same `POST /ar/objects/{id}/claim`. `objects[].model` may be null
there. Gates, QR scan and questionnaire are unchanged. A `404` with "nothing to show yet" means the
camera is off and no objects were written. Not a replacement for the first-run ARCore check (§9). Build
guide §9; work order §40.

**Latest (24 Sep, second change): check ARCore at first launch — client-side, no API change.** In the
trial two of four phones could not run the 3D camera, and it surfaced at the first booth with a clock
already running; the server was answering 200 with a full scene for both. The app must run
`ArCoreApk.checkAvailability()` on the first launch after install, offer `requestInstall()` when Play
Services for AR is missing or old, say plainly when the device is not capable, re-check on resume, and
**never block the rest of the app** on the result. Nothing to call here. Build guide §2a, §9; work
order §39.

**Latest (24 Sep): one route per team — `POST /race/start` can refuse.** Ten rotations means ten
START codes in one room, and scanning the wrong one used to open a **second** clock quietly: sessions
are keyed on (account, plan), so `/race/status` then reported the newer one and the team's whole route
changed. A START code for a different plan, while the team has a clock running, now answers **`409
already_on_another_route`** with `route {id, name}` and `race {session_id, started_at,
elapsed_seconds, indoor_map_id}` — show the message and send them to their own card. Re-scanning the
**same** code is unchanged (`just_started: false`), and a team that has finished may be moved. It is
judged per team, so the second phone is caught too. Build guide §8; work order §38.

**Latest (23 Sep, eighth change): tapping a 3D object pays.** The AR editor has always offered a
price per object and the app has always shown it, but nothing paid it — there was no endpoint and no
sum included it. Now: **`POST /api/v1/ar/objects/{hotspotId}/claim`** (no body) → `{points, claimed_at,
object, score}`; the scene body gains **`objects[].claimed`** (per team, also in the synced copy); and
`score` everywhere gains **`object_points`**, which is part of `total`. Once per team, not per phone —
a repeat answers `409 already_claimed` **with** `points` and `score`, so treat it as already-done. Gates
are the scene's own: `403 awaiting_unlock` (indoor, crew), `403 checkin_required` (outdoor), `403
race_not_started`; `422 object_has_no_points` when the object is priced at 0. An emergency stop clears
the claims with the rest of the run. Build guide §2a, §9; work order §37.

**Latest (23 Sep, seventh change): the read-back names its post.** `GET /quiz/attempts/{attemptId}`
gains **`post`** — `{type: "indoor"|"outdoor", id, name}`, or null — the booth that attempt was played
at, in the same shape `qr/lookup` sends. Reported by the client team (v48): a result reopened from
history could not name the post, and since a questionnaire may be shared by several posts its title no
longer says. Build guide §11.

**Latest (23 Sep, sixth change): a finished post closes itself.** `/indoor-map` `spots[]` gains
**`is_done`** — true once the team has answered every questionnaire at that post (a post with two
questionnaires stays false until both are in). Draw it as finished and do not let it be opened again.
The server does not rely on the app: handing in the last questionnaire **revokes that team's access**
to the post, so `/ar/locations/{id}` returns `403 awaiting_unlock` and `qr/lookup` `403
checkin_required` afterwards. A crew member may reopen it in Outpost Access — then `is_open` is true
again while `is_done` stays true. Per team, so both phones agree. Build guide §8; work order §36.

**Latest (23 Sep, fifth change): offline phase 2 — a questionnaire can be started with no signal.**
New: `POST /api/v1/quiz/offline-start {qr_code, client_token, elapsed_seconds}`. The team opens the
sealed copy at the post, plays with a stopwatch (`elapsedRealtime`), and hands the attempt to the
server when the network returns; the 200 body is exactly `/quiz/start`'s plus `repeat`. The start is
reconstructed as `now − elapsed`, clamped to the questionnaire's `time_limit` and never before the
team's race start, so the device clock buys nothing. `client_token` makes a retried upload idempotent.
The gates are unchanged, only judged late — `race_not_started`, `checkin_required` (a post the crew
never opened is refused), `not_available`, `max_attempts_reached`, `session_in_progress`,
`unknown_code`, `too_many_starts`. A `fun_game` still needs signal to be scored, so a questionnaire
containing one can be played offline but not handed in until then. Build guide §10; work order §35.

**Latest (23 Sep, fourth change): the race ends, and `/race/status` says so.** This was missing from
the contract entirely — the operator played ten posts and the app never closed. `GET /race/status`
`race` carries **`finished`** (bool) and **`finished_at`** (ISO 8601 or null), and `elapsed_seconds`
stops growing at that moment: that is the team's official time. The server sets it when the team
completes the last questionnaire with `counts_toward_finish` — **per team, not per phone**, so a team
playing on two devices sees `finished: true` on both. Finishing locks nothing: scans, `quiz/start` and
the AR gate keep working, because a bonus post is still playable afterwards. `openapi.json` cannot show
these fields — it probes `/race/status` with no session, where `race` is null. Build guide §8; work
order §34.

**Latest (23 Sep, third change): one AR Outpost serves every team.** The ten booths are now one shared
set of outposts, so the same `game_location_id` appears on several teams' plans under a different
marker name — `111` is "Pos 01" for one team and "Pos 02" for another. No shape changed and access is
still per team (`is_open`, `awaiting_unlock`, `checkin_required` unchanged). Two assumptions to drop:
a route number comes from `/indoor-map` `spots[].name`, never from the outpost; and outposts are now
named after what happens there ("QRIS Tap Experience"), so `location.name` in `/ar/locations/{id}`
reads differently after the next sync. Build guide §8; work order §33.

**Latest (23 Sep, second change): one questionnaire, several posts.** An admin can link a questionnaire
to more than one post, or to *semua pos* (every post). Consequences for the client: `qr/lookup` 200 now
carries **`post`** (`{type: "indoor", id, name}` or null) — the post this play belongs to, since the same
code is printed at more than one booth; a `403 checkin_required` for a shared questionnaire carries **no
`post`** (the server cannot know which booth the team is at) — show the `message` alone; `max_attempts`
counts **per post**, so a finished questionnaire opens again at the next post that the crew unlocks; and
`/offline/manifest` `questionnaires[]` gains **`game_location_ids`** and **`all_posts`** — read the list,
not the single `game_location_id`. Nothing changes for a questionnaire with one post. Build guide §4, §10;
work order §32.

**Latest (23 Sep): per-question results.** `POST /quiz/submit` gains `result.questions[]`
(`question_id, type, question, points, points_earned, correct (bool|null), answered`), and the new
`GET /api/v1/quiz/attempts/{attemptId}` reads the whole result back — totals, `questions[]` and
`puzzles[]` — for any attempt of the caller's own team. No right answer in either. Build guide §11;
work order §31.

**Latest (22 Sep, fourth change): AR scenes in the manifest.** `/offline/manifest` `game_locations[]` gains
`access_mode` and `scene` — the `/ar/locations/{id}` body (`location`, `objects`) minus `success` — with
every model in `models` and every tap picture in `images`. The gate is not in it: indoor still waits for
the crew; outdoor may be judged from the phone's GPS. Also: show the floor plan only after the START
clue. Build guide §8, §10; work order §30.

**Latest (22 Sep, third change): the START clue no longer opens posts.** A correct `race/clue` answer
leads to the floor plan and opens nothing; every AR Outpost opens only through the crew (Outpost
Access). Reverses the 15 Sep rule from #21. No shape changed. Build guide §8; work order §29.

**Latest (22 Sep, second change): offline phase 1.** `/offline/manifest` gains `questionnaires[]` — each
sealed with AES-256-GCM under a key derived from its post's QR code (PBKDF2-HMAC-SHA256, 100k) — plus
`offline_crypto`, `indoor_map` (the team's plan, `/indoor-map`'s shape) and `content_updated_at`, which
`/race/status` now carries too. New: `GET /offline/questionnaires/{id}/assets/{ref}` — one encrypted
question picture. Starting a questionnaire still needs signal. Build guide §10; work order §28.

**Latest (22 Sep): `/guidance` removed.** `GET /api/v1/guidance` and `/guidance/{id}` are gone (404).
The per-team briefing had no admin screen and was always empty; the app dropped its page in v43.
Delete the wrapper. Work order §27.

**Latest (21 Sep): Tebak Gambar.** A seventh question type, `picture_puzzle`: one picture and several
labelled boxes. Questions gain `slots[]` (`key`, `label`, `length`) — never the answers. Answer with
`save-answer` using `answers: {key: text}` (the whole set each time); the reply never says what is
right. Partial credit per box, rounded down; loose matching (case, spaces, punctuation ignored). The
`submit` response gains `result.puzzles[]` with per-box `correct` flags — never the right answers.
`continue` returns a puzzle's saved answer as an object. Build guide §4; work order §26.

**Latest (20 Sep, second change): `race_reset_at`.** `GET /race/status` and `GET /offline/manifest` now
carry `race_reset_at` (ISO 8601 or null) — the time of the last emergency stop, sent with or without a
session. Compare it with the value stored beside your cache: newer means every per-team cached thing
(open posts, `is_open`, clue state, attempts, queued writes, check-ins, score) is void. Build guide §2c.

**Latest (20 Sep): the photo question.** A sixth question type, `group_photo` — the team photographs
itself. Questions gain `frame_url` (a PNG to draw over the camera; may be null) and `share_caption`.
Answer with `POST /api/v1/quiz/photo-answer {attempt_id, question_id, photo}` — base64 JPEG/PNG ≤ 6 MB,
composed with the frame by the app — which stores it and awards the question's points; reposting
replaces the photo. `save-answer` on such a question is refused (`photo_answer_required`). The frame is
in `/offline/manifest` `images`. Build guide §4; work order §23.

**Latest (15 Sep, fourth change): login cards.** `POST /api/v1/auth/login-code {code, device_name}` (public,
`throttle:login-code`) returns the login body for a printed card's `EUREKA-LOGIN:<code>`; `401
invalid_login_code` otherwise. Login refusals gained `account_inactive` / `access_window_expired` keys.
Build guide §2d; work order §15.

**Latest (15 Sep, third change): the station gate.** `qr/lookup` and `quiz/start` (new attempt) refuse a
questionnaire until the team scanned START and checked in at its linked post: `403 race_not_started`,
`403 checkin_required` (+ `post {type: outdoor|indoor, id, name}`), `403 station_not_linked`. Build
guide §4; work order §14.

**Latest (15 Sep, second change): late facilitator scores, outposts, flags.** A score whose PIN passed
`verify-pin` before time-out is accepted up to 15 minutes after it (`accepted_late: true`); judged on the
server's `pin_verified_at`. `GET /quest-locations` items add `image_url`, `map_image_url`. Check-in:
repeat → `409 already_checked_in` (+ `last_checked_at`), fault → `500 checkin_failed`. Only `quiz_system`,
`quest_locations`, `gps_tracking` flags affect the app. Build guide §4, §5, §7; work order §13.

**Latest (15 Sep): team setup can pick registered participants.** `GET /api/v1/participants?q=` searches
the FEKDI x IFSE list (name or e-mail, ≥ 2 chars; e-mails masked). `POST /team` members accept
`participant_id` (instead of name/email) and `is_leader`. `GET /team` 404 carries
`participant_directory`. New errors: `participant_taken` 409, `directory_disabled` 409,
`participant_not_found` 404, `duplicate_participants` 422, `one_leader_only` 422. Build guide §2a.

**Latest (14 Sep, seventh change): `race_reset`.** An admin emergency stop wipes race clocks, points,
scans, check-ins, attempts and assessments for every team, keeping teams and accounts. Quiz calls that
name a wiped attempt or assessment return `409 {error: "race_reset", race_reset: true, message,
reset_at}`. The app shows the message, drops session state and returns to the dashboard, still logged
in. Build guide §2c.

**Latest (14 Sep, sixth change): `game_ended`.** When the admin archives a finished session, every
account in it is removed. Login and any token-authenticated call from those accounts return
`403 {error: "game_ended", game_ended: true, message, archive {name, ended_at}}`. The app shows the
Game ended screen, wipes local data and stops all requests for good. Build guide §2b.

**Latest (14 Sep, fifth change): team members are fixed after setup, and `GET /team` carries the
team score.** `POST /team/members` and `DELETE /team/members/{id}` → always `403 team_locked`.
`GET /team` → `team.score {total, base_points, earned_points, assessment_points, attempts_completed}`,
the same number as `/admin/user-progress` and the kiosk; `points` = `score.total`, `earned` =
total − base. `rank` and `total_teams` removed (operator: no ranking). Build guide §2a.

**Latest (14 Sep, fourth change): the question session is locked.** `POST /quiz/submit` →
`409 questions_incomplete` (+ `pending`) until every question is done or time runs out;
`qr/lookup` and `quiz/start` for another station → `409 session_in_progress` (+ `attempt_id`);
`complete-game`, `verify-pin` and scoring → `403 time_expired` after time-out. `completion`
is added to start, continue and the scoring response. `continue` refusals now carry `error` keys.
Build guide §4 → *The question session is a locked room*.

**Latest (14 Sep, third change): questions carry `image_urls`, and `/quiz/start` refusals all have
an `error` key.** `image_urls` is `images` made absolute — show it; `images` stays as it was.
New keys: `questionnaire_not_found` 404, `max_attempts_reached` 403, `too_many_starts` 429
(`retry_after`). The scan → questions flow is spelled out in the build guide §4.

**Latest (14 Sep, second change): scoring needs the facilitator PIN and a silent front-camera
photo.** `POST /quiz/assessments/{id}` now requires `facilitator_pin` and `facilitator_photo`;
`POST /quiz/assessments/{id}/verify-pin` checks the PIN first. See *Facilitator assessment*.

**Earlier (14 Sep): the facilitator scores a `fun_game` on the team's phone, through two new
endpoints.** `GET` / `POST /api/v1/quiz/assessments/{assessment_id}` — the id comes from
`complete-game`. Ignore the `redirect` field `complete-game` still returns; it is a web page the
app cannot use. See *Facilitator assessment* below.

1. **`POST /api/v1/quiz/complete-game` now exists.** It was missing, so the app could start and
   submit a quiz but never finish a `fun_game` question, leaving the attempt unfinishable.
2. **Quiz refusals now carry a stable `error` key and a 4xx.** They used to be plain HTTP 500
   with raw English prose in `message`, so the only way to tell a normal double-submit from an
   outage was to string-match. Switch to the `error` key (table below).
3. **`POST /api/v1/tracking/position` is now `throttle:tracking` — 30/min per player, down from
   120.** One fix every two seconds is far more than a live map needs. If your client posts on
   every GPS callback, add a minimum interval or you will start seeing 429.
4. **`tracking/position` accepts and stores `accuracy` and `device_info`.** Both optional, both
   worth sending: the columns existed and nothing filled them, so on the admin map a 5-metre fix
   and a 500-metre fix drew the same dot.
5. **`save-answer` enforces the time limit; `submit` deliberately does not** (but see *Latest*: submit
   now requires every question done while time remains). Nothing new can be
   written after the clock runs out, but the submission itself always lands — refusing it would
   strand an attempt whose clock expired mid-request and lose the team's work.
6. **`save-answer` accepts a null `answer`.** Clearing a field used to raise a `TypeError` and
   return an untrapped 500.
7. **`POST /api/v1/quest-locations/get-route` added** (walking directions). Needs
   `OPENROUTE_API_KEY` set on the server; until then it answers `success: false`, not an error.
8. **`POST /api/v1/auth/login` gained `throttle:auth`.** The controller already capped 5 attempts
   per email+IP; this adds the per-address ceiling, so one connection cannot spray five guesses
   across many accounts.
9. **Login error ordering changed.** An account whose access window has expired is now told so
   only *after* the password is correct. Previously the message came back for any email with no
   password at all, which let anyone enumerate accounts.

## Endpoints

`window` = subject to `EnsureAccessWindow`: an admin-granted time window per team. Outside it,
every one of these answers 403 with `success:false`, `expired:true`,
`access_window_expired:true` and `error:"access_window_expired"` — treat that as "session over",
not as a bug. The three flags are redundant on purpose: the booleans predate the `error` key and
are kept so existing builds keep working.

| Method | Path | Auth | Window | Rate limit |
|---|---|---|---|---|
| `GET` | `/api/v1/ar/locations/{id}` | token | yes | `api` |
| `GET` | `/api/v1/auth/me` | token | yes | `api` |
| `GET` | `/api/v1/branding` | public | no | `kiosk` |
| `GET` | `/api/v1/features` | token | yes | `api` |
| `GET` | `/api/v1/hero-slides` | public | no | `kiosk` |
| `GET` | `/api/v1/indoor-map/{id?}` | token | yes | `api` |
| `GET` | `/api/v1/offline/manifest` | token | yes | `api` |
| `GET` | `/api/v1/quest-locations` | token | yes | `api` |
| `GET` | `/api/v1/quiz/continue/{attemptId}` | token | yes | `api` |
| `GET` | `/api/v1/quiz/start/{questionnaireId}` | token | yes | `api` |
| `GET` | `/api/v1/quiz/timer/{attemptId}` | token | yes | `api` |
| `GET` | `/api/v1/race/status` | token | yes | `api` |
| `POST` | `/api/v1/auth/login` | public | no | `auth` |
| `POST` | `/api/v1/auth/logout` | token | yes | `api` |
| `POST` | `/api/v1/qr/lookup` | token | yes | `api` |
| `POST` | `/api/v1/quest-locations/checkin` | token | yes | `api` |
| `POST` | `/api/v1/quest-locations/get-route` | token | yes | `api` |
| `POST` | `/api/v1/quiz/complete-game` | token | yes | `api` |
| `GET` | `/api/v1/quiz/assessments/{assessmentId}` | token | yes | `api` |
| `POST` | `/api/v1/quiz/assessments/{assessmentId}` | token | yes | `api` |
| `POST` | `/api/v1/quiz/assessments/{assessmentId}/verify-pin` | token | yes | `api` |
| `GET` | `/api/v1/quiz/attempts/{attemptId}` | token | yes | `api` |
| `POST` | `/api/v1/quiz/photo-answer` | token | yes | `api` |
| `POST` | `/api/v1/quiz/save-answer` | token | yes | `api` |
| `POST` | `/api/v1/quiz/submit` | token | yes | `api` |
| `POST` | `/api/v1/race/clue/{map}` | token | yes | `api` |
| `POST` | `/api/v1/race/start` | token | yes | `api` |
| `POST` | `/api/v1/tracking/position` | token | yes | `tracking` |

Rate limits: `api` 120/min per authenticated user · `tracking` 30/min per user ·
`kiosk` 300/min per IP · `auth` 5/min per account plus 60/min per IP.

They key on the **user**, not the address, so twenty phones on one venue WiFi get twenty
separate budgets. A 429 from us carries `Retry-After`; honour it.

## Request bodies that are easy to get wrong

| Endpoint | Required fields |
|---|---|
| `POST /api/v1/quiz/submit` | `attempt_id`, **`verification_photo`** (base64 string) |
| `POST /api/v1/quiz/save-answer` | `attempt_id`, `question_id`; `answer` optional and may be null — or, for `picture_puzzle` only, **`answers`** `{slot key: text}` with every box |
| `POST /api/v1/quiz/complete-game` | `attempt_id`, `question_id` |
| `POST /api/v1/quiz/photo-answer` | `attempt_id`, `question_id`, **`photo`** (base64 JPEG/PNG ≤ 6 MB, frame already drawn in) |
| `POST /api/v1/quiz/assessments/{id}` | **`facilitator_pin`**, **`facilitator_photo`** (base64 JPEG/PNG ≤ 3 MB), `additional_points` (0–`max_additional_points`), `penalty` (0–`max_penalty`); `notes` optional |
| `POST /api/v1/quiz/assessments/{id}/verify-pin` | `facilitator_pin` |
| `POST /api/v1/quest-locations/checkin` | `location_id`, `user_latitude`, `user_longitude` (note the `user_` prefix) |
| `POST /api/v1/tracking/position` | `latitude`, `longitude`; `accuracy`, `device_info` optional |
| `POST /api/v1/qr/lookup` | `qr_code` |

`verification_photo` is the one that catches people: it is **required**, so a client that treats
the photo as optional cannot submit at all.

## Quiz error keys

| `error` | HTTP | Meaning | What the app should do |
|---|---|---|---|
| `attempt_not_found` | 404 | No such attempt, or it belongs to another account | Stop; re-fetch the attempt list |
| `attempt_submitted` | 409 | Already submitted | Stop editing; show the result |
| `question_not_found` | 404 | Question is not part of this questionnaire | Refresh the questionnaire |
| `time_expired` | 403 | The clock ran out | Stop saving; call `submit` |
| `game_already_assessed` | 409 | A facilitator already scored this game | Move on |
| `not_a_game_question` | 422 | `complete-game` called on a normal question | Fix the call |
| `not_a_photo_question` | 422 | `photo-answer` called on a question that is not `group_photo` | Fix the call |
| `photo_answer_required` | 422 | `save-answer` called on a `group_photo` question | Use `photo-answer` |
| `slot_answers_required` | 422 | `answer` sent to a `picture_puzzle` question | Send `answers` {key: text} |
| `unknown_slot` | 422 | A key in `answers` is not one of the question's `slots[].key` | Re-fetch the question |
| `not_a_puzzle_question` | 422 | `answers` sent to a question that is not `picture_puzzle` | Fix the call |
| `invalid_photo` | 422 | `photo` is not JPEG/PNG, or is over 6 MB | Re-encode smaller; do not retry as is |
| `unknown_code` | 404 | `qr/lookup`: code is not in this event | Show it; let them rescan |
| `invalid_login_code` | 401 | `auth/login-code`: card unknown, replaced or revoked | Offer e-mail + password |
| `account_inactive` | 403 | Login: account switched off (e.g. card revoked) | Tell the team to see the crew |
| `race_not_started` | 403 | `qr/lookup` / `quiz/start`: START not scanned | Send the team to scan START |
| `checkin_required` | 403 | Not checked in at the questionnaire's post; `post {type,id,name}` | Outdoor: map at that post; indoor: wait for crew |
| `station_not_linked` | 403 | The admin linked no post to this questionnaire | Tell the crew |
| `game_ended` | 403 | Any call, login included: the session was archived | Game ended screen, wipe, never call again |
| `race_reset` | 409 | Quiz call naming an attempt/assessment wiped by an emergency race stop | Message, drop session state, dashboard |
| `team_locked` | 403 | `team/members` add or remove after setup | Remove the call; members are fixed |
| `already_checked_in` | 409 | `quest-locations/checkin` again at the same location; `last_checked_at` | Mark visited |
| `checkin_failed` | 500 | Check-in hit a server fault | Report it; no loop |
| `participant_taken` | 409 | `POST /team`: a picked participant is already in another team | Remove them; `team_name` in body |
| `directory_disabled` | 409 | `participants` search or a `participant_id` while the admin switched the list off | Use manual entry |
| `participant_not_found` | 404 | `POST /team`: unknown `participant_id` | Remove and search again |
| `duplicate_participants` | 422 | Same participant twice | Remove the duplicate |
| `one_leader_only` | 422 | More than one `is_leader: true` | Keep one Ketua |
| `questions_incomplete` | 409 | `quiz/submit` while questions are unfinished and time remains; `pending` | Stay in the session |
| `session_in_progress` | 409 | `qr/lookup` / `quiz/start` for another station while a session is live; `attempt_id` | Open that attempt |
| `attempt_not_active` | 403 | `quiz/continue` on an attempt that is not started | Clear it locally |
| `not_available` | 403 | `qr/lookup` or `quiz/start`: `reason` = inactive \| not_open_yet \| window_closed | Show the reason; inactive = ask the crew |
| `max_attempts_reached` | 403 | `qr/lookup` or `quiz/start`: no attempts left | Show it; stop |
| `scan_required` | 403 | `quiz/start` without a recorded lookup | Back to the scanner |
| `questionnaire_not_found` | 404 | `quiz/start`: no such active questionnaire | Back to the scanner |
| `too_many_starts` | 429 | `quiz/start` more than 10×/min; `retry_after` | Wait, never loop |
| `assessment_not_found` | 404 | No such assessment, or another team's | Stop; go back to the quiz |
| `additional_out_of_range` | 422 | `additional_points` above the game's own points | Clamp the input to `max_additional_points` |
| `penalty_out_of_range` | 422 | `penalty` above `max_penalty` | Clamp the input |
| `facilitator_pin_not_set` | 409 | The admin has not set a PIN for this event | Tell the facilitator to ask the admin; nothing the team can do |
| `invalid_facilitator_pin` | 403 | Wrong PIN; body has `attempts_left` | Show "Wrong PIN — N tries left" |
| `facilitator_pin_locked` | 429 | 5 wrong PINs; body has `retry_after` (s), plus `Retry-After` header | Disable the keypad, count down |
| `invalid_facilitator_photo` | 422 | Photo missing its image bytes, not JPEG/PNG, or > 3 MB | Re-capture, downscale |

Every one is a JSON body of the shape:

```json
{ "success": false, "message": "human text", "error": "attempt_submitted" }
```

## tracking/position

```json
POST /api/v1/tracking/position
{ "latitude": -6.2, "longitude": 106.8, "accuracy": 12.5, "device_info": "Pixel 8 / Android 15" }

200 { "success": true, "message": "Position updated successfully",
      "data": { "latitude": -6.2, "longitude": 106.8, "accuracy": 12.5, "timestamp": "…" } }
```

`latitude` and `longitude` are required and range-checked; `accuracy` (metres) and `device_info`
are optional. This is the same row the admin GPS tracking screen reads — there is no second
endpoint to call.

## Facilitator assessment

```
GET  /api/v1/quiz/assessments/{assessment_id}
200 { "success": true, "assessment": {
        "id": 12, "attempt_id": 499,
        "question": { "id": 15, "game_name": "Tug of war", "question": "…", "points": 100 },
        "max_additional_points": 100, "max_penalty": 100000,
        "facilitator_pin_required": true, "facilitator_pin_set": true, "photo_required": true,
        "is_assessed": false, "additional_points": null, "penalty": null,
        "notes": null, "assessed_at": null } }

POST /api/v1/quiz/assessments/{assessment_id}/verify-pin
     { "facilitator_pin": "4821" }
200 { "success": true, "verified": true }
403 { "success": false, "error": "invalid_facilitator_pin", "attempts_left": 4, "message": "…" }
429 { "success": false, "error": "facilitator_pin_locked", "retry_after": 873, "message": "…" }

POST /api/v1/quiz/assessments/{assessment_id}
     { "facilitator_pin": "4821", "facilitator_photo": "<base64 JPEG>",
       "additional_points": 90, "penalty": 15, "notes": "optional, ≤1000 chars" }
200 { "success": true, "assessment": { …as above, is_assessed: true… },
      "team_gain": 75, "team_points": 1075, "next": "continue" }
```

- **PIN.** One PIN per event, set by the admin. 5 wrong tries (verify-pin and POST count together)
  lock this account for 15 minutes — the right PIN is refused too while locked. `verify-pin` is
  only a pre-check; the POST verifies the PIN again, so send it with the score.
- **Photo.** Required; stored privately for the admin's audit view, never returned to the app.
- **One shot.** A second `POST` is `409 game_already_assessed`. Corrections are made by an admin on
  the website, which moves the team's points by the difference.
- **`team_gain` = `additional_points − penalty`** is exactly what `Team.points` moved by; it can be
  negative. The team's starting balance is never paid again.
- **`next`**: `continue` → more questions follow, go back to the quiz; `submit` → this was the last
  question, call `POST /quiz/submit`.

## Points, if you display a score

Show the team score from `GET /team` → `team.score` (build guide §2a). It counts submitted
questionnaires only: base + correct-answer points + (additional − penalty) of scored games, and the
game part can be negative.

Read earned points from what the server returns. Never sum question points client-side:
`fun_game` answers are stored correct with **zero** points and are scored later by a facilitator,
so summing them locally double-counts.
