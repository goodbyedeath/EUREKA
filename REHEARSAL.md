# The rehearsal

`SCALING-PLAN.md` §7, and the only phase whose acceptance criterion is about people:

> **The crew has run the failure, not read about it.**

So this is a run sheet, not an explanation. Print it, do it at the venue on the real box with the
real hardware, and tick the boxes. Everything in it is a thing that will otherwise be attempted for
the first time during the event.

Two commands read back what the drills leave behind, so "we rehearsed" becomes evidence rather than
a memory:

```bash
./deploy.sh rehearsal      # what each drill left behind, and what is still missing
./deploy.sh walk-test      # per-booth LoRa margin, from the taps that actually arrived
```

Run `./deploy.sh rehearsal` at the start to see what is unproven, and again at the end. The marks
are `✓` for evidence found and `·` for nothing yet — it never fails the exit code, because a
half-done rehearsal is a normal state on the way to a done one.

---

## Before the day — at the office, not the venue

- [ ] **The stack comes up from nothing.** `./deploy.sh up` on a machine that has never run it.
- [ ] **`./deploy.sh doctor` is clean.** Every row `ok`, both Redis instances on `[noeviction]`
      where they should be, `APP_KEY vs encrypted data` → `matches`.
- [ ] **A backup exists and restores.** `./deploy.sh backup` — it dumps, then loads the dump into a
      scratch database and compares every table's row count. If that comparison fails, nothing else
      on this page matters.
- [ ] **`BACKUP_OFFSITE` is set** in `docker/.env`. A backup on the same machine as the data is not a
      backup; it is a copy.
- [ ] **A host cron takes one every ten minutes during an event:**
      `*/10 * * * * cd /path/to/docker && ./backup.sh --no-verify >> backup.log 2>&1`
      (On the host, not in a container — `backup.sh` drives `docker compose`.)
- [ ] **The spare box can take the IP.** §5 calls for one. Decide now whose job that is.
- [ ] **UPS.** The workstation, the switch, and the gateway boards.

## At the venue — setting up

- [ ] **Split-horizon DNS answers `questerra-series.com`** with the workstation's address on the
      player VLAN, and the certificate for that name is installed.
- [ ] **The player VLAN has no route to the internet.** Check it from a phone: the public site must
      be *unreachable*, not merely slower. This is what makes split-brain impossible — two servers
      with the same hostname and different databases is worse than an outage.
- [ ] **A phone can log in**, and `/admin/kesiapan` → *Jaringan venue* says what you expect. Green
      ("N logins from N addresses") means per-address limits are per-phone. Amber ("from 1 address")
      means they are shared by the whole event — survivable, since the ceilings are sized for it, but
      know which one you have.
- [ ] **`/admin/kesiapan`** shows no red: every team has accounts, a plan, posts with reachable
      questionnaires, and a START code.
- [ ] **`./deploy.sh warm`** after the last `optimize:clear`. Otherwise the first team to press Sync
      pays 1.7 s of key derivation in front of the crew.

## At the venue — the walk test

- [ ] **Place every reader**, then provision it: `node_id` and its key into NVS, and the matching row
      on `/admin/tap-nodes` pointing at the right post.
- [ ] **Start both gateways**, in two terminals:
      `./deploy.sh shell` is not where these run — they need the serial ports on the host:
      `php artisan eureka:tap-listen /dev/ttyUSB0 --gateway=1` and `…ttyUSB1 --gateway=2`.
- [ ] **Walk to every booth with a card and tap it three times.** Green LED and buzzer must fire
      immediately, before the radio has said anything.
- [ ] **`./deploy.sh walk-test`** and read the margin column:

      | Margin | Meaning |
      |---|---|
      | ≥ 20 dB | comfortable |
      | 10–20 dB | thin — move the node or raise power |
      | < 10 dB | it will drop during the event; reposition |

- [ ] **Every booth was heard by *both* gateways.** The walk test says so. One gateway is one device
      that can silence fifteen booths.
- [ ] **No booth is silent.** The walk test names them; a silent booth is the finding this exists to
      produce.
- [ ] **Every card is assigned.** Tap each team's card once anywhere, then assign the UIDs that turn
      up on `/admin/tap-nodes`. `./deploy.sh rehearsal` checks every team has one.

## The failures — run them, do not read them

Each of these is a thing that will happen. The point is that it has happened once already, with the
crew watching, in daylight.

### 1. The gateway is pulled mid-run

- [ ] Unplug gateway 1 while a team is playing.
- [ ] Tap a card **twenty times** at a booth. The node's LED must show an unacked pattern, and the
      local buzzer must still fire on every tap.
- [ ] Plug it back in.
- [ ] **All twenty arrive, once each, with the times they were taken** — not the time the cable went
      back in. `./deploy.sh rehearsal` confirms it: *"taps arrived late, carrying their own
      timestamps"*, with the longest delay, and whether they arrived in order.
- [ ] `/admin/tap-nodes` shows twenty arrivals and no duplicates.

### 2. A reader dies

- [ ] Switch a node off entirely (pull its powerbank).
- [ ] **The crew opens that post in Outpost Access instead**, by hand, and the team plays on.
      This is the fallback and it must be muscle memory, not a discovery.
- [ ] `/admin/tap-nodes` shows the node as silent within fifteen minutes.

### 3. The server is restarted mid-event

- [ ] `docker compose restart app` while a team is mid-questionnaire.
- [ ] The team's session survives — they keep answering and can hand in.
- [ ] The race clocks are unchanged. Two official clocks; a restart must not move either.
- [ ] A kiosk screen recovers on its own within a poll.

### 4. The restore

The one that is always written down and never done.

- [ ] Take a backup: `./deploy.sh backup`.
- [ ] Note the row counts it prints.
- [ ] **Restore it:** `./deploy.sh restore backups/eureka-<stamp>.sql.gz`. It names what it is about
      to overwrite, counts it, and makes you type `RESTORE`.
- [ ] The app comes back up, `doctor` is clean, and a team's floor plan still opens.
- [ ] **Time it.** Write the number here, because that number is your decision-making budget when it
      happens for real: ____ minutes.
- [ ] Do it once *with* `--with-storage`, so somebody has seen photos and archives come back too.

### 5. The uplink is cut

- [ ] Unplug the venue's internet.
- [ ] A team can still scan START, open a post, play a questionnaire and hand in. Gameplay must not
      depend on the uplink — only the certificate renewal, offsite backup, NTP and the contract
      channel do.
- [ ] Note what *did* break, and whether anyone noticed.

## After — before the real event starts

- [ ] **`./deploy.sh rehearsal`** and read it top to bottom. Anything still `·` is a drill not done.
- [ ] **Clear the rehearsal's own mess.** The sheet flags it: posts left open, races left running,
      test teams. A tap during the walk test opens a post, and it will still be open tomorrow.
- [ ] **`./deploy.sh backup`** once more, after the authoring is final, and check it went offsite.
- [ ] **Charge every node**, and have three or four spare powerbanks in a bag. The battery column on
      `/admin/tap-nodes` is how you see a booth going dark; spares are how you fix it.

---

## What this rehearsal cannot tell you

Said plainly, because a rehearsal that feels complete is the dangerous kind:

- **Load.** Fifteen crew with fifteen phones is not 5,000 players. That is the harness's job
  (`tools/loadtest/`), and its baseline row is still empty.
- **Airtime under contention.** Fifteen booths tapping at once is the condition the LoRa numbers were
  estimated for, and a walk test taps one booth at a time. Have several people tap simultaneously at
  least once and watch for unacked patterns.
- **The 2.4 GHz band with a full room in it.** §5 says this is where it will fail, and it cannot be
  rehearsed with an empty hall. Ask the WiFi vendor for the 2.4 GHz plan specifically.
