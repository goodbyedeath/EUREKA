# Work order — finish the player UI

For the agent that maintains `Eureka Client APK`. This is not reference material; it is the list
of what is still missing, in build order, with a test for each that decides whether it is done.

Fetch it at `GET /api/v1/contract/guide/work`. Its hash is in
`/api/v1/contract/version` → `guides`, so you are told when it changes.

**State as measured on 10 Sep 2026**, by reading your own source tree — not from a status note:

| Exists, has substance | Missing entirely |
|---|---|
| `LoginScreen.kt` · `DashboardScreen.kt` · `ScanScreen.kt` · `QuizScreen.kt` · `ArActivity.kt` | `TeamSetupScreen.kt` · `TeamScreen.kt` · `QuestMapScreen.kt` · `IndoorMapScreen.kt` · `ResultsScreen.kt` |

`ArActivity.kt` contains **zero** mentions of a reticle. That is the defect the operator reported:
the 3D camera opens but nothing tells a player what is interactive.

Endpoints for every task below already exist and were tested end to end. Nothing here is blocked
on the server.

---

## 1 — The AR reticle  ·  `ar/ArActivity.kt`  ·  **do this first**

The 3D camera is built and objects render, but there is no aiming circle, so a player cannot tell
what is interactive or where to point. Objects read as scenery. Full spec in
`APK-BUILD-GUIDE.md` §9; the essentials:

- A 44 × 44 dp circle at the exact centre of the screen, drawn above the AR surface and **not
  touchable** — it must never absorb the tap.
- Idle: 2 dp border `#ffffff88`, no fill. Hot: border `#4ade80`, 6 dp glow `#4ade8033`, scale 1.15.
- Every frame, cast a ray from the camera through screen centre, test the placed objects, walk up
  to the object root, and set hot/idle from whether anything was hit.
- **Aim-then-tap**: a tap anywhere opens whatever the reticle is on; an idle reticle means the tap
  does nothing. Do not hit-test from the touch point.

**Done when:** pointing the phone at an object turns the circle green before any tap, and tapping
with the circle white does nothing at all.

---

## 2 — Team setup  ·  `ui/TeamSetupScreen.kt`

Right now a player can log in and then go nowhere: there is no way to form a team.

After login call `GET /team`. `404 error:"no_team"` → this screen. `200` → the dashboard.

```
POST /api/v1/team
{ "name": "…", "description": "…"?, "department": "…"?,
  "members": [ { "name": "…", "email": "…", "phone": "…"?, "position": "…"? } ] }
```

Rules the server enforces, so mirror them in the form rather than discovering them by 422:
name 3–100 characters · 1–20 members · every email different (422 `duplicate_emails`) · one team
per account (409 `team_exists`). The creating account becomes leader and owner.

`POST /team/members` adds one; `DELETE /team/members/{id}` removes one. Both need the owner
account (403 `not_team_owner`); the leader cannot be removed (409 `cannot_remove_leader`); 20 is
the ceiling (409 `team_full`). Every one of these returns the **full team object**, so refresh the
whole screen from the response instead of re-fetching.

**Done when:** a fresh account can name a team, add and remove members, and a second attempt to
create one is refused with a readable message rather than a crash.

---

## 3 — Team score  ·  `ui/TeamScreen.kt`, and the dashboard header

`DashboardScreen.kt` currently makes no reference to points at all.

`GET /team` → `{ points, initial_points, earned, rank, total_teams, is_owner, members[] }`

- Show **`earned`** as the headline number, not `points`. `points` includes the 1000 every team
  starts with and reads as a huge score for nothing.
- `rank` / `total_teams` gives standing. There is deliberately no full table here — that is the
  kiosk screen's job, and the room can already see it.
- **Never total points yourself from question values.** `fun_game` answers are stored correct with
  ZERO points and are graded by a facilitator afterwards; summing locally double-counts every one.

Refresh after each quiz submit and on returning to the dashboard. Do not poll it.

**Done when:** the number on screen matches the kiosk leaderboard for the same team.

---

## 4 — Outdoor map  ·  `ui/QuestMapScreen.kt`

```
GET /api/v1/quest-locations?user_latitude=…&user_longitude=…
```

**Send the position.** Without those two query parameters every row returns `distance: null` and
`within_radius: false`, and the screen cannot tell the player whether check-in will work. With
them each outpost carries `distance` (metres), `within_radius`, `radius`, `checked_in`,
`check_ins_count`, `last_checked_at`. Also accepts `search=` and
`filter_status=visited|not_visited`, and paginates.

```
POST /api/v1/quest-locations/checkin
{ "location_id": …, "user_latitude": …, "user_longitude": …, "accuracy": …? }
```

Note the `user_` prefix — this endpoint alone uses it. Refusals are machine-readable:
403 `out_of_range` carries `distance` and `radius`; 409 `max_attempts_reached` carries `limit`.

Background position goes to `POST /tracking/position` with **`latitude`/`longitude`** — no prefix
there — at most **one fix every 10 seconds**. The limiter allows 30/min and a GPS callback fires
about once a second, so an unthrottled send exhausts it in two minutes.

**Done when:** walking toward an outpost shows the distance falling, check-in succeeds inside the
radius, and outside it the app says how much further to go rather than "check-in failed".

---

## 5 — Indoor map  ·  `ui/IndoorMapScreen.kt`

```
GET /api/v1/indoor-map      → { map { id, name, description, image }, spots[] }
```

- `spots` sits **beside** `map`, not inside it.
- Spot `x` and `y` are **percentages** (0–100), not pixels — multiply by the rendered image size
  so the plan works at any width.
- `is_open` says whether the crew has opened that spot for this team.
- `game_location_id` links a spot to an AR outpost; tapping such a spot opens `ArActivity`.

**Done when:** markers land on the same places they occupy in the admin's plan, at any screen size
and orientation.

---

## 6 — Results  ·  `ui/ResultsScreen.kt`

Shown after `POST /quiz/submit`. Display what the server returned — score, earned points — and
then refresh `GET /team` so the running total agrees. Do not compute anything locally.

Remember the asymmetry: `save-answer` refuses after the time limit (403 `time_expired`) but
`submit` **always** succeeds. On `time_expired`, stop saving and submit immediately; never show an
error and never block the submit, or the team loses the whole attempt.

`verification_photo` is **required** on submit, base64. There is no server-side size cap, so the
client decides — downscale before encoding, because this upload happens with a team waiting.

**Done when:** an attempt whose clock ran out mid-question still submits and still scores.

---

## Order, and why

1 first because it is a reported defect in a screen that already exists — a small change with a
visible result. Then 2 and 3, because without them a player cannot form a team or see a score, and
everything else is unreachable. Then 4 and 5, the two ways to reach an outpost. 6 last: it closes
the loop but nothing depends on it.

## Two things to fix in your own repo along the way

- `android/keystore.properties` and `android/keystore/*.jks` are not ignored. A committed signing
  key cannot be rotated — every installed copy would stop accepting updates.
- Capacitor is dead weight: nothing in Gradle, Kotlin or the manifest references it, and
  `npm run build` calls `scripts/build-web.mjs`, which does not exist, so build and sync both
  fail. Remove it or trim `package.json` to the scripts that are real.

## When you are stuck

The contract is live at `https://questerra-series.com/api/v1/contract`. If it disagrees with the
server, the **controllers win** and the contract was not regenerated — report that rather than
working around it. Nothing in this work order needs a server change; if you find something that
does, say so and it will be added.
