<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\User;
use App\Models\QuizAttempt;
use App\Models\Questionnaire;
use App\Models\Team;
use App\Models\UserAnswer;
use App\Models\GameAssessment;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Livewire\WithPagination;

class UserProgress extends Component
{
    use WithPagination;
    
    public $selectedTimeframe = '30'; // days
    public $selectedTeam = 'all';
    public $selectedRole = 'user'; // Default to only show users, not admins
    public $searchTerm = '';
    public $sortField = 'total_points';
    public $sortDirection = 'desc';
    public $showDetailedView = false;
    public $selectedUserId = null;
    public $showTeamDetailView = false;
    public $selectedTeamId = null;
    public $exportFormat = 'csv';

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

        // Enhanced statistics
        $avgCompletionTime = $this->getAverageCompletionTime();
        $strugglingUsers = $this->getStrugglingUsers();
        $inProgressAttempts = $this->getInProgressAttempts();
        $gameAssessmentStats = $this->getGameAssessmentStats();

        // User progress data with pagination
        $userProgressData = $this->getUserProgressData();
        
        // Team performance data
        $teamPerformanceData = $this->getTeamPerformanceData();
        
        // Quiz completion trends
        $completionTrends = $this->getCompletionTrends();
        
        // Top performers
        $topPerformers = $this->getTopPerformers();
        
        // Get teams for filter
        $teams = Team::all();
        
        // Detailed user view data
        $userDetailData = $this->selectedUserId ? $this->getUserDetailData($this->selectedUserId) : null;
        
        // Detailed team view data
        $teamDetailData = $this->selectedTeamId ? $this->getTeamDetailData($this->selectedTeamId) : null;

        return view('livewire.admin.user-progress', [
            'totalUsers' => $totalUsers,
            'activeUsers' => $activeUsers,
            'totalAttempts' => $totalAttempts,
            'completedAttempts' => $completedAttempts,
            'avgCompletionTime' => $avgCompletionTime,
            'strugglingUsers' => $strugglingUsers,
            'inProgressAttempts' => $inProgressAttempts,
            'gameAssessmentStats' => $gameAssessmentStats,
            'userProgressData' => $userProgressData,
            'teamPerformanceData' => $teamPerformanceData,
            'completionTrends' => $completionTrends,
            'topPerformers' => $topPerformers,
            'teams' => $teams,
            'userDetailData' => $userDetailData,
            'teamDetailData' => $teamDetailData,
            'completionRate' => $totalAttempts > 0 ? round(($completedAttempts / $totalAttempts) * 100, 1) : 0
        ]);
    }

    private function getUserProgressData()
    {
        $query = User::with(['quizAttempts' => function($q) {
            $q->where('created_at', '>=', now()->subDays($this->selectedTimeframe))
              ->with(['userAnswers.question:id,points', 'questionnaire.questions:id,questionnaire_id,points']);
        }, 'team']);

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
        
        // Apply search filter
        if ($this->searchTerm) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->searchTerm . '%')
                  ->orWhere('email', 'like', '%' . $this->searchTerm . '%');
            });
        }

        $users = $query->get()->map(function ($user) {
            $attempts = $user->quizAttempts;
            $completed = $attempts->where('status', QuizAttempt::STATUS_COMPLETED);
            $inProgress = $attempts->where('status', QuizAttempt::STATUS_STARTED);
            
            // Calculate average completion time
            $avgCompletionTime = $completed->filter(function($attempt) {
                return $attempt->total_time_seconds > 0;
            })->avg('total_time_seconds');
            
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'team' => $user->team ? $user->team->name : 'No Team',
                'team_id' => $user->team ? $user->team->id : null,
                'role' => $user->role,
                'total_attempts' => $attempts->count(),
                'completed_attempts' => $completed->count(),
                'in_progress_attempts' => $inProgress->count(),
                'completion_rate' => $attempts->count() > 0 ? round(($completed->count() / $attempts->count()) * 100, 1) : 0,
                'total_points' => $user->team ? $user->team->points : 0,
                'average_score' => $completed->count() > 0 ? round($this->calculateAveragePointsForAttempts($completed), 1) : 0, // Average points scored
                'highest_score' => $completed->count() > 0 ? $this->calculateMaxPointsForAttempts($completed) : 0,
                'avg_completion_time' => $avgCompletionTime ? $this->formatDuration($avgCompletionTime) : null,
                'last_activity' => $attempts->max('created_at'),
                'created_at' => $user->created_at,
            ];
        });
        
        // Apply sorting
        if ($this->sortDirection === 'asc') {
            return $users->sortBy($this->sortField);
        } else {
            return $users->sortByDesc($this->sortField);
        }
    }

    private function getTeamPerformanceData()
    {
        return Team::with(['users.quizAttempts' => function($q) {
            $q->where('created_at', '>=', now()->subDays($this->selectedTimeframe))
              ->with(['questionnaire:id,title', 'userAnswers.question:id,points']);
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
                'total_points' => $team->points,
                'average_score' => $completedAttempts->count() > 0 ? round($this->calculateAveragePointsForAttempts($completedAttempts), 1) : 0,
                'questionnaire_details' => $this->getTeamQuestionnaireDetails($allAttempts),
            ];
        })->sortByDesc('total_points');
    }
    
    private function getTeamQuestionnaireDetails($attempts)
    {
        return $attempts->groupBy('questionnaire_id')->map(function($groupedAttempts, $questionnaireId) {
            $questionnaire = $groupedAttempts->first()->questionnaire;
            $completed = $groupedAttempts->where('status', QuizAttempt::STATUS_COMPLETED);
            
            // Calculate completion times for completed attempts
            $completionTimes = $completed->filter(function($attempt) {
                return $attempt->total_time_seconds > 0;
            })->pluck('total_time_seconds');
            
            return [
                'questionnaire_id' => $questionnaireId,
                'questionnaire_title' => $questionnaire ? $questionnaire->title : 'Unknown Questionnaire',
                'total_attempts' => $groupedAttempts->count(),
                'completed_attempts' => $completed->count(),
                'completion_rate' => $groupedAttempts->count() > 0 ? round(($completed->count() / $groupedAttempts->count()) * 100, 1) : 0,
                'average_score' => $completed->count() > 0 ? round($this->calculateAveragePointsForAttempts($completed), 1) : 0, // Average points scored
                'average_completion_time' => $completionTimes->count() > 0 ? $this->formatDuration($completionTimes->avg()) : null,
                'fastest_completion' => $completionTimes->count() > 0 ? $this->formatDuration($completionTimes->min()) : null,
                'slowest_completion' => $completionTimes->count() > 0 ? $this->formatDuration($completionTimes->max()) : null,
                'attempts_details' => $groupedAttempts->map(function($attempt) {
                    return [
                        'id' => $attempt->id,
                        'user_name' => $attempt->user->name ?? 'Unknown User',
                        'status' => $attempt->status,
                        'started_at' => $attempt->started_at,
                        'completed_at' => $attempt->completed_at,
                        'total_score' => $this->calculatePointsForAttempt($attempt), // Sum of points earned from all answers
                        'completion_time' => $attempt->total_time_seconds > 0 
                            ? $this->formatDuration($attempt->total_time_seconds)
                            : null,
                    ];
                })->sortBy('started_at')->values(),
            ];
        })->values();
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
        })->with('team')
        ->withCount(['quizAttempts as completed_quizzes' => function($q) {
            $q->where('status', QuizAttempt::STATUS_COMPLETED)
              ->where('created_at', '>=', now()->subDays($this->selectedTimeframe));
        }])
        ->having('completed_quizzes', '>', 0)
        ->get()
        ->map(function($user) {
            return [
                'name' => $user->name,
                'team' => $user->team ? $user->team->name : 'No Team',
                'total_points' => $user->team ? $user->team->points : 0,
                'completed_quizzes' => $user->completed_quizzes,
            ];
        })
        ->sortByDesc('total_points')
        ->take(10);
    }

    // Enhanced methods for better monitoring
    private function getAverageCompletionTime()
    {
        $completedAttempts = QuizAttempt::where('status', QuizAttempt::STATUS_COMPLETED)
            ->where('created_at', '>=', now()->subDays($this->selectedTimeframe))
            ->whereNotNull('started_at')
            ->whereNotNull('completed_at')
            ->get();
            
        if ($completedAttempts->isEmpty()) {
            return 0;
        }
        
        $totalSeconds = $completedAttempts->sum('total_time_seconds');
        
        return $this->formatDuration($totalSeconds / $completedAttempts->count());
    }
    
    private function getStrugglingUsers()
    {
        return User::where('role', 'user')
            ->whereHas('quizAttempts', function($q) {
                $q->where('created_at', '>=', now()->subDays($this->selectedTimeframe));
            })
            ->whereHas('team', function($q) {
                $q->where('points', '<', 800);
            })
            ->orWhereHas('quizAttempts', function($q) {
                $q->where('status', QuizAttempt::STATUS_STARTED)
                  ->where('created_at', '<', now()->subHours(2));
            })
            ->orWhereHas('quizAttempts', function($q) {
                $q->where('status', QuizAttempt::STATUS_STARTED)
                  ->where('created_at', '<', now()->subHours(2));
            })
            ->count();
    }
    
    private function getInProgressAttempts()
    {
        return QuizAttempt::where('status', QuizAttempt::STATUS_STARTED)
            ->where('created_at', '>=', now()->subDays($this->selectedTimeframe))
            ->count();
    }
    
    private function getGameAssessmentStats()
    {
        $total = GameAssessment::whereHas('quizAttempt', function($q) {
            $q->where('created_at', '>=', now()->subDays($this->selectedTimeframe));
        })->count();
        
        $assessed = GameAssessment::where('is_assessed', true)
            ->whereHas('quizAttempt', function($q) {
                $q->where('created_at', '>=', now()->subDays($this->selectedTimeframe));
            })->count();
            
        return [
            'total' => $total,
            'assessed' => $assessed,
            'pending' => $total - $assessed,
            'assessment_rate' => $total > 0 ? round(($assessed / $total) * 100, 1) : 0
        ];
    }
    
    private function getUserDetailData($userId)
    {
        $user = User::with(['quizAttempts.questionnaire', 'quizAttempts.userAnswers', 'team'])
            ->findOrFail($userId);
            
        $attempts = $user->quizAttempts()->where('created_at', '>=', now()->subDays($this->selectedTimeframe))->get();
        
        return [
            'user' => $user,
            'attempts' => $attempts->map(function($attempt) {
                return [
                    'id' => $attempt->id,
                    'questionnaire_title' => $attempt->questionnaire->title,
                    'status' => $attempt->status,
                    'total_score' => $attempt->total_score,
                    'started_at' => $attempt->started_at,
                    'completed_at' => $attempt->completed_at,
                    'duration' => $attempt->started_at && $attempt->completed_at 
                        ? $attempt->completed_at->diffInMinutes($attempt->started_at) 
                        : null,
                    'answers_count' => $attempt->userAnswers->count(),
                ];
            }),
            'total_time_spent' => $attempts->filter(function($attempt) {
                return $attempt->started_at && $attempt->completed_at;
            })->sum(function($attempt) {
                return $attempt->completed_at->diffInMinutes($attempt->started_at);
            })
        ];
    }
    
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'desc';
        }
        $this->sortField = $field;
    }
    
    public function showUserDetail($userId)
    {
        $this->selectedUserId = $userId;
        $this->showDetailedView = true;
    }
    
    public function hideUserDetail()
    {
        $this->selectedUserId = null;
        $this->showDetailedView = false;
    }
    
    public function showTeamDetail($teamId)
    {
        $this->selectedTeamId = $teamId;
        $this->showTeamDetailView = true;
    }
    
    public function hideTeamDetail()
    {
        $this->selectedTeamId = null;
        $this->showTeamDetailView = false;
    }
    
    private function getTeamDetailData($teamId)
    {
        $team = Team::with(['users.quizAttempts' => function($q) {
            $q->where('created_at', '>=', now()->subDays($this->selectedTimeframe))
              ->with(['questionnaire:id,title', 'user:id,name', 'userAnswers.question:id,points']);
        }])->findOrFail($teamId);
        
        $allAttempts = $team->users->flatMap->quizAttempts;
        
        return [
            'team' => $team,
            'questionnaire_details' => $this->getTeamQuestionnaireDetails($allAttempts),
            'total_attempts' => $allAttempts->count(),
            'completed_attempts' => $allAttempts->where('status', QuizAttempt::STATUS_COMPLETED)->count(),
            'total_time_spent' => $this->formatDuration($allAttempts->filter(function($attempt) {
                return $attempt->total_time_seconds > 0;
            })->sum('total_time_seconds'))
        ];
    }
    
    public function exportData()
    {
        $data = $this->getUserProgressData();
        
        if ($this->exportFormat === 'csv') {
            return $this->exportToCsv($data);
        }
        
        return $this->exportToExcel($data);
    }
    
    private function exportToCsv($data)
    {
        $filename = 'user-progress-' . now()->format('Y-m-d-H-i-s') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        $csv = "Name,Email,Team,Total Attempts,Completed,Completion Rate,Team Points,Average Points,Last Activity\n";
        
        foreach ($data as $user) {
            $csv .= implode(',', [
                '"' . $user['name'] . '"',
                '"' . $user['email'] . '"',
                '"' . $user['team'] . '"',
                $user['total_attempts'],
                $user['completed_attempts'],
                $user['completion_rate'] . '%',
                $user['total_points'],
                $user['average_score'],
                $user['last_activity'] ? $user['last_activity']->format('Y-m-d H:i:s') : 'Never'
            ]) . "\n";
        }
        
        return response($csv, 200, $headers);
    }
    
    public function updatedSearchTerm()
    {
        $this->resetPage();
    }

    public function updatedSelectedTimeframe()
    {
        $this->resetPage();
    }

    public function updatedSelectedTeam()
    {
        $this->resetPage();
    }

    public function updatedSelectedRole()
    {
        $this->resetPage();
    }
    
    public function resetFilters()
    {
        $this->selectedTimeframe = '30';
        $this->selectedTeam = 'all';
        $this->selectedRole = 'user';
        $this->searchTerm = '';
        $this->sortField = 'total_points';
        $this->sortDirection = 'desc';
        $this->resetPage();
    }
    
    /**
     * Format duration from seconds to minutes and seconds
     */
    private function formatDuration($seconds)
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
     * Calculate points for a single attempt by checking correct answers and question points
     */
    private function calculatePointsForAttempt($attempt)
    {
        $totalPoints = 0;
        
        foreach ($attempt->userAnswers as $userAnswer) {
            if ($userAnswer->is_correct && $userAnswer->question) {
                $totalPoints += $userAnswer->question->points;
            }
        }
        
        return $totalPoints;
    }
    
    /**
     * Calculate average points for a collection of attempts
     */
    private function calculateAveragePointsForAttempts($attempts)
    {
        if ($attempts->isEmpty()) {
            return 0;
        }
        
        $totalPoints = 0;
        foreach ($attempts as $attempt) {
            $totalPoints += $this->calculatePointsForAttempt($attempt);
        }
        
        return $totalPoints / $attempts->count();
    }
    
    /**
     * Calculate maximum points for a collection of attempts
     */
    private function calculateMaxPointsForAttempts($attempts)
    {
        if ($attempts->isEmpty()) {
            return 0;
        }
        
        $maxPoints = 0;
        foreach ($attempts as $attempt) {
            $points = $this->calculatePointsForAttempt($attempt);
            if ($points > $maxPoints) {
                $maxPoints = $points;
            }
        }
        
        return $maxPoints;
    }
}