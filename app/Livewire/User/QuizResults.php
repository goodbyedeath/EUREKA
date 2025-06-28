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

        // Clear any cached data to ensure fresh results
        \Cache::forget("quiz_results_{$attemptId}");
        \Cache::forget("questionnaire_{$this->attempt->questionnaire_id}");
        
        $this->questionnaire = $this->attempt->questionnaire;
        $this->questions = $this->questionnaire->questions;
        
        $this->loadUserAnswers();
        $this->loadAssessments();
        
        // Log successful fresh data load for debugging
        if (config('app.debug')) {
            \Log::debug("QuizResults: Fresh data loaded for attempt {$attemptId}. Total time: {$this->attempt->total_time_seconds} seconds");
        }
    }

    public function loadUserAnswers()
    {
        $this->userAnswers = UserAnswer::with(['question'])
            ->where('quiz_attempt_id', $this->attempt->id)
            ->get()
            ->keyBy('question_id');
            
        // Debug logging
        if (config('app.debug')) {
            \Log::debug("QuizResults: Loaded " . $this->userAnswers->count() . " user answers for attempt {$this->attempt->id}");
            foreach ($this->userAnswers as $questionId => $answer) {
                \Log::debug("QuizResults: Question {$questionId} - Answer: " . ($answer->answer ?? 'NULL') . ", Correct: " . ($answer->is_correct ? 'TRUE' : 'FALSE'));
            }
        }
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
        // New scoring system: Base Points + Bonus Points = Total Points
        $basePoints = $this->getBasePoints();
        $bonusPoints = $this->getBonusPoints();
        return $basePoints + $bonusPoints;
    }

    public function getBasePoints()
    {
        // Base points from user's team initial points
        $user = auth()->user();
        if (!$user || !$user->team) {
            return 1000; // Default base points
        }
        
        return $user->team->initial_points ?? 1000;
    }

    public function getBonusPoints()
    {
        // Bonus points = accumulation of all earned points from correct answers and assessments
        $regularBonusPoints = $this->getRegularQuestionsScore();
        $assessmentBonusPoints = $this->getAssessmentBonusPoints();
        return $regularBonusPoints + $assessmentBonusPoints;
    }

    public function getAssessmentBonusPoints()
    {
        // Assessment bonus points = total_deposit minus base points for each assessment
        if (!$this->assessments) {
            return 0;
        }
        
        $basePoints = $this->getBasePoints();
        $totalBonusFromAssessments = 0;
        
        foreach ($this->assessments->where('is_assessed', true) as $assessment) {
            // Bonus from assessment = total_deposit - base_points_used_in_assessment
            $assessmentBonus = ($assessment->total_deposit ?? 0) - $basePoints;
            $totalBonusFromAssessments += max(0, $assessmentBonus); // Don't count negative as bonus
        }
        
        return $totalBonusFromAssessments;
    }

    public function getBonusPointsBreakdown()
    {
        // Detailed breakdown of all bonus points earned
        $breakdown = [
            'correct_answers' => [],
            'assessments' => [],
            'regular_total' => 0,
            'assessment_total' => 0,
            'grand_total' => 0
        ];
        
        // Regular question bonus points
        if ($this->questions && $this->userAnswers) {
            $regularQuestions = $this->questions->where('type', '!=', 'fun_game');
            foreach ($regularQuestions as $question) {
                $userAnswer = $this->userAnswers->get($question->id);
                if ($userAnswer && $userAnswer->is_correct) {
                    $breakdown['correct_answers'][] = [
                        'question' => $question->question ?? 'Question',
                        'type' => $question->type ?? 'unknown',
                        'points' => $question->points ?? 0
                    ];
                    $breakdown['regular_total'] += $question->points ?? 0;
                }
            }
        }
        
        // Assessment bonus points
        if ($this->assessments) {
            $basePoints = $this->getBasePoints();
            foreach ($this->assessments->where('is_assessed', true) as $assessment) {
                $question = $this->questions ? $this->questions->firstWhere('id', $assessment->question_id) : null;
                $assessmentBonus = ($assessment->total_deposit ?? 0) - $basePoints;
                if ($assessmentBonus > 0) {
                    $breakdown['assessments'][] = [
                        'game_name' => $question ? ($question->game_name ?? 'Fun Game') : 'Unknown Game',
                        'total_earned' => $assessment->total_deposit ?? 0,
                        'base_used' => $basePoints,
                        'bonus_earned' => $assessmentBonus
                    ];
                    $breakdown['assessment_total'] += $assessmentBonus;
                }
            }
        }
        
        $breakdown['grand_total'] = $breakdown['regular_total'] + $breakdown['assessment_total'];
        
        return $breakdown;
    }

    public function getRegularQuestionsScore()
    {
        // Score from non-fun_game questions
        if (!$this->questions || !$this->userAnswers) {
            return 0;
        }
        
        $regularQuestions = $this->questions->where('type', '!=', 'fun_game');
        $totalRegularScore = 0;
        
        foreach ($regularQuestions as $question) {
            $userAnswer = $this->userAnswers->get($question->id);
            if ($userAnswer && $userAnswer->is_correct) {
                $totalRegularScore += $question->points ?? 0;
            }
        }
        
        return $totalRegularScore;
    }

    public function getAssessmentScore()
    {
        if (!$this->assessments) {
            return 0;
        }
        
        return $this->assessments->where('is_assessed', true)->sum('total_deposit') ?? 0;
    }

    public function getTotalPossiblePoints()
    {
        // Total possible points = Regular questions + Maximum possible from assessments
        $regularPoints = $this->questions ? $this->questions->where('type', '!=', 'fun_game')->sum('points') : 0;
        $maxAssessmentPoints = $this->getMaxAssessmentPoints();
        return ($regularPoints ?? 0) + $maxAssessmentPoints;
    }

    public function getMaxAssessmentPoints()
    {
        // For fun games, max possible = team base points + questionnaire total points
        $user = auth()->user();
        if (!$user) {
            return 0;
        }
        
        $teamBasePoints = $user->team ? ($user->team->initial_points ?? 1000) : 1000;
        $questionnaireTotalPoints = $this->questionnaire && $this->questionnaire->questions 
            ? $this->questionnaire->questions->sum('points') 
            : 0;
        
        $funGameCount = $this->questions ? $this->questions->where('type', 'fun_game')->count() : 0;
        
        return $funGameCount * ($teamBasePoints + $questionnaireTotalPoints);
    }

    public function getAssessmentStatus()
    {
        if (!$this->assessments) {
            return 'none';
        }
        
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
        return $this->userAnswers ? $this->userAnswers->where('is_correct', true)->count() : 0;
    }

    public function getTotalQuestionsCount()
    {
        return $this->questions ? $this->questions->count() : 0;
    }

    public function getRegularQuestionsCount()
    {
        return $this->questions ? $this->questions->where('type', '!=', 'fun_game')->count() : 0;
    }

    public function getFunGameQuestionsCount()
    {
        return $this->questions ? $this->questions->where('type', 'fun_game')->count() : 0;
    }

    public function getScoreBreakdown()
    {
        $regularPossible = $this->questions ? $this->questions->where('type', '!=', 'fun_game')->sum('points') : 0;
        
        return [
            // New bonus points system breakdown
            'base_points' => $this->getBasePoints(),
            'bonus_points' => $this->getBonusPoints(),
            'total_points' => $this->getFinalScore(),
            
            // Detailed bonus breakdown
            'regular_bonus' => $this->getRegularQuestionsScore(),
            'regular_possible' => $regularPossible ?? 0,
            'assessment_bonus' => $this->getAssessmentBonusPoints(),
            'assessment_total' => $this->getAssessmentScore(),
            'assessment_possible' => $this->getMaxAssessmentPoints(),
            
            // Legacy compatibility
            'regular_score' => $this->getRegularQuestionsScore(),
            'assessment_score' => $this->getAssessmentScore(),
            'total_score' => $this->getFinalScore(),
            'total_possible' => $this->getTotalPossiblePoints()
        ];
    }

    public function getAssessmentDetails()
    {
        if (!$this->assessments || !$this->questions) {
            return collect([]);
        }
        
        return $this->assessments->map(function ($assessment) {
            $question = $this->questions->firstWhere('id', $assessment->question_id);
            return [
                'question_name' => $question ? ($question->game_name ?? 'Fun Game') : 'Unknown Game',
                'question_text' => $question ? $question->question : 'Question not found',
                'is_assessed' => $assessment->is_assessed ?? false,
                'base_points' => $assessment->deposit ?? 0,
                'additional_points' => $assessment->additional_points ?? 0,
                'penalty_points' => $assessment->penalty ?? 0,
                'total_score' => $assessment->total_deposit ?? 0,
                'notes' => $assessment->notes ?? ''
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

    /**
     * Check if this quiz was continued from a previous session
     */
    public function wasContinued()
    {
        if (!$this->attempt || !$this->attempt->started_at || !$this->attempt->completed_at) {
            return false;
        }

        // Check if there were multiple save sessions by looking at answer timestamps
        $answerTimestamps = $this->userAnswers->pluck('created_at')->filter()->unique();
        
        // If answers were created over multiple time periods (more than 5 minutes apart), 
        // it indicates the quiz was continued
        if ($answerTimestamps->count() > 1) {
            $firstAnswer = $answerTimestamps->min();
            $lastAnswer = $answerTimestamps->max();
            $timeDiff = $firstAnswer->diffInMinutes($lastAnswer);
            
            // If answers span more than 5 minutes, likely continued
            return $timeDiff > 5;
        }

        return false;
    }

    /**
     * Get quiz session information
     */
    public function getQuizSessionInfo()
    {
        $sessionInfo = [
            'was_continued' => $this->wasContinued(),
            'started_at' => $this->attempt->started_at,
            'completed_at' => $this->attempt->completed_at,
            'total_duration' => $this->attempt->total_time_seconds,
            'has_time_limit' => (bool) $this->questionnaire->time_limit,
            'time_limit_minutes' => $this->questionnaire->time_limit,
        ];

        if ($sessionInfo['was_continued']) {
            // Get answer session details
            $answerTimestamps = $this->userAnswers->pluck('created_at')->filter()->sort();
            $sessionInfo['answer_sessions'] = $this->getAnswerSessions($answerTimestamps);
            $sessionInfo['estimated_sessions'] = count($sessionInfo['answer_sessions']);
        }

        return $sessionInfo;
    }

    /**
     * Group answers into likely session periods
     */
    private function getAnswerSessions($timestamps)
    {
        if ($timestamps->isEmpty()) {
            return [];
        }

        $sessions = [];
        $currentSession = ['start' => $timestamps->first(), 'end' => $timestamps->first(), 'answers' => 1];
        
        foreach ($timestamps->skip(1) as $timestamp) {
            // If more than 5 minutes gap, start new session
            if ($currentSession['end']->diffInMinutes($timestamp) > 5) {
                $sessions[] = $currentSession;
                $currentSession = ['start' => $timestamp, 'end' => $timestamp, 'answers' => 1];
            } else {
                $currentSession['end'] = $timestamp;
                $currentSession['answers']++;
            }
        }
        
        $sessions[] = $currentSession; // Add the last session
        return $sessions;
    }

    /**
     * Get quiz completion statistics
     */
    public function getCompletionStats()
    {
        $totalQuestions = $this->getTotalQuestionsCount();
        $answeredQuestions = $this->userAnswers->count();
        $correctAnswers = $this->getCorrectAnswersCount();
        $assessedGames = $this->assessments ? $this->assessments->where('is_assessed', true)->count() : 0;
        $totalGames = $this->getFunGameQuestionsCount();

        return [
            'total_questions' => $totalQuestions,
            'answered_questions' => $answeredQuestions,
            'completion_rate' => $totalQuestions > 0 ? round(($answeredQuestions / $totalQuestions) * 100, 1) : 0,
            'correct_answers' => $correctAnswers,
            'accuracy_rate' => $answeredQuestions > 0 ? round(($correctAnswers / $answeredQuestions) * 100, 1) : 0,
            'assessed_games' => $assessedGames,
            'total_games' => $totalGames,
            'game_assessment_rate' => $totalGames > 0 ? round(($assessedGames / $totalGames) * 100, 1) : 0,
        ];
    }

    /**
     * Get answer timeline for continued quizzes
     */
    public function getAnswerTimeline()
    {
        if (!$this->wasContinued()) {
            return [];
        }

        $timeline = [];
        foreach ($this->userAnswers as $answer) {
            if ($answer->created_at) {
                $question = $this->questions->firstWhere('id', $answer->question_id);
                $timeline[] = [
                    'timestamp' => $answer->created_at,
                    'question_type' => $question ? $question->type : 'unknown',
                    'question_number' => $question ? ($this->questions->search(function($q) use ($question) {
                        return $q->id === $question->id;
                    }) + 1) : 'N/A',
                    'is_correct' => $answer->is_correct,
                    'points_earned' => $answer->points_earned ?? 0,
                ];
            }
        }

        // Sort by timestamp
        usort($timeline, function($a, $b) {
            return $a['timestamp']->timestamp - $b['timestamp']->timestamp;
        });

        return $timeline;
    }

    /**
     * Debug method to check user answers
     */
    public function debugUserAnswers()
    {
        $debug = [
            'attempt_id' => $this->attempt->id,
            'user_answers_count' => $this->userAnswers ? $this->userAnswers->count() : 0,
            'questions_count' => $this->questions ? $this->questions->count() : 0,
            'user_answers_details' => [],
            'questions_details' => [],
        ];

        if ($this->userAnswers) {
            foreach ($this->userAnswers as $questionId => $answer) {
                $debug['user_answers_details'][$questionId] = [
                    'answer' => $answer->answer,
                    'is_correct' => $answer->is_correct,
                    'points_earned' => $answer->points_earned ?? 0,
                    'created_at' => $answer->created_at ? $answer->created_at->toDateTimeString() : null,
                ];
            }
        }

        if ($this->questions) {
            foreach ($this->questions as $question) {
                $debug['questions_details'][$question->id] = [
                    'question' => $question->question,
                    'type' => $question->type,
                    'correct_answer' => $question->correct_answer,
                    'has_user_answer' => $this->userAnswers ? $this->userAnswers->has($question->id) : false,
                ];
            }
        }

        return $debug;
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