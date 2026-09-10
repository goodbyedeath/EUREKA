<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * The code a team scans at the start line to begin their run.
 *
 * The same mechanism serves both modes; `indoor_map_id` is the only difference. Linked to
 * a plan, the scan goes on to that venue's clue and then the plan. Unlinked, it is an
 * outdoor start: the clock begins and the team goes to their dashboard and the GPS map.
 *
 * Either way the clock stops the same way — automatically, when the last post that counts
 * toward finishing has been cleared.
 */
class RaceStart extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'indoor_map_id',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function indoorMap(): BelongsTo
    {
        return $this->belongsTo(IndoorMap::class);
    }

    /** An indoor start leads to a clue and a plan; an outdoor one leads to the dashboard. */
    public function isIndoor(): bool
    {
        return $this->indoor_map_id !== null;
    }

    /**
     * Codes are printed and taped to a wall, so they must be short enough to read back
     * over a radio and unguessable enough that a team cannot start early.
     */
    public static function generateCode(): string
    {
        do {
            $code = 'START-' . strtoupper(Str::random(8));
        } while (static::where('code', $code)->exists());

        return $code;
    }
}
