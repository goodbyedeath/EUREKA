<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One team's payment for tapping one 3D object.
 *
 * Paid once per team, not per phone: `claimedByTeam()` is what the endpoint asks before awarding,
 * and the unique index is the second line of defence for a doubled request.
 */
class HotspotClaim extends Model
{
    protected $fillable = [
        'hotspot_id',
        'user_id',
        'game_location_id',
        // Snapshot of what the object was worth when it was claimed. Re-pricing the object later
        // must not change a score that has already been read out.
        'points',
        'claimed_at',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'claimed_at' => 'datetime',
        ];
    }

    public function hotspot(): BelongsTo
    {
        return $this->belongsTo(Hotspot::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** The team's existing claim on this object, if any — a team plays on one score, not one phone. */
    public static function claimedByTeam(int $hotspotId, User $user): ?self
    {
        $accounts = $user->team_id
            ? User::where('team_id', $user->team_id)->pluck('id')
            : collect([$user->id]);

        return static::where('hotspot_id', $hotspotId)->whereIn('user_id', $accounts)->first();
    }

    /** Which of these objects this team has already been paid for. @return list<int> */
    public static function claimedIdsFor(?User $user, array $hotspotIds): array
    {
        if (! $user || $hotspotIds === []) {
            return [];
        }

        $accounts = $user->team_id
            ? User::where('team_id', $user->team_id)->pluck('id')
            : collect([$user->id]);

        return static::whereIn('hotspot_id', $hotspotIds)
            ->whereIn('user_id', $accounts)
            ->pluck('hotspot_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
