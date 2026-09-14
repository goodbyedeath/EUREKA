# EUREKA / Questerra — API v1 for the Android client

Generated from the live router on questerra-series.com. Base URL `https://questerra-series.com`.

## What changed since the last handover

Act on these — several change responses your app already handles.

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
| `POST /api/v1/quiz/save-answer` | `attempt_id`, `question_id`; `answer` optional and may be null |
| `POST /api/v1/quiz/complete-game` | `attempt_id`, `question_id` |
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
| `unknown_code` | 404 | `qr/lookup`: code is not in this event | Show it; let them rescan |
| `team_locked` | 403 | `team/members` add or remove after setup | Remove the call; members are fixed |
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
