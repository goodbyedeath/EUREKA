<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\User;
use App\Models\Team;
use App\Models\QuizAttempt;
use App\Models\Questionnaire;
use App\Models\GameAssessment;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Livewire\WithPagination;

class EnhancedUserProgress extends Component
{
    use WithPagination;

    public $view = 'overview'; // overview, users, teams, analytics
    public $searchTerm = '';
    public $selectedTeam = '';
    public $selectedTimeframe = '30';
    public $selectedStatus = 'all'; // all, active, inactive
    public $sortBy = 'name';
    public $sortDirection = 'asc';
    
    // Real-time monitoring
    public $liveUsers = [];
    public $recentActivity = [];
    
    // Analytics data
    public $analyticsData = [];
    public $completionTrends = [];
    public $teamPerformance = [];
    
    protected $queryString = ['view', 'searchTerm', 'selectedTeam', 'selectedTimeframe'];

    public function mount()
    {
        $this->loadAnalyticsData();
        $this->loadRecentActivity();
    }

    public function updatedView()
    {
        $this->resetPage();
        $this->loadAnalyticsData();
    }

    public function updatedSelectedTimeframe()
    {
        $this->loadAnalyticsData();
        $this->loadCompletionTrends();
    }

    public function updatedSearchTerm()
    {
        $this->resetPage();
    }

    public function loadAnalyticsData()
    {
        $daysBack = (int) $this->selectedTimeframe;
        $dateFilter = Carbon::now()->subDays($daysBack);

        // Overview metrics
        $this->analyticsData = [
            'total_users' => User::where('role', 'user')->count(),
            'active_users' => User::where('role', 'user')
                ->where('last_login_at', '>=', $dateFilter)
                ->count(),
            'total_teams' => Team::count(),
            'total_attempts' => QuizAttempt::where('created_at', '>=', $dateFilter)->count(),
            'completed_attempts' => QuizAttempt::where('status', 'completed')
                ->where('created_at', '>=', $dateFilter)
                ->count(),
            'average_completion_time' => QuizAttempt::where('status', 'completed')
                ->where('created_at', '>=', $dateFilter)
                ->whereNotNull('total_time_seconds')
                ->avg('total_time_seconds'),
            'average_score' => QuizAttempt::where('status', 'completed')
                ->where('created_at', '>=', $dateFilter)
                ->whereNotNull('total_score')
                ->avg('total_score'),
            'pending_assessments' => GameAssessment::where('is_assessed', false)
                ->where('created_at', '>=', $dateFilter)
                ->count(),
        ];

        // Calculate rates
        $this->analyticsData['completion_rate'] = $this->analyticsData['total_attempts'] > 0 
            ? round(($this->analyticsData['completed_attempts'] / $this->analyticsData['total_attempts']) * 100, 1)
            : 0;

        $this->analyticsData['user_activity_rate'] = $this->analyticsData['total_users'] > 0
            ? round(($this->analyticsData['active_users'] / $this->analyticsData['total_users']) * 100, 1)
            : 0;

        // Load completion trends
        $this->loadCompletionTrends();
        
        // Load team performance
        $this->loadTeamPerformance();
    }

    public function loadCompletionTrends()
    {
        $daysBack = (int) $this->selectedTimeframe;
        $periods = min($daysBack, 14); // Max 14 data points for readability
        
        $trends = [];
        for ($i = $periods - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $completed = QuizAttempt::where('status', 'completed')
                ->whereDate('completed_at', $date->toDateString())
                ->count();
            
            $trends[] = [
                'date' => $date->format('M d'),
                'completed' => $completed,
            ];
        }
        
        $this->completionTrends = $trends;
    }

    public function loadTeamPerformance()
    {
        $daysBack = (int) $this->selectedTimeframe;
        $dateFilter = Carbon::now()->subDays($daysBack);

        $this->teamPerformance = Team::select('teams.*')
            ->withCount(['users as member_count'])
            ->withAvg(['users as avg_score' => function($query) use ($dateFilter) {
                $query->join('quiz_attempts', 'users.id', '=', 'quiz_attempts.user_id')
                    ->where('quiz_attempts.status', 'completed')
                    ->where('quiz_attempts.created_at', '>=', $dateFilter)
                    ->select('quiz_attempts.total_score');
            }], 'total_score')
            ->withCount(['users as completed_attempts' => function($query) use ($dateFilter) {
                $query->join('quiz_attempts', 'users.id', '=', 'quiz_attempts.user_id')
                    ->where('quiz_attempts.status', 'completed')
                    ->where('quiz_attempts.created_at', '>=', $dateFilter);
            }])
            ->orderByDesc('avg_score')
            ->take(10)
            ->get()
            ->map(function($team) {
                $team->avg_score = round($team->avg_score ?? 0, 1);
                return $team;
            });
    }

    public function loadRecentActivity()
    {
        $this->recentActivity = QuizAttempt::with(['user', 'questionnaire'])
            ->where('created_at', '>=', Carbon::now()->subHours(24))
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get()
            ->map(function($attempt) {
                return [
                    'id' => $attempt->id,
                    'user_name' => $attempt->user->name ?? 'Unknown',
                    'questionnaire_title' => $attempt->questionnaire->title ?? 'Unknown Quiz',
                    'status' => $attempt->status,
                    'score' => $attempt->total_score,
                    'time_ago' => $attempt->created_at->diffForHumans(),
                    'created_at' => $attempt->created_at,
                ];
            });
    }

    public function getUsersProperty()
    {
        $query = User::with(['team', 'quizAttempts' => function($q) {
            $daysBack = (int) $this->selectedTimeframe;
            $q->where('created_at', '>=', Carbon::now()->subDays($daysBack));
        }])
        ->where('role', 'user');

        // Apply filters
        if ($this->searchTerm) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->searchTerm . '%')
                  ->orWhere('email', 'like', '%' . $this->searchTerm . '%');
            });
        }

        if ($this->selectedTeam) {
            $query->where('team_id', $this->selectedTeam);
        }

        if ($this->selectedStatus === 'active') {
            $query->where('last_login_at', '>=', Carbon::now()->subDays(7));
        } elseif ($this->selectedStatus === 'inactive') {
            $query->where(function($q) {
                $q->whereNull('last_login_at')
                  ->orWhere('last_login_at', '<', Carbon::now()->subDays(7));
            });
        }

        // Apply sorting
        if ($this->sortBy === 'activity') {
            $query->orderBy('last_login_at', $this->sortDirection);
        } elseif ($this->sortBy === 'progress') {
            // Sort by completion rate
            $query->withCount(['quizAttempts as completed_count' => function($q) {
                $q->where('status', 'completed');
            }])
            ->orderBy('completed_count', $this->sortDirection);
        } else {
            $query->orderBy($this->sortBy, $this->sortDirection);
        }

        return $query->paginate(15);
    }

    public function getTeamsProperty()
    {
        $daysBack = (int) $this->selectedTimeframe;
        $dateFilter = Carbon::now()->subDays($daysBack);

        return Team::withCount(['users as member_count'])
            ->withAvg(['users as avg_score' => function($query) use ($dateFilter) {
                $query->join('quiz_attempts', 'users.id', '=', 'quiz_attempts.user_id')
                    ->where('quiz_attempts.status', 'completed')
                    ->where('quiz_attempts.created_at', '>=', $dateFilter)
                    ->select('quiz_attempts.total_score');
            }], 'total_score')
            ->withCount(['users as total_attempts' => function($query) use ($dateFilter) {
                $query->join('quiz_attempts', 'users.id', '=', 'quiz_attempts.user_id')
                    ->where('quiz_attempts.created_at', '>=', $dateFilter);
            }])
            ->withCount(['users as completed_attempts' => function($query) use ($dateFilter) {
                $query->join('quiz_attempts', 'users.id', '=', 'quiz_attempts.user_id')
                    ->where('quiz_attempts.status', 'completed')
                    ->where('quiz_attempts.created_at', '>=', $dateFilter);
            }])
            ->when($this->searchTerm, function($query) {
                $query->where('name', 'like', '%' . $this->searchTerm . '%');
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(10);
    }

    public function getAvailableTeamsProperty()
    {
        return Team::orderBy('name')->get();
    }

    public function sortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function refreshData()
    {
        $this->loadAnalyticsData();
        $this->loadRecentActivity();
        session()->flash('success', 'Data refreshed successfully!');
    }

    public function exportUserProgress()
    {
        // Export functionality would be implemented here
        // For now, just show a success message
        session()->flash('success', 'Export functionality will be implemented soon!');
    }

    public function getUserProgressDetails($userId)
    {
        $user = User::with(['team', 'quizAttempts.questionnaire'])->findOrFail($userId);
        $daysBack = (int) $this->selectedTimeframe;
        
        $attempts = $user->quizAttempts()
            ->with('questionnaire')
            ->where('created_at', '>=', Carbon::now()->subDays($daysBack))
            ->orderBy('created_at', 'desc')
            ->get();

        return [
            'user' => $user,
            'total_attempts' => $attempts->count(),
            'completed_attempts' => $attempts->where('status', 'completed')->count(),
            'average_score' => $attempts->where('status', 'completed')->avg('total_score'),
            'total_time' => $attempts->where('status', 'completed')->sum('total_time_seconds'),
            'recent_attempts' => $attempts->take(5),
            'completion_rate' => $attempts->count() > 0 
                ? round(($attempts->where('status', 'completed')->count() / $attempts->count()) * 100, 1)
                : 0,
        ];
    }

    public function render()
    {
        return view('livewire.admin.enhanced-user-progress', [
            'users' => $this->view === 'users' ? $this->users : collect(),
            'teams' => $this->view === 'teams' ? $this->teams : collect(),
            'availableTeams' => $this->availableTeams,
        ]);
    }
}