# Instruction block for the Android client agent

Paste the block below to the agent that maintains `Eureka Client APK`. It covers contract-driven
sync **and** every screen the app needs.

---

```
The Laravel server publishes a machine-readable contract. Generate your network layer from it
instead of reading the server's source or waiting for a hand-written note.

════════════════════════════════════════════════════════════════════════════
CONTRACT SOURCE
════════════════════════════════════════════════════════════════════════════
  Fetch it over HTTP. No git, no credentials, no one emailing you a file.

    GET https://questerra-series.com/api/v1/contract/version    <- poll this one
    GET https://questerra-series.com/api/v1/contract            <- the OpenAPI document
    GET https://questerra-series.com/api/v1/contract/guide/work      APK-WORK-ORDER.md  <- START HERE
    GET https://questerra-series.com/api/v1/contract/guide/build     APK-BUILD-GUIDE.md
    GET https://questerra-series.com/api/v1/contract/guide/contract  API-V1-CONTRACT.md
    GET https://questerra-series.com/api/v1/contract/guide/sync      APK-SYNC-FEEDBACK.md
    GET https://questerra-series.com/api/v1/contract/guide/agent     these instructions

  All public, no token needed, ETag'd so a poll is cheap. /contract/version returns:
    { contract_sha, operations, guides { filename: sha }, generated_at, guide_urls }

  Keep the last contract_sha you generated from. When it differs, the API changed. When a
  value inside `guides` differs, the PROSE rules changed even though no endpoint did — fetch
  that guide and RE-READ it before building. A schema only carries shape; the rules that
  actually break a client live in the guide.

  The same files are in github.com/goodbyedeath/EUREKA (branch main) if you prefer git.

  Base URL comes from the contract's servers[0].url. Do not hardcode it anywhere else.

  Every operation carries three vendor fields no generator will wire for you:
    x-auth           "sanctum-bearer" or "public"
    x-access-window  true = returns 403 error:"access_window_expired" outside the team's
                     granted window. That means "session over" — stop, never retry.
    x-rate-limit     limiter name. "tracking" is 30/min per player; everything else 120/min.

════════════════════════════════════════════════════════════════════════════
SCREENS AND THE CALLS BEHIND THEM
════════════════════════════════════════════════════════════════════════════

1. SPLASH / BRANDING            GET /branding              (public, no token)
   { app_name, tagline, logo_wide, logo_icon, theme_color, version }
   The operator renames and re-skins the app from the admin panel. Never hardcode "Questerra"
   or bundle a logo. Cache on `version`; re-fetch when it differs. Optional launch carousel:
   GET /hero-slides — title, subtitle, background_image, background_gradient, text_color,
   button_color, button_style, primary_button, secondary_button, icon_svg, ordered by `order`.

2. LOGIN                        POST /auth/login
   Body { email, password, device_name }
   Returns { token, expires_at, user{ id, name, role, team_id, locale } }
   - The token EXPIRES WITH THE ACCESS WINDOW. `expires_at` says when (null = no expiry).
     Re-login is a normal path, not an error state.
   - `device_name` is a SLOT, not a label: logging in again with the same name deletes the
     previous token. Send one stable string per install. A random value per launch accumulates
     tokens nobody can revoke.
   - Two refusals carry no `error` key, only `message` — a disabled account, and an expired
     access window. Branch on status plus message for these two only.
   Store the token in EncryptedSharedPreferences. Never in plain preferences or a WebView.

3. TEAM SETUP                   GET  /team    POST /team
                                POST /team/members    DELETE /team/members/{id}
   After login, call GET /team.
     404 error:"no_team"  → show the team-creation screen
     200                  → go to the dashboard

   POST /team creates and fills it in one call:
     { name, description?, department?, members: [ { name, email, phone?, position? } ] }
     name 3–100 chars · 1–20 members · every email must differ (422 "duplicate_emails")
     The account creating the team becomes its leader and owner.
     One team per account: a second attempt returns 409 "team_exists".

   POST /team/members adds one { name, email, phone?, position? }.
   DELETE /team/members/{id} removes one. Both require the OWNER account
   (403 "not_team_owner"); the leader cannot be removed (409 "cannot_remove_leader");
   a team holds at most 20 (409 "team_full").

   Every one of these returns the full team object, so the UI refreshes from one response.

4. TEAM SCORE                   GET /team
   { points, initial_points, earned, rank, total_teams, is_owner, members[] }
   - `points` includes the 1000 every team starts with. Show `earned` as the headline number
     (points − initial_points) or players will think they scored a thousand for nothing.
   - `rank` and `total_teams` give standing without exposing other teams' scores. The full
     table is the kiosk screen's job, not the app's.
   - NEVER total points client-side from question values. fun_game answers are stored correct
     with ZERO points and are scored by a facilitator later; summing locally double-counts.
   Refresh after each submit, and on returning to the dashboard. Do not poll it.

5. QR SCANNER                   POST /qr/lookup   { qr_code }
   CameraX + ML Kit barcode-scanning, BUNDLED model — a team with no signal cannot download
   one in the field. Send the scanned string verbatim.
   Refusals: "unknown_code" (matched nothing), "not_available" (outside its date window or
   switched off — read `reason`, `opens_at`, `closes_at`), "max_attempts_reached".

6. OUTDOOR MAP / OUTPOSTS       GET /quest-locations?user_latitude=..&user_longitude=..
   PASS THE POSITION. Without those two query parameters every row returns distance:null and
   within_radius:false, and the app cannot tell whether check-in will succeed. With them:
   { distance (metres), within_radius, radius, checked_in, check_ins_count, last_checked_at }
   Also accepts search= and filter_status=visited|not_visited, and paginates.

   POST /quest-locations/checkin { location_id, user_latitude, user_longitude, accuracy? }
   NOTE THE user_ PREFIX — this endpoint alone uses it. Refusals are machine-readable:
     403 "out_of_range"        carries distance and radius, in metres
     409 "max_attempts_reached" carries limit

   Background position: POST /tracking/position
     { latitude, longitude, accuracy?, device_info? }   ← no user_ prefix here
   Send AT MOST ONE FIX EVERY 10 SECONDS even while walking. The limiter allows 30/min; a GPS
   callback fires about once a second and would exhaust it in two minutes. Keep the marker
   smooth locally, tell the server rarely. accuracy and device_info are optional but send them:
   without accuracy a 5-metre fix and a 500-metre fix draw the same dot on the operator's map.

7. INDOOR MAP                   GET /indoor-map    or /indoor-map/{id}
   { success, map{ id, name, description, image }, spots[] }
   - `spots` sits BESIDE `map`, not inside it.
   - spot x and y are PERCENTAGES (0–100), not pixels. Multiply by the rendered image size so
     the plan works at any width.
   - `is_open` says whether the crew has opened that spot for this team.
   - `game_location_id` links a spot to an AR outpost when it has one.

8. AR / 3D CAMERA               GET /ar/locations/{id}[?lat=&lng=]
   Full detail, including the reticle values, is APK-BUILD-GUIDE.md §9 — re-read it whenever
   its hash moves in x-behaviour-guides. Summary:

   ─── THIS IS A DIFFERENT CAMERA FROM THE QR SCANNER ───
   Two camera surfaces, never both alive:
     QR scanner  CameraX + ML Kit, flat 2D preview, no GL, no ARCore session.
     AR viewer   ARCore session + SceneView, its own Activity, launched by location id,
                 returning found-object ids so the caller can update progress.
   ARCore takes exclusive control of the camera. Opening the AR Activity while the scanner
   preview is still bound throws, and the reverse leaves a black frame. Fully close and
   release one before starting the other — do not try to share a surface or a session.
   (Sceneform is archived and will not build against current Gradle. Use SceneView.)

   ─── THE RETICLE: THE PART THE BUILT APK IS MISSING ───
   The admin preview draws a circle at the dead centre of the screen and the app must too.
   Without it a player cannot tell what is interactive or where to point — objects look like
   scenery. This is not decoration; it IS the interaction model.

     Idle   44 x 44 dp circle, exact screen centre, 2 px white border at ~53% alpha
            (#ffffff88), no fill, NOT touchable — it must never absorb the tap.
     Hot    border #4ade80, a 6 dp glow ring at ~20% alpha, scaled to 1.15x.

   Every rendered frame: cast a ray from the camera through screen centre (normalised 0,0),
   test it against the placed objects, walk up to the object's root, and set hot/idle from
   whether anything was hit. The web build does exactly this inside its render loop.

   Selection is AIM-THEN-TAP, not tap-the-object. A tap anywhere on the surface opens
   whatever the reticle is currently on; if the reticle is idle, the tap does nothing. Do not
   implement per-object hit-testing from the touch point — a player aiming with a phone at
   arm's length cannot reliably poke a small object, which is the whole reason the reticle
   exists.

   ─── WHAT OPENS ON TAP ───
   A bottom sheet carrying, from the object's own fields:
     title            heading
     description      body
     points           shown as "+N points"; hide the row entirely when 0 or absent
     ONE extra        an object carries a picture OR a link, never both. Switch on
                      `media_type`: "image" -> render `image`; "link" -> render `link` as a
                      button. Anything else -> no extra.

   ─── RESPONSE SHAPE ───
   location { id, name, model, latitude, longitude, radius, coordinate_source,
              quest_location_id, access_mode }
     access_mode "geofence" -> the radius decides entry; "manual" -> a crew member does, so
     show a waiting state rather than a distance. latitude/longitude null = never bound to a
     place: render with no geofence at all.

   objects[] { id, title, description, points, media_type, link, image,
               model, model_id, scale, distance,
               position { x, y, z },  rotation { x, y, z },
               animations [ { type, speed, range } ] }

   position is ALREADY resolved to metres in a right-handed camera-local frame (-z forward),
   computed server-side from the bearing, pitch and distance the admin authored on site.
   Place it as given. Never recompute anchors from lat/long, and never re-derive the bearing
   from the compass: the server's maths is the authority and the two will not agree.
   `scale` multiplies the model uniformly; `rotation` is in degrees; `animations` are looped
   idle motions (`type` with `speed` and `range`) applied locally, not synced.
   `model` is a .glb URL — pre-download every one from /offline/manifest before the event.

   ─── GATES, all 403 and distinguished by `error` ───
     "awaiting_unlock"   the crew has not opened this outpost for your team. A WAITING SCREEN
                         polled about every 15s. A normal state, not a failure.
     "location_required" an outdoor outpost called without ?lat=&lng=. Ask, then retry.
     "out_of_range"      too far. Body carries distance and radius, in metres.
   A 200 with no `objects` means the outpost has a model but nothing placed on it yet.

9. QUIZ RUNTIME                 GET  /quiz/start/{questionnaireId}
                                GET  /quiz/continue/{attemptId}
                                GET  /quiz/timer/{attemptId}
                                POST /quiz/save-answer     { attempt_id, question_id, answer? }
                                POST /quiz/complete-game   { attempt_id, question_id }
                                POST /quiz/submit          { attempt_id, verification_photo }
   - THE SERVER OWNS THE CLOCK. Read /quiz/timer and treat it as truth. A wrong device clock or
     a backgrounded app will otherwise disagree with the scoreboard.
   - save-answer refuses after time (403 "time_expired"); submit ALWAYS succeeds. On
     time_expired, stop saving and submit immediately — refusing the submit strands the work.
   - `answer` may be null (the player cleared a field).
   - verification_photo is REQUIRED on submit: base64, no server-side maximum, so YOU decide
     the size. Downscale before encoding — this upload happens with a team waiting on it.
   - Question types: text · multiple_choice · true_false · fun_game · brief.
     fun_game uses complete-game, scores ZERO at answer time, and is graded by a facilitator.
   - Must survive being backgrounded or killed: resume through /quiz/continue.

10. RACE (indoor events)        POST /race/start { code }   GET /race/status
                                POST /race/clue/{map} { answer }
    /race/status returns race:null before the START code is scanned — not an error.
    Refusals: "race_not_started", "unknown_start_code".

11. MENU VISIBILITY             GET /features
    { flags{ quiz_system, quest_locations, leaderboard, … }, features[] }
    Flags control VISIBILITY ONLY, never permission — the server enforces access with
    middleware regardless. Most flags are off in a fresh event; an empty dashboard is expected.

12. OFFLINE                     GET /offline/manifest
    { bounds, quest_locations[], game_locations[], images[], models[], pages[], counts }
    Fetch once the team is registered and PRE-DOWNLOAD EVERYTHING while there is still signal.
    `bounds` is the area worth pre-caching map tiles for; `models` are the .glb files AR needs.
    Queue offline actions (save-answer, checkin) and replay with backoff — and DROP a queued
    item on 4xx rather than retrying forever: a rejected answer will be rejected again.

════════════════════════════════════════════════════════════════════════════
NEVER GENERATE UI FROM THE SCHEMA
════════════════════════════════════════════════════════════════════════════
The schema describes shape, not behaviour. Generating screens from it produces a client that is
structurally perfect and behaviourally wrong — and that kind of wrong looks correct until event
day. Generate ONLY: Retrofit interfaces, kotlinx-serialization models, and the error-key enum.
Everything in the numbered list above that is a RULE rather than a field is written by hand.

════════════════════════════════════════════════════════════════════════════
GATES: NEVER BRANCH ON HTTP STATUS ALONE
════════════════════════════════════════════════════════════════════════════
Several distinct conditions share 403. Always read `error`. Full list, all currently emitted:
  access_window_expired · awaiting_unlock · location_required · out_of_range · scan_required
  race_not_started · unknown_start_code · unknown_code · not_available · max_attempts_reached
  team_registration_required · time_expired · attempt_not_found · attempt_submitted
  (not_available also carries reason:"inactive" when a station is switched off — that is a
   crew problem, not a bad scan, and must read differently to the player)
  question_not_found · game_already_assessed · not_a_game_question · no_team · team_exists
  duplicate_emails · not_team_owner · cannot_remove_leader · team_full · member_not_found
A 429 carries Retry-After — honour it, and back off rather than retrying in a loop.

════════════════════════════════════════════════════════════════════════════
ON EVERY SYNC
════════════════════════════════════════════════════════════════════════════
  1. GET /api/v1/contract/version. Nothing moved? Stop — there is nothing to do.
  2. contract_sha changed -> GET /api/v1/contract and diff it.
     A hash in `guides` changed -> GET that guide and re-read it. Behaviour moved, and no
     schema diff will show it.
  3. Regenerate the network layer only. Rebuild, run tests, report what changed and what broke.
  4. If the diff shows an operation REMOVED or a field NOW REQUIRED, STOP AND ASK — a build
     already in the field will start failing. Everything else is safe to apply.
  5. If the contract and the server disagree, the CONTROLLERS win and openapi.json was not
     regenerated. Report it. Never edit openapi.json — it is generated server-side.

════════════════════════════════════════════════════════════════════════════
FIX IN YOUR OWN REPO FIRST
════════════════════════════════════════════════════════════════════════════
  1. Your release signing key is one `git add .` away from being committed:
     android/keystore.properties and android/keystore/*.jks. A leaked signing key CANNOT be
     rotated — every installed copy would stop accepting updates. Add both to .gitignore.
  2. Capacitor is dead weight: nothing in Gradle, Kotlin or the manifest references it, and
     `npm run build` calls scripts/build-web.mjs, which does not exist — so build and sync both
     fail. Remove capacitor.config.json, package.json, package-lock.json, www/ and
     node_modules, or trim package.json to the scripts that exist.
```
