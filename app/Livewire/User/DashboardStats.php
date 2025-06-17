<?php

namespace App\Livewire\User;

use Livewire\Component;
use App\Models\QuizAttempt;
use Illuminate\Support\Facades\Auth;

class DashboardStats extends Component
{
    public $totalAttempts = 0;
    public $completedAttempts = 0;
    public $averageScore = 0;
    public $completionRate = 0;

    protected $listeners = [
        'refresh-stats' => 'loadStats'
    ];

    public function mount()
    {
        $this->loadStats();
    }

    public function loadStats()
    {
        $user = Auth::user();
        
        // More efficient database queries
        $this->totalAttempts = QuizAttempt::where('user_id', $user->id)->count();
        
        $this->completedAttempts = QuizAttempt::where('user_id', $user->id)
            ->where('status', 'completed')
            ->count();
        
        // Calculate average score from completed attempts only
        $averageScore = QuizAttempt::where('user_id', $user->id)
            ->where('status', 'completed')
            ->whereNotNull('total_score')
            ->avg('total_score');
            
        $this->averageScore = $averageScore ? round($averageScore, 1) : 0; // Changed to 1 decimal place for numbers
        
        $this->completionRate = $this->totalAttempts > 0 
            ? round(($this->completedAttempts / $this->totalAttempts) * 100, 0) 
            : 0;
    }

    public function getScoreColor($score)
    {
        if ($score >= 90) return 'text-green-600';
        if ($score >= 80) return 'text-blue-600';
        if ($score >= 70) return 'text-yellow-600';
        if ($score >= 60) return 'text-orange-600';
        return 'text-red-600';
    }

    public function render()
    {
        return view('livewire.user.dashboard-stats');
    }
}