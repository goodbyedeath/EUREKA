<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PostArrival;
use App\Models\TapNode;
use App\Services\TapService;
use Illuminate\Http\Request;

/**
 * A tap over HTTP, for a gateway that has a network instead of a serial cable.
 *
 * Not the path the venue uses — the booths have neither mains nor a network drop, so taps travel by
 * LoRa to a gateway beside the server and arrive through `eureka:tap-listen` (SCALING-PLAN.md §6).
 * This exists because the plan promises it for "a future network-connected gateway", and because it
 * is the only way to exercise the whole server half without hardware.
 *
 * **Not a player's token.** A Sanctum token belongs to a team, and a device that can open posts must
 * not be authenticated as one of the people it opens them for. The guard here is the node's own HMAC
 * key: the frame is signed, and `node_key` on `tap_nodes` verifies it. A node with no key configured
 * cannot use this route at all — failing closed is the only safe default for an endpoint whose whole
 * job is to unlock things.
 */
class DeviceTapController extends Controller
{
    public function store(Request $request, TapService $taps)
    {
        $data = $request->validate([
            'node' => 'required|integer|min:0|max:65535',
            'seq' => 'required|integer|min:0',
            'uid' => 'nullable|string|max:32',
            // The node's own clock, unix seconds. 0 or absent means it had not synced, and the
            // server stamps it — an honest gap beats a wrong time.
            'ts' => 'nullable|integer|min:0',
            'type' => 'nullable|integer|in:1,2,3',
            'vbat' => 'nullable|integer|min:0|max:65535',
            'rssi' => 'nullable|integer|min:-200|max:50',
            'snr' => 'nullable|numeric',
            'gw' => 'nullable|integer|min:0|max:255',
            // HMAC-SHA256 over the canonical string below, hex. Truncation is allowed: the LoRa frame
            // carries only 6 bytes of it, so a gateway relaying one has 12 hex characters to offer.
            'hmac' => 'required|string|min:8|max:64',
        ]);

        $node = TapNode::where('node_id', (int) $data['node'])->first();

        if (! $node || ! $node->node_key) {
            // Deliberately the same answer for "no such node" and "node has no key": an endpoint that
            // unlocks posts should not confirm which node ids exist.
            return response()->json([
                'success' => false,
                'error' => 'unknown_device',
                'message' => 'This device is not provisioned for HTTP taps.',
            ], 403);
        }

        $expected = hash_hmac('sha256', $this->canonical($data), $node->node_key);
        $offered = strtolower(preg_replace('/[^0-9a-fA-F]/', '', $data['hmac']));

        // Compare only as many characters as were offered, so a 6-byte LoRa signature and a full one
        // both work — and always with a timing-safe comparison over the same length.
        if (! hash_equals(substr($expected, 0, strlen($offered)), $offered)) {
            $arrival = $taps->record(['hmac_ok' => false] + $data);

            return response()->json([
                'success' => false,
                'error' => 'bad_signature',
                'message' => 'The frame signature does not match this device.',
                'arrival_id' => $arrival->id,
            ], 401);
        }

        $arrival = $taps->record($data + ['hmac_ok' => true]);

        return response()->json([
            'success' => $arrival->worked(),
            // The same vocabulary the admin page shows, so a gateway's log and the crew's screen
            // describe a refusal with the same word.
            'outcome' => $arrival->outcome,
            'message' => $arrival->label(),
            // True when this exact frame had already been recorded — a retry, or the second gateway.
            // The node should stop retrying on this, not treat it as a failure.
            'duplicate' => ! $arrival->wasRecentlyCreated,
            'arrival_id' => $arrival->id,
            'team_id' => $arrival->team_id,
            'game_location_id' => $arrival->game_location_id,
            // The server's clock, so a node can sync from the reply instead of asking separately.
            'server_ts' => now()->timestamp,
        ], $arrival->worked() || $arrival->outcome === PostArrival::HEARTBEAT ? 200 : 422);
    }

    /**
     * What is signed: the fields that matter, in a fixed order, joined by `|`.
     *
     * Only these, and never the whole body: a gateway adds `rssi`, `snr` and `gw` of its own, and
     * signing those would mean the node had to know what the gateway was going to observe.
     */
    private function canonical(array $data): string
    {
        return implode('|', [
            (int) $data['node'],
            (int) $data['seq'],
            strtoupper((string) ($data['uid'] ?? '')),
            (int) ($data['ts'] ?? 0),
            (int) ($data['type'] ?? 1),
        ]);
    }
}
