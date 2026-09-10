<?php

namespace App\Services;

use App\Models\User;
use App\Models\QuizAttempt;
use App\Models\FeatureSetting;
use App\Workflows\UserSessionTimeoutWorkflow;
use App\Workflows\QuizTimerWorkflow;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Workflow\WorkflowStub;

class WorkflowTimerService
{
    /**
     * Start or restart session timeout workflow for user
     *
     * @param User $user
     * @param int|null $timeoutMinutes
     * @return void
     */
    public function startSessionTimer(User $user, ?int $timeoutMinutes = null): void
    {
        try {
            // Check if workflow timers are enabled
            if (!$this->isWorkflowTimersEnabled()) {
                Log::debug("Workflow timers disabled, skipping session timer for user {$user->id}");
                return;
            }

            // Only start session timers for regular users
            if ($user->role !== 'user') {
                Log::debug("User {$user->id} is not a regular user, skipping session timer");
                return;
            }

            // Use user's session timeout setting or default
            $timeout = $timeoutMinutes ?? $user->session_timeout ?? 5;
            
            // Cancel existing workflow if any
            $this->cancelSessionTimer($user);
            
            // Generate unique workflow ID
            $workflowId = "session-timer-{$user->id}";
            
            Log::info("Starting session timeout workflow for user {$user->id} with {$timeout} minutes timeout");
            
            // Start the workflow using WorkflowStub
            $workflow = WorkflowStub::make(UserSessionTimeoutWorkflow::class, $workflowId);
            $workflow->start($user->id, $timeout);
            
            // Update user's last activity
            $user->update([
                'last_activity_at' => now(),
                'session_workflow_id' => $workflowId
            ]);
            
        } catch (\Exception $e) {
            Log::error("Failed to start session timer for user {$user->id}: " . $e->getMessage());
        }
    }
    
    /**
     * Cancel session timeout workflow for user
     *
     * @param User $user
     * @return void
     */
    public function cancelSessionTimer(User $user): void
    {
        try {
            $workflowId = "session-timer-{$user->id}";
            
            // Try to cancel existing workflow
            try {
                $workflow = WorkflowStub::load($workflowId);
                $workflow->cancel();
                Log::info("Cancelled session timeout workflow for user {$user->id}");
            } catch (\Exception $e) {
                // Workflow might not exist or already completed
                Log::debug("Could not cancel session workflow for user {$user->id}: " . $e->getMessage());
            }
            
            // Clear workflow ID from user
            $user->update(['session_workflow_id' => null]);
            
        } catch (\Exception $e) {
            Log::error("Failed to cancel session timer for user {$user->id}: " . $e->getMessage());
        }
    }
    
    /**
     * Reset session timeout workflow for user (on activity)
     *
     * @param User $user
     * @return void
     */
    public function resetSessionTimer(User $user): void
    {
        try {
            // Update last activity time
            $user->update(['last_activity_at' => now()]);
            
            // Restart the session timer
            $this->startSessionTimer($user);
            
        } catch (\Exception $e) {
            Log::error("Failed to reset session timer for user {$user->id}: " . $e->getMessage());
        }
    }
    
    /**
     * Start quiz timer workflow
     *
     * @param QuizAttempt $attempt
     * @return void
     */
    public function startQuizTimer(QuizAttempt $attempt): void
    {
        try {
            if (!$this->isWorkflowTimersEnabled()) {
                Log::debug("Workflow timers disabled, skipping quiz timer for attempt {$attempt->id}");
                return;
            }

            $questionnaire = $attempt->questionnaire;
            if (!$questionnaire->time_limit || $questionnaire->time_limit <= 0) {
                Log::debug("Questionnaire {$questionnaire->id} has no time limit, skipping timer");
                return;
            }
            
            // Cancel existing timer if any
            $this->cancelQuizTimer($attempt);
            
            $workflowId = "quiz-timer-{$attempt->id}";
            
            Log::info("Starting quiz timer workflow for attempt {$attempt->id} with {$questionnaire->time_limit} minutes limit");
            
            // Start the workflow using WorkflowStub
            $workflow = WorkflowStub::make(QuizTimerWorkflow::class, $workflowId);
            $workflow->start($attempt->id, $questionnaire->time_limit);
            
            // Update attempt with workflow ID
            $attempt->update([
                'timer_workflow_id' => $workflowId,
                'timer_started_at' => now()
            ]);
            
        } catch (\Exception $e) {
            Log::error("Failed to start quiz timer for attempt {$attempt->id}: " . $e->getMessage());
        }
    }
    
    /**
     * Cancel quiz timer workflow
     *
     * @param QuizAttempt $attempt
     * @return void
     */
    public function cancelQuizTimer(QuizAttempt $attempt): void
    {
        try {
            $workflowId = "quiz-timer-{$attempt->id}";
            
            try {
                $workflow = WorkflowStub::load($workflowId);
                $workflow->cancel();
                Log::info("Cancelled quiz timer workflow for attempt {$attempt->id}");
            } catch (\Exception $e) {
                // Workflow might not exist or already completed
                Log::debug("Could not cancel quiz workflow for attempt {$attempt->id}: " . $e->getMessage());
            }
            
            // Clear workflow ID from attempt
            $attempt->update(['timer_workflow_id' => null]);
            
        } catch (\Exception $e) {
            Log::error("Failed to cancel quiz timer for attempt {$attempt->id}: " . $e->getMessage());
        }
    }
    
    /**
     * Get remaining time for quiz attempt
     *
     * @param QuizAttempt $attempt
     * @return array|null [remaining_seconds, total_seconds, percentage]
     */
    public function getQuizRemainingTime(QuizAttempt $attempt): ?array
    {
        try {
            if (!$attempt->timer_started_at || $attempt->completed_at) {
                return null;
            }
            
            $questionnaire = $attempt->questionnaire;
            $totalSeconds = $questionnaire->time_limit * 60;
            $elapsedSeconds = (int) $attempt->timer_started_at->diffInSeconds(now(), false);
            $remainingSeconds = (int) max(0, $totalSeconds - $elapsedSeconds);
            $percentage = $totalSeconds > 0 ? ($remainingSeconds / $totalSeconds) * 100 : 0;
            
            return [
                'remaining_seconds' => $remainingSeconds,
                'total_seconds' => $totalSeconds,
                'percentage' => round($percentage, 1),
                'elapsed_seconds' => $elapsedSeconds
            ];
            
        } catch (\Exception $e) {
            Log::error("Failed to get quiz remaining time for attempt {$attempt->id}: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get remaining time for user session
     *
     * @param User $user
     * @return array|null [remaining_seconds, total_seconds, percentage]
     */
    public function getSessionRemainingTime(User $user): ?array
    {
        try {
            if (!$user->last_activity_at || $user->role !== 'user') {
                return null;
            }
            
            $timeoutMinutes = $user->session_timeout ?? 5;
            $totalSeconds = $timeoutMinutes * 60;
            $elapsedSeconds = (int) $user->last_activity_at->diffInSeconds(now(), false);
            $remainingSeconds = (int) max(0, $totalSeconds - $elapsedSeconds);
            $percentage = $totalSeconds > 0 ? ($remainingSeconds / $totalSeconds) * 100 : 0;
            
            return [
                'remaining_seconds' => $remainingSeconds,
                'total_seconds' => $totalSeconds,
                'percentage' => round($percentage, 1),
                'elapsed_seconds' => $elapsedSeconds
            ];
            
        } catch (\Exception $e) {
            Log::error("Failed to get session remaining time for user {$user->id}: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Check if workflow timers are enabled via feature flags
     *
     * @return bool
     */
    private function isWorkflowTimersEnabled(): bool
    {
        try {
            // Check if feature setting exists and is enabled
            if (class_exists(FeatureSetting::class)) {
                return FeatureSetting::isEnabled('workflow_timers');
            }
            
            // Default to enabled if no feature settings
            return true;
            
        } catch (\Exception $e) {
            Log::warning("Could not check workflow timer feature flag: " . $e->getMessage());
            return true; // Default to enabled
        }
    }
    
    /**
     * Get workflow timer statistics
     *
     * @return array
     */
    public function getTimerStatistics(): array
    {
        try {
            return [
                'active_session_timers' => User::whereNotNull('session_workflow_id')->count(),
                'active_quiz_timers' => QuizAttempt::whereNotNull('timer_workflow_id')
                    ->whereNull('completed_at')
                    ->count(),
                'total_workflows' => DB::table('workflows')
                    ->where('class', 'like', '%Timer%')
                    ->where('status', 'running')
                    ->count(),
                'feature_enabled' => $this->isWorkflowTimersEnabled()
            ];
        } catch (\Exception $e) {
            Log::error("Failed to get timer statistics: " . $e->getMessage());
            return [];
        }
    }
}