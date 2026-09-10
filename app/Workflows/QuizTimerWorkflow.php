<?php

namespace App\Workflows;

use App\Models\QuizAttempt;
use App\Events\QuizTimeExpired;
use App\Events\QuizTimeWarning;
use Illuminate\Support\Facades\Log;
use Workflow\WorkflowStub;
use Workflow\Workflow;

class QuizTimerWorkflow extends Workflow
{
    /**
     * Execute the quiz timer workflow
     *
     * @param int $quizAttemptId
     * @param int $timeLimitMinutes
     * @return \Generator the workflow's final value is read with getReturn()
     */
    public function execute(int $quizAttemptId, int $timeLimitMinutes): \Generator
    {
        Log::info("Starting quiz timer workflow for attempt {$quizAttemptId} with {$timeLimitMinutes} minutes limit");

        $attempt = QuizAttempt::find($quizAttemptId);
        if (!$attempt) {
            Log::error("Quiz attempt {$quizAttemptId} not found");
            return 'Quiz attempt not found';
        }

        // If quiz is already completed, exit
        if ($attempt->completed_at) {
            Log::info("Quiz attempt {$quizAttemptId} already completed");
            return 'Quiz already completed';
        }

        $startTime = WorkflowStub::now();
        $totalSeconds = $timeLimitMinutes * 60;
        
        // Send 5-minute warning if quiz is longer than 10 minutes
        if ($timeLimitMinutes > 10) {
            $warningTime = $totalSeconds - 300; // 5 minutes before expiry
            yield WorkflowStub::timer($warningTime);
            
            // Check if quiz is still active
            $attempt->refresh();
            if (!$attempt->completed_at) {
                Log::info("Sending 5-minute warning for quiz attempt {$quizAttemptId}");
                $this->sendTimeWarning($quizAttemptId, 300); // 5 minutes remaining
                
                // Wait for remaining 5 minutes
                yield WorkflowStub::timer(300);
            }
        } else if ($timeLimitMinutes > 2) {
            // For shorter quizzes, send 2-minute warning
            $warningTime = $totalSeconds - 120; // 2 minutes before expiry
            yield WorkflowStub::timer($warningTime);
            
            $attempt->refresh();
            if (!$attempt->completed_at) {
                Log::info("Sending 2-minute warning for quiz attempt {$quizAttemptId}");
                $this->sendTimeWarning($quizAttemptId, 120); // 2 minutes remaining
                
                // Wait for remaining 2 minutes
                yield WorkflowStub::timer(120);
            }
        } else {
            // For very short quizzes, just wait the full time
            yield WorkflowStub::timer($totalSeconds);
        }

        // Final check - refresh attempt status
        $attempt->refresh();
        
        if ($attempt->completed_at) {
            Log::info("Quiz attempt {$quizAttemptId} completed before timeout");
            return 'Quiz completed within time limit';
        }

        // Time expired - auto-submit quiz
        Log::info("Quiz attempt {$quizAttemptId} timed out, auto-submitting");
        
        $endTime = WorkflowStub::now();
        // Same Carbon 3 signed-diff trap: end->diffInSeconds(start) is negative, so every
        // timed-out attempt recorded a negative elapsed time and broadcast it in QuizTimeExpired.
        $actualDuration = $startTime->diffInSeconds($endTime, false);
        
        // Auto-submit the quiz
        $this->autoSubmitQuiz($attempt, $actualDuration);
        
        return "Quiz {$quizAttemptId} auto-submitted due to timeout";
    }
    
    /**
     * Send time warning to user
     */
    private function sendTimeWarning(int $quizAttemptId, int $remainingSeconds): void
    {
        WorkflowStub::sideEffect(function () use ($quizAttemptId, $remainingSeconds) {
            $attempt = QuizAttempt::find($quizAttemptId);
            if ($attempt) {
                event(new QuizTimeWarning($attempt->user_id, $quizAttemptId, $remainingSeconds));
            }
        });
    }
    
    /**
     * Auto-submit quiz when time expires
     */
    private function autoSubmitQuiz(QuizAttempt $attempt, int $actualDuration): void
    {
        WorkflowStub::sideEffect(function () use ($attempt, $actualDuration) {
            try {
                // Calculate score based on current answers
                $totalQuestions = $attempt->quiz->questions()->count();
                $answeredQuestions = collect($attempt->answers ?? [])->filter()->count();
                $correctAnswers = 0;
                
                // Calculate score for answered questions
                foreach ($attempt->answers ?? [] as $questionId => $userAnswer) {
                    $question = $attempt->quiz->questions()->find($questionId);
                    if ($question && $question->correct_answer === $userAnswer) {
                        $correctAnswers++;
                    }
                }
                
                $score = $totalQuestions > 0 ? ($correctAnswers / $totalQuestions) * 100 : 0;
                
                // Update attempt with auto-submit data
                $attempt->update([
                    'completed_at' => now(),
                    'score' => $score,
                    'duration_seconds' => $actualDuration,
                    'auto_submitted' => true,
                    'submission_reason' => 'Time limit exceeded'
                ]);
                
                Log::info("Auto-submitted quiz attempt {$attempt->id} with score {$score}%");
                
                // Broadcast time expired event
                event(new QuizTimeExpired($attempt->user_id, $attempt->id, $score, $actualDuration));
                
            } catch (\Exception $e) {
                Log::error("Error auto-submitting quiz attempt {$attempt->id}: " . $e->getMessage());
            }
        });
    }
}