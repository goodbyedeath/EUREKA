<?php

namespace App\Livewire\User;

use Livewire\Component;
use App\Models\QuizAttempt;
use Illuminate\Support\Facades\Auth;

class RecentAttempts extends Component
{
    public $recentAttempts;

    protected $listeners = [
        'refresh-attempts' => 'loadAttempts'
    ];

    public function mount()
    {
        $this->loadAttempts();
    }

    public function loadAttempts()
    {
        $this->recentAttempts = QuizAttempt::with(['questionnaire'])
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    public function continueQuiz($attemptId)
    {
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
            $elapsed = now()->diffInSeconds($attempt->started_at);
            $timeLimit = $attempt->questionnaire->time_limit * 60;
            
            if ($elapsed >= $timeLimit) {
                session()->flash('error', 'This quiz attempt has expired due to time limit.');
                $this->loadAttempts();
                return;
            }
        }

        // All checks passed, continue the quiz
        return redirect()->route('quiz.continue', $attempt->id);
    }

    public function retakeQuiz($questionnaireId)
    {
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
            session()->flash('info', 'You have an unfinished attempt for this quiz. Continuing where you left off.');
            return redirect()->route('quiz.continue', $existingAttempt->id);
        }

        return redirect()->route('quiz.take', ['questionnaireId' => $questionnaire->id]);
    }

    public function canRetakeQuiz($questionnaire)
    {
        if (!$questionnaire) return false;
        
        return $questionnaire->canUserAttempt(Auth::id()) && $questionnaire->isAvailable();
    }

    public function viewQuizDetails($attemptId)
    {
        $attempt = QuizAttempt::where('id', $attemptId)
            ->where('user_id', Auth::id())
            ->where('status', QuizAttempt::STATUS_COMPLETED) // Only allow viewing completed attempts
            ->first();

        if ($attempt) {
            return redirect()->route('quiz.results', $attemptId);
        }

        session()->flash('error', 'Quiz results not found or quiz not completed yet.');
        $this->loadAttempts();
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
            $elapsed = now()->diffInSeconds($attempt->started_at);
            $timeLimit = $attempt->questionnaire->time_limit * 60;
            
            if ($elapsed >= $timeLimit) {
                return false;
            }
        }

        return true;
    }

    public function getTimeRemaining($attempt)
    {
        if (!$attempt->questionnaire || !$attempt->questionnaire->time_limit || !$attempt->isStarted()) {
            return null;
        }

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

    public function getAttemptProgress($attempt)
    {
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
    }

    public function formatScore($attempt)
    {
        if ($attempt->status !== QuizAttempt::STATUS_COMPLETED || is_null($attempt->total_score)) {
            return '-';
        }

        // If you have total_points in questionnaire, show as fraction
        if ($attempt->questionnaire && $attempt->questionnaire->total_points) {
            return $attempt->total_score . '/' . $attempt->questionnaire->total_points;
        }

        // Return just the number without percentage sign
        return (string) $attempt->total_score;
    }

    public function render()
    {
        return view('livewire.user.recent-attempts');
    }
}