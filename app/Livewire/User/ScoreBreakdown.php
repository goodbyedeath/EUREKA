<?php

namespace App\Livewire\User;

use Livewire\Component;
use App\Models\Team;
use App\Models\QuizAttempt;
use Illuminate\Support\Facades\Auth;

class ScoreBreakdown extends Component
{
    public $showModal = false;
    public $team;
    public $basePoints = 0;
    public $earnedPoints = 0;
    public $quizAttempts = [];

    protected $listeners = ['open-score-breakdown' => 'openModal'];

    public function mount()
    {
        $this->team = Auth::user()->team;
        $this->loadScoreBreakdown();
    }

    public function loadScoreBreakdown()
    {
        if (!$this->team) {
            return;
        }

        // Base points from team's initial points (not current points)
        $this->basePoints = $this->team->initial_points ?? 1000;
        
        // Get all completed quiz attempts with details using correct calculation
        $attempts = QuizAttempt::with(['user', 'questionnaire.questions'])
            ->whereHas('user', function($query) {
                $query->where('team_id', $this->team->id);
            })
            ->where('status', 'completed')
            ->orderBy('completed_at', 'desc')
            ->get();
            
        $this->quizAttempts = $attempts->map(function($attempt) {
            // Calculate bonus points for this attempt (same logic as QuizResults)
            $bonusPoints = $this->calculateAttemptBonusPoints($attempt);
            
            return [
                'user_name' => $attempt->user->name,
                'questionnaire_title' => $attempt->questionnaire->title ?? 'Quiz',
                'score' => $bonusPoints, // This is the earned/bonus points
                'completed_at' => $attempt->completed_at,
            ];
        });

        $this->earnedPoints = $this->quizAttempts->sum('score');
    }

    private function calculateAttemptBonusPoints($attempt)
    {
        // Calculate bonus points the same way as QuizResults component
        $regularBonusPoints = $this->getAttemptRegularQuestionsScore($attempt);
        $assessmentBonusPoints = $this->getAttemptAssessmentBonusPoints($attempt);
        return $regularBonusPoints + $assessmentBonusPoints;
    }

    private function getAttemptRegularQuestionsScore($attempt)
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

    private function getAttemptAssessmentBonusPoints($attempt)
    {
        // Assessment bonus points = total_deposit minus base points for each assessment
        $assessments = \App\Models\GameAssessment::where('quiz_attempt_id', $attempt->id)
            ->where('user_id', $attempt->user_id)
            ->where('is_assessed', true)
            ->get();
            
        if ($assessments->isEmpty()) {
            return 0;
        }
        
        $basePoints = $this->basePoints;
        $totalBonusFromAssessments = 0;
        
        foreach ($assessments as $assessment) {
            // Bonus from assessment = total_deposit - base_points_used_in_assessment
            $assessmentBonus = ($assessment->total_deposit ?? 0) - $basePoints;
            $totalBonusFromAssessments += $assessmentBonus; // Negative = net penalty
        }
        
        return $totalBonusFromAssessments;
    }

    public function openModal()
    {
        $this->showModal = true;
        $this->loadScoreBreakdown();
    }

    public function closeModal()
    {
        $this->showModal = false;
    }

    public function render()
    {
        return view('livewire.user.score-breakdown');
    }
}