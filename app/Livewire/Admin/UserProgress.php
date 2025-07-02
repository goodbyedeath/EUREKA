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
    public $showAssessmentNotesView = false;
    public $selectedUserIdForNotes = null;
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
        $strugglingUsers = $this->getStrugglingUsers();
        $inProgressAttempts = $this->getInProgressAttempts();
        $gameAssessmentStats = $this->getGameAssessmentStats();

        // User progress data with pagination
        $userProgressData = $this->getUserProgressData();
        
        // Team performance data with questionnaire completion tracking
        $teamPerformanceData = $this->getTeamPerformanceData();
        
        
        // Top performers
        $topPerformers = $this->getTopPerformers();
        
        // Get teams for filter
        $teams = Team::all();
        
        // Detailed user view data
        $userDetailData = $this->selectedUserId ? $this->getUserDetailData($this->selectedUserId) : null;
        
        // Detailed team view data
        $teamDetailData = $this->selectedTeamId ? $this->getTeamDetailData($this->selectedTeamId) : null;
        
        // Assessment notes data
        $assessmentNotesData = $this->selectedUserIdForNotes ? $this->getAssessmentNotesData($this->selectedUserIdForNotes) : null;

        return view('livewire.admin.user-progress', [
            'totalUsers' => $totalUsers,
            'activeUsers' => $activeUsers,
            'totalAttempts' => $totalAttempts,
            'completedAttempts' => $completedAttempts,
            'strugglingUsers' => $strugglingUsers,
            'inProgressAttempts' => $inProgressAttempts,
            'gameAssessmentStats' => $gameAssessmentStats,
            'userProgressData' => $userProgressData,
            'teamPerformanceData' => $teamPerformanceData,
            'topPerformers' => $topPerformers,
            'teams' => $teams,
            'userDetailData' => $userDetailData,
            'teamDetailData' => $teamDetailData,
            'assessmentNotesData' => $assessmentNotesData,
            'completionRate' => $totalAttempts > 0 ? round(($completedAttempts / $totalAttempts) * 100, 1) : 0
        ]);
    }

    private function getUserProgressData()
    {
        $query = User::with(['quizAttempts' => function($q) {
            $q->where('created_at', '>=', now()->subDays($this->selectedTimeframe))
              ->with(['userAnswers.question:id,points', 'questionnaire.questions:id,questionnaire_id,points', 'gameAssessments:id,quiz_attempt_id,user_id,is_assessed,notes,total_deposit,assessed_at']);
        }, 'team:id,name,initial_points,points']);

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
            
            
            // Get questionnaire completion details
            $questionnaireDetails = $this->getUserQuestionnaireDetails($attempts);
            
            // Get team points breakdown
            $teamPointsBreakdown = $this->calculateUserTeamPointsBreakdown($user, $attempts);
            
            // Get assessment notes
            $assessmentNotes = $this->getUserAssessmentNotes($completed);
            
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
                'total_points' => $teamPointsBreakdown['total'],
                'team_points_breakdown' => $teamPointsBreakdown,
                'average_score' => $completed->count() > 0 ? round($this->calculateAverageTeamPointsForAttempts($completed), 1) : 0, // Average team points following quiz-results logic
                'highest_score' => $completed->count() > 0 ? $this->calculateMaxPointsForAttempts($completed) : 0,
                'questionnaire_details' => $questionnaireDetails,
                'assessment_notes' => $assessmentNotes,
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
            
            // Get team questionnaire completion summary
            $questionnaireCompletionSummary = $this->getTeamQuestionnaireCompletionSummary($team, $allAttempts);
            
            return [
                'id' => $team->id,
                'name' => $team->name,
                'department' => $team->department,
                'member_count' => $team->users->count(),
                'total_attempts' => $allAttempts->count(),
                'completed_attempts' => $completedAttempts->count(),
                'completion_rate' => $allAttempts->count() > 0 ? round(($completedAttempts->count() / $allAttempts->count()) * 100, 1) : 0,
                'total_points' => $this->calculateTeamTotalPoints($team, $completedAttempts),
                'average_score' => $completedAttempts->count() > 0 ? round($this->calculateAverageTeamPointsForAttempts($completedAttempts), 1) : 0,
                'questionnaire_details' => $this->getTeamQuestionnaireDetails($allAttempts),
                'questionnaire_completion_summary' => $questionnaireCompletionSummary,
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
                'average_score' => $completed->count() > 0 ? round($this->calculateAverageTeamPointsForAttempts($completed), 1) : 0, // Average team points following quiz-results logic
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
                        'total_score' => $this->calculateTeamPoints($attempt), // Team points using quiz-results logic
                        'completion_time' => ($attempt->started_at && $attempt->completed_at && $attempt->total_time_seconds > 0)
                            ? $this->formatDuration($attempt->total_time_seconds)
                            : ($attempt->started_at && $attempt->completed_at 
                                ? $this->formatDuration($attempt->completed_at->diffInSeconds($attempt->started_at))
                                : null),
                    ];
                })->sortBy('started_at')->values(),
            ];
        })->values();
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
                'total_points' => $this->calculateUserTeamPointsFromUser($user),
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
        $user = User::with(['quizAttempts.questionnaire', 'quizAttempts.userAnswers.question', 'quizAttempts.gameAssessments', 'team'])
            ->findOrFail($userId);
            
        $attempts = $user->quizAttempts()->where('created_at', '>=', now()->subDays($this->selectedTimeframe))->get();
        $completed = $attempts->where('status', QuizAttempt::STATUS_COMPLETED);
        
        return [
            'user' => $user,
            'team_points_breakdown' => $this->calculateUserTeamPointsBreakdown($user, $attempts),
            'questionnaire_details' => $this->getUserQuestionnaireDetails($attempts),
            'assessment_notes' => $this->getUserAssessmentNotes($completed),
            'attempts' => $attempts->map(function($attempt) {
                return [
                    'id' => $attempt->id,
                    'questionnaire_title' => $attempt->questionnaire->title,
                    'status' => $attempt->status,
                    'total_score' => $attempt->status === 'completed' ? $this->calculateTeamPoints($attempt) : $attempt->total_score,
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
    
    public function showAssessmentNotes($userId)
    {
        $this->selectedUserIdForNotes = $userId;
        $this->showAssessmentNotesView = true;
    }
    
    public function hideAssessmentNotes()
    {
        $this->selectedUserIdForNotes = null;
        $this->showAssessmentNotesView = false;
    }
    
    private function getTeamDetailData($teamId)
    {
        $team = Team::with(['users.quizAttempts' => function($q) {
            $q->where('created_at', '>=', now()->subDays($this->selectedTimeframe))
              ->with(['questionnaire:id,title', 'user:id,name', 'userAnswers.question:id,points']);
        }])->findOrFail($teamId);
        
        $allAttempts = $team->users->flatMap->quizAttempts;
        $completedAttempts = $allAttempts->where('status', QuizAttempt::STATUS_COMPLETED);
        
        return [
            'team' => $team,
            'questionnaire_details' => $this->getTeamQuestionnaireDetails($allAttempts),
            'questionnaire_completion_summary' => $this->getTeamQuestionnaireCompletionSummary($team, $allAttempts),
            'total_attempts' => $allAttempts->count(),
            'completed_attempts' => $completedAttempts->count(),
            'total_time_spent' => 'N/A', // Removed complex time calculation
            'team_total_points' => $this->calculateTeamTotalPoints($team, $completedAttempts),
            'team_average_points' => $completedAttempts->count() > 0 ? round($this->calculateAverageTeamPointsForAttempts($completedAttempts), 1) : 0,
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
    
    /**
     * Calculate team points following quiz-results logic:
     * Team points = base points + earned points from correct answers + assessment bonuses
     */
    private function calculateTeamPoints($attempt)
    {
        $user = $attempt->user;
        $basePoints = $user->team ? ($user->team->initial_points ?? 1000) : 1000;
        
        // Calculate bonus points from correct answers
        $userAnswers = UserAnswer::where('quiz_attempt_id', $attempt->id)
            ->with(['question'])
            ->where('is_correct', true)
            ->get();
            
        $bonusPoints = $userAnswers->sum(function($answer) {
            return $answer->question->points ?? 0;
        });
        
        // Add assessment bonus
        $assessments = GameAssessment::where('quiz_attempt_id', $attempt->id)
            ->where('user_id', $attempt->user_id)
            ->where('is_assessed', true)
            ->get();
            
        foreach ($assessments as $assessment) {
            $assessmentBonus = ($assessment->total_deposit ?? 0) - $basePoints;
            $bonusPoints += max(0, $assessmentBonus);
        }
        
        return $basePoints + $bonusPoints;
    }
    
    /**
     * Calculate user's total team points across all attempts
     */
    private function calculateUserTeamPoints($user, $attempts)
    {
        $completedAttempts = $attempts->where('status', QuizAttempt::STATUS_COMPLETED);
        
        if ($completedAttempts->isEmpty()) {
            return $user->team ? ($user->team->initial_points ?? 1000) : 1000;
        }
        
        // Get the highest team points from all completed attempts
        $maxTeamPoints = 0;
        foreach ($completedAttempts as $attempt) {
            $teamPoints = $this->calculateTeamPoints($attempt);
            if ($teamPoints > $maxTeamPoints) {
                $maxTeamPoints = $teamPoints;
            }
        }
        
        return $maxTeamPoints;
    }
    
    /**
     * Calculate user's team points from user model (for top performers)
     */
    private function calculateUserTeamPointsFromUser($user)
    {
        $attempts = $user->quizAttempts()
            ->where('status', QuizAttempt::STATUS_COMPLETED)
            ->where('created_at', '>=', now()->subDays($this->selectedTimeframe))
            ->with(['userAnswers.question'])
            ->get();
            
        return $this->calculateUserTeamPoints($user, $attempts);
    }
    
    /**
     * Calculate team's total points from all completed attempts
     */
    private function calculateTeamTotalPoints($team, $completedAttempts)
    {
        if ($completedAttempts->isEmpty()) {
            return $team->initial_points ?? 1000;
        }
        
        // Get the highest team points across all team members' attempts
        $maxTeamPoints = $team->initial_points ?? 1000;
        
        foreach ($completedAttempts as $attempt) {
            $teamPoints = $this->calculateTeamPoints($attempt);
            if ($teamPoints > $maxTeamPoints) {
                $maxTeamPoints = $teamPoints;
            }
        }
        
        return $maxTeamPoints;
    }
    
    /**
     * Calculate average team points for a collection of attempts using quiz-results logic
     */
    private function calculateAverageTeamPointsForAttempts($attempts)
    {
        if ($attempts->isEmpty()) {
            return 0;
        }
        
        $totalTeamPoints = 0;
        foreach ($attempts as $attempt) {
            $totalTeamPoints += $this->calculateTeamPoints($attempt);
        }
        
        return $totalTeamPoints / $attempts->count();
    }
    
    /**
     * Get detailed questionnaire completion data for a user
     */
    private function getUserQuestionnaireDetails($attempts)
    {
        return $attempts->groupBy('questionnaire_id')->map(function($groupedAttempts, $questionnaireId) {
            $questionnaire = $groupedAttempts->first()->questionnaire;
            $completed = $groupedAttempts->where('status', QuizAttempt::STATUS_COMPLETED);
            $totalQuestions = $questionnaire ? $questionnaire->questions->count() : 0;
            
            // Calculate questions answered correctly across all attempts
            $correctAnswers = $groupedAttempts->flatMap(function($attempt) {
                return $attempt->userAnswers->where('is_correct', true);
            })->unique('question_id')->count();
            
            return [
                'questionnaire_id' => $questionnaireId,
                'questionnaire_title' => $questionnaire ? $questionnaire->title : 'Unknown Questionnaire',
                'total_attempts' => $groupedAttempts->count(),
                'completed_attempts' => $completed->count(),
                'total_questions' => $totalQuestions,
                'questions_answered_correctly' => $correctAnswers,
                'questions_completion_rate' => $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100, 1) : 0,
                'last_attempt_date' => $groupedAttempts->max('created_at'),
                'best_score' => $completed->isNotEmpty() ? $completed->max(function($attempt) {
                    return $this->calculateTeamPoints($attempt);
                }) : 0,
            ];
        })->values();
    }
    
    /**
     * Calculate detailed team points breakdown for a user
     */
    private function calculateUserTeamPointsBreakdown($user, $attempts)
    {
        $completedAttempts = $attempts->where('status', QuizAttempt::STATUS_COMPLETED);
        
        $basePoints = $user->team ? ($user->team->initial_points ?? 1000) : 1000;
        
        if ($completedAttempts->isEmpty()) {
            return [
                'total' => $basePoints,
                'base_points' => $basePoints,
                'earned_points' => 0,
                'assessment_bonus' => 0,
                'breakdown_text' => $basePoints . ' (base)',
            ];
        }
        
        // Get the best attempt for points calculation
        $bestAttempt = null;
        $maxTeamPoints = 0;
        
        foreach ($completedAttempts as $attempt) {
            $teamPoints = $this->calculateTeamPoints($attempt);
            if ($teamPoints > $maxTeamPoints) {
                $maxTeamPoints = $teamPoints;
                $bestAttempt = $attempt;
            }
        }
        
        if (!$bestAttempt) {
            return [
                'total' => $basePoints,
                'base_points' => $basePoints,
                'earned_points' => 0,
                'assessment_bonus' => 0,
                'breakdown_text' => $basePoints . ' (base)',
            ];
        }
        
        // Calculate detailed breakdown
        $userAnswers = UserAnswer::where('quiz_attempt_id', $bestAttempt->id)
            ->with(['question'])
            ->where('is_correct', true)
            ->get();
            
        $earnedPoints = $userAnswers->sum(function($answer) {
            return $answer->question->points ?? 0;
        });
        
        // Assessment bonus
        $assessments = GameAssessment::where('quiz_attempt_id', $bestAttempt->id)
            ->where('user_id', $bestAttempt->user_id)
            ->where('is_assessed', true)
            ->get();
            
        $assessmentBonus = 0;
        foreach ($assessments as $assessment) {
            $bonus = ($assessment->total_deposit ?? 0) - $basePoints;
            $assessmentBonus += max(0, $bonus);
        }
        
        // Create breakdown text
        $breakdownParts = [$basePoints . ' (base)'];
        if ($earnedPoints > 0) {
            $breakdownParts[] = $earnedPoints . ' (earned)';
        }
        if ($assessmentBonus > 0) {
            $breakdownParts[] = $assessmentBonus . ' (assessment)';
        }
        
        $breakdownText = implode(' + ', $breakdownParts) . ' = ' . ($basePoints + $earnedPoints + $assessmentBonus);
        
        return [
            'total' => $basePoints + $earnedPoints + $assessmentBonus,
            'base_points' => $basePoints,
            'earned_points' => $earnedPoints,
            'assessment_bonus' => $assessmentBonus,
            'breakdown_text' => $breakdownText,
        ];
    }
    
    /**
     * Get assessment notes for completed attempts
     */
    private function getUserAssessmentNotes($completedAttempts)
    {
        $notes = [];
        
        foreach ($completedAttempts as $attempt) {
            $assessments = GameAssessment::where('quiz_attempt_id', $attempt->id)
                ->where('user_id', $attempt->user_id)
                ->where('is_assessed', true)
                ->get();
                
            foreach ($assessments as $assessment) {
                if (!empty($assessment->notes)) {
                    $notes[] = [
                        'attempt_id' => $attempt->id,
                        'questionnaire_title' => $attempt->questionnaire->title ?? 'Unknown Quiz',
                        'assessment_date' => $assessment->assessed_at ?? $assessment->updated_at,
                        'notes' => $assessment->notes,
                        'total_deposit' => $assessment->total_deposit,
                    ];
                }
            }
        }
        
        return collect($notes)->sortByDesc('assessment_date')->values();
    }
    
    /**
     * Get team questionnaire completion summary
     */
    private function getTeamQuestionnaireCompletionSummary($team, $allAttempts)
    {
        $questionnaires = Questionnaire::all();
        $summary = [];
        
        foreach ($questionnaires as $questionnaire) {
            $teamAttempts = $allAttempts->where('questionnaire_id', $questionnaire->id);
            $completedAttempts = $teamAttempts->where('status', QuizAttempt::STATUS_COMPLETED);
            
            // Get unique users who completed this questionnaire
            $usersCompleted = $completedAttempts->pluck('user_id')->unique();
            $totalTeamMembers = $team->users->count();
            
            // Calculate total questions in questionnaire
            $totalQuestions = $questionnaire->questions->count();
            
            // Calculate total correct answers across all team members
            $totalCorrectAnswers = $completedAttempts->flatMap(function($attempt) {
                return $attempt->userAnswers->where('is_correct', true);
            })->unique('question_id')->count();
            
            $summary[] = [
                'questionnaire_id' => $questionnaire->id,
                'questionnaire_title' => $questionnaire->title,
                'total_questions' => $totalQuestions,
                'users_completed' => $usersCompleted->count(),
                'total_team_members' => $totalTeamMembers,
                'team_completion_rate' => $totalTeamMembers > 0 ? round(($usersCompleted->count() / $totalTeamMembers) * 100, 1) : 0,
                'questions_mastered' => $totalCorrectAnswers,
                'questions_mastery_rate' => $totalQuestions > 0 ? round(($totalCorrectAnswers / $totalQuestions) * 100, 1) : 0,
                'total_attempts' => $teamAttempts->count(),
                'completed_attempts' => $completedAttempts->count(),
                'last_completion_date' => $completedAttempts->max('completed_at'),
                'team_members_completed' => $completedAttempts->map(function($attempt) {
                    return [
                        'user_name' => $attempt->user->name ?? 'Unknown',
                        'completed_at' => $attempt->completed_at,
                        'score' => $this->calculateTeamPoints($attempt),
                    ];
                })->sortByDesc('score')->values(),
            ];
        }
        
        return collect($summary)->sortByDesc('team_completion_rate')->values();
    }
    
    /**
     * Get assessment notes data for a specific user (simplified)
     */
    private function getAssessmentNotesData($userId)
    {
        $user = User::with(['team'])->findOrFail($userId);
        
        $attempts = $user->quizAttempts()
            ->where('created_at', '>=', now()->subDays($this->selectedTimeframe))
            ->with(['questionnaire', 'userAnswers.question', 'gameAssessments'])
            ->get();
            
        $assessmentNotes = $this->getUserAssessmentNotes($attempts->where('status', QuizAttempt::STATUS_COMPLETED));
        
        // Include team points breakdown for context
        $teamPointsBreakdown = $this->calculateUserTeamPointsBreakdown($user, $attempts);
        
        return [
            'user' => $user,
            'assessment_notes' => $assessmentNotes,
            'team_points_breakdown' => $teamPointsBreakdown,
        ];
    }
}