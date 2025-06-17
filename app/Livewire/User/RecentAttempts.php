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
        $attempt = QuizAttempt::where('id', $attemptId)
            ->where('user_id', Auth::id())
            ->where('status', 'started') // Fixed: changed from 'in_progress' to 'started'
            ->first();

        if ($attempt) {
            return redirect()->route('quiz.continue', $attempt->id);
        }

        session()->flash('error', 'Quiz attempt not found or already completed.');
        $this->loadAttempts();
    }

    public function retakeQuiz($questionnaireId)
    {
        // Check if user can still attempt this quiz
        $questionnaire = \App\Models\Questionnaire::find($questionnaireId);
        
        if (!$questionnaire) {
            session()->flash('error', 'Quiz not found.');
            return;
        }

        if (!$questionnaire->canUserAttempt(Auth::id())) {
            session()->flash('error', 'You have reached the maximum number of attempts for this quiz.');
            return;
        }

        if (!$questionnaire->isAvailable()) {
            session()->flash('error', 'This quiz is not currently available.');
            return;
        }

        return redirect()->route('quiz.continue', ['code' => $questionnaire->qr_code]);
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
            ->where('status', 'completed') // Only allow viewing completed attempts
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

    public function formatScore($attempt)
    {
        if ($attempt->status !== 'completed' || is_null($attempt->total_score)) {
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