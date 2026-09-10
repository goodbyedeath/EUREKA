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
use App\Exceptions\QuizRuleException;
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
        // Check authentication first
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required. Please log in and try again.',
                'error' => 'unauthenticated'
            ], 401);
        }

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
            $cacheKey = Questionnaire::apiCacheKey((int) $questionnaireId);
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

            // Check availability. Say which of the three reasons it is: every active
            // questionnaire on this install once sat behind a date window that had quietly
            // expired, and the old one-size message named none of them — on event day that
            // reads as a broken app rather than a setting an admin can change.
            if (!$questionnaire->isAvailable()) {
                $now = now();
                $reason = match (true) {
                    ! $questionnaire->is_active => 'inactive',
                    $questionnaire->start_date && $now->lt($questionnaire->start_date) => 'not_open_yet',
                    $questionnaire->end_date && $now->gt($questionnaire->end_date) => 'window_closed',
                    default => 'unavailable',
                };

                return response()->json([
                    'success' => false,
                    'error' => 'not_available',
                    'reason' => $reason,
                    'opens_at' => $questionnaire->start_date?->toIso8601String(),
                    'closes_at' => $questionnaire->end_date?->toIso8601String(),
                    'message' => match ($reason) {
                        'inactive' => 'This questionnaire has been switched off by an administrator.',
                        'not_open_yet' => 'This questionnaire opens on ' . $questionnaire->start_date->format('j M Y') . '.',
                        'window_closed' => 'This questionnaire closed on ' . $questionnaire->end_date->format('j M Y') . '.',
                        default => 'This questionnaire is not currently available',
                    },
                ], 403);
            }

            // Check max attempts
            if (!$questionnaire->canUserAttempt(Auth::id())) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have reached the maximum number of attempts for this quiz'
                ], 403);
            }

            // Require the QR scan. The code is the only proof a team actually reached the
            // outpost, so without this the sequential questionnaire ids let them clear
            // every quiz from the start line without moving.
            if (!QrCodeScan::canUserStartQuestionnaire(Auth::id(), $questionnaire->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Scan the QR code at this outpost to unlock the quiz',
                    'error' => 'scan_required'
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
        // Check authentication first
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required. Please log in and try again.',
                'error' => 'unauthenticated'
            ], 401);
        }

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
                // Carbon 3 returns a SIGNED diff, so now()->diffInSeconds($past) is
                // negative and this gate never fired — expired attempts stayed resumable.
                // Measure forward from the same origin calculateTimeRemaining() uses, so
                // the gate and the countdown shown to the team always agree.
                $timerStart = $attempt->timer_started_at ?? $attempt->started_at;
                $elapsed = $timerStart->diffInSeconds(now(), false);
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
        // Check authentication first
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required. Please log in and try again.',
                'error' => 'unauthenticated'
            ], 401);
        }

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

                if (! $attempt) {
                    throw QuizRuleException::attemptNotFound();
                }

                if (! $attempt->canEditAnswers()) {
                    throw QuizRuleException::submitted();
                }

                // The clock is the server's to enforce. continue() already refuses an
                // expired attempt, but a client that ignores the `expired` flag — or simply
                // keeps the runtime open — could still save and submit a full score long
                // after time ran out, because this was the one path that never looked.
                $this->assertWithinTimeLimit($attempt);

                // Load question
                $question = $attempt->questionnaire->questions()
                    ->where('id', $request->question_id)
                    ->first();

                if (!$question) {
                    throw QuizRuleException::questionNotFound();
                }

                // Validate answer.
                //
                // The rule above is `nullable|string`, but sanitizeAnswer() takes a
                // non-nullable string. Clearing a field — deselecting a radio, wiping a
                // text box — sent null and raised a TypeError, which is an \Error and so
                // slipped past the catch below as an untrapped 500.
                $answer = AnswerValidationService::sanitizeAnswer((string) ($request->answer ?? ''));

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

        } catch (QuizRuleException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => $e->errorKey,
            ], $e->status);
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
     * Refuse work on an attempt whose clock has run out.
     *
     * Measured forward from the same origin calculateTimeRemaining() uses, so this gate and
     * the countdown the team sees can never disagree.
     */
    private function assertWithinTimeLimit(QuizAttempt $attempt): void
    {
        if (! $attempt->questionnaire->time_limit) {
            return;
        }

        $timerStart = $attempt->timer_started_at ?? $attempt->started_at;
        $elapsed = $timerStart->diffInSeconds(now(), false);

        if ($elapsed >= $attempt->questionnaire->time_limit * 60) {
            throw QuizRuleException::timeExpired();
        }
    }

    /**
     * Submit quiz
     */
    public function submit(Request $request)
    {
        // Check authentication first
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required. Please log in and try again.',
                'error' => 'unauthenticated'
            ], 401);
        }

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
                    throw QuizRuleException::submitted();
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
                    // Past to future. Carbon 3 returns a SIGNED float, so the reverse
                    // phrasing — now()->diffInSeconds($past) — writes a negative duration.
                    'total_time_seconds' => (int) max(0, $attempt->started_at?->diffInSeconds($endTime) ?? 0),
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

        } catch (QuizRuleException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => $e->errorKey,
            ], $e->status);
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
        // Check authentication first
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required. Please log in and try again.',
                'error' => 'unauthenticated'
            ], 401);
        }

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
        // Check authentication first
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required. Please log in and try again.',
                'error' => 'unauthenticated'
            ], 401);
        }

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

                if (! $attempt) {
                    throw QuizRuleException::attemptNotFound();
                }

                if (! $attempt->canEditAnswers()) {
                    throw QuizRuleException::submitted();
                }

                // Load question
                $question = $attempt->questionnaire->questions()
                    ->where('id', $request->question_id)
                    ->first();

                if (!$question || $question->type !== 'fun_game') {
                    throw new QuizRuleException('not_a_game_question', 'That question is not a facilitator-scored game.', 422);
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
                        throw QuizRuleException::gameAlreadyAssessed();
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

        } catch (QuizRuleException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => $e->errorKey,
            ], $e->status);
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
