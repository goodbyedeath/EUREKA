<?php

namespace App\Livewire\User;

use Livewire\Component;
use App\Models\QuizAttempt;
use App\Models\UserAnswer;
use App\Models\GameAssessment;
use Illuminate\Support\Facades\Auth;

class QuizResults extends Component
{
    public QuizAttempt $attempt;
    public $userAnswers;
    public $questionnaire;
    public $questions;
    public $assessments;
    public $showDetails = false;
    public $currentQuestionIndex = 0;

    public function mount($attemptId)
    {
        $this->attempt = QuizAttempt::with(['questionnaire.questions'])
            ->where('id', $attemptId)
            ->where('user_id', Auth::id())
            ->where('status', QuizAttempt::STATUS_COMPLETED)
            ->firstOrFail();

        $this->questionnaire = $this->attempt->questionnaire;
        $this->questions = $this->questionnaire->questions;
        
        $this->loadUserAnswers();
        $this->loadAssessments();
    }

    public function loadUserAnswers()
    {
        $this->userAnswers = UserAnswer::with(['question'])
            ->where('quiz_attempt_id', $this->attempt->id)
            ->get()
            ->keyBy('question_id');
    }

    public function loadAssessments()
    {
        $this->assessments = GameAssessment::where('quiz_attempt_id', $this->attempt->id)
            ->where('user_id', Auth::id())
            ->get()
            ->keyBy('question_id');
    }

    public function toggleDetails()
    {
        $this->showDetails = !$this->showDetails;
    }

    public function setCurrentQuestion($index)
    {
        if ($index >= 0 && $index < $this->questions->count()) {
            $this->currentQuestionIndex = $index;
        }
    }

    public function nextQuestion()
    {
        if ($this->currentQuestionIndex < $this->questions->count() - 1) {
            $this->currentQuestionIndex++;
        }
    }

    public function previousQuestion()
    {
        if ($this->currentQuestionIndex > 0) {
            $this->currentQuestionIndex--;
        }
    }

    public function getScorePercentage()
    {
        $totalPoints = $this->getTotalPossiblePoints();
        
        if (!$totalPoints) {
            return 0;
        }
        
        $finalScore = $this->getFinalScore();
        return round(($finalScore / $totalPoints) * 100, 1);
    }

    public function getFinalScore()
    {
        // New scoring system: Regular questions + Game assessments
        $regularScore = $this->getRegularQuestionsScore();
        $assessmentScore = $this->getAssessmentScore();
        return $regularScore + $assessmentScore;
    }

    public function getRegularQuestionsScore()
    {
        // Score from non-fun_game questions
        $regularQuestions = $this->questions->where('type', '!=', 'fun_game');
        $totalRegularScore = 0;
        
        foreach ($regularQuestions as $question) {
            $userAnswer = $this->userAnswers->get($question->id);
            if ($userAnswer && $userAnswer->is_correct) {
                $totalRegularScore += $question->points;
            }
        }
        
        return $totalRegularScore;
    }

    public function getAssessmentScore()
    {
        return $this->assessments->where('is_assessed', true)->sum('total_deposit');
    }

    public function getTotalPossiblePoints()
    {
        // Total possible points = Regular questions + Maximum possible from assessments
        $regularPoints = $this->questions->where('type', '!=', 'fun_game')->sum('points');
        $maxAssessmentPoints = $this->getMaxAssessmentPoints();
        return $regularPoints + $maxAssessmentPoints;
    }

    public function getMaxAssessmentPoints()
    {
        // For fun games, max possible = team base points + questionnaire total points
        $user = auth()->user();
        $teamBasePoints = $user->team ? $user->team->initial_points : 1000;
        $questionnaireTotalPoints = $this->questionnaire->questions->sum('points');
        
        return $this->questions->where('type', 'fun_game')->count() * ($teamBasePoints + $questionnaireTotalPoints);
    }

    public function getAssessmentStatus()
    {
        $totalAssessments = $this->assessments->count();
        $completedAssessments = $this->assessments->where('is_assessed', true)->count();
        
        if ($totalAssessments == 0) {
            return 'none';
        }
        
        if ($completedAssessments == 0) {
            return 'pending';
        }
        
        if ($completedAssessments == $totalAssessments) {
            return 'complete';
        }
        
        return 'partial';
    }

    public function getScoreColor()
    {
        $percentage = $this->getScorePercentage();
        
        if ($percentage >= 90) return 'text-green-600';
        if ($percentage >= 80) return 'text-blue-600';
        if ($percentage >= 70) return 'text-yellow-600';
        if ($percentage >= 60) return 'text-orange-600';
        return 'text-red-600';
    }

    public function getScoreBadgeColor()
    {
        $percentage = $this->getScorePercentage();
        
        if ($percentage >= 90) return 'bg-green-100 text-green-800 border-green-200';
        if ($percentage >= 80) return 'bg-blue-100 text-blue-800 border-blue-200';
        if ($percentage >= 70) return 'bg-yellow-100 text-yellow-800 border-yellow-200';
        if ($percentage >= 60) return 'bg-orange-100 text-orange-800 border-orange-200';
        return 'bg-red-100 text-red-800 border-red-200';
    }

    public function isPassed()
    {
        if (!isset($this->questionnaire->pass_percentage) || !$this->questionnaire->pass_percentage) {
            return true; // If no pass percentage is set, consider it passed
        }
        
        return $this->getScorePercentage() >= $this->questionnaire->pass_percentage;
    }

    public function getCorrectAnswersCount()
    {
        return $this->userAnswers->where('is_correct', true)->count();
    }

    public function getTotalQuestionsCount()
    {
        return $this->questions->count();
    }

    public function getRegularQuestionsCount()
    {
        return $this->questions->where('type', '!=', 'fun_game')->count();
    }

    public function getFunGameQuestionsCount()
    {
        return $this->questions->where('type', 'fun_game')->count();
    }

    public function getScoreBreakdown()
    {
        return [
            'regular_score' => $this->getRegularQuestionsScore(),
            'regular_possible' => $this->questions->where('type', '!=', 'fun_game')->sum('points'),
            'assessment_score' => $this->getAssessmentScore(),
            'assessment_possible' => $this->getMaxAssessmentPoints(),
            'total_score' => $this->getFinalScore(),
            'total_possible' => $this->getTotalPossiblePoints()
        ];
    }

    public function getAssessmentDetails()
    {
        return $this->assessments->map(function ($assessment) {
            $question = $this->questions->firstWhere('id', $assessment->question_id);
            return [
                'question_name' => $question->game_name ?? 'Fun Game',
                'question_text' => $question->question,
                'is_assessed' => $assessment->is_assessed,
                'base_points' => $assessment->deposit ?? 0,
                'additional_points' => $assessment->additional_points ?? 0,
                'penalty_points' => $assessment->penalty ?? 0,
                'total_score' => $assessment->total_deposit ?? 0,
                'notes' => $assessment->notes
            ];
        });
    }

    public function formatDuration($seconds)
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

    public function retakeQuiz()
    {
        // Check if questionnaire has method canUserAttempt
        if (method_exists($this->questionnaire, 'canUserAttempt') && !$this->questionnaire->canUserAttempt(Auth::id())) {
            session()->flash('error', 'You have reached the maximum number of attempts for this quiz.');
            return;
        }

        // Check max attempts manually if method doesn't exist
        if (isset($this->questionnaire->max_attempts) && $this->questionnaire->max_attempts) {
            $attemptCount = QuizAttempt::where('questionnaire_id', $this->questionnaire->id)
                ->where('user_id', Auth::id())
                ->where('status', QuizAttempt::STATUS_COMPLETED)
                ->count();
                
            if ($attemptCount >= $this->questionnaire->max_attempts) {
                session()->flash('error', 'You have reached the maximum number of attempts for this quiz.');
                return;
            }
        }

        // Check if questionnaire has method isAvailable
        if (method_exists($this->questionnaire, 'isAvailable') && !$this->questionnaire->isAvailable()) {
            session()->flash('error', 'This quiz is not currently available.');
            return;
        }

        // Check availability manually if method doesn't exist
        if (isset($this->questionnaire->is_active) && !$this->questionnaire->is_active) {
            session()->flash('error', 'This quiz is not currently available.');
            return;
        }

        // Always use quiz.take for retaking a quiz
        return redirect()->route('quiz.take', ['questionnaireId' => $this->questionnaire->id]);
    }

    public function backToDashboard()
    {
        // Set the active tab to quizzes so user goes directly to quiz list
        session()->flash('active_tab', 'quizzes');
        
        return redirect()->route('user.dashboard');
    }

    public function render()
    {
        return view('livewire.user.quiz-results');
    }
}