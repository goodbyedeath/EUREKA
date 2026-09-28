# Tap node — assembly and bench test

The booth device that proves a team arrived: **ESP32 + PN532 (RFID) + Ebyte E22-900T22D (LoRa)**, battery
powered, no cable leaving the booth. Chosen by the operator on 28 Sep 2026; the reasoning is in
`SCALING-PLAN.md` §6.

This file is for whoever builds and provisions the hardware. The **firmware and the wire protocol are
commissioned in `APK-WORK-ORDER.md` §46**, which is authoritative if a pin ever disagrees with this page.

Nothing here is built yet. Build one node first, bench-test it, and only then order fifteen.

---

## 1. Bill of materials

Per node (×15 booths, plus 2 spares):

| Part | Notes |
|---|---|
| ESP32 DevKitC (or any ESP32-WROOM board with ≥ 15 free GPIO) | avoid boards whose 3.3 V regulator is under 500 mA |
| **Ebyte E22-900T22D** | SX1262, UART, 22 dBm, 850.125–930.125 MHz. Confirm the **900** variant, not 400 |
| SMA 915–930 MHz whip antenna + pigtail | must match the band; an 868 antenna wastes power |
| **PN532 breakout** (red board, I²C mode) | ~5–7 cm read range; DIP switches set to I²C per the silkscreen |
| Active buzzer 3.3 V | local feedback, must not wait for the radio |
| LED green + LED red + 2 × 220 Ω | arrived / rejected |
| 5 V → 3.3 V regulator, **≥ 1 A** | the E22 draws ~120 mA peaks on transmit; a 300 mA AMS1117 browns out |
| 100 µF + 10 µF capacitors | across the E22's 3.3 V, as close to the module as possible |
| Powerbank 10,000 mAh + short USB cable | ~125 mA awake ⇒ **~45 h**, an event day with the setup morning in front of it. **Check the auto-off**: most powerbanks cut output below ~50–100 mA, so a node that sleeps deeply switches its own supply off |
| Enclosure, non-metal, with a flat face for the card | the reader coil must sit < 1 cm behind that face |

Tags: **MIFARE Classic 1K cards, one per team** (≈ 500, not one per player — the domain is per team).
UID-only is enough: the server maps UID → team, and a tap only opens a post *for that team*, so a cloned
card buys an attacker nothing worth having.

Gateway (×2, at the server rack): ESP32 + E22-900T22D + antenna, **powered and read over USB** from the
workstation. No IP address, no WiFi, nothing to knock off the air.

## 2. Wiring

ESP32 pins chosen to avoid the strapping pins (0, 2, 5, 12, 15) and the flash pins (6–11).

| Signal | ESP32 GPIO | To |
|---|---|---|
| UART2 TX | 17 | E22 **RXD** |
| UART2 RX | 16 | E22 **TXD** |
| E22 M0 | 25 | E22 M0 |
| E22 M1 | 26 | E22 M1 |
| E22 AUX | 27 (input, pull-up) | E22 AUX |
| I²C SDA | 21 | PN532 SDA |
| I²C SCL | 22 | PN532 SCL |
| PN532 IRQ | 32 (input) | PN532 IRQ |
| PN532 RSTO | 33 | PN532 RSTO |
| Buzzer | 14 | buzzer + |
| LED green | 13 | LED + 220 Ω |
| LED red | 4 | LED + 220 Ω |

Power: regulator 3.3 V to the ESP32 3V3 rail, the E22 and the PN532; all grounds common. **Never power the
E22 without the antenna connected** — transmitting into an open port can destroy the PA.

Placement inside the booth box: the **LoRa antenna vertical and outside the enclosure**, the PN532 coil flat
against the tap face, and **no metal behind the coil**. Keep the two apart; 13.56 MHz and 923 MHz do not
interfere, but a metal bracket near either one will spoil it.

## 3. Radio settings (all nodes and both gateways identical)

Configured over UART with M0 = M1 = 1 (configuration mode):

| Setting | Value | Why |
|---|---|---|
| Frequency channel | **73** → 850.125 + 73 = **923.125 MHz** | inside the 920–923 MHz plan used in Indonesia (**verify against Kominfo and the datasheet revision you receive**) |
| Air data rate | **9.6 kbps** | range is not our problem (50 m); shorter airtime means fewer collisions between 15 booths |
| Transmit power | as low as passes the walk test, ≤ 22 dBm | less power, less collision, longer battery |
| Mode | fixed transmission, gateway address `0x0001` | nodes address the gateway rather than shouting |
| Network address | one shared value, recorded in the runbook | keeps a neighbouring event's modules out |

A 26-byte tap frame at 9.6 kbps is ≈ 25 ms of air plus preamble. Fifteen booths average about one message
per second for the whole venue, so total airtime stays a few percent.

## 4. Provisioning, per node

Each node needs three things written to NVS at flash time — **node 1 through 15, never duplicated**:

1. `node_id` (1–15, and 101/102 for the gateways).
2. `node_key`, 16 random bytes, unique per node. It signs every frame; the server holds the same keys.
3. `post_id` — the `game_locations.id` this booth serves. A node knows which post it is; the server does not
   guess from position.

Write the three into the runbook as you go: a node whose key is unknown to the server is a brick, and a
node carrying another booth's `post_id` opens the wrong post for every team that taps it.

## 5. Bench test before ordering fifteen

Run all of these on the first unit. They are the same acceptance tests the firmware is commissioned
against (`APK-WORK-ORDER.md` §46), but here with a multimeter in hand:

1. **Tap to feedback under 1 second.** Card on the face → buzzer and green LED immediately, before the radio
   has answered anything.
2. **Tap to ACK under 1 second** at 50 m with a wall in between. Red LED (or a distinct pattern) when no
   ACK arrives.
3. **Pull the gateway.** Twenty taps while it is unplugged; plug it back in; all twenty arrive, **with their
   original timestamps**, in order, exactly once.
4. **Replay.** Repeat a captured frame: the server must record one arrival, not two.
5. **Wrong key.** A frame signed with the wrong key is dropped and logged, never recorded.
6. **Battery, and the powerbank's own cutoff.** Eight hours of realistic use on the exact powerbank model
   that will be deployed, plus the idle current measured with a meter. Then the counter-intuitive one: put the
   node into its lowest-power state and confirm the powerbank **does not switch itself off**. The booths have
   no mains — mains and network are at the server station only — so this supply is the whole story.
7. **Brown-out.** Watch the 3.3 V rail on a scope or a cheap USB meter during transmit. A dip that resets
   the ESP32 means the regulator or the capacitors are wrong — this is the most common failure on a first
   build.

## 6. What still has to exist on the server

Not built yet; it is phase 6 in `SCALING-PLAN.md`:

- `rfid_tags` (uid → team, active window) and `post_arrivals` (audit trail and per-booth queue data).
- A serial listener that reads the gateway's JSON lines and writes the same `game_location_unlocks` row
  **Outpost Access writes today** — which is why nothing downstream needs to change.
- Deduplication on `(node_id, seq)` across both gateways.
- `POST /api/v1/device/tap` for a future network-connected gateway, behind a device-token guard.
- Outpost Access stays as the manual override for a reader that dies mid-event.
