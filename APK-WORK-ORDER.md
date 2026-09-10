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

1. **The AR reticle** — `ar/ArActivity.kt`
2. **Results screen**
3. **QR scanner** — was blocked on a server test. It is not any more; see §3.
4. **Offline write queue** — from your report; added as §4.
5. **Version header** — one header, and the patching system finally has its safety net. See §5.

Already closed per your report and not repeated here: team setup, team score, outdoor map,
indoor map, the keystore `.gitignore`, and removing Capacitor.

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

## 2 — Results screen

Shown after `POST /quiz/submit`. Display what the server returned — score, earned points — and
then refresh `GET /team` so the running total agrees. Do not compute anything locally.

Remember the asymmetry: `save-answer` refuses after the time limit (403 `time_expired`) but
`submit` **always** succeeds. On `time_expired`, stop saving and submit immediately; never show an
error and never block the submit, or the team loses the whole attempt.

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

Reticle first: a reported defect in a screen that already exists, small change, visible
result. Then the offline queue — it protects work already being done in the field. Results
last; nothing depends on it. §3 needs no work from you unless your frame counter says the
decode is failing.

## When you are stuck

The contract is live at `https://questerra-series.com/api/v1/contract`. If it disagrees with the
server, the **controllers win** and the contract was not regenerated — report that rather than
working around it. Nothing in this work order needs a server change; if you find something that
does, say so and it will be added.
