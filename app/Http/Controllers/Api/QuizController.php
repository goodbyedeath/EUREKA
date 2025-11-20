<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Questionnaire;
use App\Models\QuizAttempt;
use App\Models\UserAnswer;
use App\Models\QrCodeScan;
use App\Models\GameAssessment;
use App\Services\AnswerValidationService;
use App\Services\WorkflowTimerService;
use App\Models\FeatureSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class QuizController extends Controller
{
    protected $workflowTimerService;

    public function __construct()
    {
        try {
            $this->workflowTimerService = app(WorkflowTimerService::class);
        } catch (\Exception $e) {
            Log::error("Failed to initialize WorkflowTimerService: " . $e->getMessage());
            $this->workflowTimerService = null;
        }
    }

    /**
     * Start a new quiz attempt
     */
    public function start(Request $request, $questionnaireId)
    {
        // Rate limiting
        $key = 'quiz_attempts:' . Auth::id();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            return response()->json([
                'success' => false,
                'message' => 'Too many quiz attempts. Please wait.'
            ], 429);
        }
        RateLimiter::hit($key, 60);

        try {
            // Load questionnaire with questions
            $cacheKey = "questionnaire_{$questionnaireId}";
            $questionnaire = Cache::remember($cacheKey, 3600, function () use ($questionnaireId) {
                return Questionnaire::with('questions')
                    ->where('id', $questionnaireId)
                    ->where('is_active', true)
                    ->first();
            });

            if (!$questionnaire) {
                return response()->json([
                    'success' => false,
                    'message' => 'Questionnaire not found'
                ], 404);
            }

            // Check availability
            if (!$questionnaire->isAvailable()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This questionnaire is not currently available'
                ], 403);
            }

            // Check max attempts
            if (!$questionnaire->canUserAttempt(Auth::id())) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have reached the maximum number of attempts for this quiz'
                ], 403);
            }

            // Use database transaction with lock to prevent race conditions
            $isExistingAttempt = false;
            $attempt = DB::transaction(function () use ($questionnaire, &$isExistingAttempt) {
                // Check if user already has an active attempt (with row lock)
                $existingAttempt = QuizAttempt::where('user_id', Auth::id())
                    ->where('questionnaire_id', $questionnaire->id)
                    ->where('status', QuizAttempt::STATUS_STARTED)
                    ->whereNull('completed_at')
                    ->lockForUpdate()  // Prevent concurrent creation
                    ->orderBy('started_at', 'desc')
                    ->first();

                \Log::info("API /quiz/start/{$questionnaire->id} - User: " . Auth::id() . ", Existing attempt found: " . ($existingAttempt ? "YES (ID: {$existingAttempt->id})" : "NO"));

                // If active attempt exists, return it instead of creating new one
                if ($existingAttempt) {
                    \Log::info("RETURNING EXISTING ATTEMPT {$existingAttempt->id} - started_at: {$existingAttempt->started_at}, timer_started_at: {$existingAttempt->timer_started_at}");
                    $isExistingAttempt = true;
                    return $existingAttempt;
                }

                // Create new attempt
                \Log::info("CREATING NEW ATTEMPT for user " . Auth::id() . " on questionnaire {$questionnaire->id}");
                $newAttempt = QuizAttempt::create([
                    'questionnaire_id' => $questionnaire->id,
                    'user_id' => Auth::id(),
                    'status' => QuizAttempt::STATUS_STARTED,
                    'started_at' => now(),
                    'timer_started_at' => now(),  // Set timer start immediately
                ]);
                \Log::info("NEW ATTEMPT CREATED: ID {$newAttempt->id}");

                return $newAttempt;
            });

            // Start workflow timer if enabled
            if ($this->isWorkflowTimersEnabled() && $questionnaire->time_limit && $this->workflowTimerService) {
                try {
                    $this->workflowTimerService->startQuizTimer($attempt);
                } catch (\Exception $e) {
                    Log::error("Failed to start quiz timer: " . $e->getMessage());
                }
            }

            // Load questions
            $questions = $questionnaire->questions()
                ->select('id', 'question', 'type', 'options', 'points', 'order', 'description', 'game_name', 'images')
                ->orderBy('order', 'asc')
                ->get()
                ->toArray();

            // Calculate total points
            $totalPoints = 0;
            foreach ($questions as $question) {
                if ($question['type'] !== 'fun_game' && $question['type'] !== 'brief') {
                    $totalPoints += $question['points'];
                }
            }

            // Calculate time remaining
            $timeRemaining = $this->calculateTimeRemaining($attempt);

            Log::debug("Quiz start - Questionnaire ID: {$questionnaire->id}, time_limit from DB: {$questionnaire->time_limit} minutes, timeRemaining calculated: {$timeRemaining} seconds, started_at: {$attempt->started_at}");

            // Load existing answers if returning existing attempt
            $answers = [];
            if ($isExistingAttempt) {
                $userAnswers = $attempt->userAnswers()->get();
                foreach ($userAnswers as $answer) {
                    $answers[$answer->question_id] = $answer->answer;
                }
            }

            return response()->json([
                'success' => true,
                'attempt' => [
                    'id' => $attempt->id,
                    'started_at' => $attempt->started_at->toISOString(),
                    'status' => $attempt->status,
                ],
                'questionnaire' => [
                    'id' => $questionnaire->id,
                    'title' => $questionnaire->title,
                    'description' => $questionnaire->description,
                    'time_limit' => $questionnaire->time_limit,
                ],
                'questions' => $questions,
                'answers' => $answers,
                'totalPoints' => $totalPoints,
                'timeRemaining' => $timeRemaining,
            ]);

        } catch (\Exception $e) {
            Log::error('Quiz start error', [
                'user_id' => Auth::id(),
                'questionnaire_id' => $questionnaireId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while starting the quiz'
            ], 500);
        }
    }

    /**
     * Continue existing quiz attempt
     */
    public function continue(Request $request, $attemptId)
    {
        try {
            // Load attempt with relations
            $attempt = QuizAttempt::with(['questionnaire.questions', 'userAnswers'])
                ->where('id', $attemptId)
                ->where('user_id', Auth::id())
                ->first();

            if (!$attempt) {
                return response()->json([
                    'success' => false,
                    'message' => 'Quiz attempt not found'
                ], 404);
            }

            // Check if completed
            if ($attempt->isCompleted()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Quiz already completed',
                    'redirect' => route('quiz.results', ['attemptId' => $attempt->id])
                ], 403);
            }

            // Check if started
            if (!$attempt->isStarted()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This quiz attempt cannot be continued'
                ], 403);
            }

            // Check timer
            if ($attempt->questionnaire->time_limit) {
                $elapsed = now()->diffInSeconds($attempt->started_at);
                $timeLimit = $attempt->questionnaire->time_limit * 60;

                if ($elapsed >= $timeLimit) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Quiz time has expired',
                        'expired' => true
                    ], 403);
                }
            }

            // Load questions
            $questions = $attempt->questionnaire->questions()
                ->select('id', 'question', 'type', 'options', 'points', 'order', 'description', 'game_name', 'images')
                ->orderBy('order', 'asc')
                ->get()
                ->toArray();

            // Load existing answers
            $answers = [];
            foreach ($attempt->userAnswers as $answer) {
                $answers[$answer->question_id] = $answer->answer;
            }

            // Calculate total points
            $totalPoints = 0;
            foreach ($questions as $question) {
                if ($question['type'] !== 'fun_game' && $question['type'] !== 'brief') {
                    $totalPoints += $question['points'];
                }
            }

            // Calculate time remaining
            $timeRemaining = $this->calculateTimeRemaining($attempt);

            return response()->json([
                'success' => true,
                'attempt' => [
                    'id' => $attempt->id,
                    'started_at' => $attempt->started_at->toISOString(),
                    'status' => $attempt->status,
                ],
                'questionnaire' => [
                    'id' => $attempt->questionnaire->id,
                    'title' => $attempt->questionnaire->title,
                    'description' => $attempt->questionnaire->description,
                    'time_limit' => $attempt->questionnaire->time_limit,
                ],
                'questions' => $questions,
                'answers' => $answers,
                'totalPoints' => $totalPoints,
                'timeRemaining' => $timeRemaining,
                'continued' => true,
            ]);

        } catch (\Exception $e) {
            Log::error('Quiz continue error', [
                'user_id' => Auth::id(),
                'attempt_id' => $attemptId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading the quiz'
            ], 500);
        }
    }

    /**
     * Save an answer
     */
    public function saveAnswer(Request $request)
    {
        $request->validate([
            'attempt_id' => 'required|integer',
            'question_id' => 'required|integer',
            'answer' => 'nullable|string',
        ]);

        try {
            DB::transaction(function () use ($request) {
                // Lock attempt
                $attempt = QuizAttempt::where('id', $request->attempt_id)
                    ->where('user_id', Auth::id())
                    ->lockForUpdate()
                    ->first();

                if (!$attempt || !$attempt->canEditAnswers()) {
                    throw new \Exception('Cannot edit answers - quiz has been submitted');
                }

                // Load question
                $question = $attempt->questionnaire->questions()
                    ->where('id', $request->question_id)
                    ->first();

                if (!$question) {
                    throw new \Exception('Question not found');
                }

                // Validate answer
                $answer = AnswerValidationService::sanitizeAnswer($request->answer);

                if ($question->type !== 'brief') {
                    $questionModel = new \App\Models\Question([
                        'id' => $question->id,
                        'type' => $question->type,
                        'options' => $question->options ?? [],
                        'correct_answer' => '',
                        'points' => $question->points
                    ]);

                    $errors = AnswerValidationService::validateAnswerFormat($questionModel, $answer);
                    if (!empty($errors)) {
                        throw ValidationException::withMessages(['answer' => $errors]);
                    }
                }

                // Determine correctness and points
                if ($question->type === 'fun_game') {
                    $isCorrect = ($answer === 'completed');
                    $pointsEarned = 0;
                } elseif ($question->type === 'brief') {
                    $isCorrect = false;
                    $pointsEarned = 0;
                } else {
                    $isCorrect = AnswerValidationService::isAnswerCorrect($question, $answer);
                    $pointsEarned = $isCorrect ? $question->points : 0;
                }

                // Save answer
                UserAnswer::updateOrCreate(
                    [
                        'quiz_attempt_id' => $attempt->id,
                        'question_id' => $question->id,
                    ],
                    [
                        'answer' => $answer,
                        'is_correct' => $isCorrect,
                        'points_earned' => $pointsEarned,
                    ]
                );
            });

            return response()->json([
                'success' => true,
                'message' => 'Answer saved'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Save answer error', [
                'user_id' => Auth::id(),
                'attempt_id' => $request->attempt_id,
                'question_id' => $request->question_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Submit quiz
     */
    public function submit(Request $request)
    {
        $request->validate([
            'attempt_id' => 'required|integer',
            'verification_photo' => 'required|string',
        ]);

        try {
            $result = DB::transaction(function () use ($request) {
                // Lock attempt
                $attempt = QuizAttempt::where('id', $request->attempt_id)
                    ->where('user_id', Auth::id())
                    ->lockForUpdate()
                    ->first();

                if (!$attempt || !$attempt->canSubmit()) {
                    throw new \Exception('Quiz has already been submitted');
                }

                // Cancel workflow timer
                if ($this->isWorkflowTimersEnabled() && $this->workflowTimerService) {
                    try {
                        $this->workflowTimerService->cancelQuizTimer($attempt);
                    } catch (\Exception $e) {
                        Log::error("Failed to cancel quiz timer: " . $e->getMessage());
                    }
                }

                // Calculate score
                $earnedPoints = 0;
                $questionnaire = $attempt->questionnaire;
                $questions = $questionnaire->questions;
                $answers = $attempt->userAnswers()->get()->keyBy('question_id');

                foreach ($questions as $question) {
                    if ($question->type === 'fun_game' || $question->type === 'brief') {
                        continue;
                    }

                    $userAnswer = $answers[$question->id] ?? null;
                    if ($userAnswer && $userAnswer->is_correct) {
                        $earnedPoints += $question->points;
                    }
                }

                // Calculate percentage score
                $regularQuestionsPoints = 0;
                foreach ($questions as $question) {
                    if ($question->type !== 'fun_game' && $question->type !== 'brief') {
                        $regularQuestionsPoints += $question->points;
                    }
                }

                $score = $regularQuestionsPoints > 0
                    ? round(($earnedPoints / $regularQuestionsPoints) * 100, 2)
                    : 0;

                // Get base points from team
                $user = Auth::user();
                $basePoints = $user && $user->team ? ($user->team->initial_points ?? 1000) : 1000;

                $endTime = now();

                // Update attempt
                $updateData = [
                    'status' => QuizAttempt::STATUS_COMPLETED,
                    'completed_at' => $endTime,
                    'total_score' => $basePoints + $earnedPoints,
                    'total_time_seconds' => $endTime->diffInSeconds($attempt->started_at),
                ];

                if ($request->verification_photo) {
                    $updateData['verification_photo'] = $request->verification_photo;
                    $updateData['photo_captured_at'] = now();
                }

                $attempt->update($updateData);

                // Deactivate QR code scan
                QrCodeScan::deactivateScan(Auth::id(), $questionnaire->id);

                return [
                    'attempt_id' => $attempt->id,
                    'score' => $score,
                    'earned_points' => $earnedPoints,
                    'total_score' => $basePoints + $earnedPoints,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Quiz submitted successfully',
                'result' => $result,
                'redirect' => route('quiz.results', ['attemptId' => $result['attempt_id']])
            ]);

        } catch (\Exception $e) {
            Log::error('Quiz submit error', [
                'user_id' => Auth::id(),
                'attempt_id' => $request->attempt_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get timer information
     */
    public function timer(Request $request, $attemptId)
    {
        try {
            $attempt = QuizAttempt::with('questionnaire')
                ->where('id', $attemptId)
                ->where('user_id', Auth::id())
                ->first();

            if (!$attempt) {
                return response()->json([
                    'success' => false,
                    'message' => 'Quiz attempt not found'
                ], 404);
            }

            $timeRemaining = $this->calculateTimeRemaining($attempt);

            return response()->json([
                'success' => true,
                'timeRemaining' => $timeRemaining,
                'expired' => $timeRemaining !== null && $timeRemaining <= 0
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error getting timer information'
            ], 500);
        }
    }

    /**
     * Complete a fun game
     */
    public function completeGame(Request $request)
    {
        $request->validate([
            'attempt_id' => 'required|integer',
            'question_id' => 'required|integer',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                // Lock attempt
                $attempt = QuizAttempt::where('id', $request->attempt_id)
                    ->where('user_id', Auth::id())
                    ->lockForUpdate()
                    ->first();

                if (!$attempt || !$attempt->canEditAnswers()) {
                    throw new \Exception('Cannot complete game - quiz has been submitted');
                }

                // Load question
                $question = $attempt->questionnaire->questions()
                    ->where('id', $request->question_id)
                    ->first();

                if (!$question || $question->type !== 'fun_game') {
                    throw new \Exception('Invalid game question');
                }

                // Check if already completed
                $existingAssessment = GameAssessment::where('quiz_attempt_id', $attempt->id)
                    ->where('question_id', $question->id)
                    ->where('user_id', Auth::id())
                    ->first();

                if ($existingAssessment) {
                    if (!$existingAssessment->is_assessed) {
                        return [
                            'success' => true,
                            'message' => 'Game already completed',
                            'assessment_id' => $existingAssessment->id,
                            'redirect' => route('game.assessment', ['assessmentId' => $existingAssessment->id])
                        ];
                    } else {
                        throw new \Exception('Game already completed and assessed');
                    }
                }

                // Create assessment
                $assessment = GameAssessment::create([
                    'quiz_attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                    'user_id' => Auth::id(),
                    'deposit' => 0,
                    'penalty' => 0,
                    'total_deposit' => 0,
                    'is_assessed' => false,
                ]);

                // Mark as answered
                UserAnswer::updateOrCreate(
                    [
                        'quiz_attempt_id' => $attempt->id,
                        'question_id' => $question->id,
                    ],
                    [
                        'answer' => 'completed',
                        'is_correct' => true,
                        'points_earned' => 0,
                    ]
                );

                return [
                    'success' => true,
                    'message' => 'Game completed',
                    'assessment_id' => $assessment->id,
                    'redirect' => route('game.assessment', ['assessmentId' => $assessment->id])
                ];
            });

        } catch (\Exception $e) {
            Log::error('Complete game error', [
                'user_id' => Auth::id(),
                'attempt_id' => $request->attempt_id,
                'question_id' => $request->question_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate time remaining for attempt
     */
    protected function calculateTimeRemaining($attempt)
    {
        if (!$attempt->questionnaire->time_limit) {
            return null;
        }

        // Use timer_started_at if available, otherwise fall back to started_at
        $timerStart = $attempt->timer_started_at ?? $attempt->started_at;

        // Calculate elapsed time from when timer started until now
        // diffInSeconds with false parameter gives signed difference (positive if future, negative if past)
        $elapsed = $timerStart->diffInSeconds(now(), false);

        $totalTime = $attempt->questionnaire->time_limit * 60;
        $remaining = (int) max(0, $totalTime - $elapsed);

        Log::debug("Timer calculation for attempt {$attempt->id} - time_limit: {$attempt->questionnaire->time_limit} min, timer_started_at: {$timerStart}, now: " . now() . ", elapsed: {$elapsed}s, total: {$totalTime}s, remaining: {$remaining}s");

        return $remaining;
    }

    /**
     * Check if workflow timers are enabled
     */
    protected function isWorkflowTimersEnabled()
    {
        try {
            return FeatureSetting::isEnabled('workflow_timers');
        } catch (\Exception $e) {
            return false;
        }
    }
}
