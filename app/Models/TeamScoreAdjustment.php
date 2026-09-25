<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One manual correction to a team's score. Signed: a negative row takes points away.
 *
 * Summed into the team's total by PointsCalculationService, so a correction shows up everywhere a
 * score is read — the kiosk, /admin/user-progress, the team card in the app — and not only on the
 * page where it was typed.
 */
class TeamScoreAdjustment extends Model
{
    protected $fillable = ['team_id', 'points', 'reason', 'created_by'];

    protected function casts(): array
    {
        return ['points' => 'integer'];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
