<?php

namespace App\Services;

use App\Models\User;
use App\Models\QuizAttempt;
use App\Models\UserAnswer;
use App\Models\GameAssessment;
use App\Models\Team;
use Illuminate\Support\Collection;

class PointsCalculationService
{
    /**
     * A team's score, as /admin/user-progress, the kiosk and the app all show it — one formula, so
     * the three can never disagree again:
     *
     *   total = initial_points (once)
     *         + Σ points_earned of correct answers  in COMPLETED attempts by the team's accounts
     *         + Σ (total_deposit − initial_points)  of assessed games on those attempts
     *
     * The game term = additional − penalty and may be negative: a penalty larger than the award
     * lowers the score (operator, 14 Sep; user-progress used to clamp it to 0 while the kiosk did
     * not). Every completed attempt counts, as it always has on user-progress. $since narrows to
     * attempts created from then on — the admin page's timeframe filter.
     *
     * @return array{total:int, base_points:int, earned_points:int, assessment_points:int, attempts_completed:int}
     */
    public function teamScore(Team $team, ?\DateTimeInterface $since = null): array
    {
        $accounts = User::where('team_id', $team->id)->pluck('id')->all();

        return $this->scoreFor($accounts, (int) ($team->initial_points ?? 1000), $since);
    }

    /** One account's share of that score, on the same formula and its team's base. */
    public function userScore(User $user, ?\DateTimeInterface $since = null): array
    {
        return $this->scoreFor([$user->id], (int) ($user->team?->initial_points ?? 1000), $since);
    }

    private function scoreFor(array $userIds, int $base, ?\DateTimeInterface $since): array
    {
        $attemptIds = QuizAttempt::whereIn('user_id', $userIds ?: [0])
            ->where('status', QuizAttempt::STATUS_COMPLETED)
            ->when($since, fn ($q) => $q->where('created_at', '>=', $since))
            ->pluck('id');

        $earned = (int) UserAnswer::whereIn('quiz_attempt_id', $attemptIds)
            ->where('is_correct', true)
            ->sum('points_earned');

        $assessment = (int) GameAssessment::whereIn('quiz_attempt_id', $attemptIds)
            ->where('is_assessed', true)
            ->get(['total_deposit'])
            ->sum(fn ($a) => (int) $a->total_deposit - $base);

        return [
            'total' => $base + $earned + $assessment,
            'base_points' => $base,
            'earned_points' => $earned,
            'assessment_points' => $assessment,
            'attempts_completed' => $attemptIds->count(),
        ];
    }

    /**
     * Calculate earned points from a single quiz attempt (correct answers only)
     * IMPORTANT: Uses points_earned from user_answers table, NOT question->points
     * This ensures fun_game questions (which have points_earned = 0) are handled correctly
     */
    public function calculateEarnedPoints(QuizAttempt $attempt): int
    {
        return UserAnswer::where('quiz_attempt_id', $attempt->id)
            ->where('is_correct', true)
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
            return $team->initial_points ?? 1000;
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
        
        return $highestPoints ?? ($team->initial_points ?? 1000);
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
        return $user->team ? ($user->team->initial_points ?? 1000) : 1000;
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
}