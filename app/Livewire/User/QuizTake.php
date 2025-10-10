<?php

namespace App\Livewire\User;

use App\Models\Questionnaire;
use App\Models\QuizAttempt;
use App\Models\UserAnswer;
use App\Models\QrCodeScan;
use Livewire\Component;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException as ValidationException;
use App\Services\AnswerValidationService;
use Illuminate\Support\Facades\DB;
use App\Models\GameAssessment;
use App\Services\WorkflowTimerService;
use App\Models\FeatureSetting;


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
    public $quizLocked = false;
    public $preventNavigation = true;

    protected $listeners = [
        'timeExpired' => 'handleTimeExpiry',
        'nextQuestion' => 'goToNextQuestion',
        'previousQuestion' => 'goToPreviousQuestion',
        'submitQuiz' => 'submitQuiz',
        'syncTimer' => 'syncTimer',
        'checkTimer' => 'checkTimer',
        'confirmExit' => 'handleExitAttempt',
        'echo:quiz-time-warning,QuizTimeWarning' => 'handleQuizWarning',
        'echo:quiz-time-expired,QuizTimeExpired' => 'handleQuizTimeExpired'
    ];

    private ?WorkflowTimerService $workflowTimerService = null;

    public function mount($attemptId = null, $questionnaireId = null)
    {
        // Rate limiting for quiz attempts
        $key = 'quiz_attempts:' . auth()->id();
        if (RateLimiter::tooManyAttempts($key, 10)) { // 10 attempts per minute
            abort(429, 'Too many quiz attempts. Please wait.');
        }
        RateLimiter::hit($key, 60); // 1 minute window

        if ($attemptId) {
            // Load existing attempt (quiz continuation)
            $this->attempt = QuizAttempt::with(['questionnaire.questions', 'userAnswers'])
                ->where('id', $attemptId)
                ->where('user_id', auth()->id())
                ->firstOrFail();
                
            // Check if attempt is completed - redirect to results if so
            if ($this->attempt->isCompleted()) {
                return redirect()->route('quiz.results', ['attemptId' => $this->attempt->id]);
            }
            
            // Only allow started attempts for editing
            if (!$this->attempt->isStarted()) {
                abort(403, 'This quiz attempt cannot be continued.');
            }
            
            // Enhanced timer validation for continued quizzes
            if ($this->attempt->questionnaire->time_limit) {
                $elapsed = now()->diffInSeconds($this->attempt->started_at);
                $timeLimit = $this->attempt->questionnaire->time_limit * 60;
                
                if ($elapsed >= $timeLimit) {
                    session()->flash('warning', 'Quiz time has expired. Submitting automatically.');
                    $this->handleTimeExpiry();
                    return;
                }
            }
            
            $this->questionnaire = $this->attempt->questionnaire;
            $this->loadExistingAnswers();
            $this->startTime = $this->attempt->started_at;
            
            // Set flag for frontend to know this is a continued quiz
            session()->flash('quiz_continued', true);
            
        } elseif ($questionnaireId) {
            // Cache questionnaire data to reduce DB queries
            $cacheKey = "questionnaire_{$questionnaireId}";
            $this->questionnaire = Cache::remember($cacheKey, 3600, function () use ($questionnaireId) {
                return Questionnaire::with('questions')
                    ->where('id', $questionnaireId)
                    ->where('is_active', true)
                    ->firstOrFail();
            });

            // Check if questionnaire is available (date range, etc.)
            if (!$this->questionnaire->isAvailable()) {
                session()->flash('error', 'This questionnaire is not currently available.');
                return redirect()->route('user.dashboard');
            }

            // Check if user can attempt this questionnaire (max attempts validation)
            if (!$this->questionnaire->canUserAttempt(auth()->id())) {
                session()->flash('error', 'You have reached the maximum number of attempts for this quiz.');
                return redirect()->route('user.dashboard');
            }
            
            $this->createNewAttempt();
            
            // Redirect to continue route to prevent restart on refresh
            return redirect()->route('quiz.continue', ['attemptId' => $this->attempt->id]);
        } else {
            abort(404, 'Invalid quiz access');
        }

        try {
            $this->workflowTimerService = app(WorkflowTimerService::class);
        } catch (\Exception $e) {
            \Log::error("Failed to initialize WorkflowTimerService in QuizTake: " . $e->getMessage());
            $this->workflowTimerService = null;
        }
        
        $this->loadQuestions();
        $this->calculateTimeRemaining();
        $this->calculateTotalPoints();
        $this->initializeAnswers();
        
        // Start workflow timer if workflow timers are enabled and quiz has time limit
        if ($this->isWorkflowTimersEnabled() && $this->questionnaire->time_limit && $this->workflowTimerService) {
            try {
                $this->workflowTimerService->startQuizTimer($this->attempt);
            } catch (\Exception $e) {
                \Log::error("Failed to start quiz timer: " . $e->getMessage());
            }
        }
        
        // Lock the quiz once mounted to prevent navigation
        $this->quizLocked = true;
    }

    public function loadQuestions()
    {
        // Temporarily disable cache for debugging
        $questions = $this->questionnaire->questions()
            ->select('id', 'question', 'type', 'options', 'points', 'order', 'description', 'game_name', 'images')
            ->orderBy('order', 'asc')
            ->get();
        
        if ($questions->isEmpty()) {
            throw new \Exception('No questions found for this questionnaire');
        }
        
        $this->questions = $questions->toArray();
        
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

    // Enhanced timer calculation for both new and continued quizzes
    public function calculateTimeRemaining()
    {
        if (!$this->questionnaire->time_limit || $this->isCompleted || !$this->attempt) {
            $this->timeRemaining = null;
            return;
        }

        // Use workflow timer if enabled, otherwise fallback to old calculation
        if ($this->isWorkflowTimersEnabled() && $this->workflowTimerService) {
            try {
                $timerData = $this->workflowTimerService->getQuizRemainingTime($this->attempt);
                if ($timerData) {
                    $this->timeRemaining = $timerData['remaining_seconds'];
                    
                    // Auto-submit if time is up
                    if ($this->timeRemaining <= 0 && !$this->isCompleted && !$this->timerExpired) {
                        $this->timerExpired = true;
                        $this->handleTimeExpiry();
                    }
                    return;
                }
            } catch (\Exception $e) {
                \Log::error("Failed to get quiz remaining time: " . $e->getMessage());
                // Fall through to fallback calculation
            }
        }

        // Fallback to old calculation method
        $startTime = $this->attempt->started_at;
        if (!$startTime) {
            $this->timeRemaining = null;
            return;
        }
        
        $now = now();
        $elapsed = $now->getTimestamp() - $startTime->getTimestamp();
        $totalTime = $this->questionnaire->time_limit * 60; // Convert minutes to seconds
        
        $this->timeRemaining = max(0, $totalTime - $elapsed);
        
        // Auto-submit if time is up
        if ($this->timeRemaining <= 0 && !$this->isCompleted && !$this->timerExpired) {
            $this->timerExpired = true;
            $this->handleTimeExpiry();
        }
    }

    
    public function createNewAttempt()
    {
        // Double-check max attempts as a fail-safe before creating new attempt
        if (!$this->questionnaire->canUserAttempt(auth()->id())) {
            session()->flash('error', 'You have reached the maximum number of attempts for this quiz.');
            return redirect()->route('user.dashboard');
        }

        $this->attempt = QuizAttempt::create([
            'questionnaire_id' => $this->questionnaire->id,
            'user_id' => auth()->id(),
            'status' => QuizAttempt::STATUS_STARTED,
            'started_at' => now(),
        ]);

        $this->startTime = $this->attempt->started_at;
        
        // Start workflow timer if enabled and quiz has time limit
        if ($this->isWorkflowTimersEnabled() && $this->questionnaire->time_limit && $this->workflowTimerService) {
            try {
                $this->workflowTimerService->startQuizTimer($this->attempt);
            } catch (\Exception $e) {
                \Log::error("Failed to start quiz timer in createNewAttempt: " . $e->getMessage());
            }
        }
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

    // Get comprehensive timing data for JavaScript initialization
    public function getQuizTimingData()
    {
        $this->calculateTimeRemaining();
        
        $data = [
            'hasTimeLimit' => (bool) $this->questionnaire->time_limit,
            'timeRemaining' => $this->timeRemaining,
            'timeLimitSeconds' => $this->questionnaire->time_limit ? $this->questionnaire->time_limit * 60 : null,
            'isCompleted' => $this->isCompleted,
            'isTimerExpired' => $this->timerExpired,
            'formattedTimeRemaining' => $this->getFormattedTimeRemaining(),
        ];
        
        if ($this->attempt && $this->attempt->started_at) {
            $data['startTime'] = $this->attempt->started_at->getTimestamp();
            $data['elapsedTime'] = now()->getTimestamp() - $this->attempt->started_at->getTimestamp();
            $data['isContinuedQuiz'] = $data['elapsedTime'] > 60; // If more than 1 minute has passed, it's likely a continued quiz
        }
        
        return $data;
    }

    public function calculateTotalPoints()
    {
        if (!is_array($this->questions) || empty($this->questions)) {
            $this->totalPoints = 0;
            return;
        }
        
        // Only count points from regular questions (not fun_game or brief)
        $this->totalPoints = 0;
        foreach ($this->questions as $question) {
            if ($question['type'] !== 'fun_game' && $question['type'] !== 'brief') {
                $this->totalPoints += $question['points'];
            }
        }
    }

    public function initializeAnswers()
    {
        if (!is_array($this->questions) || empty($this->questions)) {
            throw new \Exception('Questions not properly loaded');
        }
        
        foreach ($this->questions as $question) {
            if (!isset($this->answers[$question['id']])) {
                $this->answers[$question['id']] = '';
            }
        }
    }

    protected $pendingAnswers = [];

    public function saveAnswer($questionId, $answer)
    {
        // Debug logging
        if (config('app.debug')) {
            \Log::debug("QuizTake: saveAnswer called - QuestionID: {$questionId}, Answer: '{$answer}'");
        }
        
        // Prevent saving answers if quiz is completed
        if (!$this->attempt->canEditAnswers()) {
            \Log::warning("QuizTake: Cannot edit answers - quiz has been submitted. AttemptID: {$this->attempt->id}");
            session()->flash('error', 'Cannot edit answers - quiz has been submitted.');
            return;
        }
        
        // Validate the answer before saving
        $question = collect($this->questions)->firstWhere('id', $questionId);
        if (!$question) {
            \Log::error("QuizTake: Question not found - QuestionID: {$questionId}");
            return;
        }

        try {
            $answer = $this->validateAnswer($question, $answer);
            if (config('app.debug')) {
                \Log::debug("QuizTake: Answer validated successfully - QuestionID: {$questionId}, ValidatedAnswer: '{$answer}'");
            }
        } catch (ValidationException $e) {
            \Log::error("QuizTake: Answer validation failed - QuestionID: {$questionId}, Error: " . $e->getMessage());
            // Handle validation error
            session()->flash('error', $e->getMessage());
            return;
        }

        $this->answers[$questionId] = $answer;
        $this->pendingAnswers[$questionId] = $answer;
        
        if (config('app.debug')) {
            \Log::debug("QuizTake: Answer added to pending - QuestionID: {$questionId}, PendingCount: " . count($this->pendingAnswers));
        }
        
        // EMERGENCY FIX: Force immediate save instead of batching
        if (config('app.debug')) {
            \Log::debug("QuizTake: Force-flushing pending answers (immediate save enabled)");
        }
        $this->flushPendingAnswers();
        
        // Original batching logic (commented out for debugging)
        // if (count($this->pendingAnswers) >= 5) {
        //     if (config('app.debug')) {
        //         \Log::debug("QuizTake: Auto-flushing pending answers (5+ answers)");
        //     }
        //     $this->flushPendingAnswers();
        // }
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
        if (config('app.debug')) {
            \Log::debug("QuizTake: flushPendingAnswers called - PendingCount: " . count($this->pendingAnswers));
        }
        
        if (empty($this->pendingAnswers)) {
            if (config('app.debug')) {
                \Log::debug("QuizTake: No pending answers to flush");
            }
            return;
        }
        
        // Use database transaction with pessimistic locking to prevent race conditions
        DB::transaction(function () {
            // Lock the quiz attempt to prevent concurrent modifications
            $lockedAttempt = QuizAttempt::where('id', $this->attempt->id)
                ->lockForUpdate()
                ->first();
            
            if (!$lockedAttempt || !$lockedAttempt->canEditAnswers()) {
                $this->pendingAnswers = [];
                return;
            }
            
            $now = now();
            $data = [];
            
            foreach ($this->pendingAnswers as $questionId => $answer) {
                $question = collect($this->questions)->firstWhere('id', $questionId);
                if (!$question) {
                    continue;
                }

                // For fun_game questions, don't calculate traditional correctness/points
                if ($question['type'] === 'fun_game') {
                    $isCorrect = ($answer === 'completed');
                    $pointsEarned = 0; // Points are handled via game assessments
                } elseif ($question['type'] === 'brief') {
                    // Brief questions don't have correct/incorrect answers - they're just feedback
                    $isCorrect = false;
                    $pointsEarned = 0;
                } else {
                    $isCorrect = $this->isAnswerCorrect($question, $answer);
                    $pointsEarned = $isCorrect ? $question['points'] : 0;
                }

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
                if (config('app.debug')) {
                    \Log::debug("QuizTake: Saving " . count($data) . " answers to database");
                    foreach ($data as $answerData) {
                        \Log::debug("QuizTake: Saving - Q{$answerData['question_id']}: '{$answerData['answer']}' (Correct: " . ($answerData['is_correct'] ? 'YES' : 'NO') . ")");
                    }
                }
                
                // Use upsert for better performance
                UserAnswer::upsert(
                    $data,
                    ['quiz_attempt_id', 'question_id'],
                    ['answer', 'is_correct', 'points_earned', 'updated_at']
                );
                
                if (config('app.debug')) {
                    \Log::debug("QuizTake: Successfully saved answers to database");
                }
            } else {
                if (config('app.debug')) {
                    \Log::debug("QuizTake: No data to save to database");
                }
            }
        });
        
        $this->pendingAnswers = [];
    }

    protected function validateAnswer($question, $answer)
    {
        // Brief questions require minimal validation - just sanitize and return
        if ($question['type'] === 'brief') {
            return AnswerValidationService::sanitizeAnswer($answer);
        }
        
        // Sanitize input
        $answer = AnswerValidationService::sanitizeAnswer($answer);
        
        // Create temporary Question model for validation
        $questionModel = new \App\Models\Question([
            'id' => $question['id'],
            'type' => $question['type'],
            'options' => $question['options'] ?? [],
            'correct_answer' => '', // Not needed for format validation
            'points' => $question['points']
        ]);

        // Validate format using the service
        $errors = AnswerValidationService::validateAnswerFormat($questionModel, $answer);
        
        if (!empty($errors)) {
            throw ValidationException::withMessages([
                'answer' => $errors
            ]);
        }
        
        return $answer;
    }

    public function goToNextQuestion()
    {
        $this->flushPendingAnswers();
        
        // Check if user has completed fun games behind current position that need assessment
        if ($this->hasUnassessedCompletedFunGames()) {
            session()->flash('error', 'You must complete the assessment for the previous fun game before proceeding to the next question.');
            return;
        }
        
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
        
        // Only allow going to previous questions or if no unassessed fun games
        if ($index > $this->currentQuestionIndex && $this->hasUnassessedCompletedFunGames()) {
            session()->flash('error', 'You must complete the assessment for the previous fun game before proceeding.');
            return;
        }
        
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
        $this->submitQuiz(null); // No photo when auto-submitting due to time expiry
    }

    public function submitQuiz($verificationPhoto = null)
    {
        // Cancel workflow timer when quiz is submitted
        if ($this->isWorkflowTimersEnabled() && $this->workflowTimerService) {
            try {
                $this->workflowTimerService->cancelQuizTimer($this->attempt);
            } catch (\Exception $e) {
                \Log::error("Failed to cancel quiz timer: " . $e->getMessage());
            }
        }
        
        // Block submission if there are unassessed completed fun games (unless auto-submitted due to timer)
        if (!$this->autoSubmitted && $this->hasUnassessedCompletedFunGames()) {
            session()->flash('error', 'You must complete the assessment for all fun games before submitting the quiz.');
            return;
        }
        
        // Check if this is a fun-game-only quiz
        if ($this->isFunGameOnlyQuiz()) {
            $this->autoCompleteQuiz();
            return;
        }

        // Use database transaction with locking to prevent race conditions
        DB::transaction(function () use ($verificationPhoto) {
            // Lock the quiz attempt to prevent concurrent submissions
            $lockedAttempt = QuizAttempt::where('id', $this->attempt->id)
                ->lockForUpdate()
                ->first();
            
            if (!$lockedAttempt || !$lockedAttempt->canSubmit()) {
                session()->flash('error', 'Quiz has already been submitted and cannot be resubmitted.');
                return;
            }
            
            // Double-check timer before submission
            if ($this->questionnaire->time_limit && !$this->autoSubmitted) {
                $this->calculateTimeRemaining();
                if ($this->timeRemaining <= 0) {
                    $this->autoSubmitted = true;
                }
            }
            
            // Flush any pending answers before submission
            if (config('app.debug')) {
                \Log::debug("QuizTake: About to flush pending answers before submission. Pending count: " . count($this->pendingAnswers));
            }
            $this->flushPendingAnswers();
            
            $this->endTime = now();
            
            // Verify this is a legitimate submission
            if ($lockedAttempt->user_id !== auth()->id()) {
                abort(403, 'Unauthorized quiz submission.');
            }

            // Calculate score
            $this->calculateScore();

            // Prepare update data
            $updateData = [
                'status' => QuizAttempt::STATUS_COMPLETED,
                'completed_at' => $this->endTime,
                'total_score' => $this->getBasePoints() + $this->earnedPoints,
                'total_time_seconds' => $this->endTime->diffInSeconds($lockedAttempt->started_at),
            ];
            
            // Add verification photo if provided
            if ($verificationPhoto && is_string($verificationPhoto)) {
                $updateData['verification_photo'] = $verificationPhoto;
                $updateData['photo_captured_at'] = now();
                
                if (config('app.debug')) {
                    \Log::debug("QuizTake: Storing verification photo for attempt {$lockedAttempt->id}");
                }
            }
            
            // Update attempt with final scores and photo
            $lockedAttempt->update($updateData);

            // Update local attempt reference
            $this->attempt = $lockedAttempt;
            
            // Deactivate QR code scan since quiz is completed
            QrCodeScan::deactivateScan(auth()->id(), $this->questionnaire->id);
        });

        $this->isCompleted = true;
        $this->quizLocked = false; // Unlock quiz after completion
        $this->preventNavigation = false; // Allow navigation after completion

        session()->flash('success', $this->autoSubmitted 
            ? 'Quiz submitted automatically due to time limit.'
            : 'Quiz submitted successfully!');

        $this->dispatch('quizCompleted', [
            'score' => $this->score,
            'totalPoints' => $this->totalPoints,
            'earnedPoints' => $this->earnedPoints,
            'attemptId' => $this->attempt->id
        ]);

        // Redirect to dedicated results page to ensure fresh data
        return $this->redirect(route('quiz.results', ['attemptId' => $this->attempt->id]), navigate: true);
    }

    public function isFunGameOnlyQuiz()
    {
        foreach ($this->questions as $question) {
            if ($question['type'] !== 'fun_game') {
                return false;
            }
        }
        return true;
    }

    public function autoCompleteQuiz($skipRedirect = false)
    {
        // For fun-game-only quizzes, auto-complete when all games are done
        $allGamesCompleted = true;
        foreach ($this->questions as $question) {
            if ($question['type'] === 'fun_game' && !$this->isGameCompleted($question['id'])) {
                $allGamesCompleted = false;
                break;
            }
        }

        if (!$allGamesCompleted) {
            if (!$skipRedirect) {
                session()->flash('info', 'Please complete all games before finishing the quiz.');
            }
            return;
        }

        // Auto-complete the quiz
        DB::transaction(function () {
            $lockedAttempt = QuizAttempt::where('id', $this->attempt->id)
                ->lockForUpdate()
                ->first();
            
            if (!$lockedAttempt || !$lockedAttempt->canSubmit()) {
                return;
            }

            $this->endTime = now();

            // For fun-game quizzes, score is 0 as it comes from assessments
            $lockedAttempt->update([
                'status' => QuizAttempt::STATUS_COMPLETED,
                'completed_at' => $this->endTime,
                'total_score' => 0, // Score comes from assessments
                'total_time_seconds' => $this->endTime->diffInSeconds($lockedAttempt->started_at),
            ]);

            $this->attempt = $lockedAttempt;
            QrCodeScan::deactivateScan(auth()->id(), $this->questionnaire->id);
        });

        $this->isCompleted = true;
        $this->quizLocked = false;
        $this->preventNavigation = false;

        // Only show message if not skipping redirect
        if (!$skipRedirect) {
            session()->flash('success', 'All games completed! Results will be available after assessment.');
        }

        $this->dispatch('quizCompleted', [
            'score' => 0,
            'totalPoints' => 0,
            'earnedPoints' => 0,
            'attemptId' => $this->attempt->id
        ]);

        // Only redirect if not skipping
        if (!$skipRedirect) {
            return $this->redirect(route('quiz.results', ['attemptId' => $this->attempt->id]), navigate: true);
        }
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

    public function calculateScore()
    {
        $this->earnedPoints = 0;

        foreach ($this->questions as $question) {
            // Skip fun_game and brief questions - they are not scored
            if ($question['type'] === 'fun_game' || $question['type'] === 'brief') {
                continue;
            }
            
            $userAnswer = $this->answers[$question['id']] ?? '';
            
            if ($this->isAnswerCorrect($question, $userAnswer)) {
                $this->earnedPoints += $question['points'];
            }
        }

        // Calculate score only based on regular questions
        $regularQuestionsPoints = $this->getRegularQuestionsPoints();
        $this->score = $regularQuestionsPoints > 0 
            ? round(($this->earnedPoints / $regularQuestionsPoints) * 100, 2)
            : 0;
    }

    public function getRegularQuestionsPoints()
    {
        $totalRegularPoints = 0;
        foreach ($this->questions as $question) {
            if ($question['type'] !== 'fun_game' && $question['type'] !== 'brief') {
                $totalRegularPoints += $question['points'];
            }
        }
        return $totalRegularPoints;
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

        // Create a temporary Question model for validation
        $questionModel = new \App\Models\Question([
            'id' => $question['id'],
            'type' => $question['type'],
            'options' => $question['options'] ?? [],
            'correct_answer' => $correctAnswer,
            'points' => $question['points']
        ]);

        return AnswerValidationService::isAnswerCorrect($questionModel, $userAnswer);
    }

    public function getAnsweredQuestionsCount()
    {
        if (!is_array($this->answers) || empty($this->answers)) {
            return 0;
        }
        
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
        // Prevent navigation if quiz is in progress and not completed
        if ($this->quizLocked && !$this->isCompleted) {
            session()->flash('error', 'You cannot leave the quiz until all answers are submitted.');
            return;
        }
        
        // Clear any session data related to the current quiz
        session()->forget(['active_questionnaire_id', 'scanned_qr_code']);
        
        // Set the active tab to quizzes so user goes directly to quiz list
        session()->flash('active_tab', 'quizzes');
        
        return redirect()->route('user.dashboard');
    }
    
    public function handleExitAttempt()
    {
        if ($this->quizLocked && !$this->isCompleted) {
            session()->flash('warning', 'Quiz is in progress. Please complete all questions before leaving.');
            return false;
        }
        return true;
    }
    
    public function canExitQuiz()
    {
        return !$this->quizLocked || $this->isCompleted;
    }
    
    public function forceExit()
    {
        // Only allow force exit in specific circumstances
        if ($this->attempt && $this->attempt->canEditAnswers()) {
            // Mark attempt as abandoned
            $this->attempt->update([
                'status' => QuizAttempt::STATUS_ABANDONED,
            ]);
            
            $this->quizLocked = false;
            session()->flash('warning', 'Quiz has been abandoned. Your progress was not saved.');
            return redirect()->route('user.dashboard');
        }
        
        session()->flash('error', 'Cannot exit quiz at this time.');
    }

    // Dedicated handler for text input blur events
    public function saveTextAnswer($questionId, $answer)
    {
        if (config('app.debug')) {
            \Log::debug("QuizTake: saveTextAnswer called - QuestionID: {$questionId}, Answer: '{$answer}'");
        }
        
        // Prevent saving answers if quiz is completed
        if (!$this->attempt->canEditAnswers()) {
            \Log::warning("QuizTake: Cannot edit answers - quiz has been submitted. AttemptID: {$this->attempt->id}");
            session()->flash('error', 'Cannot edit answers - quiz has been submitted.');
            return;
        }
        
        // Check if this is a brief question
        $question = collect($this->questions)->firstWhere('id', $questionId);
        if ($question && $question['type'] === 'brief') {
            // For brief questions, use debounced saving to reduce processing
            $this->saveBriefAnswer($questionId, $answer);
        } else {
            $this->saveAnswer($questionId, $answer);
        }
    }

    // Optimized saving method for brief feedback questions
    public function saveBriefAnswer($questionId, $answer)
    {
        // Update the local answer
        $this->answers[$questionId] = $answer;
        
        // Add to pending answers so it gets saved during quiz submission
        $this->pendingAnswers[$questionId] = $answer;
        
        // Use lighter debounced saving for brief questions (longer delay)
        $this->dispatch('startBriefAutoSaveTimer', [
            'questionId' => $questionId,
            'answer' => $answer,
            'delay' => 3000 // 3 seconds delay for brief questions
        ]);
    }

    // Watch for answer changes and auto-save (excluding text inputs)
    public function updatedAnswers($value, $key)
    {
        if (config('app.debug')) {
            \Log::debug("QuizTake: updatedAnswers triggered - Key: {$key}, Value: '{$value}', Is Numeric: " . (is_numeric($key) ? 'YES' : 'NO'));
        }
        
        if (is_numeric($key)) {
            // Prevent editing if quiz is completed
            if (!$this->attempt->canEditAnswers()) {
                if (config('app.debug')) {
                    \Log::debug("QuizTake: Cannot edit answers, resetting value for key: {$key}");
                }
                // Reset the answer to prevent frontend changes
                $this->answers[$key] = $this->attempt->userAnswers()->where('question_id', $key)->first()->answer ?? '';
                return;
            }
            
            // Skip automatic save for text and brief inputs - they use dedicated methods
            $question = collect($this->questions)->firstWhere('id', $key);
            
            if ($question && ($question['type'] === 'text' || $question['type'] === 'brief')) {
                if (config('app.debug')) {
                    \Log::debug("QuizTake: Skipping updatedAnswers for {$question['type']} question: {$key} - handled by dedicated method");
                }
                return;
            }
            
            if (config('app.debug')) {
                \Log::debug("QuizTake: Calling saveAnswer from updatedAnswers - Key: {$key}, Value: '{$value}'");
            }
            
            // For radio buttons and other inputs, save immediately
            $this->saveAnswer($key, $value);
            
            // Force flush for non-text inputs only
            if (config('app.debug')) {
                \Log::debug("QuizTake: Force-flushing from updatedAnswers for non-text input");
            }
            $this->flushPendingAnswers();
        } else {
            if (config('app.debug')) {
                \Log::debug("QuizTake: Skipping non-numeric key: {$key}");
            }
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
    
    /**
     * Check if the current user can edit answers for this quiz
     */
    public function canEditAnswers()
    {
        return $this->attempt && $this->attempt->canEditAnswers();
    }

    /**
     * Auto-complete a fun game triggered by external systems
     */
    public function autoCompleteGame($questionId, $triggerType = 'manual', $triggerData = [])
    {
        // Log the trigger for debugging
        if (config('app.debug')) {
            \Log::debug("QuizTake: autoCompleteGame triggered - QuestionID: {$questionId}, Trigger: {$triggerType}", $triggerData);
        }
        
        // Call the main completion method
        return $this->completeGame($questionId, $triggerType, $triggerData);
    }

    /**
     * Complete a fun game and create assessment record
     */
    public function completeGame($questionId, $triggerType = 'manual', $triggerData = [])
    {
        // Prevent completing if quiz is locked or completed
        if (!$this->attempt->canEditAnswers()) {
            session()->flash('error', 'Cannot complete game - quiz has been submitted.');
            return;
        }

        // Find the question
        $question = collect($this->questions)->firstWhere('id', $questionId);
        if (!$question || $question['type'] !== 'fun_game') {
            session()->flash('error', 'Invalid game question.');
            return;
        }

        // Check if already completed
        if ($this->isGameCompleted($questionId)) {
            // Find the existing assessment
            $existingAssessment = GameAssessment::where('quiz_attempt_id', $this->attempt->id)
                ->where('question_id', $questionId)
                ->where('user_id', auth()->id())
                ->first();
            
            if ($existingAssessment && !$existingAssessment->is_assessed) {
                // Redirect to assessment page if not yet assessed
                session()->flash('info', 'Game already completed. Please complete the assessment.');
                return $this->redirect(route('game.assessment', ['assessmentId' => $existingAssessment->id]), navigate: true);
            } else {
                session()->flash('info', 'Game already completed and assessed.');
                return;
            }
        }

        // Prepare trigger information for notes
        $triggerInfo = '';
        if ($triggerType !== 'manual') {
            $triggerInfo = "Auto-completed via {$triggerType}";
            if (!empty($triggerData)) {
                $triggerInfo .= ': ' . json_encode($triggerData);
            }
        }

        // Create game assessment record
        $assessment = GameAssessment::create([
            'quiz_attempt_id' => $this->attempt->id,
            'question_id' => $questionId,
            'user_id' => auth()->id(),
            'deposit' => 0,
            'penalty' => 0,
            'total_deposit' => 0,
            'is_assessed' => false,
            'notes' => $triggerInfo ?: null
        ]);

        // Mark as "answered" in the quiz system
        $this->answers[$questionId] = 'completed';
        $this->saveAnswer($questionId, 'completed');

        // Always redirect to assessment immediately after game completion
        // The blocking logic will prevent quiz progression until assessment is completed
        session()->flash('success', 'Game completed! Please complete the assessment before continuing with the quiz.');

        // For fun-game-only quizzes, auto-complete quiz if all games are done
        if ($this->isFunGameOnlyQuiz()) {
            $allGamesCompleted = true;
            foreach ($this->questions as $question) {
                if ($question['type'] === 'fun_game' && !$this->isGameCompleted($question['id'])) {
                    $allGamesCompleted = false;
                    break;
                }
            }

            if ($allGamesCompleted) {
                // Auto-complete the quiz first (skip redirect since we're going to assessment)
                $this->autoCompleteQuiz(true);
            }
        }
        
        // Always redirect to assessment immediately after any fun game completion
        return $this->redirect(route('game.assessment', ['assessmentId' => $assessment->id]), navigate: true);
    }

    /**
     * Check if a fun game is completed
     */
    public function isGameCompleted($questionId)
    {
        return GameAssessment::where('quiz_attempt_id', $this->attempt->id)
            ->where('question_id', $questionId)
            ->where('user_id', auth()->id())
            ->exists();
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
        ])->layout('layouts.quiz');
    }

    /**
     * DIAGNOSTIC METHOD: Test answer saving manually
     */
    public function testAnswerSaving($questionId = null, $testAnswer = 'TEST_ANSWER')
    {
        if (!config('app.debug')) {
            return ['error' => 'Debug mode must be enabled'];
        }

        \Log::debug("QuizTake: DIAGNOSTIC - Testing answer saving");
        
        // Use first question if no ID provided
        if (!$questionId && !empty($this->questions)) {
            $questionId = $this->questions[0]['id'];
        }
        
        $diagnostic = [
            'timestamp' => now()->toDateTimeString(),
            'attempt_id' => $this->attempt ? $this->attempt->id : 'NULL',
            'attempt_status' => $this->attempt ? $this->attempt->status : 'NULL',
            'can_edit_answers' => $this->attempt ? $this->attempt->canEditAnswers() : false,
            'question_id' => $questionId,
            'test_answer' => $testAnswer,
            'questions_count' => count($this->questions ?? []),
            'pending_answers_before' => count($this->pendingAnswers),
        ];

        try {
            // Test the full saving process
            $this->saveAnswer($questionId, $testAnswer);
            
            $diagnostic['save_completed'] = true;
            $diagnostic['pending_answers_after'] = count($this->pendingAnswers);
            
            // Check if answer was actually saved to database
            $savedAnswer = UserAnswer::where('quiz_attempt_id', $this->attempt->id)
                ->where('question_id', $questionId)
                ->first();
                
            $diagnostic['database_saved'] = $savedAnswer ? true : false;
            $diagnostic['database_answer'] = $savedAnswer ? $savedAnswer->answer : null;
            
        } catch (\Exception $e) {
            $diagnostic['error'] = $e->getMessage();
            $diagnostic['save_completed'] = false;
        }

        \Log::debug("QuizTake: DIAGNOSTIC RESULTS", $diagnostic);
        
        return $diagnostic;
    }

    /**
     * Check if there are completed fun games behind current position that haven't been assessed
     */
    protected function hasUnassessedCompletedFunGames(): bool
    {
        // Look at all questions up to current position
        for ($i = 0; $i < $this->currentQuestionIndex; $i++) {
            $question = $this->questions[$i];
            
            // If it's a fun game that has been completed
            if ($question['type'] === 'fun_game' && $this->isGameCompleted($question['id'])) {
                // Check if there's an assessment record for this game
                $assessment = GameAssessment::where('user_id', auth()->id())
                    ->where('quiz_attempt_id', $this->attempt->id)
                    ->where('question_id', $question['id'])
                    ->first();
                
                // If no assessment exists or assessment is not completed
                if (!$assessment || !$assessment->is_assessed) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Handle quiz time warning event from workflow
     */
    public function handleQuizWarning($event)
    {
        $data = $event['data'] ?? [];
        $remainingMinutes = $data['remaining_minutes'] ?? 0;
        
        session()->flash('warning', "Time warning: Only {$remainingMinutes} minutes remaining!");
        
        // Recalculate time remaining
        $this->calculateTimeRemaining();
        
        // Dispatch browser notification
        $this->dispatch('showTimeWarning', [
            'message' => "Only {$remainingMinutes} minutes remaining!",
            'remainingTime' => $this->timeRemaining
        ]);
    }

    /**
     * Handle quiz time expired event from workflow
     */
    public function handleQuizTimeExpired($event)
    {
        if ($this->isCompleted || $this->timerExpired) {
            return;
        }
        
        session()->flash('warning', 'Quiz time has expired. Submitting automatically.');
        $this->handleTimeExpiry();
    }

    /**
     * Check if workflow timers are enabled
     */
    public function isWorkflowTimersEnabled(): bool
    {
        try {
            return FeatureSetting::isEnabled('workflow_timers');
        } catch (\Exception $e) {
            return false; // Default to false if feature settings not available
        }
    }
}