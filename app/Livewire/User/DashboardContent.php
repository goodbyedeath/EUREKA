<?php
// app/Livewire/User/DashboardContent.php

namespace App\Livewire\User;

use App\Traits\HasFeatureAccess;
use Livewire\Component;
use App\Services\WorkflowTimerService;
use App\Models\FeatureSetting;


class DashboardContent extends Component
{
    use HasFeatureAccess;
    public $activeTab = 'dashboard';
    public $scannedqr_code = null;
    public $questionnaireId = null;
    public $team = null;

    // Quiz stats
    public $completedQuizzes = 0;
    public $averageScore = 0;
    public $totalTimeSpent = 0;
    public $currentStreak = 0;

    protected $listeners = [
        'active-tab-changed' => 'handleTabChange',
        'qr-code-scanned' => 'handleqr_codeScanned',
        'dashboard-refreshed' => 'refreshData',
        'refresh-stats' => 'loadQuizStats',
        'echo:session-expired,SessionExpired' => 'handleSessionExpired'
    ];

    public $sessionTimeRemaining = null;
    public $sessionTimeout = null;
    private ?WorkflowTimerService $workflowTimerService = null;

    public function mount()
    {
        try {
            $this->workflowTimerService = app(WorkflowTimerService::class);
        } catch (\Exception $e) {
            \Log::error("Failed to initialize WorkflowTimerService in DashboardContent: " . $e->getMessage());
            $this->workflowTimerService = null;
        }
        
        $this->loadUserData();
        
        // Check if we should switch to a specific tab (e.g., after completing a quiz)
        if (session()->has('active_tab')) {
            $this->activeTab = session('active_tab');
            session()->forget('active_tab');
        }
        
        // Initialize session timer data if workflow timers are enabled
        if ($this->isWorkflowTimersEnabled()) {
            $this->updateSessionTimer();
        }
    }

    public function loadUserData()
    {
        // Load user's team
        $user = auth()->user();
        $this->team = $user->createdTeam;

        // Check for active questionnaire
        $this->questionnaireId = session('active_questionnaire_id');

        // Check for scanned QR code
        $this->scannedqr_code = session('scanned_qr_code');

        // Load quiz statistics
        $this->loadQuizStats();
    }

    public function loadQuizStats()
    {
        $user = auth()->user();

        // Get completed quizzes count
        $this->completedQuizzes = \App\Models\QuizAttempt::where('user_id', $user->id)
            ->where('status', 'completed')
            ->count();

        // Calculate average score (earned points per completed quiz)
        $completedAttempts = \App\Models\QuizAttempt::with(['questionnaire.questions'])
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->get();

        $totalEarnedPoints = 0;
        $completedCount = 0;

        // The team's starting balance, which every assessment carries inside total_deposit.
        $basePoints = (int) ($user->team->initial_points ?? 1000);

        foreach ($completedAttempts as $attempt) {
            $completedCount++;

            // Points actually awarded, read from the answer row.
            //
            // This used to join `questions` and sum `questions.points`, which counts
            // fun_game answers at full value — they are stored is_correct = true with
            // points_earned = 0 precisely because a facilitator scores them later. So a
            // fun game was counted twice: once here, and again through its assessment.
            $earnedPoints = \App\Models\UserAnswer::where('quiz_attempt_id', $attempt->id)
                ->sum('points_earned');

            // The assessment's *gain*, not its raw deposit.
            //
            // total_deposit = initial_points + additional − penalty, so summing it raw
            // added the whole starting balance again for every assessed game. Every other
            // consumer of this column subtracts the base first — DashboardStats,
            // ScoreBreakdown, KioskController, PointsCalculationService.
            $assessmentBonus = \App\Models\GameAssessment::where('quiz_attempt_id', $attempt->id)
                ->where('user_id', $user->id)
                ->where('is_assessed', true)
                ->get()
                ->sum(fn ($a) => ($a->total_deposit ?? 0) - $basePoints);

            $totalEarnedPoints += $earnedPoints + $assessmentBonus;
        }

        $this->averageScore = $completedCount > 0
            ? round($totalEarnedPoints / $completedCount, 1)
            : 0;

        // Calculate total time spent (in seconds, convert to hours and minutes)
        $this->totalTimeSpent = \App\Models\QuizAttempt::where('user_id', $user->id)
            ->where('status', 'completed')
            ->sum('total_time_seconds');

        // Calculate current streak (consecutive days with completed quizzes)
        $this->currentStreak = $this->calculateStreak($user);
    }

    private function calculateStreak($user)
    {
        $attempts = \App\Models\QuizAttempt::where('user_id', $user->id)
            ->where('status', 'completed')
            ->whereNotNull('completed_at')
            ->orderBy('completed_at', 'desc')
            ->get();

        if ($attempts->isEmpty()) {
            return 0;
        }

        $streak = 0;
        $currentDate = now()->startOfDay();
        $lastDate = null;

        foreach ($attempts as $attempt) {
            $attemptDate = $attempt->completed_at->startOfDay();

            if ($lastDate === null) {
                // First attempt
                if ($attemptDate->isSameDay($currentDate) || $attemptDate->isSameDay($currentDate->copy()->subDay())) {
                    $streak = 1;
                    $lastDate = $attemptDate;
                } else {
                    // Last attempt was more than 1 day ago, no streak
                    break;
                }
            } else {
                // Check if this attempt is the day before the last one
                if ($attemptDate->isSameDay($lastDate->copy()->subDay())) {
                    $streak++;
                    $lastDate = $attemptDate;
                } elseif ($attemptDate->isSameDay($lastDate)) {
                    // Multiple attempts on the same day, continue
                    continue;
                } else {
                    // Streak broken
                    break;
                }
            }
        }

        return $streak;
    }

    public function getFormattedTotalTime()
    {
        if ($this->totalTimeSpent < 60) {
            return $this->totalTimeSpent . 's';
        }

        $minutes = floor($this->totalTimeSpent / 60);

        if ($minutes < 60) {
            return $minutes . 'm';
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        return $hours . 'h ' . ($remainingMinutes > 0 ? $remainingMinutes . 'm' : '');
    }

    public function handleTabChange($tab)
{
    $tabValue = is_array($tab) ? $tab['tab'] : $tab;

    if (in_array($tabValue, ['dashboard', 'quizzes', 'members', 'quests', 'games'])) {
        $this->activeTab = $tabValue;
    }
}


    public function handleqr_codeScanned($qr_code)
    {
        $this->scannedqr_code = $qr_code;
        $this->activeTab = 'quizzes';
    }

    public function refreshData()
    {
        $this->loadUserData();
    }

    public function switchToQuizzes()
    {
        $this->activeTab = 'quizzes';
        $this->dispatch('tab-switched', tab: 'quizzes');
    }

    public function switchToMembers()
    {
        $this->activeTab = 'members';
        $this->dispatch('tab-switched', tab: 'members');
    }

    public function switchToGames()
    {
        $this->activeTab = 'games';
        $this->dispatch('tab-switched', tab: 'games');
    }
    
    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->dispatch('tab-switched', tab: $tab);
    }

    public function openQRScanner()
    {
        $this->dispatch('open-qr-scanner');
    }

    /**
     * Handle session expired event from workflow
     */
    public function handleSessionExpired($event)
    {
        session()->flash('error', 'Your session has expired due to inactivity.');
        $this->dispatch('forceLogout');
    }

    /**
     * Update session timer data for display
     */
    public function updateSessionTimer()
    {
        if (!$this->isWorkflowTimersEnabled() || !$this->workflowTimerService) {
            return;
        }

        try {
            $user = auth()->user();
            if ($user->role !== 'user') {
                return;
            }

            $timerData = $this->workflowTimerService->getSessionRemainingTime($user);
            if ($timerData) {
                $this->sessionTimeRemaining = $timerData['remaining_seconds'];
                $this->sessionTimeout = $timerData['total_seconds'];
            }
        } catch (\Exception $e) {
            \Log::error("Failed to update session timer: " . $e->getMessage());
            // Don't set timer data if there's an error
        }
    }

    /**
     * Get formatted session time remaining for display
     */
    public function getFormattedSessionTime()
    {
        if (!$this->sessionTimeRemaining) {
            return '--:--';
        }

        $minutes = floor($this->sessionTimeRemaining / 60);
        $seconds = $this->sessionTimeRemaining % 60;
        return sprintf('%d:%02d', $minutes, $seconds);
    }

    /**
     * Check if workflow timers are enabled
     */
    public function isWorkflowTimersEnabled(): bool
    {
        try {
            return FeatureSetting::isEnabled('workflow_timers');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get session timer status for UI styling
     */
    public function getSessionTimerStatus(): string
    {
        if (!$this->sessionTimeRemaining || !$this->sessionTimeout) {
            return 'normal';
        }

        $percentage = ($this->sessionTimeRemaining / $this->sessionTimeout) * 100;
        
        if ($percentage <= 10) { // Less than 10% remaining
            return 'critical';
        } elseif ($percentage <= 25) { // Less than 25% remaining
            return 'warning';
        }

        return 'normal';
    }

    public function render()
    {
        // Update session timer data on each render if workflow timers are enabled
        if ($this->isWorkflowTimersEnabled()) {
            $this->updateSessionTimer();
        }
        
        return view('livewire.user.dashboard-content');
    }
}