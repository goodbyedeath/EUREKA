<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One admin's decision to open an indoor outpost's 3D camera for one team.
 *
 * Rows are kept after revoking rather than deleted, so "who opened what, when" survives
 * the event — during a live race that record is the only way to settle a dispute.
 */
class GameLocationUnlock extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_location_id',
        'user_id',
        'granted_by',
        'granted_at',
        'revoked_at',
    ];

    protected $casts = [
        'granted_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function gameLocation(): BelongsTo
    {
        return $this->belongsTo(GameLocation::class);
    }

    /** The team account the access was opened for. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    public function isActive(): bool
    {
        return $this->granted_at !== null && $this->revoked_at === null;
    }
}
