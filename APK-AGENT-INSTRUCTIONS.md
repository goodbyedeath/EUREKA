# Instruction block for the Android client agent

Paste the block below to the agent that maintains `Eureka Client APK`. It sets up contract-driven
sync against the server repo.

---

```
The Laravel server for this app publishes a machine-readable contract. From now on, generate your
network layer from it instead of reading the server's source or waiting for a hand-written note.

CONTRACT SOURCE
  repo:      https://github.com/goodbyedeath/EUREKA.git   (branch: main)
  contract:  openapi.json          — generated from the running router; never edited by hand
  companion: APK-BUILD-GUIDE.md    — behaviour a schema cannot express; read it, do not parse it
             API-V1-CONTRACT.md    — endpoint reference for humans
             APK-SYNC-FEEDBACK.md  — what changed for you, and why

  Base URL is in the contract's `servers[0].url`. Do not hardcode it anywhere else.

WHAT TO DO ON EVERY SYNC
  1. Fetch the repo and diff openapi.json against the revision you last generated from.
  2. Regenerate ONLY the network layer: Retrofit interfaces, kotlinx-serialization models,
     and the error-key enum in net/ApiError.kt.
  3. Rebuild and run your test suite. Report what changed and what broke.

  Each operation carries three vendor fields you must honour, because they are not part of the
  request shape and no generator will wire them for you:

    x-auth           "sanctum-bearer" or "public"   — send the token, or do not
    x-access-window  true  — this call returns 403 error:"access_window_expired" outside the
                             team's granted window. Treat that as "session over", never retry.
    x-rate-limit     the limiter name. "tracking" is 30/min per player — everything else is
                     120/min. Match your client-side interval to it.

NEVER GENERATE UI FROM THE SCHEMA
  This is the important instruction. The schema describes shape, not behaviour. Generating screens
  from it produces a client that is structurally perfect and behaviourally wrong — and that kind
  of wrong looks correct until event day. These rules live in APK-BUILD-GUIDE.md and must be
  implemented by hand:

    - save-answer refuses after the time limit; submit ALWAYS succeeds. On error "time_expired",
      stop saving and submit immediately. Refusing the submit strands the team's work.
    - fun_game answers are stored correct with ZERO points; a facilitator scores them later.
      Never total points client-side — you will double-count every game.
    - "awaiting_unlock" is a waiting screen polled every ~15s, not a failure.
    - Post position at most once per 10 seconds even while walking, whatever the limiter allows.
    - The Sanctum token expires with the access window. `expires_at` on the login response says
      when. Re-login is a normal path, not an error state.
    - Never branch on HTTP status alone: several distinct conditions share 403. Read `error`.

BREAKING CHANGES — STOP AND ASK
  If the diff shows an operation REMOVED, or a field that is now REQUIRED, do not silently adapt.
  Report it to the operator before changing the client: it means a build already in the field will
  start failing. Everything else (new optional field, new endpoint, relaxed rule) is safe to apply.

WHEN THE CONTRACT AND THE SERVER DISAGREE
  The controllers win, and it means openapi.json was not regenerated. Report it — do not work
  around it silently, and do not edit openapi.json yourself. It is generated on the server side by
  tools/openapi-gen.php.

WHAT YOU MUST FIX IN YOUR OWN REPO FIRST
  1. Your release signing key is one `git add .` away from being committed:
       android/keystore.properties   (storePassword, keyPassword)
       android/keystore/*.jks
     A leaked signing key cannot be rotated — every installed copy would stop accepting updates.
     Add both to .gitignore before your next commit. (Fixed already on the operator's copy; your
     origin repo still needs it.)
  2. Capacitor is dead weight: no Gradle, Kotlin or manifest reference remains, and
     `npm run build` calls scripts/build-web.mjs, which does not exist — so build and sync both
     fail. Remove capacitor.config.json, package.json, package-lock.json, www/ and node_modules,
     or trim package.json to the scripts that exist.
```

---

## Before this works: two prerequisites

The contract is written but **not yet on GitHub**, and this server holds no GitHub credentials
(no SSH key, no credential helper), so it cannot push. `origin/main` is at `571f3e8`; the local
branch is one commit ahead and the contract files are untracked.

Someone with push access must run, from `EUREKA_APP/`:

```bash
git add openapi.json tools/openapi-gen.php tools/contract-check.php \
        API-V1-CONTRACT.md APK-BUILD-GUIDE.md APK-SYNC-FEEDBACK.md \
        APK-AGENT-INSTRUCTIONS.md APK/.gitignore
git commit -m "Publish generated /api/v1 contract for the Android client"
git push origin main
```

Until that push lands, the Android agent has nothing to fetch.
