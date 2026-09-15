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
000000. **Team setup: registered participants** — search the FEKDI x IFSE list or type manually. See §12.
0000000. **Answers to #13 and #14, outposts and flags** — see §13.
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

For the FEKDI x IFSE event (24–27 Sep) the server holds the client's participant list. On the Team
setup screen each member is added either from that list (search by name or e-mail) or manually, as
now. Each member can be made Ketua. The list can be switched off by the admin at any time; manual
entry always works.

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
