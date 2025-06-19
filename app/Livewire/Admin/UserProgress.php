<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\User;
use App\Models\QuizAttempt;
use App\Models\Questionnaire;
use App\Models\Team;
use Illuminate\Support\Facades\DB;

class UserProgress extends Component
{
    public $selectedTimeframe = '30'; // days
    public $selectedTeam = 'all';
    public $selectedRole = 'user'; // Default to only show users, not admins

    public function render()
    {
        // Get overview statistics (only for users with role 'user')
        $totalUsers = User::where('role', 'user')->count();
        $activeUsers = User::where('role', 'user')->whereHas('quizAttempts', function($q) {
            $q->where('created_at', '>=', now()->subDays(30));
        })->count();
        
        $totalAttempts = QuizAttempt::where('created_at', '>=', now()->subDays($this->selectedTimeframe))->count();
        $completedAttempts = QuizAttempt::where('status', QuizAttempt::STATUS_COMPLETED)
            ->where('created_at', '>=', now()->subDays($this->selectedTimeframe))
            ->count();

        // User progress data
        $userProgressData = $this->getUserProgressData();
        
        // Team performance data
        $teamPerformanceData = $this->getTeamPerformanceData();
        
        // Quiz completion trends
        $completionTrends = $this->getCompletionTrends();
        
        // Top performers
        $topPerformers = $this->getTopPerformers();
        
        // Get teams for filter
        $teams = Team::all();

        return view('livewire.admin.user-progress', [
            'totalUsers' => $totalUsers,
            'activeUsers' => $activeUsers,
            'totalAttempts' => $totalAttempts,
            'completedAttempts' => $completedAttempts,
            'userProgressData' => $userProgressData,
            'teamPerformanceData' => $teamPerformanceData,
            'completionTrends' => $completionTrends,
            'topPerformers' => $topPerformers,
            'teams' => $teams,
            'completionRate' => $totalAttempts > 0 ? round(($completedAttempts / $totalAttempts) * 100, 1) : 0
        ]);
    }

    private function getUserProgressData()
    {
        $query = User::with(['quizAttempts' => function($q) {
            $q->where('created_at', '>=', now()->subDays($this->selectedTimeframe));
        }]);

        // Apply filters
        if ($this->selectedTeam !== 'all') {
            $query->where('team_id', $this->selectedTeam);
        }

        if ($this->selectedRole !== 'all') {
            $query->where('role', $this->selectedRole);
        } else {
            // Default to only show users with role 'user', not admins
            $query->where('role', 'user');
        }

        return $query->get()->map(function ($user) {
            $attempts = $user->quizAttempts;
            $completed = $attempts->where('status', QuizAttempt::STATUS_COMPLETED);
            
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'team' => $user->team ? $user->team->name : 'No Team',
                'role' => $user->role,
                'total_attempts' => $attempts->count(),
                'completed_attempts' => $completed->count(),
                'completion_rate' => $attempts->count() > 0 ? round(($completed->count() / $attempts->count()) * 100, 1) : 0,
                'average_score' => $completed->count() > 0 ? round($completed->avg('total_score'), 1) : 0,
                'last_activity' => $attempts->max('created_at'),
            ];
        })->sortByDesc('completion_rate');
    }

    private function getTeamPerformanceData()
    {
        return Team::with(['users.quizAttempts' => function($q) {
            $q->where('created_at', '>=', now()->subDays($this->selectedTimeframe));
        }])->get()->map(function ($team) {
            $allAttempts = $team->users->flatMap->quizAttempts;
            $completedAttempts = $allAttempts->where('status', QuizAttempt::STATUS_COMPLETED);
            
            return [
                'id' => $team->id,
                'name' => $team->name,
                'department' => $team->department,
                'member_count' => $team->users->count(),
                'total_attempts' => $allAttempts->count(),
                'completed_attempts' => $completedAttempts->count(),
                'completion_rate' => $allAttempts->count() > 0 ? round(($completedAttempts->count() / $allAttempts->count()) * 100, 1) : 0,
                'average_score' => $completedAttempts->count() > 0 ? round($completedAttempts->avg('total_score'), 1) : 0,
            ];
        })->sortByDesc('completion_rate');
    }

    private function getCompletionTrends()
    {
        $days = collect();
        for ($i = $this->selectedTimeframe - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $completed = QuizAttempt::where('status', QuizAttempt::STATUS_COMPLETED)
                ->whereDate('completed_at', $date)
                ->count();
            
            $days->push([
                'date' => $date->format('M d'),
                'completed' => $completed,
            ]);
        }
        
        return $days;
    }

    private function getTopPerformers()
    {
        return User::where('role', 'user')
            ->whereHas('quizAttempts', function($q) {
            $q->where('status', QuizAttempt::STATUS_COMPLETED)
              ->where('created_at', '>=', now()->subDays($this->selectedTimeframe));
        })->withAvg(['quizAttempts as average_score' => function($q) {
            $q->where('status', QuizAttempt::STATUS_COMPLETED)
              ->where('created_at', '>=', now()->subDays($this->selectedTimeframe));
        }], 'total_score')
        ->withCount(['quizAttempts as completed_quizzes' => function($q) {
            $q->where('status', QuizAttempt::STATUS_COMPLETED)
              ->where('created_at', '>=', now()->subDays($this->selectedTimeframe));
        }])
        ->having('completed_quizzes', '>', 0)
        ->orderByDesc('average_score')
        ->limit(10)
        ->get()
        ->map(function($user) {
            return [
                'name' => $user->name,
                'team' => $user->team ? $user->team->name : 'No Team',
                'average_score' => round($user->average_score, 1),
                'completed_quizzes' => $user->completed_quizzes,
            ];
        });
    }

    public function updatedSelectedTimeframe()
    {
        // Component will re-render automatically
    }

    public function updatedSelectedTeam()
    {
        // Component will re-render automatically
    }

    public function updatedSelectedRole()
    {
        // Component will re-render automatically
    }
}