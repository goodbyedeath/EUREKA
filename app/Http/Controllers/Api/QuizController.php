<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\QuizAttempt;
use App\Models\UserAnswer;
use App\Models\User;
use App\Services\PointsCalculationService;
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

            // Same gate as qr/lookup, for a NEW attempt: it closes scans recorded before the rule existed.
            // Resuming an attempt already open at this station is never blocked.
            $resuming = QuizAttempt::where('user_id', Auth::id())->where('questionnaire_id', $questionnaire->id)
                ->where('status', QuizAttempt::STATUS_STARTED)->whereNull('completed_at')->exists();
            if (! $resuming && ($refusal = app(\App\Services\StationGate::class)->refusal(Auth::id(), $questionnaire->id))) {
                return $refusal;
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
                ->select('id', 'question', 'type', 'options', 'points', 'order', 'description', 'game_name', 'images', 'frame_path', 'share_caption', 'answer_slots')
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
            $answers = $isExistingAttempt ? $this->answersForClient($attempt, $questions) : [];

            return response()->json([
                'success' => true,
                'attempt' => [
                    'id' => $attempt->id,
                    'started_at' => $attempt->started_at?->toISOString(),
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
                $timerStart = $attempt->timerOrigin();
                $elapsed = $timerStart?->diffInSeconds(now(), false) ?? 0;
                $timeLimit = $attempt->questionnaire->time_limit * 60;

                if ($timerStart && $elapsed >= $timeLimit) {
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
                ->select('id', 'question', 'type', 'options', 'points', 'order', 'description', 'game_name', 'images', 'frame_path', 'share_caption', 'answer_slots')
                ->orderBy('order', 'asc')
                ->get()
                ->map(fn ($q) => $this->questionRow($q))
                ->all();

            // Load existing answers
            $answers = $this->answersForClient($attempt, $questions);

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
                    'started_at' => $attempt->started_at?->toISOString(),
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
            // Tebak Gambar only: {box key: typed text}, the whole set every time.
            'answers' => 'nullable|array|max:'.\App\Services\PicturePuzzle::MAX_SLOTS,
            'answers.*' => 'nullable|string|max:'.\App\Services\PicturePuzzle::MAX_ANSWER_LENGTH,
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

                // A "foto bersama" is answered with the photograph, through photo-answer. Typed text
                // would otherwise be stored, satisfy the completion gate and carry the question's
                // points without anyone having taken a picture.
                if ($question->type === QuestionType::GROUP_PHOTO->value) {
                    throw new QuizRuleException('photo_answer_required', 'Pertanyaan ini dijawab dengan foto.', 422);
                }

                // Tebak Gambar is answered box by box, through `answers`.
                if ($question->type === QuestionType::PICTURE_PUZZLE->value) {
                    $this->savePuzzleAnswer($attempt, $question, $request->input('answers'));

                    return;
                }

                if ($request->has('answers')) {
                    throw new QuizRuleException('not_a_puzzle_question', 'Pertanyaan ini tidak dijawab per kolom.', 422);
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

        // The team is the unit that scores, so the history is the team's (APK #23: it asked
        // whether listing Auth::id() alone was intended — it was not). With one account per
        // team, as the login cards make, this is the same list as before.
        $user = Auth::user();
        $accounts = $user->team_id
            ? User::where('team_id', $user->team_id)->pluck('id')->all()
            : [$user->id];

        $rows = QuizAttempt::with(['questionnaire:id,title', 'user:id,name'])
            ->whereIn('user_id', $accounts)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        // Only completed attempts have a settled score; the rest report null rather than 0,
        // which would read as "this outpost was worth nothing".
        $base = (int) ($user->team?->initial_points ?? 0);
        $breakdown = app(PointsCalculationService::class)->breakdownForAttempts(
            $rows->where('status', QuizAttempt::STATUS_COMPLETED)->pluck('id'),
            $base,
        );

        return response()->json([
            'success' => true,
            // The count is of everything, not of this page: a team that has done thirty
            // outposts should see thirty, not the twenty it asked to list.
            'total_attempts' => QuizAttempt::whereIn('user_id', $accounts)->count(),
            'attempts' => $rows->map(fn (QuizAttempt $a) => [
                'id' => $a->id,
                'questionnaire' => $a->questionnaire ? [
                    'id' => $a->questionnaire->id,
                    'title' => $a->questionnaire->title,
                ] : null,
                'status' => $a->status,
                // What submit wrote at hand-in. Kept for older builds; the three fields below
                // are the ones that add up to the team's score card.
                'total_score' => $a->total_score !== null ? (float) $a->total_score : null,
                'earned_points' => $breakdown[$a->id]['earned_points'] ?? null,
                'assessment_points' => $breakdown[$a->id]['assessment_points'] ?? null,
                'points' => $breakdown[$a->id]['points'] ?? null,
                // Who did it, for a team whose members each have an account.
                'by' => $a->user?->name,
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

        $timerStart = $attempt->timerOrigin();

        // No origin means no clock has started yet; refusing the team's work here would be worse
        // than letting it through, and the gate resumes as soon as a start time exists.
        if (! $timerStart) {
            return;
        }

        if ($timerStart->diffInSeconds(now(), false) >= $attempt->questionnaire->time_limit * 60) {
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

                // Tebak Gambar: mark again against the answer key as it stands now. The mark made at
                // save time is provisional — an admin who fixes a typo in the key mid-event should
                // not leave earlier savers scored against the typo — and the per-box result goes back
                // in the response, the first moment the team is told which boxes were right.
                $puzzles = [];
                foreach ($questions->where('type', QuestionType::PICTURE_PUZZLE->value) as $puzzle) {
                    $saved = $answers[$puzzle->id] ?? null;
                    $marked = \App\Services\PicturePuzzle::score($puzzle, \App\Services\PicturePuzzle::decode($saved?->answer));

                    if ($saved) {
                        $saved->update(['is_correct' => $marked['all_correct'], 'points_earned' => $marked['points']]);
                    }

                    $puzzles[] = [
                        'question_id' => $puzzle->id,
                        'correct' => $marked['correct'],
                        'total' => $marked['total'],
                        'points_earned' => $saved ? $marked['points'] : 0,
                        // Which boxes were right — never what the right answer was. The next team to
                        // reach this post gets the same puzzle.
                        'slots' => collect(\App\Services\PicturePuzzle::slots($puzzle))
                            ->map(fn ($s) => ['key' => $s['key'], 'label' => $s['label'], 'correct' => $marked['slots'][$s['key']] ?? false])
                            ->values()
                            ->all(),
                    ];
                }

                foreach ($questions as $question) {
                    if ($question->type === 'fun_game' || $question->type === 'brief') {
                        continue;
                    }

                    // What the answer earned, not the question's face value when it is right. The
                    // two agree for every whole-or-nothing type; for "Tebak Gambar" only this one
                    // carries partial credit, and it is what the team score reads.
                    $userAnswer = $answers[$question->id] ?? null;
                    if ($userAnswer) {
                        $earnedPoints += (int) $userAnswer->points_earned;
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
                $basePoints = $user && $user->team ? ($user->team->initial_points ?? 0) : 0;

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
                    'puzzles' => $puzzles,
                    // Question by question, so the Results screen can show which one paid
                    // (operator's field test, APK report #32). Never a right answer.
                    'questions' => app(PointsCalculationService::class)->questionBreakdown($attempt->fresh(), $basePoints),
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

        // The server's own record that the PIN was right while the session was still open.
        $assessment->forceFill(['pin_verified_at' => now()])->save();

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
            // Time over = session over — unless the PIN was accepted before time-out and the score the
            // app queued on a weak signal arrives within the grace window (operator, 15 Sep).
            $acceptedLate = $this->assertCanDeliverScore($assessment);
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
            'accepted_late' => $acceptedLate,
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
     * Answer a "foto bersama" question with the photograph itself.
     *
     * The app composes the picture with the frame and sends the finished image, because the frame
     * is what makes every team's photo look like this event's, and the team is about to post it.
     * Taking the photo is the whole task: there is no right answer to mark, so a stored photo is
     * worth the question's points. Retaking replaces the previous one rather than scoring twice.
     */
    public function photoAnswer(Request $request)
    {
        $data = $request->validate([
            'attempt_id' => 'required|integer',
            'question_id' => 'required|integer',
            'photo' => 'required|string',
        ]);

        try {
            $attempt = QuizAttempt::where('id', $data['attempt_id'])
                ->where('user_id', Auth::id())
                ->firstOrFail();

            $this->assertSessionOpen($attempt);
            $this->assertWithinTimeLimit($attempt);

            $question = Question::where('id', $data['question_id'])
                ->where('questionnaire_id', $attempt->questionnaire_id)
                ->firstOrFail();

            if ($question->type !== QuestionType::GROUP_PHOTO->value) {
                throw new QuizRuleException('not_a_photo_question', 'Pertanyaan ini tidak dijawab dengan foto.', 422);
            }

            $path = $this->storeGroupPhoto($data['photo'], $attempt->id, $question->id);

            $answer = DB::transaction(function () use ($attempt, $question, $path) {
                $existing = UserAnswer::where('quiz_attempt_id', $attempt->id)
                    ->where('question_id', $question->id)
                    ->first();

                // A retake replaces the picture; the old file goes, so a post's worth of storage
                // does not pile up over an event.
                if ($existing && $existing->answer && Storage::disk('public')->exists($existing->answer)) {
                    Storage::disk('public')->delete($existing->answer);
                }

                return UserAnswer::updateOrCreate(
                    ['quiz_attempt_id' => $attempt->id, 'question_id' => $question->id],
                    ['answer' => $path, 'is_correct' => true, 'points_earned' => (int) $question->points],
                );
            });

            return response()->json([
                'success' => true,
                'photo_url' => Storage::disk('public')->url($path),
                'share_caption' => $question->share_caption,
                'points_earned' => (int) $answer->points_earned,
                'completion' => $this->completion($attempt->fresh()),
            ]);
        } catch (QuizRuleException $e) {
            return $this->ruleRefusal($e);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'error' => 'not_found', 'message' => 'Attempt atau pertanyaan tidak ditemukan.'], 404);
        }
    }

    /**
     * Store the finished photograph. Same shape as the facilitator's photo: base64, JPEG or PNG,
     * size-checked before it reaches the disk.
     */
    private function storeGroupPhoto(string $base64, int $attemptId, int $questionId): string
    {
        if (preg_match('#^data:image/[a-z]+;base64,#i', $base64, $m)) {
            $base64 = substr($base64, strlen($m[0]));
        }

        $bytes = base64_decode($base64, true);
        $info = $bytes !== false && strlen($bytes) <= 6 * 1024 * 1024
            ? @getimagesizefromstring($bytes)
            : false;

        $ext = match ($info['mime'] ?? null) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            default => null,
        };

        if (! $ext) {
            throw new QuizRuleException('invalid_photo', 'photo harus base64 JPEG atau PNG, maksimal 6 MB.', 422);
        }

        $path = "group-photos/{$attemptId}-{$questionId}-".Str::random(12).".{$ext}";
        Storage::disk('public')->put($path, $bytes);

        return $path;
    }

    /**
     * One question as the app receives it. `images` stays as stored (disk-relative paths, which a
     * client that already prefixes a base URL depends on); `image_urls` is the same list made
     * absolute, because a native client has no page origin to resolve "games/…" against and
     * showed every game without its picture.
     */
    private function questionRow($question): array
    {
        // One door for every question, shared with the offline manifest — see QuestionPayload.
        return \App\Support\QuestionPayload::forApp($question);
    }

    /**
     * Save a Tebak Gambar answer: every box at once, marked and scored on the spot.
     *
     * The client sends the whole set each time, so the last save wins and a replayed offline write
     * is harmless; a box it leaves out is stored empty. What is right is worked out here and kept on
     * the answer, but not said — the reply is the same "saved" every type gets, so a team cannot
     * learn a box is wrong by saving it. Which boxes were right is told after the session is
     * submitted; the right answers themselves never are.
     */
    private function savePuzzleAnswer(QuizAttempt $attempt, Question $question, $given): void
    {
        if (! is_array($given)) {
            throw new QuizRuleException('slot_answers_required', 'Isi jawaban per kolom lewat `answers`.', 422);
        }

        $known = array_column(\App\Services\PicturePuzzle::slots($question), 'key');
        $unknown = array_values(array_diff(array_map('strval', array_keys($given)), $known));
        if ($unknown) {
            throw new QuizRuleException('unknown_slot', 'Kolom tidak dikenal: '.implode(', ', $unknown).'.', 422);
        }

        // Stored in the question's own box order, every box present.
        $boxes = [];
        foreach ($known as $key) {
            $boxes[$key] = trim((string) ($given[$key] ?? ''));
        }

        $marked = \App\Services\PicturePuzzle::score($question, $boxes);

        UserAnswer::updateOrCreate(
            ['quiz_attempt_id' => $attempt->id, 'question_id' => $question->id],
            [
                'answer' => json_encode($boxes, JSON_UNESCAPED_UNICODE),
                // Wholly right only when every box is; partial credit lives in points_earned,
                // which is what every score in the system reads.
                'is_correct' => $marked['all_correct'],
                'points_earned' => $marked['points'],
            ],
        );
    }

    /**
     * One finished attempt's result, read back — the Results screen reopened from history.
     *
     * The submit reply carries this once; a team that closed the app, or a crew member looking at a
     * team's phone later, has no way back to it (APK report #32). Readable by the team that owns the
     * attempt — history is per team, as /quiz/attempts already is — and it carries no right answer.
     */
    public function attemptResult(Request $request, $attemptId)
    {
        $user = Auth::user();
        $accounts = $user->team_id
            ? User::where('team_id', $user->team_id)->pluck('id')
            : collect([$user->id]);

        $attempt = QuizAttempt::with('questionnaire')->whereIn('user_id', $accounts)->find($attemptId);

        if (! $attempt) {
            if ($reset = RaceReset::forRef('attempt', (int) $attemptId)) {
                return RaceReset::response($reset);
            }

            return response()->json(['success' => false, 'error' => 'attempt_not_found',
                'message' => 'That quiz attempt does not exist, or belongs to another team.'], 404);
        }

        $base = (int) ($user->team?->initial_points ?? 0);
        $breakdown = app(PointsCalculationService::class)->breakdownForAttempts([$attempt->id], $base)[$attempt->id] ?? null;

        return response()->json([
            'success' => true,
            'attempt' => [
                'id' => $attempt->id,
                'status' => $attempt->status,
                'started_at' => $attempt->started_at?->toIso8601String(),
                'completed_at' => $attempt->completed_at?->toIso8601String(),
                'total_time_seconds' => $attempt->total_time_seconds !== null ? (int) $attempt->total_time_seconds : null,
                'by' => $attempt->user?->name,
            ],
            'questionnaire' => $attempt->questionnaire ? [
                'id' => $attempt->questionnaire->id,
                'title' => $attempt->questionnaire->title,
            ] : null,
            'earned_points' => $breakdown['earned_points'] ?? 0,
            'assessment_points' => $breakdown['assessment_points'] ?? 0,
            'total_score' => $attempt->total_score !== null ? (float) $attempt->total_score : null,
            'questions' => app(PointsCalculationService::class)->questionBreakdown($attempt, $base),
            'puzzles' => $this->puzzleResults($attempt),
        ]);
    }

    /**
     * Tebak Gambar, box by box: which were right, never what was right.
     */
    private function puzzleResults(QuizAttempt $attempt): array
    {
        $answers = $attempt->userAnswers()->get()->keyBy('question_id');

        return $attempt->questionnaire->questions()
            ->where('type', QuestionType::PICTURE_PUZZLE->value)
            ->orderBy('order')
            ->get()
            ->map(function ($puzzle) use ($answers) {
                $saved = $answers[$puzzle->id] ?? null;
                $marked = \App\Services\PicturePuzzle::score($puzzle, \App\Services\PicturePuzzle::decode($saved?->answer));

                return [
                    'question_id' => $puzzle->id,
                    'correct' => $marked['correct'],
                    'total' => $marked['total'],
                    'points_earned' => $saved ? $marked['points'] : 0,
                    'slots' => collect(\App\Services\PicturePuzzle::slots($puzzle))
                        ->map(fn ($s) => ['key' => $s['key'], 'label' => $s['label'], 'correct' => $marked['slots'][$s['key']] ?? false])
                        ->values()->all(),
                ];
            })->values()->all();
    }

    /**
     * The team's saved answers keyed by question id, to fill the screen back in on a resume.
     *
     * Every type is the stored string, except Tebak Gambar, whose answer is its boxes: sent as an
     * object {box key: typed text} so the app never has to parse JSON out of a string field. These
     * are what the team typed — nothing here says whether any of it is right.
     */
    private function answersForClient(QuizAttempt $attempt, array $questions): array
    {
        $puzzleIds = collect($questions)
            ->where('type', QuestionType::PICTURE_PUZZLE->value)
            ->pluck('id')
            ->all();

        $answers = [];
        foreach ($attempt->userAnswers()->get() as $answer) {
            $answers[$answer->question_id] = in_array($answer->question_id, $puzzleIds, true)
                ? (object) \App\Services\PicturePuzzle::decode($answer->answer)
                : $answer->answer;
        }

        return $answers;
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
                // The photo is the answer; without it the session is not finished.
                'group_photo' => isset($answers[$q->id]) && filled($answers[$q->id]->answer) ? null : 'photo_not_taken',
                // Answered once any box holds something: empty boxes score nothing, but a team stuck
                // on two of ten is not held back — or pushed to type rubbish to get out.
                'picture_puzzle' => isset($answers[$q->id]) && \App\Services\PicturePuzzle::anyFilled($answers[$q->id]->answer)
                    ? null
                    : 'unanswered',
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

    /** Minutes after time-out a queued score may still arrive, if its PIN was accepted in time. */
    private const SCORE_GRACE_MINUTES = 15;

    /**
     * Whether a facilitator score may be delivered now. Inside the session: yes. After time-out: only
     * when verify-pin succeeded before the deadline and we are within SCORE_GRACE_MINUTES of it —
     * judged on the server's pin_verified_at, never a device-supplied time.
     *
     * @return bool true when accepted inside the grace window
     */
    private function assertCanDeliverScore(GameAssessment $assessment): bool
    {
        $attempt = $assessment->quizAttempt;
        if (! $attempt || $attempt->status === QuizAttempt::STATUS_ABANDONED) {
            throw QuizRuleException::submitted();
        }

        if (! $attempt->isTimeExpired()) {
            $this->assertSessionOpen($attempt);

            return false;
        }

        $deadline = $attempt->timerOrigin()?->copy()
            ?->addMinutes((int) $attempt->questionnaire->time_limit);
        $verifiedAt = $assessment->pin_verified_at;

        if ($deadline && $verifiedAt && $verifiedAt->lte($deadline) && now()->lte($deadline->copy()->addMinutes(self::SCORE_GRACE_MINUTES))) {
            return true;
        }

        throw QuizRuleException::timeExpired();
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

        $timerStart = $attempt->timerOrigin();

        if (! $timerStart) {
            return $attempt->questionnaire->time_limit * 60;
        }

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
