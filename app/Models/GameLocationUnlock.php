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

    /**
     * Close a post for a whole team — used when they have answered its questionnaire.
     *
     * Operator, 23 Sep: a post a team has finished must not be walked into again. Revoking the
     * access is what actually stops it (the AR gate answers `awaiting_unlock` again and the station
     * gate `checkin_required`); the `is_done` flag on the floor plan is only how the app shows it.
     * The whole team is closed, not one account, because the crew opens the whole team.
     *
     * Kept revocable rather than deleted, so a crew that needs to let a team back in can simply
     * reopen the post in Outpost Access.
     *
     * @return int how many rows were closed
     */
    public static function closeForTeamAt(int $gameLocationId, int $userId): int
    {
        $teamId = User::where('id', $userId)->value('team_id');
        $accountIds = $teamId
            ? User::where('team_id', $teamId)->pluck('id')
            : collect([$userId]);

        return static::where('game_location_id', $gameLocationId)
            ->whereIn('user_id', $accountIds)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }
}
