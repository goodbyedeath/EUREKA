<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\QrCodeScan;
use App\Models\Questionnaire;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserQuizController extends Controller
{
    /**
     * Start a new quiz
     */
    public function start($questionnaireId)
    {
        // Validate questionnaire exists
        $questionnaire = Questionnaire::where('id', $questionnaireId)
            ->where('is_active', true)
            ->first();

        if (!$questionnaire) {
            return redirect()->route('user.dashboard')
                ->with('error', 'Questionnaire not found');
        }

        // Check if available
        if (!$questionnaire->isAvailable()) {
            return redirect()->route('user.dashboard')
                ->with('error', 'This questionnaire is not currently available');
        }

        // Check max attempts
        if (!$questionnaire->canUserAttempt(Auth::id())) {
            return redirect()->route('user.dashboard')
                ->with('error', 'You have reached the maximum number of attempts for this quiz');
        }

        // Require the QR scan - see QrCodeScan::canUserStartQuestionnaire. Listing a quiz
        // is fine; opening one without having scanned its code is not.
        if (!QrCodeScan::canUserStartQuestionnaire(Auth::id(), $questionnaire->id)) {
            return redirect()->route('user.dashboard')
                ->with('error', 'Scan the QR code at this outpost to unlock the quiz');
        }

        // Check if user already has an active (started) attempt for this questionnaire
        $existingAttempt = QuizAttempt::where('user_id', Auth::id())
            ->where('questionnaire_id', $questionnaireId)
            ->where('status', QuizAttempt::STATUS_STARTED)
            ->whereNull('completed_at')
            ->orderBy('started_at', 'desc')
            ->first();

        \Log::info("UserQuizController::start - User " . Auth::id() . " accessing quiz {$questionnaireId}, existing attempt: " . ($existingAttempt ? $existingAttempt->id : 'none'));

        // If active attempt exists, use it instead of creating new one
        if ($existingAttempt) {
            \Log::info("Returning existing attempt {$existingAttempt->id} for user " . Auth::id());
            return view('user.quiz-take', [
                'questionnaireId' => null,
                'attemptId' => $existingAttempt->id,
                'questionnaire' => $questionnaire,
            ]);
        }

        \Log::info("Creating new attempt for user " . Auth::id() . " on quiz {$questionnaireId}");

        return view('user.quiz-take', [
            'questionnaireId' => $questionnaireId,
            'attemptId' => null,
            'questionnaire' => $questionnaire,
        ]);
    }

    /**
     * Continue existing quiz
     */
    public function continue($attemptId)
    {
        // Load attempt
        $attempt = QuizAttempt::with('questionnaire')
            ->where('id', $attemptId)
            ->where('user_id', Auth::id())
            ->first();

        if (!$attempt) {
            return redirect()->route('user.dashboard')
                ->with('error', 'Quiz attempt not found');
        }

        // Check if completed - redirect to results
        if ($attempt->isCompleted()) {
            return redirect()->route('quiz.results', ['attemptId' => $attempt->id]);
        }

        // Check if started
        if (!$attempt->isStarted()) {
            return redirect()->route('user.dashboard')
                ->with('error', 'This quiz attempt cannot be continued');
        }

        // Check timer expiry
        if ($attempt->questionnaire->time_limit) {
            // Carbon 3 returns a SIGNED diff, so now()->diffInSeconds($past) is negative
            // and this gate never fired — expired attempts stayed resumable. Measure
            // forward from the same origin calculateTimeRemaining() uses, so the gate
            // and the countdown shown to the team always agree.
            $timerStart = $attempt->timer_started_at ?? $attempt->started_at;
            $elapsed = $timerStart->diffInSeconds(now(), false);
            $timeLimit = $attempt->questionnaire->time_limit * 60;

            if ($elapsed >= $timeLimit) {
                return redirect()->route('user.dashboard')
                    ->with('warning', 'Quiz time has expired');
            }
        }

        return view('user.quiz-take', [
            'questionnaireId' => null,
            'attemptId' => $attemptId,
            'questionnaire' => $attempt->questionnaire,
        ]);
    }

    /**
     * Show quiz results
     */
    public function results($attemptId)
    {
        $attempt = QuizAttempt::with(['questionnaire.questions'])
            ->where('id', $attemptId)
            ->where('user_id', Auth::id())
            ->whereIn('status', ['completed', 'time_expired'])
            ->firstOrFail();

        return view('user.quiz-results', compact('attempt'));
    }
}
