<?php

namespace App\Services;

use App\Exceptions\QuizRuleException;
use App\Models\GameAssessment;
use App\Models\Team;
use Illuminate\Support\Facades\DB;

/**
 * The one place a fun_game score is recorded and paid into Team.points.
 *
 * The facilitator scores on the TEAM'S phone (operator decision), through the app or the retired
 * web form; the admin page is the correction path. All three used to disagree:
 *
 *  - the team form paid `initial_points + additional − penalty` into Team.points, so every station
 *    re-credited the team's whole starting balance (7 stations = +7000 phantom points);
 *  - the admin form stored `deposit − penalty`, dropping additional points, and never touched
 *    Team.points at all, so a correction never reached the kiosk leaderboard;
 *  - `additional` was free up to 999999 on a phone the team holds.
 *
 * Stored shape is unchanged, because every reader (PointsCalculationService, KioskController,
 * DashboardStats, UserProgress, TeamManager) computes gain as `total_deposit − initial_points`:
 *
 *     deposit       = team.initial_points   (reference only, never paid)
 *     total_deposit = deposit + additional − penalty
 *     team gain     = additional − penalty  (what Team.points moves by)
 *
 * Re-scoring pays only the difference from the previous gain.
 */
class GameAssessmentService
{
    public const MAX_PENALTY = 100000;

    /** Highest `additional_points` a facilitator may award: the question's own points. */
    public function maxAdditional(GameAssessment $assessment): int
    {
        return max(0, (int) ($assessment->question?->points ?? 0));
    }

    /**
     * @return array{assessment: GameAssessment, team_gain: int, team_points: int|null}
     */
    public function record(
        GameAssessment $assessment,
        int $additional,
        int $penalty,
        ?string $notes,
        int $assessorId,
        bool $allowReassess = false,
        ?string $photoPath = null,
    ): array {
        return DB::transaction(function () use ($assessment, $additional, $penalty, $notes, $assessorId, $allowReassess, $photoPath) {
            $locked = GameAssessment::with(['question', 'user'])->lockForUpdate()->findOrFail($assessment->id);

            if ($locked->is_assessed && ! $allowReassess) {
                throw QuizRuleException::gameAlreadyAssessed();
            }

            $max = $this->maxAdditional($locked);
            if ($additional < 0 || $additional > $max) {
                throw new QuizRuleException('additional_out_of_range', "additional_points must be between 0 and {$max}.", 422);
            }
            if ($penalty < 0 || $penalty > self::MAX_PENALTY) {
                throw new QuizRuleException('penalty_out_of_range', 'penalty must be between 0 and '.self::MAX_PENALTY.'.', 422);
            }

            $team = $locked->user?->team_id ? Team::lockForUpdate()->find($locked->user->team_id) : null;
            $base = (int) ($team->initial_points ?? 1000);

            // What this assessment already paid, if it was scored before. An assessment that was
            // "skipped" stored total_deposit = deposit, i.e. a gain of 0, which this reads correctly.
            $previousGain = $locked->is_assessed
                ? (int) $locked->total_deposit - (int) $locked->deposit
                : 0;
            $gain = $additional - $penalty;

            $locked->update([
                'deposit' => $base,
                'additional_points' => $additional,
                'penalty' => $penalty,
                'total_deposit' => $base + $gain,
                'notes' => $notes,
                'is_assessed' => true,
                'assessed_by' => $assessorId,
                'assessed_at' => now(),
            ] + ($photoPath ? [
                'facilitator_photo' => $photoPath,
                'facilitator_photo_captured_at' => now(),
            ] : []));

            $delta = $gain - $previousGain;
            if ($team && $delta !== 0) {
                $delta > 0 ? $team->addPoints($delta) : $team->deductPoints(-$delta);
            }

            return [
                'assessment' => $locked->fresh(['question']),
                'team_gain' => $gain,
                'team_points' => $team ? (int) $team->fresh()->points : null,
            ];
        });
    }
}
