<?php

namespace App\Livewire\User;

use App\Models\Questionnaire;
use App\Models\QuizAttempt;
use App\Models\UserAnswer;
use Livewire\Component;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException as ValidationException;


class QuizTake extends Component
{
    public $questionnaire;
    public $attempt;
    public $questions;
    public $currentQuestionIndex = 0;
    public $answers = [];
    public $timeRemaining;
    public $isCompleted = false;
    public $showResults = false;
    public $score = 0;
    public $totalPoints = 0;
    public $startTime;
    public $endTime;
    public $autoSubmitted = false;
    public $earnedPoints = 0;
    public $timerExpired = false;

    protected $listeners = [
        'timeExpired' => 'handleTimeExpiry',
        'nextQuestion' => 'goToNextQuestion',
        'previousQuestion' => 'goToPreviousQuestion',
        'submitQuiz' => 'submitQuiz',
        'syncTimer' => 'syncTimer',
        'checkTimer' => 'checkTimer'
    ];

    public function mount($attemptId = null, $questionnaireId = null)
    {
        // Rate limiting for quiz attempts
        $key = 'quiz_attempts:' . auth()->id();
        if (RateLimiter::tooManyAttempts($key, 10)) { // 10 attempts per minute
            abort(429, 'Too many quiz attempts. Please wait.');
        }
        RateLimiter::hit($key, 60); // 1 minute window

        if ($attemptId) {
            // Load existing attempt
            $this->attempt = QuizAttempt::with(['questionnaire.questions', 'userAnswers'])
                ->where('id', $attemptId)
                ->where('user_id', auth()->id())
                ->where('status', QuizAttempt::STATUS_STARTED)
                ->firstOrFail();
            
            // Check if attempt hasn't expired - this is crucial for timer accuracy
            if ($this->attempt->questionnaire->time_limit) {
                $elapsed = now()->diffInSeconds($this->attempt->started_at);
                $timeLimit = $this->attempt->questionnaire->time_limit * 60;
                
                if ($elapsed >= $timeLimit) {
                    $this->handleTimeExpiry();
                    return;
                }
            }
            
            $this->questionnaire = $this->attempt->questionnaire;
            $this->loadExistingAnswers();
            $this->startTime = $this->attempt->started_at;
            
        } elseif ($questionnaireId) {
            // Cache questionnaire data to reduce DB queries
            $cacheKey = "questionnaire_{$questionnaireId}";
            $this->questionnaire = Cache::remember($cacheKey, 3600, function () use ($questionnaireId) {
                return Questionnaire::with('questions')
                    ->where('id', $questionnaireId)
                    ->where('is_active', true)
                    ->firstOrFail();
            });

            // Check if user has already completed this quiz (if single attempt)
            if ($this->questionnaire->max_attempts) {
                $attemptCount = QuizAttempt::where('questionnaire_id', $questionnaireId)
                    ->where('user_id', auth()->id())
                    ->where('status', QuizAttempt::STATUS_COMPLETED)
                    ->count();
                    
                if ($attemptCount >= $this->questionnaire->max_attempts) {
                    session()->flash('error', 'You have reached the maximum number of attempts for this quiz.');
                    return redirect()->route('user.available-quest');
                }
            }
            
            $this->createNewAttempt();
        } else {
            abort(404, 'Invalid quiz access');
        }

        $this->loadQuestions();
        $this->calculateTimeRemaining();
        $this->calculateTotalPoints();
        $this->initializeAnswers();
    }

    public function loadQuestions()
    {
        $this->questions = Cache::remember(
            "questions_safe_{$this->questionnaire->id}",
            3600,
            function () {
                return $this->questionnaire->questions()
                    ->select('id', 'question', 'type', 'options', 'points', 'order')
                    // Remove correct_answer from client-side data
                    ->orderBy('order', 'asc')
                    ->get()
                    ->toArray();
            }
        );
    }
    
    private function getCorrectAnswers()
    {
        return Cache::remember(
            "correct_answers_{$this->questionnaire->id}",
            3600,
            function () {
                return $this->questionnaire->questions()
                    ->pluck('correct_answer', 'id')
                    ->toArray();
            }
        );
    }

    // Enhanced timer sync method that handles all timer-related logic
    public function syncTimer()
    {
        if (!$this->questionnaire->time_limit || $this->isCompleted) {
            return ['timeRemaining' => null, 'isExpired' => false];
        }

        // Always calculate from database to prevent client manipulation
        $elapsed = now()->diffInSeconds($this->attempt->started_at);
        $totalTime = $this->questionnaire->time_limit * 60;
        $remaining = max(0, $totalTime - $elapsed);
        
        $this->timeRemaining = $remaining;
        
        // Auto-submit with buffer to prevent race conditions
        if ($remaining <= 5 && !$this->isCompleted && !$this->timerExpired) {
            $this->timerExpired = true;
            $this->handleTimeExpiry();
            return ['timeRemaining' => 0, 'isExpired' => true, 'autoSubmitted' => true];
        }

        return [
            'timeRemaining' => $remaining,
            'formattedTime' => $this->getFormattedTimeRemaining(),
            'isExpired' => $remaining <= 0,
            'serverTime' => now()->timestamp,
            'warningThreshold' => $remaining <= 60 // 1-minute warning
        ];
    }

    // New method specifically for frontend timer checks
    public function checkTimer()
    {
        return $this->syncTimer();
    }
    
    public function createNewAttempt()
    {
        $this->attempt = QuizAttempt::create([
            'questionnaire_id' => $this->questionnaire->id,
            'user_id' => auth()->id(),
            'status' => QuizAttempt::STATUS_STARTED,
            'started_at' => now(),
        ]);

        $this->startTime = $this->attempt->started_at;
    }

    public function loadExistingAnswers()
    {
        $existingAnswers = $this->attempt->userAnswers()
            ->get()
            ->keyBy('question_id')
            ->toArray();

        foreach ($existingAnswers as $questionId => $answer) {
            $this->answers[$questionId] = $answer['answer'];
        }

        $this->startTime = $this->attempt->started_at;
    }

    public function calculateTimeRemaining()
    {
        if (!$this->questionnaire->time_limit || $this->isCompleted) {
            $this->timeRemaining = null;
            return;
        }

        // Always calculate from database time to ensure accuracy and persistence
        $startTime = $this->attempt->started_at;
        $now = now();
        $elapsed = $now->diffInSeconds($startTime);
        $totalTime = $this->questionnaire->time_limit * 60; // Convert minutes to seconds
        
        // Calculate remaining time
        $remaining = $totalTime - $elapsed;
        
        // Ensure we don't go below 0
        $this->timeRemaining = max(0, (int) $remaining);
        
        // Auto-submit if time is up and not already completed
        if ($this->timeRemaining <= 0 && !$this->isCompleted && !$this->timerExpired) {
            $this->timerExpired = true;
            $this->handleTimeExpiry();
        }
    }

    // Enhanced method to get formatted time display
    public function getFormattedTimeRemaining()
    {
        if ($this->timeRemaining === null) {
            return null;
        }
        
        $minutes = floor($this->timeRemaining / 60);
        $seconds = $this->timeRemaining % 60;
        
        return sprintf('%d:%02d', $minutes, $seconds);
    }

    // Get quiz timing data for JavaScript (callable from frontend)
    public function getQuizTimingData()
    {
        // Recalculate time remaining to ensure accuracy
        $this->calculateTimeRemaining();
        
        if (!$this->questionnaire->time_limit || $this->isCompleted) {
            return [
                'hasTimeLimit' => false,
                'timeRemaining' => null,
                'startTimestamp' => null,
                'timeLimitSeconds' => null,
                'serverTimestamp' => now()->timestamp,
            ];
        }

        return [
            'hasTimeLimit' => true,
            'timeRemaining' => $this->timeRemaining,
            'startTimestamp' => $this->attempt->started_at->timestamp,
            'timeLimitSeconds' => $this->questionnaire->time_limit * 60,
            'serverTimestamp' => now()->timestamp,
        ];
    }

    public function calculateTotalPoints()
    {
        $this->totalPoints = collect($this->questions)->sum('points');
    }

    public function initializeAnswers()
    {
        foreach ($this->questions as $question) {
            if (!isset($this->answers[$question['id']])) {
                $this->answers[$question['id']] = '';
            }
        }
    }

    protected $pendingAnswers = [];

    public function saveAnswer($questionId, $answer)
    {
        // Validate the answer before saving
        $question = collect($this->questions)->firstWhere('id', $questionId);
        if (!$question) {
            return;
        }

        try {
            $answer = $this->validateAnswer($question, $answer);
        } catch (ValidationException $e) {
            // Handle validation error
            session()->flash('error', $e->getMessage());
            return;
        }

        $this->answers[$questionId] = $answer;
        $this->pendingAnswers[$questionId] = $answer;
        
        // Batch save every 5 answers or on navigation
        if (count($this->pendingAnswers) >= 5) {
            $this->flushPendingAnswers();
        }
    }

    public function saveAnswerDebounced($questionId, $answer)
    {
        $this->answers[$questionId] = $answer;
        
        // Set new timer for 2 seconds
        $this->dispatch('startAutoSaveTimer', [
            'questionId' => $questionId,
            'answer' => $answer,
            'delay' => 2000
        ]);
    }
    
    // Handle the actual save after debounce period
    public function handleAutoSave($questionId, $answer)
    {
        $this->saveAnswer($questionId, $answer);
    }

    public function flushPendingAnswers()
    {
        if (empty($this->pendingAnswers)) {
            return;
        }
        
        $now = now();
        $data = [];
        
        foreach ($this->pendingAnswers as $questionId => $answer) {
            $question = collect($this->questions)->firstWhere('id', $questionId);
            if (!$question) {
                continue;
            }

            $isCorrect = $this->isAnswerCorrect($question, $answer);
            $pointsEarned = $isCorrect ? $question['points'] : 0;

            $data[] = [
                'quiz_attempt_id' => $this->attempt->id,
                'question_id' => $questionId,
                'answer' => $answer,
                'is_correct' => $isCorrect,
                'points_earned' => $pointsEarned,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        
        if (!empty($data)) {
            // Use upsert for better performance
            UserAnswer::upsert(
                $data,
                ['quiz_attempt_id', 'question_id'],
                ['answer', 'is_correct', 'points_earned', 'updated_at']
            );
        }
        
        $this->pendingAnswers = [];
    }

    protected function validateAnswer($question, $answer)
    {
        // Add input sanitization
        $answer = trim($answer);
        
        switch ($question['type']) {
            case 'multiple_choice':
                $validOptions = is_array($question['options']) 
                    ? $question['options'] 
                    : json_decode($question['options'], true);
                    
                if (!is_array($validOptions) || !in_array($answer, $validOptions)) {
                    throw ValidationException::withMessages([
                        'answer' => ['Invalid option selected']
                    ]);
                }
                break;
                
            case 'true_false':
                if (!in_array(strtolower($answer), ['true', 'false', '1', '0'])) {
                    throw ValidationException::withMessages([
                        'answer' => ['Invalid true/false value']
                    ]);
                }
                break;
                
            case 'text':
                if (strlen($answer) > 1000) {
                    throw ValidationException::withMessages([
                        'answer' => ['Answer exceeds maximum length']
                    ]);
                }
                // Add XSS protection
                $answer = htmlspecialchars($answer, ENT_QUOTES, 'UTF-8');
                break;
                
            case 'number':
                if (!is_numeric($answer)) {
                    throw ValidationException::withMessages([
                        'answer' => ['Answer must be a number']
                    ]);
                }
                break;
        }
        
        return $answer;
    }

    public function goToNextQuestion()
    {
        $this->flushPendingAnswers();
        if ($this->currentQuestionIndex < count($this->questions) - 1) {
            $this->currentQuestionIndex++;
        }
    }

    public function goToPreviousQuestion()
    {
        $this->flushPendingAnswers();
        if ($this->currentQuestionIndex > 0) {
            $this->currentQuestionIndex--;
        }
    }

    public function goToQuestion($index)
    {
        $this->flushPendingAnswers();
        if ($index >= 0 && $index < count($this->questions)) {
            $this->currentQuestionIndex = $index;
        }
    }

    public function handleTimeExpiry()
    {
        // Prevent multiple submissions
        if ($this->isCompleted || $this->autoSubmitted) {
            return;
        }
        
        $this->autoSubmitted = true;
        $this->timerExpired = true;
        $this->submitQuiz();
    }

    public function submitQuiz()
    {
        // Double-check timer before submission
        if ($this->questionnaire->time_limit && !$this->autoSubmitted) {
            $this->calculateTimeRemaining();
            if ($this->timeRemaining <= 0) {
                $this->autoSubmitted = true;
            }
        }
        
        // Flush any pending answers before submission
        $this->flushPendingAnswers();
        
        $this->endTime = now();
        
        // Verify this is a legitimate submission
        if ($this->attempt->status === QuizAttempt::STATUS_COMPLETED) {
            session()->flash('error', 'Quiz already completed.');
            return;
        }

        if ($this->attempt->user_id !== auth()->id()) {
            abort(403, 'Unauthorized quiz submission.');
        }

        // Calculate score
        $this->calculateScore();

        // Update attempt with final scores
        $this->attempt->update([
            'status' => QuizAttempt::STATUS_COMPLETED,
            'completed_at' => $this->endTime,
            'total_score' => $this->earnedPoints,
            'total_time_seconds' => $this->endTime->diffInSeconds($this->startTime),
        ]);

        $this->isCompleted = true;
        $this->showResults = true;

        session()->flash('success', $this->autoSubmitted 
            ? 'Quiz submitted automatically due to time limit.'
            : 'Quiz submitted successfully!');

        $this->dispatch('quizCompleted', [
            'score' => $this->score,
            'totalPoints' => $this->totalPoints,
            'earnedPoints' => $this->earnedPoints,
            'attemptId' => $this->attempt->id
        ]);
    }

    public function calculateScore()
    {
        $this->earnedPoints = 0;

        foreach ($this->questions as $question) {
            $userAnswer = $this->answers[$question['id']] ?? '';
            
            if ($this->isAnswerCorrect($question, $userAnswer)) {
                $this->earnedPoints += $question['points'];
            }
        }

        $this->score = $this->totalPoints > 0 
            ? round(($this->earnedPoints / $this->totalPoints) * 100, 2)
            : 0;
    }

    public function isAnswerCorrect($question, $userAnswer)
    {
        if (empty($userAnswer)) {
            return false;
        }

        $correctAnswers = $this->getCorrectAnswers();
        $correctAnswer = $correctAnswers[$question['id']] ?? null;

        if (empty($correctAnswer)) {
            return false;
        }

        switch ($question['type']) {
            case 'multiple_choice':
                return trim($userAnswer) === trim($correctAnswer);
            
            case 'true_false':
                $userBool = in_array(strtolower(trim($userAnswer)), ['true', '1']);
                $correctBool = in_array(strtolower(trim($correctAnswer)), ['true', '1']);
                return $userBool === $correctBool;
            
            case 'text':
                // Enhanced text matching with fuzzy comparison option
                if (isset($question['exact_match']) && $question['exact_match']) {
                    return strtolower(trim($userAnswer)) === strtolower(trim($correctAnswer));
                } else {
                    // Allow for multiple correct answers (comma-separated)
                    $acceptableAnswers = array_map('trim', explode(',', strtolower($correctAnswer)));
                    return in_array(strtolower(trim($userAnswer)), $acceptableAnswers);
                }
            
            case 'number':
                $tolerance = isset($question['tolerance']) ? $question['tolerance'] : 0;
                return abs((float)$userAnswer - (float)$correctAnswer) <= $tolerance;
            
            default:
                return false;
        }
    }

    public function getAnsweredQuestionsCount()
    {
        return collect($this->answers)->filter(function ($answer) {
            return !empty(trim($answer));
        })->count();
    }

    public function getCurrentQuestion()
    {
        return $this->questions[$this->currentQuestionIndex] ?? null;
    }

    public function getProgressPercentage()
    {
        if (empty($this->questions)) {
            return 0;
        }
        
        return round((($this->currentQuestionIndex + 1) / count($this->questions)) * 100);
    }

    public function getAnsweredPercentage()
    {
        if (empty($this->questions)) {
            return 0;
        }
        
        return round(($this->getAnsweredQuestionsCount() / count($this->questions)) * 100);
    }

    public function isQuestionAnswered($questionId)
    {
        return !empty(trim($this->answers[$questionId] ?? ''));
    }

    public function canGoNext()
    {
        return $this->currentQuestionIndex < count($this->questions) - 1;
    }

    public function canGoPrevious()
    {
        return $this->currentQuestionIndex > 0;
    }

    public function isLastQuestion()
    {
        return $this->currentQuestionIndex === count($this->questions) - 1;
    }

    public function goToQuizList()
    {
        return redirect()->route('user.dashboard');
    }

    // Watch for answer changes and auto-save
    public function updatedAnswers($value, $key)
    {
        if (is_numeric($key)) {
            // For text inputs, we might want to debounce
            $question = collect($this->questions)->firstWhere('id', $key);
            
            if ($question && $question['type'] === 'text') {
                // Use wire:model.blur instead of immediate saving for text
                // This is handled in the Blade template
                return;
            }
            
            // For radio buttons and other inputs, save immediately
            $this->saveAnswer($key, $value);
        }
    }
    
    public function getDetailedProgress()
    {
        $answered = $this->getAnsweredQuestionsCount();
        $total = count($this->questions);
        $current = $this->currentQuestionIndex + 1;
        
        return [
            'current' => $current,
            'total' => $total,
            'answered' => $answered,
            'percentage' => round(($current / $total) * 100),
            'completion' => round(($answered / $total) * 100),
            'remaining' => $total - $answered,
            'timePerQuestion' => $this->getAverageTimePerQuestion(),
            'estimatedTimeRemaining' => $this->getEstimatedTimeRemaining()
        ];
    }

    private function getAverageTimePerQuestion()
    {
        if ($this->currentQuestionIndex === 0) {
            return 0;
        }
        
        $elapsed = now()->diffInSeconds($this->startTime);
        return round($elapsed / ($this->currentQuestionIndex + 1));
    }

    private function getEstimatedTimeRemaining()
    {
        $avgTime = $this->getAverageTimePerQuestion();
        $remainingQuestions = count($this->questions) - ($this->currentQuestionIndex + 1);
        
        return $avgTime * $remainingQuestions;
    }
    
    public function render()
    {
        // Update time remaining if quiz has time limit and not completed
        if ($this->questionnaire->time_limit && !$this->isCompleted) {
            $this->calculateTimeRemaining();
        }

        return view('livewire.user.quiz-take', [
            'currentQuestion' => $this->getCurrentQuestion(),
            'progressPercentage' => $this->getProgressPercentage(),
            'answeredPercentage' => $this->getAnsweredPercentage(),
            'formattedTimeRemaining' => $this->getFormattedTimeRemaining(),
        ]);
    }
}