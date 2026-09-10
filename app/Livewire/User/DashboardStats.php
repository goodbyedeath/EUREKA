<?php

namespace App\Livewire\User;

use Livewire\Component;
use App\Models\QuizAttempt;
use App\Models\Team;
use Illuminate\Support\Facades\Auth;

class DashboardStats extends Component
{
    public $totalAttempts = 0;
    public $completedAttempts = 0;
    public $averageScore = 0;
    public $completionRate = 0;
    public $teamPoints = 0;
    public $teamName = '';
    public $showDetailsModal = false;
    public $scoreDetails = [];
    public $showHistoryModal = false;
    public $recentAttempts = [];

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
        
        // Get basic attempt counts
        $this->totalAttempts = QuizAttempt::where('user_id', $user->id)->count();
        
        $this->completedAttempts = QuizAttempt::where('user_id', $user->id)
            ->where('status', 'completed')
            ->count();
        
        // Calculate CLEAR metrics as requested:
        // 1. Average Score = Total gained points from correct answers ÷ Total questions answered
        // 2. Total Score = Base points + All gained points from correct answers
        
        $this->calculateAverageScore($user);
        $this->calculateTeamTotalScore($user);
        
        $this->completionRate = $this->totalAttempts > 0 
            ? round(($this->completedAttempts / $this->totalAttempts) * 100, 0) 
            : 0;
    }

    private function calculateAverageScore($user)
    {
        // Average Score = Earned points (from score details modal) ÷ Total completed questionnaires
        
        $totalEarnedPoints = 0;
        $completedQuestionnaires = 0;
        
        // Get all completed quiz attempts
        $completedAttempts = QuizAttempt::with(['questionnaire.questions'])
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->get();
        
        foreach ($completedAttempts as $attempt) {
            $completedQuestionnaires++;
            
            // Calculate earned points for this attempt (same as score details modal)
            $basePoints = $this->getBasePointsForUser($user);
            $bonusPoints = $this->calculateBonusPoints($attempt);
            $earnedPoints = $bonusPoints; // Earned points = bonus points (without base)
            
            $totalEarnedPoints += $earnedPoints;
        }
        
        $this->averageScore = $completedQuestionnaires > 0 
            ? round($totalEarnedPoints / $completedQuestionnaires, 1) 
            : 0;
    }

    private function calculateTeamTotalScore($user)
    {
        // Total Score = Base/Initial Points + accumulation of gained points from completing question challenges
        
        $basePoints = $this->getBasePointsForUser($user);
        $totalGainedPoints = 0;
        
        // Get all gained points from correct answers
        $userAnswers = \App\Models\UserAnswer::whereHas('quizAttempt', function($query) use ($user) {
            $query->where('user_id', $user->id)
                  ->where('status', 'completed');
        })->with(['question'])->where('is_correct', true)->get();
        
        foreach ($userAnswers as $answer) {
            if ($answer->question) {
                $totalGainedPoints += $answer->question->points ?? 0;
            }
        }
        
        // Add assessment gains (bonus from fun games)
        $assessmentGains = \App\Models\GameAssessment::whereHas('quizAttempt', function($query) use ($user) {
            $query->where('user_id', $user->id)
                  ->where('status', 'completed');
        })->where('is_assessed', true)->get();
        
        foreach ($assessmentGains as $assessment) {
            // Assessment gain = total_deposit - base_points_used
            $assessmentGain = ($assessment->total_deposit ?? 0) - $basePoints;
            $totalGainedPoints += $assessmentGain;
        }
        
        // Load team information
        if ($user->team_id) {
            $team = Team::find($user->team_id);
            if ($team) {
                $this->teamPoints = $basePoints + $totalGainedPoints; // Clear calculation: Base + Gained
                $this->teamName = $team->name;
            }
        } else {
            $this->teamPoints = 0;
            $this->teamName = '';
        }
    }

    private function getBasePointsForUser($user)
    {
        // Base points from user's team initial points (same as QuizResults)
        if (!$user || !$user->team) {
            return 1000; // Default base points
        }
        
        return $user->team->initial_points ?? 1000;
    }

    private function calculateBonusPoints($attempt)
    {
        // Calculate bonus points the same way as QuizResults component
        $regularBonusPoints = $this->getRegularQuestionsScore($attempt);
        $assessmentBonusPoints = $this->getAssessmentBonusPoints($attempt);
        return $regularBonusPoints + $assessmentBonusPoints;
    }

    private function getRegularQuestionsScore($attempt)
    {
        // Score from non-fun_game questions
        $userAnswers = \App\Models\UserAnswer::where('quiz_attempt_id', $attempt->id)->get()->keyBy('question_id');
        
        if (!$attempt->questionnaire || !$attempt->questionnaire->questions || !$userAnswers) {
            return 0;
        }
        
        $regularQuestions = $attempt->questionnaire->questions->where('type', '!=', 'fun_game');
        $totalRegularScore = 0;
        
        foreach ($regularQuestions as $question) {
            $userAnswer = $userAnswers->get($question->id);
            if ($userAnswer && $userAnswer->is_correct) {
                $totalRegularScore += $question->points ?? 0;
            }
        }
        
        return $totalRegularScore;
    }

    private function getAssessmentBonusPoints($attempt)
    {
        // Assessment bonus points = total_deposit minus base points for each assessment
        $assessments = \App\Models\GameAssessment::where('quiz_attempt_id', $attempt->id)
            ->where('user_id', $attempt->user_id)
            ->where('is_assessed', true)
            ->get();
            
        if ($assessments->isEmpty()) {
            return 0;
        }
        
        $basePoints = $this->getBasePointsForUser($attempt->user ?? Auth::user());
        $totalBonusFromAssessments = 0;
        
        foreach ($assessments as $assessment) {
            // Bonus from assessment = total_deposit - base_points_used_in_assessment
            $assessmentBonus = ($assessment->total_deposit ?? 0) - $basePoints;
            $totalBonusFromAssessments += $assessmentBonus; // Negative = net penalty
        }
        
        return $totalBonusFromAssessments;
    }

    public function openScoreDetails()
    {
        $user = Auth::user();
        
        // Load detailed score information with correct calculations
        $attempts = QuizAttempt::with(['questionnaire.questions'])
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->orderBy('completed_at', 'desc')
            ->get();
            
        $this->scoreDetails = $attempts->map(function($attempt) {
            $basePoints = $this->getBasePointsForUser(Auth::user());
            $bonusPoints = $this->calculateBonusPoints($attempt);
            $totalScore = $basePoints + $bonusPoints;
            
            // Calculate percentage based on total possible points
            $totalPossiblePoints = $this->getTotalPossiblePoints($attempt);
            $percentage = $totalPossiblePoints > 0 ? round(($totalScore / $totalPossiblePoints) * 100, 1) : 0;
            
            return [
                'id' => $attempt->id,
                'questionnaire_name' => $attempt->questionnaire->title ?? 'Unknown Quiz',
                'completed_at' => $attempt->completed_at->format('M j, Y g:i A'),
                'total_score' => $totalScore,
                'base_points' => $basePoints,
                'earned_points' => $bonusPoints,
                'percentage' => $percentage,
                'duration' => $this->formatDuration($attempt->total_time_seconds ?? 0),
            ];
        })->toArray();
        
        $this->showDetailsModal = true;
    }

    private function getTotalPossiblePoints($attempt)
    {
        // Calculate total possible points for this quiz
        if (!$attempt->questionnaire || !$attempt->questionnaire->questions) {
            return 0;
        }
        
        $regularPoints = $attempt->questionnaire->questions->where('type', '!=', 'fun_game')->sum('points');
        $funGameCount = $attempt->questionnaire->questions->where('type', 'fun_game')->count();
        
        // For fun games, max possible = team base points + questionnaire total points
        $basePoints = $this->getBasePointsForUser(Auth::user());
        $questionnaireTotalPoints = $attempt->questionnaire->questions->sum('points');
        $maxAssessmentPoints = $funGameCount * ($basePoints + $questionnaireTotalPoints);
        
        return ($regularPoints ?? 0) + $maxAssessmentPoints;
    }

    private function formatDuration($seconds)
    {
        if (!$seconds || $seconds < 0) {
            return '0 seconds';
        }
        
        if ($seconds < 60) {
            return $seconds . ' seconds';
        }
        
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        
        if ($minutes < 60) {
            return $minutes . 'm ' . $remainingSeconds . 's';
        }
        
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        
        return $hours . 'h ' . $remainingMinutes . 'm ' . $remainingSeconds . 's';
    }
    
    public function closeDetailsModal()
    {
        $this->showDetailsModal = false;
        $this->scoreDetails = [];
    }

    public function openHistoryModal()
    {
        $user = Auth::user();
        
        // Load recent quiz attempts
        $attempts = QuizAttempt::with(['questionnaire'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();
            
        // Debug logging
        if (config('app.debug')) {
            \Log::debug("DashboardStats: Found " . $attempts->count() . " attempts for user " . $user->id);
            foreach ($attempts as $attempt) {
                \Log::debug("DashboardStats: Attempt {$attempt->id} - Status: {$attempt->status}, Quiz: " . ($attempt->questionnaire->title ?? 'Unknown'));
            }
        }
            
        $this->recentAttempts = $attempts->map(function($attempt) {
            $basePoints = $this->getBasePointsForUser(Auth::user());
            $bonusPoints = $attempt->status === 'completed' ? $this->calculateBonusPoints($attempt) : 0;
            $totalScore = $attempt->status === 'completed' ? $basePoints + $bonusPoints : 0;
            
            return [
                'id' => $attempt->id,
                'questionnaire_id' => $attempt->questionnaire_id,
                'quiz_title' => $attempt->questionnaire->title ?? 'Unknown Quiz',
                'status' => $attempt->status,
                'score' => $totalScore,
                'date' => $attempt->created_at->format('M j, Y g:i A'),
            ];
        })->toArray();
        
        $this->showHistoryModal = true;
    }
    
    public function closeHistoryModal()
    {
        $this->showHistoryModal = false;
        $this->recentAttempts = [];
    }

    public function getScoreColor($score)
    {
        // Convert total score to a percentage-like scale for color coding
        // Assuming base score is around 1000 and good performance adds 100+ points
        if ($score >= 1100) return 'text-green-600';
        if ($score >= 1080) return 'text-blue-600';
        if ($score >= 1050) return 'text-yellow-600';
        if ($score >= 1020) return 'text-orange-600';
        return 'text-red-600';
    }

    public function render()
    {
        return view('livewire.user.dashboard-stats');
    }
}