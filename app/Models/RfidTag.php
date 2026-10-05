<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One card, one team (SCALING-PLAN.md §6).
 *
 * UID only, and that is enough: the server maps UID → team and a tap opens a post *for that team*,
 * so a cloned card buys nothing worth having — the clone opens the same team's posts, which is what
 * the team could already do.
 */
class RfidTag extends Model
{
    protected $fillable = ['uid', 'team_id', 'label', 'is_active', 'valid_from', 'valid_until', 'last_used_at'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
            'last_used_at' => 'datetime',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /** One spelling for a UID, everywhere: uppercase hex, no separators. */
    public static function normalise(?string $uid): string
    {
        return strtoupper(preg_replace('/[^0-9A-Fa-f]/', '', (string) $uid));
    }

    /** Within its window, if it has one. A tag printed for one event must not open the next. */
    public function isUsable(?\DateTimeInterface $at = null): bool
    {
        $at = $at ? \Illuminate\Support\Carbon::instance($at) : now();

        if (! $this->is_active) {
            return false;
        }

        if ($this->valid_from && $at->lt($this->valid_from)) {
            return false;
        }

        return ! ($this->valid_until && $at->gt($this->valid_until));
    }
}
