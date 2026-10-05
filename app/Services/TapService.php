<?php

namespace App\Services;

use App\Models\GameLocationUnlock;
use App\Models\PostArrival;
use App\Models\RfidTag;
use App\Models\TapNode;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * A card tapped at a booth opens that post for that team — SCALING-PLAN.md §6, the server half.
 *
 * The whole design is in one sentence: **a tap writes the row Outpost Access writes.** Not a new
 * notion of "open", not a parallel gate — the same `game_location_unlocks` row, so the AR outpost,
 * the station gate and the questionnaire all behave exactly as they do when a crew member clicks.
 * The reader becomes another way to produce a fact that already exists, and Outpost Access stays as
 * the override for a reader that dies mid-event.
 *
 * Three properties the plan asks for, and where each one lives:
 *
 *   - **A tap opens the post** — `record()`, which ends in the same `updateOrCreate` the panel uses.
 *   - **A replayed tap does not double** — the unique `(node_id, seq)` on `post_arrivals`. Not a
 *     check-then-insert, which two gateways delivering the same frame in the same millisecond would
 *     both pass; the database refuses the second one. That is also how two gateways collapse into
 *     one arrival, and why `seq` must never be reused by a node.
 *   - **A dead gateway loses nothing** — nothing here cares when a frame arrived. `tapped_at` is the
 *     node's own clock, so twenty taps flushed after a twenty-minute outage land with the times they
 *     were taken at. A race with two official clocks cannot have arrivals stamped by luck.
 */
class TapService
{
    /**
     * One frame from a gateway, or one HTTP tap. Idempotent on `(node_id, seq)`.
     *
     * @param  array{node:int, seq:int, uid?:?string, ts?:?int, vbat?:?int, rssi?:?int, snr?:?float, gw?:?int, hmac_ok?:bool, type?:int}  $frame
     * @return PostArrival  the arrival as it stands — a fresh one, or the one already recorded
     */
    public function record(array $frame): PostArrival
    {
        $nodeId = (int) ($frame['node'] ?? 0);
        $seq = (int) ($frame['seq'] ?? 0);
        $uid = RfidTag::normalise($frame['uid'] ?? null);
        $now = now();

        // The node's own clock. 0 or missing means it had not synced yet, and the firmware is told to
        // send 0 rather than a wrong time — so the server stamps it and the gap is honest.
        $tappedAt = ! empty($frame['ts']) && (int) $frame['ts'] > 0
            ? Carbon::createFromTimestamp((int) $frame['ts'])
            : $now;

        $node = TapNode::where('node_id', $nodeId)->first();

        // Health first, and regardless of what the frame turns out to be: a heartbeat and a refused
        // tap are both evidence the booth is alive.
        if ($node) {
            $node->forceFill(array_filter([
                'last_seen_at' => $now,
                'last_seq' => max((int) $node->last_seq, $seq),
                'vbat_mv' => $frame['vbat'] ?? null,
                'rssi' => $frame['rssi'] ?? null,
            ], fn ($v) => $v !== null))->save();
        }

        [$outcome, $teamId, $locationId] = $this->decide($frame, $node, $uid, $tappedAt);

        $arrival = $this->remember($frame, $nodeId, $seq, $uid, $teamId, $locationId, $outcome, $tappedAt, $now);

        // A second delivery of a frame already recorded: nothing more to do, and in particular no
        // second unlock. The caller gets the original arrival back so it can say so.
        if (! $arrival->wasRecentlyCreated) {
            return $arrival;
        }

        if ($outcome === PostArrival::OPENED) {
            $this->open($locationId, $teamId, $nodeId);
        }

        if (in_array($outcome, [PostArrival::OPENED, PostArrival::ALREADY_OPEN], true) && $uid !== '') {
            RfidTag::where('uid', $uid)->update(['last_used_at' => $now]);
        }

        if ($outcome === PostArrival::BAD_SIGNATURE) {
            // Loud on purpose. Anyone with a LoRa module could otherwise claim "Team 7 reached Post 3",
            // and a spoofing attempt is something the crew should be able to see.
            Log::warning('Tap frame with a bad signature', ['node' => $nodeId, 'seq' => $seq, 'uid' => $uid]);
        }

        return $arrival;
    }

    /**
     * What this frame means, in the order the crew would ask.
     *
     * @return array{0:string, 1:?int, 2:?int}  outcome, team id, game location id
     */
    private function decide(array $frame, ?TapNode $node, string $uid, Carbon $tappedAt): array
    {
        // The gateway checks the HMAC and reports it; over LoRa the key never has to travel. A frame
        // that failed is recorded and goes no further.
        if (array_key_exists('hmac_ok', $frame) && $frame['hmac_ok'] === false) {
            return [PostArrival::BAD_SIGNATURE, null, $node?->game_location_id];
        }

        // Type 0x02 is a heartbeat: battery and "I am still here", no card involved.
        if ((int) ($frame['type'] ?? 1) === 2 || $uid === '') {
            return [PostArrival::HEARTBEAT, null, $node?->game_location_id];
        }

        if (! $node) {
            return [PostArrival::UNKNOWN_NODE, null, null];
        }

        if (! $node->is_active) {
            return [PostArrival::NODE_INACTIVE, null, $node->game_location_id];
        }

        if (! $node->game_location_id) {
            return [PostArrival::NODE_UNPLACED, null, null];
        }

        $tag = RfidTag::where('uid', $uid)->first();

        if (! $tag) {
            return [PostArrival::UNKNOWN_TAG, null, $node->game_location_id];
        }

        if (! $tag->team_id) {
            return [PostArrival::TAG_UNASSIGNED, null, $node->game_location_id];
        }

        if (! $tag->isUsable($tappedAt)) {
            return [PostArrival::TAG_EXPIRED, $tag->team_id, $node->game_location_id];
        }

        $accounts = User::where('team_id', $tag->team_id)->pluck('id');

        if ($accounts->isEmpty()) {
            return [PostArrival::NO_ACCOUNTS, $tag->team_id, $node->game_location_id];
        }

        // The same refusal Outpost Access makes: opening a post that is not on this team's floor plan
        // changes nothing they can see, so it is a fault to report rather than a thing to do quietly.
        if (! $this->onTeamPlan($node->game_location_id, (int) $tag->team_id)) {
            return [PostArrival::NOT_ON_PLAN, $tag->team_id, $node->game_location_id];
        }

        $alreadyOpen = GameLocationUnlock::where('game_location_id', $node->game_location_id)
            ->whereIn('user_id', $accounts)
            ->whereNull('revoked_at')
            ->exists();

        return [
            $alreadyOpen ? PostArrival::ALREADY_OPEN : PostArrival::OPENED,
            $tag->team_id,
            $node->game_location_id,
        ];
    }

    /**
     * Write the arrival, or hand back the one already there.
     *
     * The insert is allowed to fail on the unique key and that is the dedupe: two gateways racing on
     * the same frame both try, one wins, and the loser reads the winner's row.
     */
    private function remember(
        array $frame, int $nodeId, int $seq, string $uid,
        ?int $teamId, ?int $locationId, string $outcome, Carbon $tappedAt, Carbon $now,
    ): PostArrival {
        $attributes = [
            'uid' => $uid !== '' ? $uid : null,
            'team_id' => $teamId,
            'game_location_id' => $locationId,
            'outcome' => $outcome,
            'tapped_at' => $tappedAt,
            'received_at' => $now,
            'gateway' => isset($frame['gw']) ? (int) $frame['gw'] : null,
            'rssi' => isset($frame['rssi']) ? (int) $frame['rssi'] : null,
            'snr' => isset($frame['snr']) ? (float) $frame['snr'] : null,
            'vbat_mv' => isset($frame['vbat']) ? (int) $frame['vbat'] : null,
            'hmac_ok' => (bool) ($frame['hmac_ok'] ?? true),
        ];

        try {
            $arrival = PostArrival::create(['node_id' => $nodeId, 'seq' => $seq] + $attributes);
            $arrival->wasRecentlyCreated = true;

            return $arrival;
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            $existing = PostArrival::where('node_id', $nodeId)->where('seq', $seq)->first();

            if ($existing) {
                $existing->wasRecentlyCreated = false;

                return $existing;
            }

            throw new \RuntimeException("Arrival ($nodeId, $seq) collided but cannot be read back.");
        }
    }

    /**
     * Open the post for every account of the team — exactly what `OutpostAccessPanel::toggleTeam()`
     * does, because the crew opens a team and not a phone.
     *
     * `granted_by` stays null: no admin decided this. Which node did is in the arrival row, which is
     * where the audit belongs.
     */
    private function open(?int $locationId, ?int $teamId, int $nodeId): void
    {
        if (! $locationId || ! $teamId) {
            return;
        }

        $accounts = User::where('team_id', $teamId)->pluck('id');

        DB::transaction(function () use ($accounts, $locationId) {
            foreach ($accounts as $userId) {
                GameLocationUnlock::updateOrCreate(
                    ['game_location_id' => $locationId, 'user_id' => $userId],
                    ['granted_by' => null, 'granted_at' => now(), 'revoked_at' => null],
                );
            }
        });

        Log::info('Outpost access opened by a tap', [
            'game_location_id' => $locationId, 'team_id' => $teamId, 'node' => $nodeId,
        ]);
    }

    /**
     * Is this post on the team's floor plan?
     *
     * A post carried by no plan at all is treated as open to everyone, which is how
     * `OutpostAccessPanel` treats it too: an outdoor-only event has no plans, and refusing every tap
     * there would be a rule inherited from a screen that is not in use.
     */
    private function onTeamPlan(int $locationId, int $teamId): bool
    {
        $plans = DB::table('indoor_map_spots')
            ->where('game_location_id', $locationId)
            ->whereNotNull('indoor_map_id')
            ->distinct()
            ->pluck('indoor_map_id');

        if ($plans->isEmpty()) {
            return true;
        }

        $teamPlan = DB::table('teams')->where('id', $teamId)->value('indoor_map_id');

        return $teamPlan !== null && $plans->contains($teamPlan);
    }
}
