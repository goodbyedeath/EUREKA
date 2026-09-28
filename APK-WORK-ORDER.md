# Work order — finish the player UI

For the agent that maintains `Eureka Client APK`. Not reference material: the list of what is
still open, with a test for each that decides whether it is done.

Fetch at `GET /api/v1/contract/guide/work`. Its hash is in `/api/v1/contract/version` → `guides`.

## This file does not state your build state — you do

The previous revision carried a table of "screens that exist" measured from the zip sent on
10 Sep. That was **v0.9**, four releases behind, and it listed `QuestMapScreen`, `IndoorMapScreen`
and the team screens as missing when they had shipped in v0.11–v0.13. It also assumed two files
where you built one: a single `TeamScreen.kt` covering setup and score.

The server cannot see your tree, so it will not describe it again. **If a task below is already
done, say so and skip it** — the way you did, which is what caught this. Report file names that
differ; the names here are suggestions, not requirements.

## Open (as you last reported, 10 Sep)

0. **Scan does not open the questions — BLOCKER, reported by the operator 14 Sep.** See §7. Every
   station depends on it, so it goes before everything below.
00. **Lock the question session** — operator rule 14 Sep, server-enforced. See §8. Build it together
   with §7: it is the same screen.
000. **Team setup once + score card** — operator 14 Sep. See §9.
0000. **Game ended screen** — the admin can now archive a finished session; its APKs must go silent. See §10.
00000. **Race reset handling** — the admin can emergency-stop the race for all teams. See §11.
000000. **Team setup: registered participants** — search the connected partner's list or type manually. See §12.
0000000. **Answers to #13 and #14, outposts and flags** — see §13.
00000000. **Station gate: START and check-in before a questionnaire** — see §14.
000000000. **Login cards (#16)** — the server side exists. See §15.
1. **The 3D Camera** — `ar/ArActivity.kt`. §9 rewritten into a full spec; one earlier answer reversed.
2. **Results screen**
3. **QR scanner** — was blocked on a server test. It is not any more; see §3.
4. **Offline write queue** — from your report; added as §4.
5. **Version header** — one header, and the patching system finally has its safety net. See §5.
6. **Facilitator scoring screen** — new server endpoints, 14 Sep; now with a facilitator PIN and a
   silent front-camera photo, both required by the server. Without it a `fun_game` station never
   pays the team. See §6.

Already closed per your report and not repeated here: team setup, team score, outdoor map,
indoor map, the keystore `.gitignore`, and removing Capacitor.

---

## 1 — The 3D Camera  ·  `ar/ArActivity.kt`  ·  **do this first**

**§9 of `APK-BUILD-GUIDE.md` has been rewritten into a full specification of this feature. Read it
before touching the code — one earlier answer in it was reversed, and building to the old one wastes
work.**

What changed since you last read it:

| | |
|---|---|
| **No QR, no Start gate, no calibration** | **This reverses what §9 previously said.** The camera opens straight into the scene. Objects carry no real-world anchor, so scene rotation is cosmetic and the gate bought nothing. |
| **No plane hit-test** | Objects sit above eye level, not on the ground. A plane requirement blocks an outpost outright on dark ground. |
| **The crosshair is the whole interaction** | 44 dp circle at screen centre, green `#4ade80` when something is aimed at. Aim-then-tap: a tap anywhere opens whatever the crosshair is on; an idle crosshair does nothing, silently. |
| **Three sheet shapes, chosen by `media_type`** | text · text + image · text + url. Never infer from which fields are non-null. |
| **The whole screen is specified** | top bar with the found counter, hint line, direction guide, Re-centre — each with its offset, colour and behaviour. The direction guide is the one most often skipped and the one players need most. |
| **A state machine for opening it** | CHECKING → WAITING_UNLOCK / NEED_LOCATION / OUT_OF_RANGE → PREPARING → SCENE_LIVE → SHEET_OPEN. Indoor and outdoor differ only in which gate fires; `access_mode` says which. |
| **Edge cases** | including the one with no server answer: leaving the radius mid-session. Hysteresis, not an instant close. |

Fetch it: `GET /api/v1/contract/guide/build` — §9.

**Done when:** pointing the phone at an object turns the crosshair green before any tap; tapping
with it white does nothing at all; and each of the three sheet shapes renders from a real object.

## 2 — Results screen

Shown after `POST /quiz/submit`. Display what the server returned — score, earned points — and
then refresh `GET /team` so the running total agrees. Do not compute anything locally.

Results is reached only through the Finish screen (§8): after every question is done, or after the
clock runs out. `submit` is refused with `409 questions_incomplete` before that, and always
accepted after time-out — so on `time_expired`, stop saving and go to Finish; never block it.

`verification_photo` is **required** on submit, base64. There is no server-side size cap, so the
client decides — downscale before encoding, because this upload happens with a team waiting.

**Done when:** an attempt whose clock ran out mid-question still submits and still scores.

---

## 3 — QR scanner: server side tested, here is the answer

You were waiting on this. **The server lookup works.** Tested against every code in the live
database:

| Input | Result |
|---|---|
| 8 active questionnaire codes | `200` — all resolve |
| 7 switched-off codes | `403 not_available` |
| nonsense string | `404 unknown_code` |
| empty | `422` validation |

**So if the app scans an active code and does not get a 200, the fault is camera-side, not the
server.** Your v0.12 frame counter and last-decoded value should settle it: frames climbing but
no decode = the camera never resolved the code; a decode that then fails = send us the exact
string you decoded.

Two changes came out of this test, both live now:

- **A switched-off station no longer looks like a bad scan.** `is_active` was filtered inside the
  lookup query, so a questionnaire the crew had not enabled fell through to `unknown_code` —
  identical to a misread QR. It now returns `403 not_available` with `reason: "inactive"` and a
  message telling the player to ask the crew. On event day those two need completely different
  responses, and the app could not tell them apart.
- **Questionnaire results now carry `type: "questionnaire"`.** Race starts already returned a
  `type`; questionnaires did not, so you had to infer the kind from which key happened to be
  present. Both shapes now announce themselves.

Success shape: `{ success, type, questionnaire { id, title, description, time_limit } }`
Race start: `{ success, type: "race_start", … }`

---

## 4 — Offline write queue

Your report: submit without signal fails immediately. Nothing on the server blocks this.

Queue `save-answer` and `checkin` locally and replay with backoff. Two rules that matter more than
the queue itself:

- **Drop a queued item on any 4xx.** A rejected answer will be rejected again; retrying it forever
  turns one bad request into a permanent loop.
- **Keep it on 5xx and on transport failure**, with backoff, and honour `Retry-After` on a 429.

`submit` is the one that must never be lost: it always succeeds server-side, even after the clock
expires, so a queued submit stays valid. Prefer failing the *save* and letting the submit carry
the answers over losing the attempt.

---

## 5 — Send your version, and read ours

The APK is a shell: branding, feature flags, questionnaires, outposts, indoor plans and AR scenes
all arrive from the server, so most changes reach players **without a new build**. What was
missing is the other half — the server had no idea which build was talking to it, so a phone
running an old APK met a changed contract and failed in the field with nothing useful to say.

**Send `X-App-Version: <versionCode>` on every authenticated request.** An integer, the same one
in `build.gradle`. That single header is what turns a silent mid-quiz failure into a clear
"update the app" before anything starts.

```
GET /api/v1/app/release          (public, no token)
{ latest_version, latest_name, minimum_version, download_url, notes }
```

Ask it at launch:

- `versionCode < minimum_version` → stop, show the download. The API will refuse you anyway with
  **426 `update_required`**, carrying `your_version`, `minimum_version` and `download_url`.
- `versionCode < latest_version` → carry on, but offer the update.
- `minimum_version: 0` → no gate is armed. That is today's setting.

**When the gate is armed, the header is required — no header is treated as too old.** An earlier
draft let headerless requests through to protect installed builds, but there are none: the app
reaches only your team, and a higher versionCode simply supersedes the last. Protecting a
population that does not exist would have cost a permanent bypass, since anything armed could then
be skipped by omitting the header.

The gate is off today (`minimum_version: 0`), so nothing is refused. Add the header now and it
costs you nothing; leave it out and the day someone arms the gate, every request fails at once.

Handle 426 anywhere, not just at launch. The minimum can be raised between one request and the
next — that is the point of it.

---

### Report your releases

`latest_version` was stale by four builds within a day, because this server cannot see your Drive
folder. You reported it; now you own it.

```
POST /api/v1/app/release
{ "version_code": 18, "version_name": "0.18", "download_url": "…"?, "notes": "…"? }
```

Post it as part of publishing. Monotonic: a code at or below the highest recorded is refused with
`409 not_newer`, so releases only ever move up.

**`minimum_version` is not settable from here, and will not be.** A remotely settable minimum is
one request away from locking every team out of a live event; it stays in server config where only
the operator reaches it. You report what exists, the operator decides what is required.

Your builds 14–17 are already recorded from your report. `/app/release` now answers
`latest_version: 17`.

## 6 — Facilitator scoring screen  ·  new endpoints

The operator decided the facilitator scores on the **team's phone**. Until now that form existed
only as a web page, so from the app a `fun_game` was completed but never scored, and the team
earned nothing for it. The server side also paid wrongly (it re-credited the team's starting
balance on every game); that is fixed, so build against the new numbers.

Spec: `APK-BUILD-GUIDE.md` §4 → *The facilitator scores on the team's phone* (flow, inputs,
resume, errors). Shapes: `API-V1-CONTRACT.md` → *Facilitator assessment*.

In short: `complete-game` → `assessment_id` → `GET /quiz/assessments/{id}` → hand-over step →
**PIN gate** (`verify-pin`, lockout countdown) → arm the front camera silently → inputs clamped to
`max_additional_points` / `max_penalty` → Confirm = **silent capture** → `POST` with PIN + photo +
score → show `team_gain` → follow `next` (`continue` | `submit`). Ignore `redirect`.

The PIN and the photo are server-enforced: a POST without either is 422, a wrong PIN is 403, five
wrong PINs lock the account for 15 minutes (429). The admin sets the PIN on the website; until they
do, every score is 409 `facilitator_pin_not_set` — show that message, it is not an app bug.

**Done when:**
- scoring 90 with penalty 15 shows "Team gains 75", and after hand-in `GET /team` → `score.total` is
  exactly 75 higher;
- a wrong PIN shows the tries left, and the sixth attempt shows a countdown instead of the keypad;
- the admin page shows the facilitator's photo next to that score, and nothing on the phone showed
  a preview or played a sound when it was taken;
- after scoring, the AR camera and the QR scanner still open normally (the front camera was released);
- killing the app on the scoring screen then reopening it lands back on the same screen.
---

## 7 — Scan opens the questions screen  ·  **blocker, do first**

The operator scanned a questionnaire QR and no questions appeared. The server was tested the same
day against all 8 active codes: lookup 200, `/quiz/start` 200 with questions, every image loads.
So the fault is in the app between the scan and the screen.

Spec: `APK-BUILD-GUIDE.md` §4 → *From scan to the questions screen* — the call sequence, the
verified 200 body, and a render row for every question type.

The most likely cause, from the data: **every active station is a single `fun_game` question.**
A screen that renders only answerable types, or that treats `fun_game` as "nothing to show",
is blank at every station. Other candidates worth checking: parsing `answers` as a list (it is an
object), expecting snake_case `time_remaining` (it is `timeRemaining`), or navigating on
`questionnaire` from a `race_start` lookup where it is absent.

Also new: `image_urls` (absolute) next to `images` on every question — use it; and `/quiz/start`
refusals that had no `error` key now carry one: `questionnaire_not_found`, `max_attempts_reached`,
`too_many_starts` (with `retry_after`).

**Done when:**
- scanning the Pos Merah code (`98c93a89-6fe3-4b65-9c31-2701537e416b`) shows *Field Bomb (Pos
  Merah)* — heading, full description with line breaks, the picture, and a Complete button — with
  no tap between the scan and that screen;
- killing the app on that screen and reopening it shows the same attempt, not a new one;
- scanning a switched-off station shows "ask the crew", not a blank screen or "no signal".

If it still fails, send `kind: "bug"` with the lookup status + body and the start status + body.

---

## 8 — Lock the question session  ·  server-enforced since 14 Sep

Operator: *"Submit quiz must not appear during the questions — the team would go back to the
dashboard, scan again and the timer resets. The only way out of a question session is finishing
every question. If it is a game, they cannot leave it until the facilitator has scored it."*
And: when the time runs out, the session ends and whatever is unfinished scores 0. `brief` is
optional.

Spec: `APK-BUILD-GUIDE.md` §4 → *The question session is a locked room*. The server already
refuses every way around it (`questions_incomplete`, `session_in_progress`, `time_expired` on
scoring after time-out), and `completion` on start / continue / scoring tells you where you are.

**Done when:**
- on the Pos Merah game there is no Submit button, Android back does nothing but show the hint,
  and the dashboard and scanner cannot be reached;
- after Complete, the screen stays on waiting-for-facilitator until the score is saved, then goes
  straight to Finish (photo → submit → Results);
- swiping the app away mid-session and reopening it lands back inside the same attempt with the
  timer where the server says, not restarted;
- letting the timer reach 0 closes the inputs and goes to Finish, and submit succeeds.

---

## 9 — Team setup once, and the team score card  ·  operator 14 Sep

Two operator requests:

1. *"Team member setup in the APK happens only once, at the first login. After that it cannot be
   done again."* Server: `POST /team/members` and `DELETE /team/members/{id}` are now always
   `403 team_locked`.
2. *"Show the team's current score on the dashboard — the team has a right to know what it has
   achieved."* Same logic as `/admin/user-progress`. Server: `GET /team` → `team.score`; the
   operator chose **total + breakdown, no rank**, so `rank` and `total_teams` were removed.

Spec: `APK-BUILD-GUIDE.md` §2a.

**Done when:**
- a fresh account sees Team setup straight after login and cannot leave it; after saving, no screen
  offers add / edit / remove member, and relaunching never shows setup again;
- the dashboard shows the total and the three lines exactly as `team.score` returns them, a negative
  game line keeps its minus sign, and no rank appears;
- after a game scored 90 − 15 and hand-in, the card total is 75 higher than before.

### Answers to your report #11

1. **Do not auto-submit from the scoring screen — you are right.** The facilitator holds the phone,
   so the verification photo would show them. After scoring with `next = submit`: "Serahkan HP
   kembali ke tim" → the Finish screen (§8) → team photo → submit. But the Finish screen is the only
   exit: your v28 "submit on the last page" must appear only when `completion.can_submit` is true.
2. **Show `team_gain`, not `team_points` — confirmed.** The dashboard card reads `team.score` from
   `GET /team` (§2a). Note it moves after hand-in, not at scoring.

---

## 10 — Game ended: go silent  ·  operator 14 Sep

Operator: *"After a session is archived the APK can no longer be used, with a notice that the game
is over and to uninstall the app — for the security of the running system and our network."*

Server: archiving removes every account of that session. Their tokens and logins now get
`403 game_ended` with the message to show.

Spec: `APK-BUILD-GUIDE.md` §2b.

**Done when:**
- after the admin archives, the next action in the app — or a relaunch — shows Game ended with the
  server's message and the session name;
- relaunching in airplane mode still shows it (the flag is read before any network call);
- after that screen the device makes no request at all: no tracking, no queue replay, no polling
  (the server side will check its logs);
- Uninstall opens the system uninstall dialog;
- a login attempt with an archived account also lands on Game ended, not "wrong password".

---

## 11 — Emergency race stop: `race_reset`  ·  operator 14 Sep

Operator: an **emergency stop** button that halts the race time and resets points, visited posts and
answered questions for every team in the session. Teams, accounts and tokens remain; teams start
again by scanning START.

Server: quiz calls naming a wiped attempt or assessment return `409 race_reset` with the message to
show; `GET /team` returns base points; `GET /race/status` returns `race: null`.

Spec: `APK-BUILD-GUIDE.md` §2c.

**Done when:**
- a team in the middle of a question session (or on the facilitator scoring screen) sees the
  message right after the admin presses stop — on their next action — and lands on the dashboard,
  still logged in, score back to base;
- killing and reopening the app after a stop does not reopen the old session;
- the dashboard shows "Scan START", and scanning it starts a fresh clock from zero;
- nothing from the old attempt is replayed from the offline queue.

---

## 12 — Team setup: pick registered participants, or type them  ·  operator 15 Sep

When an event is run with a partner, the server holds that partner's participant list. On the Team setup
screen each member is added either from that list (search by name or e-mail) or manually, as now. Each
member can be made Ketua.

**Between events there is no partner, and that is the normal state** — `GET /participants` then answers
`409 directory_disabled` and `POST /team` refuses a `participant_id` the same way. FEKDI x IFSE was the
first partner (24–27 Sep) and its keys were removed on 26 Sep, so the directory is off today. Nothing in
the API changed with it: **manual entry is the path that always works**, and the app must treat the
directory as a feature that may be absent rather than one that was lost.

Server: `GET /team` 404 carries `participant_directory`; `GET /participants?q=`; `POST /team` members
accept `participant_id` and `is_leader`. The points each registered participant earns are sent to the
client's leaderboard by the server — nothing for the app to do there.

Spec: `APK-BUILD-GUIDE.md` §2a → *Team setup happens exactly once*.

**Done when:**
- with the list on, typing two letters of a participant's name shows them with a masked e-mail, and
  picking them adds a member whose name you did not type;
- someone already in another team shows greyed with that team's name and cannot be picked;
- a team of two picked + one typed member, with the second as Ketua, saves in one call, and GET /team
  shows exactly that leader;
- with the list off, the "Sudah terdaftar" option is not shown and manual setup works unchanged.

---

## 13 — Answers to your reports #13 and #14, and two alignments  ·  15 Sep

### #14 — offline facilitator scores: approved, with a server rule

The operator confirmed the request. Keep v30's design (PIN verified online first, only the scoring POST
queued after a transport failure, Keystore-sealed, deleted on delivery / 4xx / game_ended). Add
`race_reset` to the delete list.

Your server question: **no `scored_at`** — a device clock is not evidence. Instead the server now records
`pin_verified_at` when `verify-pin` succeeds, and accepts a score that arrives after time-out when that
moment was before the deadline and the POST lands within **15 minutes** of it (`accepted_late: true`).
Later than that: `403 time_expired`, game 0. Build guide §4 → *Silent facilitator photo*.

### #13 — resuming via `/quiz/start` instead of `/quiz/continue`

Fine for a normal resume. **One case breaks:** after an emergency race stop (§11) the wiped attempt no
longer exists and its scan is gone, so `/quiz/start` answers `403 scan_required` — not `race_reset`. Either
resume with `GET /quiz/continue/{attempt_id}` (you have the id from start), or treat `scan_required` on a
**stored** session as "that session is over": drop it and go to the dashboard.

### Outposts (Quest Locations)

Locations now carry absolute `image_url` / `map_image_url`; a repeat check-in answers `409
already_checked_in` instead of a bare 200 failure; `checkin_failed` is a 500. `what_to_do` is the team's
instruction at the spot — show it. `quest_points` is not awarded — do not show it as points. Build guide §5.

### Feature flags

Only `quiz_system`, `quest_locations` and `gps_tracking` affect the app; the rest are web/kiosk/server.
Note `gps_tracking` is currently **off**, so background position sending must not run. Build guide §7.

**Done when:** a score queued with no signal and delivered 5 minutes after time-out shows as accepted;
one delivered 20 minutes after is dropped with the time-out message; a stored session wiped by a race
stop does not reopen; the outpost sheet shows `what_to_do` and its picture; a second check-in shows
"already checked in"; with `gps_tracking` off no position is sent.

---

## 14 — Station gate: START, then check-in, then the questionnaire  ·  operator 15 Sep

Operator's field test: after an emergency reset, teams scanned a questionnaire QR without scanning
START or reaching the post, and answered it. Fixed on the server: a questionnaire is linked to one post,
and opens only after START plus a check-in there (outdoor: GPS check-in; indoor: the crew opens the
post on Outpost Access). Unlinked questionnaires cannot be scanned.

New `403` refusals on `qr/lookup` and on `quiz/start` for a new attempt: `race_not_started`,
`checkin_required` (with `post {type, id, name}`), `station_not_linked`. Spec: build guide §4 →
*From scan to the questions screen*.

**Done when:**
- scanning a questionnaire before START shows "Scan QR START dulu" and offers the scanner;
- after START, scanning an outdoor questionnaire before checking in shows the post name and opens the
  map at that post; after the check-in the same scan opens the questions;
- an indoor questionnaire shows "wait for the crew" until the crew opens the post;
- none of these refusals is shown as "no signal" or queued for retry.

Your #16 (team accounts + QR login cards) is approved by the operator and is being built next; its
contract entry will follow.

---

## 15 — Login cards: your #16 is built  ·  15 Sep

As proposed, with these specifics: `POST /api/v1/auth/login-code {code, device_name}` returns exactly the
login body; the QR payload is `EUREKA-LOGIN:<code>` (32 url-safe chars, 192 bits, stored hashed); a wrong,
replaced or revoked card is `401 invalid_login_code`; reusable per card; `qr/lookup` answers
`404 unknown_code` for a login payload; archiving tombstones the codes, so an old card gets `403
game_ended`. Rate limit: 60/min per address (a room of teams on one WiFi). Admin: "Kartu Login Tim" —
generate N accounts (name prefix, access hours), print PDF cards, "Ganti kartu" (new code + password,
all tokens revoked), "Cabut" (account off, logged out).

Spec: build guide §2d.

**Done when:** scanning a printed card on the login screen lands on team setup; a card the admin
replaced shows the invalid-card message and the e-mail/password form; any non-card QR on that scanner
is rejected locally.

---

## 16 — Clue opens the floor plan, outdoor map basemap, AR facing  ·  15 Sep

Answers to your #19, #20 and #21, and one AR change. Spec: build guide §8, §9 and §10.

**#21 — a correct clue opens the posts.** Server-side, nothing to fake. A first correct
`POST /race/clue/{map}` opens every post on that plan for the team, and `is_open` turns true. The crew
can still close one, and the clue does not reopen it. No new field. **#19** — no further server gate
on the clue.

**#20 — outdoor map.**
1. Tiles: the new `map` block on `/offline/manifest` (`tiles`, `attribution`, `max_zoom`). Pre-download
   `bounds` on Sync and read the cache first.
2. Points: `GET /quest-locations` only: marker colour, radius circle, `checked_in`, the station-gate
   post focused, and the team's own position from the device.
3. Placement: on the dashboard during an outdoor race, as the operator asked — a compact map above
   the score card that opens full-screen on tap, with the list still reachable.
4. Never show other teams' positions or routes; the admin tracking map is crew-only.

**AR rotation order is now Y·X·Z** (yaw outermost) and spin adds to `y`, so a coin stood up with
`x: 90` spins like a top. Every object placed so far has `x = z = 0`, where nothing moves. Your #18
notes (formulas are the spec, far plane) are in §9.

**Done when:**
- answering the indoor clue correctly shows every post on the plan as open within one re-read;
- a post the crew then closes shows closed;
- the outdoor dashboard map draws its tiles from `map.tiles`, shows the attribution and still shows
  the venue with signal off after a Sync;
- an object with `rotation {x: 90, y: 0, z: 0}` and a spin motion turns about the vertical.

---

## 17 — The route line on the outdoor map  ·  16 Sep

The operator draws routes in a separate tracker app. EUREKA now copies them in and serves them
itself; nothing on your side talks to that app, and its host is not yours to call. Spec: build
guide §10.

- `GET /api/v1/map/routes` → `{ success, routes }`, and the same list under `routes` in
  `/offline/manifest`, so a synced team draws the route with no signal.
- Draw `points` (`[lng, lat]`, ordered, pre-simplified) as one polyline in `color`, under the
  outpost pins. `markers` are signposts only — no radius, no check-in, not tappable for points.
- Posts stay `/quest-locations` exactly as before. `quest_location_ids` says which of them sit on
  this route; a post is never repeated in `markers`.
- An empty `routes` list is normal (no route switched on): draw the outposts alone.

**Done when:** the outdoor map shows the line with the posts on it, after a Sync it still draws
with mobile data off, and no request is made to any host other than questerra-series.com.

---

## 18 — History points, and the dashboard question  ·  16 Sep

**Your #23 — done, server-side.** `GET /quiz/attempts` rows now carry `earned_points`,
`assessment_points` and `points` for every `completed` attempt (null otherwise), computed with
`PointsCalculationService`. So `base_points + Σ points = team.score.total`, and the per-row
subtraction you were doing can go. Spec: build guide §11.

**And the question in it:** no, listing `Auth::id()` alone was not intended. The endpoint now lists
every account on the team, with `by` naming who did each row. One account per team (the printed
cards) sees no change.

**Your #24 — no such rule.** Flags are display-only; a locked button with a reason beats a hidden
one, and the server gates every screen regardless. Nothing on the web or kiosk side requires a
flagged feature to be invisible to teams. Note that the sixteen switches left over from the retired
web dashboard were deleted on 16 Sep: read `/features` as a list, treat a missing key as "not
applicable", and never hardcode the set.

**Also changed, and it touches your score card:** teams now start at **0 points**, not 1000
(operator, 16 Sep). `base_points` is 0 for every new team. Keep rendering the row from the field —
an event can be set up with a balance again.

**Done when:** a history row shows the number the server sends in `points`, the rows sum to the
score card, and an unfinished row shows no points rather than 0.

---

## 19 — A floor plan per team  ·  16 Sep

The crew now assigns an indoor plan to each team in Team Management, because an indoor event runs
several teams on different routes at once. A team with no assignment keeps using the plan on the
START code.

**Nothing to build if you already do this:** read `race.indoor_map_id` from `/race/status` and
request that id. The session records the team's plan at START, so the id you get is already the
right one. `GET /indoor-map` without an id also returns the team's own plan.

**One thing to check:** a plan id that is not the team's now answers **404**, where before every
active plan was readable. If anything in the app remembers a plan id across races, or picks the
first plan from a list, drop it and use the race's id.

Spec: build guide §8. Also in §8: `is_post` on a spot — a marker with no outpost is a label, not a
locked post, and must not be drawn with a lock.

**Done when:** two teams with different plans each see their own after scanning the same START
code, and a stale plan id shows the 'ask the crew' path rather than someone else's map.

---

## 20 — Participant search sends no photo  ·  19 Sep

`GET /participants` rows now carry `avatar: null`, always. The app shows no participant photos
(operator), so the server stopped sending the Google avatar URLs — long signed links to personal
pictures that no screen used. The key stays so nothing that reads it breaks.

**Done when:** the team-setup search shows names without an image slot, and nothing requests an
avatar. If the app never read `avatar`, there is nothing to change.

---

## 21 — The app is called Questerra  ·  20 Sep

Operator: rename the app from EUREKA to Questerra. The server side is done — window titles, the
PWA manifest, logo alt text, the landing and login pages, the kiosk popups and the PDF report all
say Questerra, and the name still comes from the brand row, so an event can override it.

On your side:
- the launcher label and the name shown in Android's app list and share sheets,
- any string in the app that spells EUREKA at a person, including splash, about and error copy,
- the `User-Agent` on your own downloads (`EUREKA-Android/<version>` → `Questerra-Android/<version>`).

**Do not rename:** the login-card QR prefix `EUREKA-LOGIN:` — those cards are printed and in the
crew's hands; the scanner must keep accepting exactly that string. The applicationId / package
name is yours to judge: changing it makes an upgrade a fresh install, which loses the token and
every cached download, so keep it unless you plan a clean re-install for everyone.

**Done when:** the launcher and about screen say Questerra, a printed card still scans, and an
update over an installed build keeps its session.

---

## 22 — Panduan comes from the server  ·  20 Sep

The operator has written the player's how-to in plain Indonesian — logging in with a card, team
setup, scanning START, the opening clue, finding posts, answering, facilitator-scored games, the
3D camera, the team score, losing signal, and what to do when something looks stuck. It lives at
/admin/panduan/user, where the crew edits it between events.

Render your Panduan page from `GET /api/v1/guide/user`: `sections[]` of `{ icon, title, lines[] }`,
already ordered, sentences already split, no markup. The same list is in `/offline/manifest` as
`user_guide`, so the page works after a Sync with no signal. Cache on `updated_at`.

Spec: build guide §11. Nothing else changes; this replaces any how-to text hardcoded in the app.

**Done when:** Panduan shows the server's sections in order, still opens offline after a Sync, and
an edit made on the website appears on the next open.

---

## 23 — Foto bersama: a photo question  ·  20 Sep

Operator, 20 Sep: "Tambahkan fitur question type: foto bersama. Peserta harus foto bersama,
tambahkan kemampuan untuk menambah frame foto (admin upload manual), lalu upload ke sosial media
mereka." The admin side is built — the frame is uploaded per question — and the API is live.

A question of type `group_photo` arrives like any other in the questions list, with two extra
fields: `frame_url` (a PNG with a transparent middle, or null) and `share_caption` (or null).

The screen:

1. Show `question` and `description` as the instruction, then a camera preview with `frame_url`
   drawn over it at the frame's own aspect ratio, so the team frames the shot as it will be
   published. Front and rear camera both; a group photo is usually the rear one on a tripod or a
   crew member's hands, so do not force selfie mode.
2. Capture, then **compose**: the finished image is the photo with the frame burned in, scaled to
   about 1080 on the long edge, JPEG quality ~85. That is the file the team will post, so what they
   see before sharing must be exactly what was uploaded.
3. Upload with `POST /api/v1/quiz/photo-answer {attempt_id, question_id, photo}` (base64, ≤ 6 MB).
   The reply carries `photo_url`, `share_caption` and `points_earned`.
4. Offer **Bagikan** — Android's share sheet with the composed JPEG and `share_caption` pre-filled —
   and **Ulangi** to retake. Sharing is the team's own action through whatever app they choose;
   there is no social login and the server posts nothing.
5. Retaking posts again with the same ids. The server replaces the file and the points count once.

What will bite you:

- `save-answer` on this question is refused with `photo_answer_required` (422). There is no text
  answer, and the question does not belong in the normal answer loop.
- The session will not submit until the photo is in: `completion.pending` carries
  `reason: "photo_not_taken"`. Word it plainly — "Belum ada foto bersama" — not "lengkapi jawaban".
- The clock applies. After `time_expired` the photo is refused like any other write, so put the
  camera in the question flow, not on the submit screen.
- `invalid_photo` (422) means not a JPEG/PNG or over 6 MB decoded. Do not resend the same bytes;
  re-encode smaller. A raw sensor frame will fail this.
- `frame_url` can be null. Then there is no overlay and the photo is the plain capture — still a
  valid answer, still worth the points. Never block the team on a missing frame.
- The frame is listed in `/offline/manifest` under `images`: precache it on Sync, compose offline,
  and queue the upload in the offline write queue (§4). Queue the **composed** JPEG, show the photo
  as taken, and let the sharing happen immediately — it does not need the server.

**Done when:** a `group_photo` question opens a framed camera, the composed photo uploads and scores,
the share sheet offers that exact image with the caption, a retake replaces it, the session refuses
to submit before the photo, and all of it works with the phone in airplane mode after a Sync.

Spec: build guide §4 (`group_photo` — foto bersama); contract: `POST /api/v1/quiz/photo-answer`.
---

## 24 — Two from the operator's test, both on your side  ·  20 Sep

### 24a — Objects placed in the 3D editor land in the wrong place in the app

**Closed by your #28 (v42), 21 Sep.** None of our four suspects: the anchor was built from the whole
ARCore camera pose, so the scene tipped by however the phone was held when the camera opened. It
now keeps only the heading and drops pitch and roll. That was a fifth cause we did not list — and our
own guide had said "anchor to the camera pose", which is what invites it. Build guide §9 now says
*levelled heading* in both places. The rest of this section is kept as the record of what was ruled
out.

Operator: "algoritma 3D Camera yang di setting via Editor 3D, ketika di buka di APK, 3D object nya
kacau lokasinya." We re-checked the server before sending this, because it would be the obvious
suspect. It is not: `position` is derived from the authored bearing/pitch/distance as
`x = d·cos(pitch)·sin(yaw)`, `y = d·sin(pitch)`, `z = −d·cos(pitch)·cos(yaw)`, and a round-trip back
to angles agrees to **1.3 mm at the very worst** (50 m out, 89° up — the only loss is rounding to
4 decimals). The same numbers drive our own web preview, where the placement is correct. So the
fault is in how the app consumes them.

The four things that produce exactly this symptom:

1. **Handedness.** `position` is right-handed, camera-local, **−z forward**, +x right, +y up,
   metres. If your scene graph is left-handed (or you feed these into a library that is), z is
   mirrored and every object lands behind you or on the wrong side. Negate z at the boundary, once,
   and nowhere else.
2. **Re-deriving the anchor.** Never recompute a position from lat/long, and never re-derive the
   bearing from the compass. The admin authored these standing at the outpost; the server's maths
   is the authority and a magnetometer reading will not agree with it. Anchor the scene to the
   camera pose **at Start** and place the objects from there, as given.
3. **Rotation order.** `'YXZ'` — `R = Ry(y)·Rx(x)·Rz(z)`, yaw outermost, degrees, and spin adds to
   `y`. If your engine composes XYZ, a tilted object rotates inside its own plane instead of
   turning like a top. Build the quaternion in this order yourself if you cannot set it.
4. **The far plane.** `distance` reaches 50 m; several defaults stop drawing at 30 and the object
   is not misplaced but absent.

Guide §9 has all of this. **If, after those four, the placement is still wrong, send us the failing
case through `POST /api/v1/contract/feedback`** — the location id, one object's `position`,
`rotation` and `distance` as you received them, and where it actually appeared relative to the
camera at Start. That is a report we can act on; "kacau" alone we cannot.

### 24b — The login-card scanner should look like the QR scanner

Operator: "Scan QR Login tampilannya di buat seperti scan QR Code." Same camera, same framing, same
reticle and torch control as the outpost scanner — the crew teaches one gesture at the start line
and it has to be the same gesture on the login screen. Nothing changes on the wire:
`EUREKA-LOGIN:<code>` still goes to `POST /api/v1/auth/login-code` (§15), and that prefix stays as
it is because the cards are already printed.

**Done when:** both scanners are visibly the same screen with different copy, and a printed card
scans from the login screen as it does now.
---

## 25 — After an emergency stop, throw the cached world away  ·  20 Sep

**Closed by your #29 (v43), 21 Sep.** Built as specified, and better: the first-sight guard (a stamp
seen with nothing stored only counts if the held race began before it) is now part of build guide
§2c, so it stays a rule rather than an implementation detail. The Kamera 3D list now follows what a
team can actually enter, which is the other half of what the operator reported.

Operator, 20 Sep: after an emergency stop, a team's 3D camera page **still listed posts as open**,
although entering one was correctly refused.

We checked the server before writing this, by running a real emergency stop inside a transaction and
asking the API what it then says. It is consistent: no race clocks, no opened posts, `/indoor-map`
reports every spot `is_open: false`, and `/ar/locations/{id}` answers `403 awaiting_unlock`. The
refusal you already honour is what kept entry locked. The list is stale on your side — painted from
a Sync taken before the stop, with nothing to tell it otherwise.

So we have given you the something:

**`race_reset_at`** — ISO 8601, or null if the race has never been stopped — is now on
`GET /race/status` **and** `GET /offline/manifest`, sent whether or not the team has a session.

- Store it next to your cache when you Sync.
- Compare it on every `/race/status` poll. **Newer than what you stored means the world was wiped.**
- `race: null` while you are holding a running clock means the same thing.

On either signal, drop every per-team thing you cached — opened posts and `is_open`, the clue's
solved state, attempts and answers, any queued offline writes naming them, check-ins, the team score
— re-fetch, and send the team to "Scan START". Keep the token, the team, and everything the admin
authored; a stop does not touch those.

Do not infer a reset from a single 4xx, and do not wipe on a failed poll: a team out of signal must
keep playing. Only a `race_reset_at` that actually moved, or `race: null` against a clock you hold,
means the race was stopped.

**Done when:** an emergency stop mid-race leaves no post showing as open on any screen — the floor
plan, the 3D camera list and the dashboard all agree within one poll — and a team with no signal is
unaffected until it reconnects.

Spec: build guide §2c (`race_reset_at`).
---

## 26 — Tebak Gambar: one picture, several answer boxes  ·  21 Sep

Operator, 21 Sep: a question type where the team looks at one picture and fills several boxes — a
crossword with five across and five down (ten boxes), or a sheet of five company logos (five boxes).
The admin side and the API are live; this is your screen.

1. **The picture, full-screen with pinch-zoom.** `image_urls[0]`. Non-negotiable: a crossword on a
   phone cannot be read at page width.
2. **One box per `slots[]` entry, in order**, captioned with `label`; when `length` is set, show
   "5 huruf" and cap the box to it.
3. **Save the whole set** on `save-answer` with `answers: {key: text}` — debounce while typing, the
   same budget as a text question — and put it in the offline write queue like any other answer.
   Send what was typed; the server does the case/space/punctuation folding.
4. **Never show right or wrong while the session is open.** The save reply does not say, on purpose.
5. **After submit**, tick or cross each box from `result.puzzles[].slots[].correct`, and show
   `correct / total` and `points_earned`. The right answers are never sent — do not look for them.
6. **Resume** reads this question's answer as an object from `answers[question_id]`.

What will bite you: an empty box is fine and scores 0, and **one** filled box is enough to clear the
completion gate — do not require every box before Submit. `unknown_slot` means the admin edited the
boxes under you: re-fetch the question, keep what the team typed in the boxes whose keys survived.

**Done when:** a crossword picture can be zoomed and read on a phone, ten boxes save and survive a
restart and airplane mode, nothing says right or wrong until Submit, and after Submit each box is
ticked or crossed with the partial points shown.

Spec: build guide §4 (`picture_puzzle` — Tebak Gambar).

---

## 27 — Answers to your #29  ·  22 Sep

**Pengarahan — removed for good, on both sides.** The operator confirms it: there is no per-team
briefing. The server has removed `GET /guidance` and `/guidance/{id}` (both now 404), the model and the
unreachable editor. Delete the wrapper you kept; nothing replaces it, and there is nothing to bring
back. Panduan (`/guide/user`) is unaffected.

**The per-account login lockout — no, operator's decision, 22 Sep.** There will be no server-side
lockout. Two reasons. It would add nothing: card passwords are 10 random characters (~58 bits) and
card codes 192 bits, so under the existing limits (5/min per account and address, 60/min per
address) guessing is out of reach. And it would hand out a weapon: the team's e-mail is printed on its
card, so anyone at the venue who sees a card could fail ten times and lock that team out mid-race,
right password or not. Keep your on-phone throttle exactly as built — it is the only lockout, and it
never blocks `account_inactive`, `access_window_expired` or `game_ended`, which is right.

Everything else in #29 matched the contract, and two of your notes are now rules in it: the
first-sight guard (build guide §2c) and re-downloading the floor plan when its id changes (§2c).

**Done when:** no call to `/guidance` remains in the app.

---

## 28 — Offline, phase 1: questionnaires and the floor plan survive a dead network  ·  22 Sep

The operator found that the floor plan, questionnaires and questions do not work offline, and asked
whether the server or the app was at fault. **The server.** The contract told you to pre-download
everything in `/offline/manifest`, and the manifest never contained quiz content or the floor plan;
questions only ever came from `quiz/start`. You built what was specified. It now carries them.

Build guide §10, "Offline, phase 1", has the whole spec. In short:

1. **Sync** also stores `questionnaires[]` (sealed) and downloads every `assets[].url` (encrypted
   pictures), plus `indoor_map` and its pictures from `images`.
2. **Floor plan offline** from `indoor_map` when `/indoor-map` cannot be reached.
3. **Scan → `qr_hash`** to know which questionnaire it is, with no network; open it with the key from
   the scanned code. The crypto is exact — PBKDF2-HMAC-SHA256 100k, two HMAC labels, AES-256-GCM with
   AAD. Get one byte wrong and nothing opens, so test against the server early.
4. **Starting still needs signal** (`quiz/start`). Once started, render from the local copy and carry
   on through a dropped connection: answers and submit queued, the clock on `elapsedRealtime()`.
   With no signal at scan time, name the questionnaire and ask for a moment of signal — do not show
   its questions.
5. **Re-sync** when `/race/status`'s `content_updated_at` is newer than the one you stored.

Decisions behind it, so you do not relitigate them: questions travel sealed rather than in the clear,
because a team must not read a post's questions before standing at it (operator, 22 Sep); indoor posts
the crew opens need the venue WiFi (operator, 22 Sep); starting offline is phase 2, not this.

**Done when:** in airplane mode after a Sync, the floor plan opens; a questionnaire started with signal
survives the signal dropping — questions, pictures, answers, submit — and uploads when it returns; a
post scanned with no signal is named but not opened; and nothing readable about a questionnaire is in
the app's storage before its code has been scanned.

---

## 29 — The START clue opens the plan, not the posts  ·  22 Sep

The operator, 22 Sep: the 3D Camera at an indoor post opens only when the team is there — the
facilitator reports it and the admin opens that post in Outpost Access. The 15 Sep rule from your #21,
under which a correct START clue opened every post on the plan at once, bypassed that and is removed.

Nothing changes on the wire. After a correct clue, expect every post on the plan `is_open: false` and
`/ar/locations/{id}` answering `awaiting_unlock` until the crew opens it. If the app ever treated a
correct clue as "posts are open", take that out.

**Done when:** after answering the START clue, no post shows open and the 3D Camera waits for the
crew; opening one post in Outpost Access opens that post alone, within one re-check.

---

## 30 — The 3D Camera offline, and the plan behind the clue  ·  22 Sep

Operator, 22 Sep: the 3D Camera must work with no internet — the objects *and* where the admin placed
them. Every AR Outpost's scene is now in `/offline/manifest` as `game_locations[].scene`, identical to
what `/ar/locations/{id}` returns once open (verified on both live posts: 5 and 3 objects).

1. **Sync** keeps `scene` per outpost; its models are in `models`, its tap pictures in `images`.
2. **Indoor:** render the synced scene when the post is open for the team (`is_open`, over the venue
   WiFi) — the crew's opening is still the gate.
3. **Outdoor:** judge the radius from the phone's own GPS against `scene.location`; fully offline.
4. Online, `/ar/locations` as before; the synced scene is the fallback, not a replacement for the gate.
5. **The floor plan** — live or synced — opens only after the START clue is answered (build guide §8).

The clues are in the clear on purpose (operator: the app lives on the phone for the event only).

**Done when:** in airplane mode after a Sync, an outdoor post's 3D Camera opens inside its radius and
plays; an indoor post opened by the crew plays with no download; an unopened indoor post still waits;
and the floor plan does not show before the clue is answered.

---

## 31 — Results, question by question  ·  23 Sep

Your #32 is built. `POST /quiz/submit` now carries `result.questions[]`, and
`GET /quiz/attempts/{attemptId}` returns the same result later — totals, `questions[]`, `puzzles[]` —
so a team that closed the app, or a crew member checking a phone afterwards, can reopen it.

Row: `{ question_id, type, question, points, points_earned, correct, answered }`. `correct` is
nullable and null is not a cross: a `fun_game` is the facilitator's to score, a `brief` is feedback,
an unanswered question was never marked. `points_earned` is what the question actually paid — partial
for Tebak Gambar, the facilitator's award for a `fun_game`, possibly negative when a penalty outweighs
it. Totals still come from the server; never add them up yourself.

The four other findings in #32 were yours and are in v47 — nothing needed here.

**Done when:** after hand-in the Results screen lists every question with its points and a tick, a
cross or neither; reopening that attempt from history shows the same screen; and a fun_game scored
afterwards shows the facilitator's points.

---

## 32 — One questionnaire can serve several posts  ·  operator 23 Sep

The venue has ten booths and two floor plans that walk them in a different order, so the same booth is
"Pos 01" for one team and "Pos 02" for another. An admin can now link one questionnaire to several
posts, or tick *berlaku di semua pos*. The printed QR stays one code — the questionnaire's own, as
always. Four things change on your side:

- **`qr/lookup` 200 carries `post`**: `{ "type": "indoor", "id": 117, "name": "T1 Pos 07 · T2 Pos 01 — OJK" }`,
  or `null` outdoor. It is the post the crew opened for this team, and so the post this play belongs
  to. Show it on the quiz screen and in Results; the questionnaire title no longer says where the team
  is standing.
- **A `checkin_required` for a shared questionnaire has no `post` object.** The server cannot tell
  which booth a team is at when one code is printed at ten of them, and naming the wrong one would
  send them across the venue. Show `message`; do not fall back to the first post you know.
- **`max_attempts` counts per post.** `1` means once at each booth: after a hand-in at one post the
  same questionnaire opens again at the next post the crew unlocks. Do not cache "this questionnaire
  is finished" against the questionnaire id alone — key it on `(questionnaire_id, post_id)`.
- **The manifest lists them**: `questionnaires[].game_location_ids` (every post) and `all_posts`.
  `game_location_id` remains, as the first of the list. Offer a questionnaire at a post when that post
  is in `game_location_ids`, or when `all_posts` is true.

One live session at a time is unchanged: a session open at one post must be finished before the same
questionnaire can be scanned at another (`409 session_in_progress`). A questionnaire with a single
post behaves exactly as it does today — nothing to change for those.

**Done when:** a questionnaire shared by two posts can be played at both, one after the other; the
quiz and Results screens name the post; and a refusal with no `post` shows its message without
inventing one.

---

## 33 — One AR Outpost, every team  ·  operator 23 Sep

Same venue, same ten booths, three floor plans that walk them in a different order. There is now **one
AR Outpost per booth**, shared by every team's plan, instead of a copied set per team. Live right now:
outpost `111` is "Pos 01" on Tim 01's plan and "Pos 02" on Tim 03's.

Nothing in the API's shape changed, and nothing about access changed — `is_open`, `awaiting_unlock`
and `checkin_required` are still decided per team account, so a booth opened for one team stays shut
for the others. What changes is an assumption that was safe while every team had its own posts:

- **A route number belongs to the marker, not the outpost.** Take the number and the order from
  `/indoor-map` `spots[].name`; `game_location_id` no longer identifies a position in anyone's route.
- **Outposts are named after what happens there now** — "QRIS Tap Experience", "Graffiti Wall" —
  because a name like "Pos 01" would be wrong on two of the three plans. If your AR screen shows
  `location.name`, it will change wording after the next sync; that is intended.
- **Cache the scene by `game_location_id` if you like** (it is identical for every team), but never
  cache "which post is this in our route" against that id.
- The manifest's `game_locations[]` covers the whole event, not one team's route. The route is
  `indoor_map.spots`.

**Done when:** two teams on different plans can play the same booth, each seeing their own number for
it, and one team's unlock never opens it for the other.

---

## 34 — The race ends: show it  ·  operator 23 Sep

The operator ran a full test — ten posts, all cleared — and **the app never closed**. Two faults, one
on each side. Ours: the clock was stopped only when a single account cleared everything, so a team
playing on two phones could never finish; it counts per team now. Yours: nothing in this contract ever
told you the race can end, or where to read it. That is fixed here.

`GET /race/status`, which you already poll every 10 s, carries it:

```json
"race": { "elapsed_seconds": 4137, "finished": true,
          "finished_at": "2026-09-23T13:20:01+00:00", … }
```

- When `finished` turns true: stop your local ticker, show `elapsed_seconds` as the **final time**,
  and show the team its finished state with the score from `GET /team`.
- **Never decide this yourself.** Counting ten posts locally is wrong — bonus and optional posts do
  not count towards the finish, and only the server knows which are which.
- **Both phones of a team flip together**, so a device that cleared nothing itself will still see
  `finished: true`. Do not tie the finish screen to what that device happened to play.
- **Nothing is locked afterwards.** `qr/lookup`, `quiz/start` and the AR gate keep answering, because
  a bonus post is still playable after the clock stops. Show the finish; do not close the app down.
- After an emergency stop, `race` is `null` again — back to "scan START", as in §2c.

**Done when:** a team that clears its last counting post sees the finish screen and a time that stops,
on both of its phones, without restarting the app; and a bonus post scanned afterwards still opens.

---

## 35 — Offline, phase 2: start without signal  ·  operator 23 Sep

Phase 1 (§28) put the questions on the phone but left the **start** on the server, so a post reached
while the WiFi was down still stopped the team. That was the last hole. The server side is built and
live; build guide §10 "Offline, phase 2" is the spec.

What changes on your side, at the post with no signal:

1. Match `qr_hash` as you do now, open the sealed questionnaire, **mint a `client_token`** (UUID),
   start `SystemClock.elapsedRealtime()`, and show the questions. Persist the token with the queued
   play — it is that attempt's name for the rest of its life.
2. Answers go into the write queue exactly as they do when a session started online loses signal.
3. When the network comes back, **before flushing the queue**, call
   `POST /quiz/offline-start {qr_code, client_token, elapsed_seconds}`. The body you get back is
   `/quiz/start`'s, plus `repeat`. Replay the queue against the `attempt.id` it returns.
4. Retry freely: the same token always returns the same attempt (`repeat: true`).
5. Handle the refusals as refusals of that play, not of the app: `checkin_required` means the crew
   never opened that post for this team — hold the play, send them to a facilitator, retry after.
   Do not silently drop a queued questionnaire on a 403 here; that is a team's work.

Two limits, and they are deliberate. `elapsed_seconds` must come from the monotonic clock — the
server rebuilds the start as `now − elapsed` and clamps it to the questionnaire's limit, so a
fiddled device clock gains nothing and an honest one loses nothing. And a `fun_game` question still
needs signal for the facilitator's PIN and score, so a questionnaire containing one can be played
offline but only handed in once the network is back; say that on screen instead of letting `submit`
look broken.

**Done when:** in airplane mode a post scans, opens and is answered end to end; back on WiFi the play
uploads once, keeps its duration, scores the same as if it had been online, and a second upload of
the same token changes nothing.

---

## 36 — A post the team has finished cannot be opened again  ·  operator 23 Sep

Once a team has answered everything at a post, that post is over for them. On the floor plan it must
read as finished and must not lead anywhere: no AR camera, no scan screen, no second run.

`/indoor-map` `spots[]` now carries **`is_done`**:

```json
{ "id": 98, "name": "Pos 03", "game_location_id": 117,
  "is_post": true, "is_open": false, "is_done": true }
```

- **True means every questionnaire at that post is in.** A post carrying two questionnaires stays
  `false` until both are answered — do not infer "done" from a single hand-in of your own.
- **Per team.** A post answered on the other phone of the same team reads `is_done: true` here too.
- **The server enforces it, so a stale screen cannot cheat it.** Handing in the last questionnaire
  revokes the team's access to that post: `/ar/locations/{id}` → `403 awaiting_unlock`, `qr/lookup` →
  `403 checkin_required`. Treat those as the normal finished state at a done post, not as an error.
- **`is_open` and `is_done` are independent.** A crew member can reopen a finished post ("let them in
  once more") — then `is_open` is true again while `is_done` stays true. Let `is_open` decide whether
  the post can be entered, and `is_done` decide how it is drawn.
- In the offline manifest, `is_done` is as of the Sync, exactly like `is_open`.

**Done when:** a post played through turns finished on the plan by itself, tapping it opens nothing,
the team's other phone shows the same, and a post with two questionnaires only turns finished after
the second.

---

## 37 — Tapping a 3D object pays  ·  operator 23 Sep (client requirement)

The AR editor has offered a price per object since the beginning, and your object sheet has shown it
faithfully. Nothing ever paid it: no endpoint took the tap, and no total counted it. The operator's
client requires it for the event, so the server side is built and live.

```
POST /api/v1/ar/objects/{hotspotId}/claim      (no body)
200 { "success": true, "points": 15, "claimed_at": "…",
      "object": { "id": 67, "title": "Peti Harta" },
      "score": { "total": 95, …, "object_points": 15 } }
```

1. When the team taps an object whose `points` is above 0, call it **once**, then update the score
   card from the `score` in the reply. Never add the points up locally.
2. Read the new **`objects[].claimed`** in the scene (live and synced): true means this team has
   already been paid — draw the points row as collected and do not offer it again.
3. **`409 already_claimed` is not an error.** It comes back on a repeat tap, on the team's second
   phone, and on a retry after a dropped connection; it carries `points` and `score`, so treat it as
   a claim that already happened and move on.
4. Refusals are the scene's own gates: `403 awaiting_unlock`, `403 checkin_required`,
   `403 race_not_started`. `422 object_has_no_points` means the object is priced at 0 — in that case
   show no points row at all.
5. `score` gained **`object_points`** everywhere it appears (§2a). It is already inside `total`.

Once per team, not per phone. An emergency stop clears the claims with the rest of the run, so after
a reset the objects are worth collecting again.

**Done when:** tapping a priced object raises the team's total by exactly that amount, the sheet
shows it as collected afterwards, the second phone of the same team sees it collected too, and a
repeat tap changes nothing.

---

## 38 — One route per team: `409 already_on_another_route`  ·  operator 24 Sep

Tomorrow ten teams walk the same fourteen booths in ten different orders, so there are **ten START
codes** in the room. A team that scans the wrong card used to start a **second** clock — sessions are
keyed on (account, plan) — and `/race/status` then reported the newer one, so the team's route changed
under them while the first clock kept running. The server refuses that now.

```json
{ "success": false, "error": "already_on_another_route",
  "route": { "id": 64, "name": "Tim 05" },
  "race": { "session_id": 163, "elapsed_seconds": 412, "indoor_map_id": 64 },
  "message": "Tim ini sudah memulai rute Tim 05. …" }
```

- Show the `message`. The `race` block is the clock the team already has, so return to that race
  rather than to an error screen.
- **Re-scanning the same code is unchanged**: 200 with `just_started: false`, clock untouched.
- Judged **per team**, so the second phone scanning a stray card is caught too.
- A team whose race has finished can still be moved by the crew — the refusal only applies while a
  clock is running.

**Done when:** a team that scans another route's START card stays on its own race and is told which
one it is already running, and scanning its own card twice still changes nothing.

---

## 39 — Tell the team ARCore is missing **before** the race, not at a post  ·  operator 24 Sep

In last night's trial, two of four teams reached their first booth and were told **"ARCore tidak
support"** there, with a facilitator waiting and the clue to the paper QR locked inside the 3D scene.
Everything else on those phones worked — login, plan, scanner, quiz — so nothing warned anyone. The
server was healthy for both teams: `/ar/locations/{id}` returned 200 with three objects and every
`.glb` served in under 0.3 s. It is the handset, and it is knowable at install time.

**Ask on first launch after install** (before or right after login), and again the first time the 3D
camera opens, since the answer changes once the user installs what was missing. Never cache a negative
answer permanently. Full spec, with the state table and the exact messages, is in build guide §9
("Check the phone can do AR at all").

Three outcomes, and they are not interchangeable:

1. `SUPPORTED_NOT_INSTALLED` / `SUPPORTED_APK_TOO_OLD` → offer `ArCoreApk.requestInstall()`:
   "Layanan Google Play untuk AR", ±30 MB, fixed on the spot.
2. `UNSUPPORTED_DEVICE_NOT_CAPABLE` → "HP ini tidak mendukung Kamera 3D. Lapor ke panitia sebelum
   race dimulai." The crew swaps the phone while the team is still at the start line.
3. `UNKNOWN_CHECKING` → re-poll; `UNKNOWN_ERROR` / `TIMED_OUT` → "belum bisa memeriksa", retry.

**Do not block the rest of the app.** A phone without ARCore can still play everything except the 3D
camera and the object bonus points; refusing login over it would cost the team the whole event instead
of a few points. No API call is involved and nothing in the contract's shape changes.

**Done when:** a freshly installed APK on a device without Google Play Services for AR offers to
install it on first launch; a device that can never run ARCore says so plainly at that moment and
still lets the team log in and play; and a device that installs the service mid-event finds the 3D
camera working without reinstalling the APK.

---

## 23 September, in one page — §32 to §37

Five changes went out today. They are independent, and none of them changes an existing field's
meaning; four add a field, one is a rule you cannot see from the wire.

| | What | Where to read it |
|---|---|---|
| §32 | One questionnaire can serve several posts, or all of them. `qr/lookup` gains `post`; `max_attempts` counts per post. | guide §4 |
| §33 | One AR Outpost is shared by every team. Route numbers come from `spots[].name`, not from the outpost. | guide §8 |
| §34 | The race ends by itself: `/race/status` `race.finished` + `finished_at`, `elapsed_seconds` stops. **Per team, not per phone.** | guide §8 |
| §35 | Offline phase 2: `POST /quiz/offline-start` — play a post with no signal, hand it over when the network returns. | guide §10 |
| §36 | A finished post closes itself: `spots[].is_done`, and the access is revoked server-side. | guide §8 |
| §37 | Tapping a priced 3D object pays: `POST /ar/objects/{id}/claim`, `objects[].claimed`, `score.object_points`. | guide §2a, §9 |

If you build them in one pass, the order that costs least: §34 (one flag, visible result), §36 (one
flag, same screen), §33 (relabelling), §32 (per-post attempt keys), §35 (the queue — the real work).

---

## 39 — Check ARCore at first launch, not at the first post  ·  operator 24 Sep

In the 24 Sep trial, two of four teams reached their first booth, opened the 3D camera and got
**"ARCore tidak support"**. Server side was clean for both — 14 posts open, `/ar/locations/{id}` → 200
with three objects, every `.glb` serving — so those phones were never going to work, and it surfaced
with a race clock already running. Move the discovery to the start table.

**Build it as a first-run capability check** (build guide §9, "Check ARCore on the first run"):

1. On the first launch after install, call `ArCoreApk.getInstance().checkAvailability(context)` and
   handle **every** value — including `UNKNOWN_CHECKING`, which is transient and must be polled, not
   treated as a failure.
2. `SUPPORTED_NOT_INSTALLED` / `SUPPORTED_APK_TOO_OLD` → offer `requestInstall()`. This is the common
   and fixable case: *Google Play Services for AR*, ~30 MB from the Play Store.
3. `UNSUPPORTED_DEVICE_NOT_CAPABLE` → say it plainly, in Indonesian, and tell them to see the crew
   **before** the race: "HP ini tidak mendukung kamera 3D (ARCore). Beri tahu panitia."
4. **Never block the app.** Login, team setup, floor plan, clue, QR scanner, questionnaires and score
   must all work on a phone without ARCore — the 3D camera is one step of a post, not the app.
5. **Re-check on resume and when the 3D camera opens.** A team that installs Play Services for AR
   mid-event must be able to carry on without reinstalling the APK.
6. Show the result on your own about/settings screen, so a facilitator can read it out.

No API change — nothing on the server needs to move for this. If the operator later wants the crew to
see it on **Cek Kesiapan** before the start, the server will add an endpoint for the app to report the
result and it will be specified here; do not invent one.

**Done when:** a phone without Play Services for AR is told at first launch and offered the install; a
phone that is not capable says so at first launch and can still play everything except the 3D camera;
and installing Play Services for AR later needs no reinstall of the APK.

---

## 40 — A post can run without the camera: `ar_enabled`  ·  operator 24 Sep

The flow itself is right, but not every phone can run ARCore — so the crew can now switch the camera
off **per post**. The post keeps its objects, their text and their bonus points; only the renderer
changes.

`/ar/locations/{id}` and the synced scene carry **`location.ar_enabled`**:

- **False → do not start an ARCore session.** Show `objects[]` as rows: title, description (the clue
  naming the paper QR's hiding place) and the points row, the same content the object sheet shows.
  A tap on a row claims its points through the usual `POST /ar/objects/{hotspotId}/claim`.
- `objects[].model` may be null in that case. Not an error — there is nothing to render.
- Everything else is unchanged: the post still opens only when the crew opens it, the paper QR is
  still scanned afterwards, the questionnaire is untouched.
- `404 "This location has nothing to show yet"` = camera off **and** no objects written: send the team
  to the facilitator instead of showing an empty screen.
- Offline too: read it from `game_locations[].scene.location.ar_enabled`. The row's **`uses_ar` is
  false** for those posts, so the build already in the field offers no 3D there without any change —
  `uses_ar` means "open the camera here", not "has a model".

This is a per-post decision by the crew and does **not** replace §39's first-run ARCore check — other
posts may still have their cameras on.

**Done when:** a post with `ar_enabled: false` opens as a readable list on any phone, its bonus points
can still be collected, and no ARCore session is started for it.

---

## 41 — A race can end on time  ·  operator 24 Sep

Until today a run ended only when every counting post was cleared. A START code can now carry a **time
limit in minutes**, set per route, and the run ends on whichever comes first.

```json
"race": { "elapsed_seconds": 5400, "deadline_at": "…T10:46:35+00:00",
          "time_limit_minutes": 90, "finished": true, "finished_reason": "time_limit" }
```

1. Read **`deadline_at`** from `/race/start` and `/race/status` and show the time remaining. Count down
   locally if you wish, but the finish is the server's to declare — it stamps `finished_at` **at the
   deadline**, so a phone that polls late still gets the official time. `elapsed_seconds` never exceeds
   the limit.
2. **`finished_reason`** is `all_posts` or `time_limit`. The finish screen should say which — "semua pos
   selesai" and "waktu habis" are not the same news.
3. **`403 race_time_up`** is the new refusal from `qr/lookup` and from a **new** `quiz/start` once the
   deadline has passed; it carries `deadline_at` and `finished_at`. Show its message: *pos baru tidak
   lagi terbuka*.
4. **Do not block what is already open.** A questionnaire started before the deadline still resumes
   through `quiz/start` (same attempt), answers still save, `submit` is still accepted. A team must not
   lose work it was in the middle of — that was the operator's explicit choice.
5. **Ring a bell when the time runs out.** Scheduled locally from `deadline_at`: three short notes and a
   vibration at the deadline, once, plus one quieter warning at ten minutes left. It must fire with no
   signal — a team on the far side of a venue has no other clock — and a phone that slept through the
   deadline sounds it once on waking instead of replaying every alarm it missed. It announces; it never
   decides. The crew's desk rings the same bell at the same moment (Outpost Access, 24 Sep), so the two
   ends of the radio agree.
6. A code with **no** limit sends `deadline_at: null` and behaves exactly as before — and rings nothing.

**Done when:** a team sees its deadline from the first second, hears one bell at the deadline (and one
warning ten minutes before) even in airplane mode, the finish screen names the reason, a scan after the
deadline is refused with the server's message, and a quiz opened before the deadline can still be
finished and handed in.

---

## 42 — The bell is the crew's choice, and it is on the wire  ·  operator 24 Sep

§41 told you to ring a bell at the deadline. What it sounds like is no longer yours to decide: the
crew picks it in the admin (Outpost Access, where the same bell rings on their desk), and the choice
travels with the race.

```json
"bell": { "tone": "sirine", "volume": 45, "repeat": 2, "sound_url": null }
```

It rides on **`GET /race/status`** — present whenever a run exists, absent with `race: null` — and on
**`GET /offline/manifest`**, so a phone has it before the event starts.

1. **Four named tones, and they are not files:** `bel` (three even strikes, the default), `lonceng`
   (two long strikes that keep ringing), `sirine` (a sweep 500→1400 Hz, twice), `bip` (five short hard
   beeps). Synthesise or bundle them on your side — the desk draws its own from the same names. That
   is deliberate: a tone that is a *name* cannot arrive late, miss the cache or 404 in a basement.
2. **`volume` 0–100 and `repeat` 1–5 are instructions, not hints.** Apply them to the deadline bell.
   The ten-minute warning stays one quieter note whatever `repeat` says — a warning that repeats five
   times is an alarm, and the alarm is for the deadline.
3. **`custom` means the crew uploaded a sound**, and `sound_url` is a plain path URL — never a signed
   query, which this host strips. Cache it at Sync beside the models and the tiles. **If it is
   missing, unfetched or unplayable, ring `bel`.** A phone that stays silent at the deadline is the
   one failure that matters here.
4. **Keep the last `bell` you saw** and read it again on every poll. The alert is scheduled locally
   from `deadline_at`, so it has to work with no signal; and a crew that changes the tone between two
   routes must not have to make every phone sync again.
5. Nothing else about §41 changes. The bell still only announces — the finish comes from the server.

**Done when:** the crew changes the tone in the admin, the next poll changes what the phone rings, an
uploaded sound plays after a Sync and in airplane mode, a broken `sound_url` falls back to `bel`
instead of silence, and `volume` and `repeat` are audibly obeyed.

---

## 43 — Lock the Kamera 3D button when no post uses a camera  ·  operator 24 Sep

Your report #43 asked whether a camera-off post should be listed on Kamera 3D. It should not, and the
answer is in build guide §9 beside the `ar_enabled` rules. The operator's own instruction adds the case
you did not ask about: **an event where the crew switched the camera off everywhere.** Then the 3D list
is empty, and an empty screen reached through a live button is a team standing still wondering what it
did wrong.

```json
"counts": { "game_locations": 14, "ar_posts": 0, … }
```

1. **`counts.ar_posts`** is new in `GET /offline/manifest`: how many posts really open a camera — the
   posts whose `uses_ar` is true. It is the same gate the server applies, counted once for you.
2. **Zero means lock the button.** Visibly disabled, with a short reason in the team's own words
   (*"Event ini tidak memakai kamera 3D"*), and not tappable.
3. **Only zero.** `ar_posts: 1` out of fourteen posts leaves the button live — the operator decided this
   explicitly on 24 Sep. A team whose route has one AR post still needs that screen, and locking it
   because the other thirteen are switched off would take away the post that works. Never count a
   fraction; the test is `== 0`.
4. **Do not hide it.** A button that vanished reads as a broken install and brings the team to the
   crew; a locked one with a reason answers itself. Same argument as the camera-off post opening as a
   list instead of showing nothing.
5. **Re-read it at every Sync.** One post switched back on makes `ar_posts` non-zero, and the button
   must come alive without a reinstall.
6. Per-post behaviour is unchanged: a camera-off post stays off the 3D list and is reached from the
   floor plan, where its objects open as a list and its priced objects are still claimable.

**Done when:** with every post's camera off, the dashboard shows Kamera 3D locked with a reason and a
tap does nothing; with one post switched back on, the next Sync unlocks it; and a post reached from the
floor plan still claims its points either way.

---

## 44 — A score the crew corrected by hand  ·  operator 25 Sep

The crew now has a way to put a team's score right from the admin when the app could not: a facilitator's
phone died before a game was scored, a booth's QR never printed, a questionnaire was handed in twice.
Each correction is a signed, audited row on our side; for you it is one more line in the breakdown.

```json
"score": { "total": 1150, "base_points": 1000, "earned_points": 0,
           "assessment_points": 0, "object_points": 0, "manual_points": 150 }
```

1. **`manual_points` is signed and may be absent on an older server.** Default it to 0.
2. **Show it only when it is not 0**, as its own line with the sign — "Koreksi kru +150". Do not invent
   an explanation: the reason is recorded in the admin, not in this response, and a team that wants it
   asks the crew.
3. **`total` already contains it.** Keep showing the server's `total` and never sum the lines yourself —
   the same rule as every other part of this score.
4. **A total can move while the team does nothing.** Re-read `GET /team` when the dashboard opens, do
   not cache a total as final before the race ends, and do not report a jump as a defect.

**Done when:** a team whose score the crew corrected sees the corrected `total` and a "Koreksi kru" line
with the right sign, a team with no correction sees no such line, and nothing in the app computes the
total from its parts.

---

## 45 — A clock the crew stopped by hand  ·  operator 25 Sep

Until now a run ended two ways: every counting post cleared, or the START code's time limit running out.
There is a third, and it is a button in Team Management: the crew stops one team's clock. A team that
gave up, one pulled out of the venue, a phone that died with the race still running.

```json
"race": { "finished": true, "finished_at": "…T08:12:00+00:00", "elapsed_seconds": 720,
          "finished_reason": "manual", "deadline_at": "…T09:16:00+00:00" }
```

1. **Handle `finished_reason: "manual"`** wherever you already handle `all_posts` and `time_limit`. The
   finish screen should say the crew ended the run — not "waktu habis", which the team would argue with,
   and not "semua pos selesai", which is false.
2. **`elapsed_seconds` is frozen at the moment of the stop**, which is the team's official time. Note
   that `deadline_at` may still be in the future: the clock stopped early, so do not keep counting down
   to it.
3. **New posts are closed.** `qr/lookup` and a new `quiz/start` answer `403 race_time_up` with a message
   of its own — *"Race tim ini sudah dihentikan kru…"*. Show the server's message rather than your
   wording for the deadline. A questionnaire already open still resumes, saves and submits.
4. **It can be undone.** The crew can start the clock again, and then `/race/status` reports
   `finished: false`, `finished_at: null`, `finished_reason: null` and a clock that is running. Do not
   latch the finish screen or wipe local state on a finish — follow the server on every poll.

**Done when:** a crew-stopped team sees a finish screen that names the right ending and a frozen time, a
scan afterwards shows the server's refusal message, an open quiz can still be handed in, and starting the
clock again puts the team back into the race without a reinstall.

---

## 46 — Firmware for the tap node (ESP32 + PN532 + LoRa)  ·  operator 28 Sep

**This one is not Android.** The operator picked the hardware for proving a team has arrived at a booth, and
you have the PlatformIO toolchain in VS Code, so the firmware is commissioned here. Two targets in one
project; the Android app is untouched by this item.

Why it exists: a post opens today because a facilitator radios the admin, who clicks in Outpost Access. At
5,000 players that is ~7,500 manual unlocks, and it also keeps up to 333 req/s of `awaiting_unlock` polling
alive. A tap replaces both. Background and the rejected alternatives are in `SCALING-PLAN.md` §6;
assembly, wiring and provisioning are in `TAP-NODE-BUILD.md`.

### Hardware, fixed

ESP32-WROOM · **PN532** over I²C · **Ebyte E22-900T22D** (SX1262, UART, 22 dBm) · active buzzer · green and
red LED · powerbank. Gateways: the same radio on an ESP32 **read over USB serial** by the server — no WiFi,
no IP.

Pin map (authoritative; `TAP-NODE-BUILD.md` repeats it for whoever solders):

| Signal | GPIO | Signal | GPIO |
|---|---|---|---|
| UART2 TX → E22 RXD | 17 | I²C SDA | 21 |
| UART2 RX ← E22 TXD | 16 | I²C SCL | 22 |
| E22 M0 | 25 | PN532 IRQ | 32 |
| E22 M1 | 26 | PN532 RSTO | 33 |
| E22 AUX | 27 | Buzzer | 14 |
| LED green | 13 | LED red | 4 |

Radio: channel **73** (923.125 MHz — confirm against Kominfo and your datasheet revision), air rate
**9.6 kbps**, fixed transmission to gateway address `0x0001`, transmit power as low as passes the walk test.

### Wire protocol, node → gateway (26 bytes, little-endian)

```
0      ver        = 0x01
1      type       0x01 tap · 0x02 heartbeat · 0x03 time request
2-3    node_id    1–15 nodes, 101/102 gateways
4-7    seq        monotonic per node, persisted in NVS, never reused
8-11   ts         unix seconds, node clock
12-18  tag_uid    4 or 7 bytes, zero padded
19     uid_len
20-25  hmac       first 6 bytes of HMAC-SHA256(bytes 0..19, node_key)
```

ACK, gateway → node (12 bytes): `ver`, `type` 0x81, `node_id`, the **echoed seq**, `server_ts` (4), 4-byte
HMAC. A time request is answered with `type` 0x83 carrying `server_ts`.

### What the firmware must do

1. **Feedback first, radio second.** Buzzer and green LED fire the moment the UID is read — never after the
   ACK. A team standing at a booth must not wait on a radio to know the tap landed. No ACK within the retry
   window: red LED pattern, and the tap stays queued.
2. **Queue in NVS, at least 200 taps, ring buffer.** Retries at roughly 0.5 s, 1.5 s, 4 s, 10 s, then every
   30 s, each with random jitter so two booths that tap together do not collide forever. Send **the original
   `ts`**, never the time the queue happened to drain.
3. **Monotonic `seq` that survives a reset.** The server deduplicates on `(node_id, seq)`; a reset that
   restarts the counter would make old taps look new.
4. **Time.** Request the time at boot and hourly; hold it in RAM with drift correction. This race has two
   official clocks — a 40-second error is a defect, not a detail.
5. **Provisioning from NVS, not from source:** `node_id`, `node_key` (16 bytes, unique), `post_id` (the
   `game_locations.id` this booth serves). Include a serial command to write all three, and one to print
   them for the runbook.
6. **Duplicate-tap window.** The same UID within ~5 s is one arrival: buzz, but do not queue a second frame.
7. **Gateway firmware:** receive, verify nothing (the server holds the keys and verifies), and print one JSON
   line per frame to USB serial:
   `{"node":3,"seq":149,"ts":1790500000,"uid":"04A2B3C4","rssi":-71,"snr":9.5,"gw":1,"hmac":"a1b2c3d4e5f6"}`
   Send the ACK the moment the frame is received — do **not** wait for the server, or a busy workstation
   turns into a booth full of red LEDs. Two gateways will both ACK and both print; the server dedupes.
8. **Deep sleep is an optimisation, not a requirement.** Light sleep with a poll every ~300 ms is the
   baseline; if PN532 card-detect can wake the ESP32 through the IRQ line reliably, take it, and report the
   measured idle current either way.

### Deliverables

A PlatformIO project (name it `questerra-tap`) with two environments, `node` and `gateway`, sharing the
protocol code; `platformio.ini` pinning library versions; a README with the flash and provisioning steps; and
the measured idle current in it. Report through `POST /api/v1/contract/feedback` as usual.

**Done when:** a tap buzzes in under a second and reaches the server's serial line in under a second at 50 m
through a wall; twenty taps made with the gateway unplugged all arrive afterwards, once each, in order, with
their original timestamps; a replayed frame is still one arrival; a frame signed with the wrong key is
dropped; `seq` survives a power cut; and the node runs eight hours on a powerbank with the idle current
written down.

### Not yours

The server side — `rfid_tags`, `post_arrivals`, the serial listener that writes the same
`game_location_unlocks` row Outpost Access writes, deduplication across both gateways, and
`POST /api/v1/device/tap` for a future networked gateway — is this side's work and is not built yet
(`SCALING-PLAN.md` phase 6). Nothing in `/api/v1` changes for the Android app because of this item.
---

## Talking back — the channel runs both ways now

Until today this was one-way: the server published, you consumed, and anything you had to say
reached us only if the operator retyped it. That is exactly how this file came to list screens as
missing four releases after you shipped them. Your correction existed; it had no route back.

```
POST https://questerra-series.com/api/v1/contract/feedback
Content-Type: application/json

{ "kind": "mismatch",                     mismatch | blocked | done | question | bug
  "subject": "Work order §1 — reticle",   the endpoint, guide section or task number
  "detail": "…",                          up to 4000 characters
  "client_version": "14",                 your versionCode
  "contract_sha": "…" }                   from /contract/version, so we know what you read
```

No token. Rate limited to 20/min. `201` with an id means it landed.

**Send one whenever:**

- a task in the work order is **already done** → `done`, and skip it. You did this by hand and it
  caught a four-release error; now it has a route.
- the contract and the server disagree → `mismatch`. The controllers win, and it means
  `openapi.json` was not regenerated. Do not work around it silently.
- something blocks you that only this side can unblock → `blocked`. The QR scanner sat waiting on
  a server test that nobody had asked for.
- a guide is wrong, unclear, or describes your tree instead of ours → `mismatch`.

`client_version` and `contract_sha` are not decoration: without them a report cannot be judged.
"This is wrong" means nothing if we cannot tell which contract revision you were reading.

Reports are read before the next contract change. You will see the result as a moved hash in
`/contract/version`, not as a reply — there is nobody to reply.

---

## Order

§7 first: without it no station works at all. Then:

Reticle first: a reported defect in a screen that already exists, small change, visible
result. Then the offline queue — it protects work already being done in the field. The facilitator scoring screen (§6) next to Results — both close out an
outpost, and without §6 a game station pays nothing. Results
last; nothing depends on it. §3 needs no work from you unless your frame counter says the
decode is failing.

## When you are stuck

The contract is live at `https://questerra-series.com/api/v1/contract`. If it disagrees with the
server, the **controllers win** and the contract was not regenerated — report that rather than
working around it. Nothing in this work order needs a server change; if you find something that
does, say so and it will be added.
