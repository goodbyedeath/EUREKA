<?php

namespace App\Services;

use App\Models\User;
use App\Models\QuizAttempt;
use App\Models\UserAnswer;
use App\Models\GameAssessment;
use App\Models\Team;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PointsCalculationService
{
    /**
     * A team's score, as /admin/user-progress, the kiosk and the app all show it — one formula, so
     * the three can never disagree again:
     *
     *   total = initial_points (once)
     *         + Σ points_earned of every answer     in COMPLETED attempts by the team's accounts
     *           (not only is_correct ones: a Tebak Gambar answer earns partial credit without being
     *           wholly right; every other type stores 0 when wrong, so for them nothing changes)
     *         + Σ (total_deposit − initial_points)  of assessed games on those attempts
     *         + Σ points of a tapped 3D object's claims
     *         + Σ points of the crew's manual corrections   (team_score_adjustments, signed)
     *
     * The game term = additional − penalty and may be negative: a penalty larger than the award
     * lowers the score (operator, 14 Sep; user-progress used to clamp it to 0 while the kiosk did
     * not). Every completed attempt counts, as it always has on user-progress. $since narrows to
     * attempts created from then on — the admin page's timeframe filter.
     *
     * @return array{total:int, base_points:int, earned_points:int, assessment_points:int, object_points:int, manual_points:int, attempts_completed:int}
     */
    public function teamScore(Team $team, ?\DateTimeInterface $since = null): array
    {
        // One team is the bulk path with one team in it — see teamScores(), which is where the
        // formula now lives. Six queries became five, and there is only one of them to keep right.
        return $this->teamScores([$team], $since)[$team->id];
    }

    /**
     * The same formula as teamScore(), for any number of teams in five queries rather than six per
     * team.
     *
     * The kiosk board called teamScore() once per team, and each call is six queries of its own: at
     * 500 teams that is ~3,000 queries per poll, multiplied by every screen in the venue
     * (SCALING-PLAN.md §3b). None of the arithmetic ever needed to be per-team — every term is a SUM
     * that MySQL can group by `users.team_id` — so this groups instead, and teamScore() is now this
     * method with one team in it. That is deliberate: one formula, so the board and the admin pages
     * cannot drift apart again, and the single-team path keeps exercising the same SQL.
     *
     * A team with nothing to its name still gets a row — its base balance and zeros — so a caller
     * walking its own team list never has to guess which keys exist.
     *
     * @param  iterable<\App\Models\Team>  $teams
     * @return array<int, array{total:int, base_points:int, earned_points:int, assessment_points:int, object_points:int, manual_points:int, attempts_completed:int}>
     */
    public function teamScores(iterable $teams, ?\DateTimeInterface $since = null): array
    {
        $teams = collect($teams)->keyBy('id');
        $ids = $teams->keys()->all();

        if (! $ids) {
            return [];
        }

        // Anything hanging off an attempt joins quiz_attempts → users, so the grouping key is the
        // team and the `completed` and `$since` filters are the ones scoreFor() applies.
        $perAttempt = fn (string $table, string $alias) => DB::table("$table as $alias")
            ->join('quiz_attempts as qa', 'qa.id', '=', "$alias.quiz_attempt_id")
            ->join('users as u', 'u.id', '=', 'qa.user_id')
            ->where('qa.status', QuizAttempt::STATUS_COMPLETED)
            ->whereIn('u.team_id', $ids)
            ->when($since, fn ($q) => $q->where('qa.created_at', '>=', $since))
            ->groupBy('u.team_id');

        // Unfiltered on is_correct, as ever: a Tebak Gambar answer earns partial credit without
        // being wholly right, and every other type stores 0 when wrong.
        $earned = $perAttempt('user_answers', 'ua')
            ->selectRaw('u.team_id as team_id, COALESCE(SUM(ua.points_earned), 0) as v')
            ->pluck('v', 'team_id');

        // total_deposit is a balance, not a gain, so the starting points come back out of it once
        // per assessed row. A null deposit sums as 0 and still pays the base back, which is exactly
        // what `(int) $a->total_deposit - $base` did row by row.
        $assessed = $perAttempt('game_assessments', 'ga')
            ->where('ga.is_assessed', true)
            ->selectRaw('u.team_id as team_id, COUNT(*) as n, COALESCE(SUM(ga.total_deposit), 0) as deposit')
            ->get()
            ->keyBy('team_id');

        $attempts = DB::table('quiz_attempts as qa')
            ->join('users as u', 'u.id', '=', 'qa.user_id')
            ->where('qa.status', QuizAttempt::STATUS_COMPLETED)
            ->whereIn('u.team_id', $ids)
            ->when($since, fn ($q) => $q->where('qa.created_at', '>=', $since))
            ->groupBy('u.team_id')
            ->selectRaw('u.team_id as team_id, COUNT(*) as v')
            ->pluck('v', 'team_id');

        // A tapped 3D object belongs to no attempt — a team may take the points and never open the
        // questionnaire — so this term is filtered on its own timestamp, as scoreFor() does.
        $objects = DB::table('hotspot_claims as hc')
            ->join('users as u', 'u.id', '=', 'hc.user_id')
            ->whereIn('u.team_id', $ids)
            ->when($since, fn ($q) => $q->where('hc.claimed_at', '>=', $since))
            ->groupBy('u.team_id')
            ->selectRaw('u.team_id as team_id, COALESCE(SUM(hc.points), 0) as v')
            ->pluck('v', 'team_id');

        $manual = DB::table('team_score_adjustments')
            ->whereIn('team_id', $ids)
            ->when($since, fn ($q) => $q->where('created_at', '>=', $since))
            ->groupBy('team_id')
            ->selectRaw('team_id, COALESCE(SUM(points), 0) as v')
            ->pluck('v', 'team_id');

        $out = [];

        foreach ($teams as $id => $team) {
            $base = (int) ($team->initial_points ?? 0);
            $row = $assessed->get($id);

            $e = (int) ($earned[$id] ?? 0);
            $a = $row ? (int) $row->deposit - ((int) $row->n * $base) : 0;
            $o = (int) ($objects[$id] ?? 0);
            $m = (int) ($manual[$id] ?? 0);

            $out[$id] = [
                'total' => $base + $e + $a + $o + $m,
                'base_points' => $base,
                'earned_points' => $e,
                'assessment_points' => $a,
                'object_points' => $o,
                'manual_points' => $m,
                'attempts_completed' => (int) ($attempts[$id] ?? 0),
            ];
        }

        return $out;
    }

    /**
     * What the crew corrected by hand. Signed, so a row may take points away, and counted on the
     * team rather than on an account: a correction is made for the team, not for one phone.
     */
    private function manualFor(Team $team, ?\DateTimeInterface $since): int
    {
        return (int) \App\Models\TeamScoreAdjustment::where('team_id', $team->id)
            ->when($since, fn ($q) => $q->where('created_at', '>=', $since))
            ->sum('points');
    }

    /**
     * One account's share of that score, on the same formula and its team's base.
     *
     * Two terms here are the team's, not the account's: the base balance and the crew's manual
     * corrections. Both are shown per member on /admin/user-progress, which is why they are counted the
     * same way — the column there is "Team Points", and a correction the crew has just made has to be
     * visible in it. Nothing sums these rows into a team total (teamScore does that from the team's
     * own accounts and one base), so repeating them cannot double-count.
     */
    public function userScore(User $user, ?\DateTimeInterface $since = null): array
    {
        $team = $user->team;

        return $this->scoreFor(
            [$user->id],
            (int) ($team?->initial_points ?? 0),
            $since,
            $team ? $this->manualFor($team, $since) : 0,
        );
    }

    private function scoreFor(array $userIds, int $base, ?\DateTimeInterface $since, int $manual = 0): array
    {
        $attemptIds = QuizAttempt::whereIn('user_id', $userIds ?: [0])
            ->where('status', QuizAttempt::STATUS_COMPLETED)
            ->when($since, fn ($q) => $q->where('created_at', '>=', $since))
            ->pluck('id');

        // Every answer's points_earned, right or not. A "Tebak Gambar" answer seven boxes of ten
        // right is not is_correct yet earned seven tenths of the points; filtering on is_correct
        // would throw that away. A wrong answer of any other type stores 0, so nothing else moves.
        $earned = (int) UserAnswer::whereIn('quiz_attempt_id', $attemptIds)
            ->sum('points_earned');

        $assessment = (int) GameAssessment::whereIn('quiz_attempt_id', $attemptIds)
            ->where('is_assessed', true)
            ->get(['total_deposit'])
            ->sum(fn ($a) => (int) $a->total_deposit - $base);

        // The third source (operator, 23 Sep): a 3D object with a price, tapped at the post. It does
        // not belong to any attempt — a team may tap the object and never open the questionnaire —
        // so it is summed from the claims themselves, on the amount recorded at the time.
        $objects = (int) \App\Models\HotspotClaim::whereIn('user_id', $userIds ?: [0])
            ->when($since, fn ($q) => $q->where('claimed_at', '>=', $since))
            ->sum('points');

        return [
            'total' => $base + $earned + $assessment + $objects + $manual,
            'base_points' => $base,
            'earned_points' => $earned,
            'assessment_points' => $assessment,
            'object_points' => $objects,
            // The crew's own corrections (operator, 25 Sep). Kept as its own line rather than folded
            // into another: a score that was put right by hand should say so wherever it is read.
            'manual_points' => $manual,
            'attempts_completed' => $attemptIds->count(),
        ];
    }

    /**
     * The same score, split per attempt, so a history row can show what that outpost was worth
     * (APK report #23: rows carried `total_score`, which is what submit wrote — the old base
     * balance plus quiz points, and never the facilitator's game points).
     *
     * Two grouped queries, not two per row. base_points stays out of the rows: it is paid once
     * for the team, so base + Σ points over the team's completed attempts = teamScore total.
     *
     * @param  \Illuminate\Support\Collection<int, int>|array<int, int>  $attemptIds
     * @return array<int, array{earned_points:int, assessment_points:int, points:int}>
     */
    public function breakdownForAttempts($attemptIds, int $base): array
    {
        $ids = collect($attemptIds)->all();
        if (! $ids) {
            return [];
        }

        // Unfiltered for the same reason as teamScore(): partial credit is earned, not correct.
        $earned = UserAnswer::whereIn('quiz_attempt_id', $ids)
            ->selectRaw('quiz_attempt_id, SUM(points_earned) AS total')
            ->groupBy('quiz_attempt_id')
            ->pluck('total', 'quiz_attempt_id');

        $assessed = GameAssessment::whereIn('quiz_attempt_id', $ids)
            ->where('is_assessed', true)
            ->get(['quiz_attempt_id', 'total_deposit'])
            ->groupBy('quiz_attempt_id')
            ->map(fn ($rows) => $rows->sum(fn ($a) => (int) $a->total_deposit - $base));

        $out = [];
        foreach ($ids as $id) {
            $e = (int) ($earned[$id] ?? 0);
            $a = (int) ($assessed[$id] ?? 0);
            $out[$id] = ['earned_points' => $e, 'assessment_points' => $a, 'points' => $e + $a];
        }

        return $out;
    }

    /**
     * Calculate earned points from a single quiz attempt (correct answers only)
     * IMPORTANT: Uses points_earned from user_answers table, NOT question->points
     * This ensures fun_game questions (which have points_earned = 0) are handled correctly
     */
    public function calculateEarnedPoints(QuizAttempt $attempt): int
    {
        // Unfiltered: see teamScore(). Partial credit counts though the answer is not wholly right.
        return UserAnswer::where('quiz_attempt_id', $attempt->id)
            ->get()
            ->sum(function($answer) {
                // Use points_earned from user_answers table to match Livewire logic
                return $answer->points_earned ?? 0;
            });
    }

    /**
     * Calculate team points for a single attempt (base + earned + assessment)
     */
    public function calculateTeamPoints(QuizAttempt $attempt): int
    {
        $user = $attempt->user;
        if (!$user) {
            return 0;
        }
        
        $basePoints = $this->getUserBasePoints($user);
        $earnedPoints = $this->calculateEarnedPoints($attempt);
        
        // Check for manual assessment override
        $assessment = GameAssessment::where('quiz_attempt_id', $attempt->id)
            ->where('user_id', $attempt->user_id)
            ->where('is_assessed', true)
            ->first();
            
        if ($assessment && $assessment->total_deposit !== null) {
            return $assessment->total_deposit;
        }
        
        return $basePoints + $earnedPoints;
    }

    /**
     * Calculate progressive total points for a user across all completed questionnaires
     */
    public function calculateUserTotalPoints(User $user, Collection $attempts = null): int
    {
        if (!$attempts) {
            $attempts = $user->quizAttempts()
                ->where('status', QuizAttempt::STATUS_COMPLETED)
                ->with(['userAnswers.question', 'gameAssessments'])
                ->get();
        }
        
        $completedAttempts = $attempts->where('status', QuizAttempt::STATUS_COMPLETED);
        
        if ($completedAttempts->isEmpty()) {
            return $this->getUserBasePoints($user);
        }
        
        // Sort attempts chronologically to ensure progressive calculation
        $sortedAttempts = $completedAttempts->sortBy('completed_at');
        
        // Start with base points
        $currentPoints = $this->getUserBasePoints($user);
        
        // Track completed questionnaires to avoid double-counting
        $completedQuestionnaires = [];
        
        foreach ($sortedAttempts as $attempt) {
            // Skip if questionnaire already completed (take first completion only)
            if (in_array($attempt->questionnaire_id, $completedQuestionnaires)) {
                continue;
            }
            
            // Mark questionnaire as completed
            $completedQuestionnaires[] = $attempt->questionnaire_id;
            
            // Add earned points progressively
            $earnedPoints = $this->calculateEarnedPoints($attempt);
            $currentPoints += $earnedPoints;
            
            // Apply assessment bonus if exists (following Livewire logic)
            $assessment = GameAssessment::where('quiz_attempt_id', $attempt->id)
                ->where('user_id', $attempt->user_id)
                ->where('is_assessed', true)
                ->first();

            if ($assessment && $assessment->total_deposit !== null) {
                // Assessment gain = total_deposit - base_points (matches Livewire)
                $assessmentGain = ($assessment->total_deposit ?? 0) - $this->getUserBasePoints($user);
                // A facilitator's penalty must be able to pull the score down; clamping
                // this to 0 silently discarded every penalty ever awarded.
                $currentPoints += $assessmentGain;
            }
        }
        
        return $currentPoints;
    }

    /**
     * Get detailed points breakdown for a user
     */
    public function getUserPointsBreakdown(User $user, Collection $attempts = null): array
    {
        if (!$user) {
            return [
                'total' => 0,
                'base_points' => 0,
                'earned_points' => 0,
                'assessment_bonus' => 0,
                'questionnaires_completed' => 0,
                'breakdown_text' => '0 (no user data)',
            ];
        }
        
        if (!$attempts) {
            $attempts = $user->quizAttempts()
                ->where('status', QuizAttempt::STATUS_COMPLETED)
                ->with(['userAnswers.question', 'gameAssessments'])
                ->get();
        }
        
        $completedAttempts = $attempts->where('status', QuizAttempt::STATUS_COMPLETED);
        $basePoints = $this->getUserBasePoints($user);
        
        if ($completedAttempts->isEmpty()) {
            return [
                'total' => $basePoints,
                'base_points' => $basePoints,
                'earned_points' => 0,
                'assessment_bonus' => 0,
                'questionnaires_completed' => 0,
                'breakdown_text' => $basePoints . ' (base)',
            ];
        }
        
        // Calculate using progressive method
        $totalPoints = $this->calculateUserTotalPoints($user, $attempts);
        
        // Calculate breakdown components
        $sortedAttempts = $completedAttempts->sortBy('completed_at');
        $completedQuestionnaires = [];
        $totalEarnedPoints = 0;
        $totalAssessmentBonus = 0;
        
        foreach ($sortedAttempts as $attempt) {
            if (in_array($attempt->questionnaire_id, $completedQuestionnaires)) {
                continue;
            }
            
            $completedQuestionnaires[] = $attempt->questionnaire_id;
            $earnedPoints = $this->calculateEarnedPoints($attempt);
            $totalEarnedPoints += $earnedPoints;
            
            // Calculate assessment bonus (following Livewire logic)
            $assessment = GameAssessment::where('quiz_attempt_id', $attempt->id)
                ->where('user_id', $attempt->user_id)
                ->where('is_assessed', true)
                ->first();

            if ($assessment && $assessment->total_deposit !== null) {
                // Assessment gain = total_deposit - base_points (NOT base + earned)
                // This matches the Livewire component calculation
                $assessmentGain = ($assessment->total_deposit ?? 0) - $basePoints;
                $totalAssessmentBonus += $assessmentGain;
            }
        }
        
        // Create breakdown text
        $breakdownParts = [$basePoints . ' (base)'];
        if ($totalEarnedPoints > 0) {
            $breakdownParts[] = $totalEarnedPoints . ' (earned from ' . count($completedQuestionnaires) . ' questionnaires)';
        }
        if ($totalAssessmentBonus != 0) {
            $breakdownParts[] = $totalAssessmentBonus . ' (assessment adjustments)';
        }
        
        return [
            'total' => $totalPoints,
            'base_points' => $basePoints,
            'earned_points' => $totalEarnedPoints,
            'assessment_bonus' => $totalAssessmentBonus,
            'questionnaires_completed' => count($completedQuestionnaires),
            'breakdown_text' => implode(' + ', $breakdownParts) . ' = ' . $totalPoints,
        ];
    }

    /**
     * Calculate highest points achieved by any team member
     */
    public function calculateTeamHighestPoints(Team $team, Collection $attempts = null): int
    {
        if (!$attempts) {
            $attempts = $team->users->flatMap(function($user) {
                return $user->quizAttempts()
                    ->where('status', QuizAttempt::STATUS_COMPLETED)
                    ->with(['userAnswers.question', 'gameAssessments'])
                    ->get();
            });
        }
        
        $completedAttempts = $attempts->where('status', QuizAttempt::STATUS_COMPLETED);
        
        if ($completedAttempts->isEmpty()) {
            return $team->initial_points ?? 0;
        }
        
        $highestPoints = null; // start unset: seeding with initial_points meant a penalised team could never rank below its starting balance
        
        // Group attempts by user to calculate each user's total
        $attemptsByUser = $completedAttempts->groupBy('user_id');
        
        foreach ($attemptsByUser as $userId => $userAttempts) {
            $user = $userAttempts->first()->user;
            if ($user) {
                $userTotal = $this->calculateUserTotalPoints($user, $userAttempts);
                if ($highestPoints === null || $userTotal > $highestPoints) {
                    $highestPoints = $userTotal;
                }
            }
        }
        
        return $highestPoints ?? ($team->initial_points ?? 0);
    }

    /**
     * Calculate average points across multiple attempts
     */
    public function calculateAveragePoints(Collection $attempts): float
    {
        if ($attempts->isEmpty()) {
            return 0;
        }
        
        $totalPoints = 0;
        $validAttempts = 0;
        
        foreach ($attempts as $attempt) {
            if ($attempt && $attempt->user) {
                $totalPoints += $this->calculateTeamPoints($attempt);
                $validAttempts++;
            }
        }
        
        return $validAttempts > 0 ? $totalPoints / $validAttempts : 0;
    }

    /**
     * Get user's base points from team
     */
    public function getUserBasePoints(User $user): int
    {
        return $user->team ? ($user->team->initial_points ?? 0) : 0;
    }

    /**
     * Format duration from seconds to human readable format
     */
    public function formatDuration(?int $seconds): ?string
    {
        if ($seconds === null || $seconds <= 0) {
            return null;
        }
        
        $seconds = round($seconds);
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        
        if ($minutes > 0) {
            return $minutes . 'm ' . $remainingSeconds . 's';
        } else {
            return $remainingSeconds . 's';
        }
    }

    /**
     * Get points statistics for a collection of users
     */
    public function getUsersPointsStats(Collection $users): array
    {
        $totalPoints = 0;
        $userCount = 0;
        $highestPoints = 0;
        $lowestPoints = PHP_INT_MAX;
        
        foreach ($users as $user) {
            $userPoints = $this->calculateUserTotalPoints($user);
            $totalPoints += $userPoints;
            $userCount++;
            
            if ($userPoints > $highestPoints) {
                $highestPoints = $userPoints;
            }
            
            if ($userPoints < $lowestPoints) {
                $lowestPoints = $userPoints;
            }
        }
        
        return [
            'average_points' => $userCount > 0 ? round($totalPoints / $userCount, 1) : 0,
            'highest_points' => $highestPoints,
            'lowest_points' => $lowestPoints === PHP_INT_MAX ? 0 : $lowestPoints,
            'total_points' => $totalPoints,
            'user_count' => $userCount,
        ];
    }

    /**
     * One attempt, question by question — what the team sees on the Results screen.
     *
     * Asked for by the operator after a field test (APK report #32, 23 Sep): the screen showed only
     * the totals, so a team could not tell which question had paid and which had not.
     *
     * `correct` is deliberately nullable. A fun_game is scored by a facilitator, so right and wrong
     * do not apply; `brief` is feedback; an unanswered question has nothing to mark. **No right
     * answer is ever included** — the next team gets the same questionnaire.
     *
     * fun_game's points live on its assessment (total_deposit − the team's starting balance, which
     * may be negative when a penalty outweighs the award), never on the answer row, which is why its
     * points_earned is read from there.
     *
     * @return list<array{question_id: int, type: string, question: string, points: int, points_earned: int, correct: bool|null, answered: bool}>
     */
    public function questionBreakdown(QuizAttempt $attempt, int $base): array
    {
        $answers = UserAnswer::where('quiz_attempt_id', $attempt->id)->get()->keyBy('question_id');
        $games = GameAssessment::where('quiz_attempt_id', $attempt->id)->get()->keyBy('question_id');

        return $attempt->questionnaire->questions()->orderBy('order')
            ->get(['id', 'question', 'type', 'points'])
            ->map(function ($q) use ($answers, $games, $base) {
                $answer = $answers[$q->id] ?? null;
                $game = $games[$q->id] ?? null;

                [$earned, $correct, $answered] = match ($q->type) {
                    'fun_game' => [
                        $game && $game->is_assessed ? (int) $game->total_deposit - $base : 0,
                        null,
                        (bool) $game,
                    ],
                    'brief' => [0, null, $answer && filled($answer->answer)],
                    default => [
                        (int) ($answer->points_earned ?? 0),
                        $answer ? (bool) $answer->is_correct : null,
                        (bool) $answer && filled($answer->answer),
                    ],
                };

                return [
                    'question_id' => $q->id,
                    'type' => $q->type,
                    'question' => $q->question,
                    'points' => (int) $q->points,
                    'points_earned' => $earned,
                    'correct' => $answered ? $correct : null,
                    'answered' => $answered,
                ];
            })
            ->values()
            ->all();
    }
}