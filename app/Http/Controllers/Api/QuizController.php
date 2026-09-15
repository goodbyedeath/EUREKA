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
use App\Models\RaceReset;
use App\Services\FacilitatorPin;
use App\Services\GameAssessmentService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
                'message' => 'Too many quiz attempts. Please wait.',
                'error' => 'too_many_starts',
                'retry_after' => RateLimiter::availableIn($key),
            ], 429)->header('Retry-After', (string) RateLimiter::availableIn($key));
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
                    'message' => 'Questionnaire not found',
                    'error' => 'questionnaire_not_found',
                ], 404);
            }

            // One live session at a time; it is left only by finishing it or running out of time. Checked before
            // the scan gate, so the answer names the live session rather than "scan required".
            if ($live = QuizAttempt::liveSessionFor(Auth::id(), $questionnaire->id)) {
                return response()->json([
                    'success' => false,
                    'error' => 'session_in_progress',
                    'attempt_id' => $live->id,
                    'questionnaire' => ['id' => $live->questionnaire->id, 'title' => $live->questionnaire->title],
                    'message' => 'Finish every question at "'.$live->questionnaire->title.'" first.',
                ], 409);
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
                    'message' => 'You have reached the maximum number of attempts for this quiz',
                    'error' => 'max_attempts_reached',
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
                ->map(fn ($q) => $this->questionRow($q))
                ->all();

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
                'completion' => $this->completion($attempt),
                'questions' => $questions,
                // Always an object. An empty PHP array encodes as [] and a filled one keyed by
                // question_id as {}, so a fresh attempt and a resumed one disagreed on the type of
                // the same field — and a client modelling it as a map rejected every first attempt.
                'answers' => (object) $answers,
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
                if ($reset = $this->raceResetFor()) {
                    return $reset;
                }
                return response()->json([
                    'success' => false,
                    'message' => 'Quiz attempt not found',
                    'error' => 'attempt_not_found',
                ], 404);
            }

            // Check if completed
            if ($attempt->isCompleted()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Quiz already completed',
                    'error' => 'attempt_submitted',
                    'redirect' => route('quiz.results', ['attemptId' => $attempt->id])
                ], 403);
            }

            // Check if started
            if (!$attempt->isStarted()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This quiz attempt cannot be continued',
                    'error' => 'attempt_not_active',
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
                        'error' => 'time_expired',
                        'attempt_id' => $attempt->id,
                        'expired' => true
                    ], 403);
                }
            }

            // Load questions
            $questions = $attempt->questionnaire->questions()
                ->select('id', 'question', 'type', 'options', 'points', 'order', 'description', 'game_name', 'images')
                ->orderBy('order', 'asc')
                ->get()
                ->map(fn ($q) => $this->questionRow($q))
                ->all();

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
                'completion' => $this->completion($attempt),
                'questions' => $questions,
                // Always an object. An empty PHP array encodes as [] and a filled one keyed by
                // question_id as {}, so a fresh attempt and a resumed one disagreed on the type of
                // the same field — and a client modelling it as a map rejected every first attempt.
                'answers' => (object) $answers,
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
            return $this->ruleRefusal($e);
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
     * The caller's own attempt history.
     *
     * `livewire/user/recent-attempts` gave a team its own record and had no API twin, so after
     * several outposts a team holding only the app could not see what it had already done. The
     * query mirrors `App\Livewire\User\RecentAttempts`: scoped to the caller, newest first.
     */
    public function attempts(Request $request)
    {
        $data = $request->validate([
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $limit = (int) ($data['limit'] ?? 20);

        $rows = QuizAttempt::with('questionnaire:id,title')
            ->where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            // The count is of everything, not of this page: a team that has done thirty
            // outposts should see thirty, not the twenty it asked to list.
            'total_attempts' => QuizAttempt::where('user_id', Auth::id())->count(),
            'attempts' => $rows->map(fn (QuizAttempt $a) => [
                'id' => $a->id,
                'questionnaire' => $a->questionnaire ? [
                    'id' => $a->questionnaire->id,
                    'title' => $a->questionnaire->title,
                ] : null,
                'status' => $a->status,
                'total_score' => $a->total_score !== null ? (float) $a->total_score : null,
                'total_time_seconds' => $a->total_time_seconds !== null ? (int) $a->total_time_seconds : null,
                'created_at' => $a->created_at?->toIso8601String(),
                'completed_at' => $a->completed_at?->toIso8601String(),
            ])->values(),
        ]);
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

                // The only way out of a session is finishing it, or the clock running out. An early
                // submit let a team walk out, rescan and get a fresh timer.
                $completion = $this->completion($attempt);
                if (! $completion['can_submit']) {
                    throw new QuizRuleException('questions_incomplete', 'Finish every question before handing in.', 409, ['pending' => $completion['pending']]);
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
            return $this->ruleRefusal($e);
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
                if ($reset = $this->raceResetFor()) {
                    return $reset;
                }
                return response()->json([
                    'success' => false,
                    'message' => 'Quiz attempt not found',
                    'error' => 'attempt_not_found',
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
    /**
     * The facilitator scoring screen, shown on the team's own phone after complete-game.
     */
    public function assessment(int $assessmentId)
    {
        $assessment = $this->ownAssessment($assessmentId);
        if (! $assessment) {
            return $this->assessmentNotFound();
        }

        return response()->json(['success' => true, 'assessment' => $this->assessmentPayload($assessment)]);
    }

    private const MAX_PHOTO_BYTES = 3 * 1024 * 1024;

    /**
     * Check the facilitator PIN before the scoring inputs are shown. Counts toward the lockout.
     */
    public function verifyFacilitatorPin(Request $request, int $assessmentId, FacilitatorPin $pin)
    {
        $data = $request->validate([
            'facilitator_pin' => 'required|string|max:8',
        ]);

        $assessment = $this->ownAssessment($assessmentId);
        if (! $assessment) {
            return $this->assessmentNotFound();
        }

        try {
            if ($assessment->is_assessed) {
                throw QuizRuleException::gameAlreadyAssessed();
            }
            // Time over = session over: a game not scored by then stays at 0.
            $this->assertSessionOpen($assessment->quizAttempt);
            $pin->verify($data['facilitator_pin'], Auth::id());
        } catch (QuizRuleException $e) {
            return $this->ruleRefusal($e);
        }

        return response()->json(['success' => true, 'verified' => true]);
    }

    /**
     * Record the facilitator's score. The team holds the phone, so this needs the event's
     * facilitator PIN and a photo the app took from the front camera while the facilitator
     * scored. One shot: a second POST is 409 game_already_assessed; an admin corrects on the web.
     */
    public function assess(Request $request, int $assessmentId, GameAssessmentService $service, FacilitatorPin $pin)
    {
        $data = $request->validate([
            'facilitator_pin' => 'required|string|max:8',
            'facilitator_photo' => 'required|string',
            'additional_points' => 'required|integer|min:0',
            'penalty' => 'required|integer|min:0|max:'.GameAssessmentService::MAX_PENALTY,
            'notes' => 'nullable|string|max:1000',
        ]);

        $assessment = $this->ownAssessment($assessmentId);
        if (! $assessment) {
            return $this->assessmentNotFound();
        }

        $path = null;
        try {
            if ($assessment->is_assessed) {
                throw QuizRuleException::gameAlreadyAssessed();
            }
            // Time over = session over: a game not scored by then stays at 0.
            $this->assertSessionOpen($assessment->quizAttempt);
            // PIN first: a wrong guess must not leave a stored photo behind.
            $pin->verify($data['facilitator_pin'], Auth::id());
            $path = $this->storeFacilitatorPhoto($data['facilitator_photo'], $assessment->id);

            $result = $service->record(
                $assessment,
                (int) $data['additional_points'],
                (int) $data['penalty'],
                $data['notes'] ?? null,
                Auth::id(),
                photoPath: $path,
            );
        } catch (QuizRuleException $e) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            return $this->ruleRefusal($e);
        }

        return response()->json([
            'success' => true,
            'assessment' => $this->assessmentPayload($result['assessment']),
            'team_gain' => $result['team_gain'],
            'team_points' => $result['team_points'],
            'next' => $this->nextAfterGame($result['assessment']),
            'completion' => $this->completion($result['assessment']->quizAttempt()->first()),
        ]);
    }

    /** Private disk: only the admin route serves it. Returns the stored path. */
    private function storeFacilitatorPhoto(string $base64, int $assessmentId): string
    {
        if (preg_match('#^data:image/[a-z]+;base64,#i', $base64, $m)) {
            $base64 = substr($base64, strlen($m[0]));
        }
        $bytes = base64_decode($base64, true);
        $info = ($bytes !== false && $bytes !== '' && strlen($bytes) <= self::MAX_PHOTO_BYTES)
            ? @getimagesizefromstring($bytes)
            : false;
        $ext = match ($info[2] ?? null) {
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
            default => null,
        };
        if (! $ext) {
            throw new QuizRuleException('invalid_facilitator_photo', 'facilitator_photo must be a base64 JPEG or PNG of at most 3 MB.', 422);
        }

        $path = "facilitator-photos/{$assessmentId}-".Str::random(16).".{$ext}";
        Storage::disk('local')->put($path, $bytes);

        return $path;
    }

    /**
     * If this request names an attempt or assessment that an emergency race stop wiped, the answer is
     * race_reset — not "not found" — so the app knows the organisers ended it and goes to the dashboard.
     */
    private function raceResetFor(): ?\Illuminate\Http\JsonResponse
    {
        $request = request();
        $reset = null;

        if (is_numeric($assessmentId = $request->route('assessmentId'))) {
            $reset = RaceReset::forRef('assessment', (int) $assessmentId);
        }
        $attemptId = $request->route('attemptId') ?? $request->input('attempt_id');
        if (! $reset && is_numeric($attemptId)) {
            $reset = RaceReset::forRef('attempt', (int) $attemptId);
        }

        return $reset ? RaceReset::response($reset) : null;
    }

    private function ruleRefusal(QuizRuleException $e)
    {
        // A wiped attempt looks "not found" to every lookup, and "submitted" to submit().
        if (in_array($e->errorKey, ['attempt_not_found', 'attempt_submitted'], true) && ($reset = $this->raceResetFor())) {
            return $reset;
        }

        $response = response()->json([
            'success' => false,
            'message' => $e->getMessage(),
            'error' => $e->errorKey,
        ] + $e->context, $e->status);

        if (isset($e->context['retry_after'])) {
            $response->header('Retry-After', (string) $e->context['retry_after']);
        }

        return $response;
    }

    /**
     * One question as the app receives it. `images` stays as stored (disk-relative paths, which a
     * client that already prefixes a base URL depends on); `image_urls` is the same list made
     * absolute, because a native client has no page origin to resolve "games/…" against and
     * showed every game without its picture.
     */
    private function questionRow($question): array
    {
        $row = $question->toArray();
        $row['image_urls'] = collect((array) ($question->images ?? []))
            ->filter(fn ($p) => is_string($p) && $p !== '')
            ->map(fn ($p) => preg_match('#^https?://#i', $p) ? $p : Storage::disk('public')->url($p))
            ->values()
            ->all();

        return $row;
    }

    /**
     * What still stands between the team and leaving this questionnaire. The operator's rule: the
     * only way out of a question session is finishing every question — a fun_game counts only once
     * a facilitator has scored it — or the clock running out. `brief` never blocks.
     */
    private function completion(QuizAttempt $attempt): array
    {
        $answers = $attempt->userAnswers()->get()->keyBy('question_id');
        $games = GameAssessment::where('quiz_attempt_id', $attempt->id)->get()->keyBy('question_id');

        $pending = [];
        foreach ($attempt->questionnaire->questions()->orderBy('order')->get(['questions.id', 'questions.type']) as $q) {
            $reason = match ($q->type) {
                'brief' => null,
                'fun_game' => ! isset($games[$q->id])
                    ? 'game_not_completed'
                    : ($games[$q->id]->is_assessed ? null : 'awaiting_assessment'),
                default => (isset($answers[$q->id]) && trim((string) $answers[$q->id]->answer) !== '')
                    ? null
                    : 'unanswered',
            };
            if ($reason !== null) {
                $pending[] = ['question_id' => $q->id, 'type' => $q->type, 'reason' => $reason];
            }
        }

        $expired = $attempt->isTimeExpired();

        return [
            'can_submit' => $pending === [] || $expired,
            'time_expired' => $expired,
            'pending' => $pending,
        ];
    }

    /** Game completion and facilitator scoring happen only inside a running session. */
    private function assertSessionOpen(?QuizAttempt $attempt): void
    {
        if (! $attempt || ! $attempt->canEditAnswers()) {
            throw QuizRuleException::submitted();
        }
        $this->assertWithinTimeLimit($attempt);
    }

    private function ownAssessment(int $id): ?GameAssessment
    {
        return GameAssessment::with(['question', 'quizAttempt'])
            ->where('id', $id)
            ->where('user_id', Auth::id())
            ->first();
    }

    private function assessmentNotFound()
    {
        if ($reset = $this->raceResetFor()) {
            return $reset;
        }

        return response()->json([
            'success' => false,
            'message' => 'That assessment does not exist, or belongs to someone else.',
            'error' => 'assessment_not_found',
        ], 404);
    }

    private function assessmentPayload(GameAssessment $a): array
    {
        return [
            'id' => $a->id,
            'attempt_id' => $a->quiz_attempt_id,
            'question' => [
                'id' => $a->question?->id,
                'game_name' => $a->question?->game_name,
                'question' => $a->question?->question,
                'points' => (int) ($a->question?->points ?? 0),
            ],
            'max_additional_points' => app(\App\Services\GameAssessmentService::class)->maxAdditional($a),
            'max_penalty' => \App\Services\GameAssessmentService::MAX_PENALTY,
            'facilitator_pin_required' => true,
            'facilitator_pin_set' => app(FacilitatorPin::class)->isSet(),
            'photo_required' => true,
            'is_assessed' => (bool) $a->is_assessed,
            'additional_points' => $a->is_assessed ? (int) $a->additional_points : null,
            'penalty' => $a->is_assessed ? (int) $a->penalty : null,
            'notes' => $a->notes,
            'assessed_at' => $a->assessed_at?->toIso8601String(),
        ];
    }

    /** "continue" while the questionnaire has questions after this game, otherwise "submit". */
    private function nextAfterGame(GameAssessment $a): string
    {
        $ids = $a->quizAttempt?->questionnaire?->questions()->orderBy('order')->pluck('id') ?? collect();
        $pos = $ids->search($a->question_id);

        return ($pos !== false && $pos < $ids->count() - 1) ? 'continue' : 'submit';
    }

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
            return $this->ruleRefusal($e);
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
