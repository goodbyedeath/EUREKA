<?php

namespace App\Livewire\User;

use Livewire\Component;
use App\Models\QuizAttempt;
use App\Models\FeatureSetting;
use App\Services\WorkflowTimerService;
use Illuminate\Support\Facades\Auth;

class RecentAttempts extends Component
{
    public $recentAttempts;
    public $loadLimit = 10;
    public $totalAttempts = 0;
    
    private WorkflowTimerService $workflowTimerService;

    protected $listeners = [
        'refresh-attempts' => 'loadAttempts'
    ];

    public function mount()
    {
        try {
            $this->workflowTimerService = app(WorkflowTimerService::class);
        } catch (\Exception $e) {
            // Log error but continue - component should still work without workflow timers
            \Log::error("Failed to initialize WorkflowTimerService in RecentAttempts: " . $e->getMessage());
            $this->workflowTimerService = null;
        }
        
        $this->loadAttempts();
    }

    public function loadAttempts()
    {
        try {
            // Get total count for pagination
            $this->totalAttempts = QuizAttempt::where('user_id', Auth::id())->count();
            
            // Load attempts with current limit
            $this->recentAttempts = QuizAttempt::with(['questionnaire'])
                ->where('user_id', Auth::id())
                ->orderBy('created_at', 'desc')
                ->limit($this->loadLimit)
                ->get();
        } catch (\Exception $e) {
            \Log::error("Failed to load quiz attempts: " . $e->getMessage());
            $this->totalAttempts = 0;
            $this->recentAttempts = collect(); // Empty collection
            session()->flash('error', 'Failed to load quiz attempts. Please try again.');
        }
    }

    public function loadMoreAttempts()
    {
        try {
            // Increase the limit by 10
            $this->loadLimit += 10;
            
            // Reload attempts with new limit
            $this->loadAttempts();
        } catch (\Exception $e) {
            \Log::error("Failed to load more quiz attempts: " . $e->getMessage());
            session()->flash('error', 'Failed to load more quiz attempts. Please try again.');
        }
    }

    public function hasMoreAttempts()
    {
        try {
            return $this->recentAttempts && $this->recentAttempts->count() < $this->totalAttempts;
        } catch (\Exception $e) {
            \Log::error("Failed to check if more attempts exist: " . $e->getMessage());
            return false;
        }
    }

    public function continueQuiz($attemptId)
    {
        try {
            // First check if the attempt exists and belongs to the user
            $attempt = QuizAttempt::where('id', $attemptId)
                ->where('user_id', Auth::id())
                ->first();

            if (!$attempt) {
                session()->flash('error', 'Quiz attempt not found.');
                $this->loadAttempts();
                return;
            }

        // Check if the attempt can be continued
        if (!$attempt->isStarted()) {
            if ($attempt->isCompleted()) {
                session()->flash('error', 'This quiz has already been completed. Use "View Results" to see your score.');
            } else {
                session()->flash('error', 'This quiz attempt cannot be continued.');
            }
            $this->loadAttempts();
            return;
        }

        // Check if questionnaire is still available
        if ($attempt->questionnaire && !$attempt->questionnaire->isAvailable()) {
            session()->flash('error', 'This quiz is no longer available.');
            $this->loadAttempts();
            return;
        }

        // Check for timer expiry if it's a timed quiz
        if ($attempt->questionnaire && $attempt->questionnaire->time_limit) {
            try {
                if ($this->isWorkflowTimersEnabled() && $this->workflowTimerService) {
                    // Use workflow timer service to check expiry
                    $timerData = $this->workflowTimerService->getQuizRemainingTime($attempt);
                    if (!$timerData || (isset($timerData['remaining_seconds']) && $timerData['remaining_seconds'] <= 0)) {
                        session()->flash('error', 'This quiz attempt has expired due to time limit.');
                        $this->loadAttempts();
                        return;
                    }
                } else {
                    // Fallback to client-side calculation
                    $elapsed = now()->diffInSeconds($attempt->started_at);
                    $timeLimit = $attempt->questionnaire->time_limit * 60;
                    
                    if ($elapsed >= $timeLimit) {
                        session()->flash('error', 'This quiz attempt has expired due to time limit.');
                        $this->loadAttempts();
                        return;
                    }
                }
            } catch (\Exception $e) {
                // Log the error but don't block the user - fallback to client-side calculation
                \Log::warning("Workflow timer check failed in continueQuiz: " . $e->getMessage());
                
                $elapsed = now()->diffInSeconds($attempt->started_at);
                $timeLimit = $attempt->questionnaire->time_limit * 60;
                
                if ($elapsed >= $timeLimit) {
                    session()->flash('error', 'This quiz attempt has expired due to time limit.');
                    $this->loadAttempts();
                    return;
                }
            }
        }

            // All checks passed, continue the quiz
            return redirect()->route('quiz.continue', $attempt->id);
        } catch (\Exception $e) {
            \Log::error("Failed to continue quiz: " . $e->getMessage());
            session()->flash('error', 'Failed to continue quiz. Please try again.');
            $this->loadAttempts();
        }
    }

    public function retakeQuiz($questionnaireId)
    {
        try {
            // Check if user can still attempt this quiz
            $questionnaire = \App\Models\Questionnaire::find($questionnaireId);
            
            if (!$questionnaire) {
                session()->flash('error', 'Quiz not found.');
                $this->loadAttempts();
                return;
            }

        if (!$questionnaire->isAvailable()) {
            session()->flash('error', 'This quiz is not currently available.');
            $this->loadAttempts();
            return;
        }

        if (!$questionnaire->canUserAttempt(Auth::id())) {
            session()->flash('error', 'You have reached the maximum number of attempts for this quiz.');
            $this->loadAttempts();
            return;
        }

        // Check if user has an existing in-progress attempt for this quiz
        $existingAttempt = $questionnaire->getUserInProgressAttempt(Auth::id());
        if ($existingAttempt) {
            // Check if the existing attempt has expired (using workflow timers if enabled)
            if ($existingAttempt->questionnaire && $existingAttempt->questionnaire->time_limit) {
                $isExpired = false;
                
                try {
                    if ($this->isWorkflowTimersEnabled() && $this->workflowTimerService) {
                        $timerData = $this->workflowTimerService->getQuizRemainingTime($existingAttempt);
                        $isExpired = !$timerData || (isset($timerData['remaining_seconds']) && $timerData['remaining_seconds'] <= 0);
                    } else {
                        $elapsed = now()->diffInSeconds($existingAttempt->started_at);
                        $timeLimit = $existingAttempt->questionnaire->time_limit * 60;
                        $isExpired = $elapsed >= $timeLimit;
                    }
                } catch (\Exception $e) {
                    // Log error and fallback to client-side calculation
                    \Log::warning("Workflow timer check failed in retakeQuiz: " . $e->getMessage());
                    
                    $elapsed = now()->diffInSeconds($existingAttempt->started_at);
                    $timeLimit = $existingAttempt->questionnaire->time_limit * 60;
                    $isExpired = $elapsed >= $timeLimit;
                }
                
                if ($isExpired) {
                    // Mark expired attempt as abandoned and allow new attempt
                    try {
                        $existingAttempt->update(['status' => QuizAttempt::STATUS_ABANDONED]);
                        
                        // Cancel workflow timer if enabled
                        if ($this->isWorkflowTimersEnabled() && $this->workflowTimerService) {
                            $this->workflowTimerService->cancelQuizTimer($existingAttempt);
                        }
                    } catch (\Exception $e) {
                        // Log error but continue - user experience shouldn't be affected
                        \Log::error("Failed to cleanup expired attempt in retakeQuiz: " . $e->getMessage());
                    }
                    
                    session()->flash('info', 'Previous quiz attempt has expired. Starting a new attempt.');
                } else {
                    session()->flash('info', 'You have an unfinished attempt for this quiz. Continuing where you left off.');
                    return redirect()->route('quiz.continue', $existingAttempt->id);
                }
            } else {
                session()->flash('info', 'You have an unfinished attempt for this quiz. Continuing where you left off.');
                return redirect()->route('quiz.continue', $existingAttempt->id);
            }
        }

            return redirect()->route('quiz.take', ['questionnaireId' => $questionnaire->id]);
        } catch (\Exception $e) {
            \Log::error("Failed to retake quiz: " . $e->getMessage());
            session()->flash('error', 'Failed to retake quiz. Please try again.');
            $this->loadAttempts();
        }
    }

    public function canRetakeQuiz($questionnaire)
    {
        try {
            if (!$questionnaire) return false;
            
            return $questionnaire->canUserAttempt(Auth::id()) && $questionnaire->isAvailable();
        } catch (\Exception $e) {
            \Log::error("Failed to check if quiz can be retaken: " . $e->getMessage());
            return false;
        }
    }

    public function viewQuizDetails($attemptId)
    {
        try {
            $attempt = QuizAttempt::where('id', $attemptId)
                ->where('user_id', Auth::id())
                ->where('status', QuizAttempt::STATUS_COMPLETED) // Only allow viewing completed attempts
                ->first();

            if ($attempt) {
                return redirect()->route('quiz.results', $attemptId);
            }

            session()->flash('error', 'Quiz results not found or quiz not completed yet.');
            $this->loadAttempts();
        } catch (\Exception $e) {
            \Log::error("Failed to view quiz details: " . $e->getMessage());
            session()->flash('error', 'Failed to load quiz results. Please try again.');
            $this->loadAttempts();
        }
    }

    public function getScoreColor($score)
    {
        if (!$score) return 'text-gray-500';
        
        if ($score >= 90) return 'text-green-600';
        if ($score >= 80) return 'text-blue-600';
        if ($score >= 70) return 'text-yellow-600';
        if ($score >= 60) return 'text-orange-600';
        return 'text-red-600';
    }

    public function getStatusBadge($status)
    {
        $badges = [
            'completed' => 'bg-green-100 text-green-800',
            'started' => 'bg-yellow-100 text-yellow-800', // Fixed: changed from 'in_progress'
            'not_started' => 'bg-gray-100 text-gray-800',
            'expired' => 'bg-red-100 text-red-800',
            'abandoned' => 'bg-red-100 text-red-800'
        ];

        return $badges[$status] ?? 'bg-gray-100 text-gray-800';
    }

    public function getStatusText($status)
    {
        $statusTexts = [
            'completed' => 'Completed',
            'started' => 'In Progress',
            'not_started' => 'Not Started',
            'expired' => 'Expired',
            'abandoned' => 'Abandoned'
        ];

        return $statusTexts[$status] ?? ucfirst($status);
    }

    public function canContinueAttempt($attempt)
    {
        // Check if attempt can be continued
        if (!$attempt->isStarted()) {
            return false;
        }

        // Check if questionnaire is still available
        if ($attempt->questionnaire && !$attempt->questionnaire->isAvailable()) {
            return false;
        }

        // Check for timer expiry if it's a timed quiz
        if ($attempt->questionnaire && $attempt->questionnaire->time_limit) {
            try {
                if ($this->isWorkflowTimersEnabled() && $this->workflowTimerService) {
                    // Use workflow timer service to check expiry
                    $timerData = $this->workflowTimerService->getQuizRemainingTime($attempt);
                    if (!$timerData || (isset($timerData['remaining_seconds']) && $timerData['remaining_seconds'] <= 0)) {
                        return false;
                    }
                } else {
                    // Fallback to client-side calculation
                    $elapsed = now()->diffInSeconds($attempt->started_at);
                    $timeLimit = $attempt->questionnaire->time_limit * 60;
                    
                    if ($elapsed >= $timeLimit) {
                        return false;
                    }
                }
            } catch (\Exception $e) {
                // Log error but don't block user - fallback to client-side calculation
                \Log::warning("Workflow timer check failed in canContinueAttempt: " . $e->getMessage());
                
                $elapsed = now()->diffInSeconds($attempt->started_at);
                $timeLimit = $attempt->questionnaire->time_limit * 60;
                
                if ($elapsed >= $timeLimit) {
                    return false;
                }
            }
        }

        return true;
    }

    public function getTimeRemaining($attempt)
    {
        if (!$attempt->questionnaire || !$attempt->questionnaire->time_limit || !$attempt->isStarted()) {
            return null;
        }

        try {
            if ($this->isWorkflowTimersEnabled() && $this->workflowTimerService) {
                // Use workflow timer service
                $timerData = $this->workflowTimerService->getQuizRemainingTime($attempt);
                if (!$timerData || !isset($timerData['remaining_seconds'])) {
                    return null;
                }
                
                $remaining = $timerData['remaining_seconds'];
                if ($remaining <= 0) {
                    return 'Expired';
                }
                
                $minutes = floor($remaining / 60);
                $seconds = $remaining % 60;
                
                if ($minutes > 0) {
                    return "{$minutes}m {$seconds}s remaining";
                } else {
                    return "{$seconds}s remaining";
                }
            } else {
                // Fallback to client-side calculation
                $elapsed = now()->diffInSeconds($attempt->started_at);
                $timeLimit = $attempt->questionnaire->time_limit * 60;
                $remaining = $timeLimit - $elapsed;

                if ($remaining <= 0) {
                    return 'Expired';
                }

                $minutes = floor($remaining / 60);
                $seconds = $remaining % 60;

                if ($minutes > 0) {
                    return "{$minutes}m {$seconds}s remaining";
                } else {
                    return "{$seconds}s remaining";
                }
            }
        } catch (\Exception $e) {
            // Log error and fallback to client-side calculation
            \Log::warning("Workflow timer check failed in getTimeRemaining: " . $e->getMessage());
            
            $elapsed = now()->diffInSeconds($attempt->started_at);
            $timeLimit = $attempt->questionnaire->time_limit * 60;
            $remaining = $timeLimit - $elapsed;

            if ($remaining <= 0) {
                return 'Expired';
            }

            $minutes = floor($remaining / 60);
            $seconds = $remaining % 60;

            if ($minutes > 0) {
                return "{$minutes}m {$seconds}s remaining";
            } else {
                return "{$seconds}s remaining";
            }
        }
    }

    public function getAttemptProgress($attempt)
    {
        try {
            if (!$attempt->isStarted() || !$attempt->questionnaire) {
                return null;
            }

            $totalQuestions = $attempt->questionnaire->questions()->count();
            $answeredQuestions = $attempt->userAnswers()->count();

            if ($totalQuestions === 0) {
                return null;
            }

            $percentage = round(($answeredQuestions / $totalQuestions) * 100);
            
            return [
                'answered' => $answeredQuestions,
                'total' => $totalQuestions,
                'percentage' => $percentage
            ];
        } catch (\Exception $e) {
            \Log::error("Failed to calculate attempt progress: " . $e->getMessage());
            return null;
        }
    }

    public function formatScore($attempt)
    {
        try {
            if ($attempt->status !== QuizAttempt::STATUS_COMPLETED || is_null($attempt->total_score)) {
                return '-';
            }

            // Calculate earned points using the same logic as quiz-results.blade.php
            $basePoints = auth()->user()->team ? (auth()->user()->team->initial_points ?? 1000) : 1000;
            
            // Get bonus points from correct answers
            $userAnswers = \App\Models\UserAnswer::where('quiz_attempt_id', $attempt->id)
                ->with(['question'])
                ->where('is_correct', true)
                ->get();
                
            $earnedPoints = $userAnswers->sum(function($answer) {
                return $answer->question->points ?? 0;
            });
            
            // Add assessment bonus points
            $assessments = \App\Models\GameAssessment::where('quiz_attempt_id', $attempt->id)
                ->where('user_id', $attempt->user_id)
                ->where('is_assessed', true)
                ->get();
                
            foreach ($assessments as $assessment) {
                $assessmentBonus = ($assessment->total_deposit ?? 0) - $basePoints;
                $earnedPoints += max(0, $assessmentBonus);
            }

            // Show earned points vs total possible quiz points
            if ($attempt->questionnaire && $attempt->questionnaire->total_points) {
                return $earnedPoints . '/' . $attempt->questionnaire->total_points . ' pts';
            }

            // Return just the earned points
            return $earnedPoints . ' pts';
        } catch (\Exception $e) {
            \Log::error("Failed to format score: " . $e->getMessage());
            return '-';
        }
    }

    public function calculatePercentage($attempt)
    {
        try {
            if ($attempt->status !== QuizAttempt::STATUS_COMPLETED) {
                return 0;
            }

            // Calculate percentage based on correct answers vs total questions/games
            $userAnswers = \App\Models\UserAnswer::where('quiz_attempt_id', $attempt->id)
                ->with(['question'])
                ->where('is_correct', true)
                ->get();

            $assessments = \App\Models\GameAssessment::where('quiz_attempt_id', $attempt->id)
                ->where('user_id', $attempt->user_id)
                ->where('is_assessed', true)
                ->get();

            $totalQuestions = $attempt->questionnaire->questions->count() ?? 0;
            $correctAnswers = $userAnswers->count();
            $completedAssessments = $assessments->count();
            
            $totalItems = $totalQuestions; // Total questions + games
            $correctItems = $correctAnswers + $completedAssessments; // Correct answers + completed assessments
            
            return $totalItems > 0 ? round(($correctItems / $totalItems) * 100) : 0;
        } catch (\Exception $e) {
            \Log::error("Failed to calculate percentage: " . $e->getMessage());
            return 0;
        }
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

    public function render()
    {
        return view('livewire.user.recent-attempts');
    }
}