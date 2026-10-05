<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One RFID reader at one booth (SCALING-PLAN.md §6).
 *
 * `node_id` is the two bytes that travel in the LoRa frame. Which post it stands at is here and not
 * in the frame — the client agent was right to refuse to invent a `post` field the gateway cannot
 * know (report #48) — so moving a booth is editing one row rather than re-provisioning two gateways.
 */
class TapNode extends Model
{
    protected $fillable = [
        'node_id', 'label', 'game_location_id', 'node_key', 'is_active',
        'last_seq', 'vbat_mv', 'rssi', 'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'node_id' => 'integer',
            'is_active' => 'boolean',
            'last_seq' => 'integer',
            'vbat_mv' => 'integer',
            'rssi' => 'integer',
            'last_seen_at' => 'datetime',
        ];
    }

    public function gameLocation(): BelongsTo
    {
        return $this->belongsTo(GameLocation::class);
    }

    /** Has this booth been heard from lately? The booths run on powerbanks, so silence matters. */
    public function isSilent(int $minutes = 15): bool
    {
        return $this->last_seen_at === null || $this->last_seen_at->lt(now()->subMinutes($minutes));
    }

    /**
     * A powerbank-fed node reports millivolts. Below ~3.5 V a Li-ion pack is nearly done; the exact
     * figure depends on the pack, so this is a hint for the crew rather than a measurement.
     */
    public function batteryLow(): bool
    {
        return $this->vbat_mv !== null && $this->vbat_mv > 0 && $this->vbat_mv < 3500;
    }
}
