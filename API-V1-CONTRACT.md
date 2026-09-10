# EUREKA / Questerra — API v1 for the Android client

Generated from the live router on questerra-series.com. Base URL `https://questerra-series.com`.

## What changed since the last handover

Act on these — several change responses your app already handles.

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
5. **`save-answer` enforces the time limit; `submit` deliberately does not.** Nothing new can be
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

## Points, if you display a score

Read earned points from what the server returns. Never sum question points client-side:
`fun_game` answers are stored correct with **zero** points and are scored later by a facilitator,
so summing them locally double-counts.
