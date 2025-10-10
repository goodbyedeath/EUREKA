<?php

namespace App\Workflows;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Workflow\WorkflowStub;
use Workflow\Workflow;

class UserSessionTimeoutWorkflow extends Workflow
{
    /**
     * Execute the user session timeout workflow
     *
     * @param int $userId
     * @param int $timeoutMinutes
     * @return string
     */
    public function execute(int $userId, int $timeoutMinutes = 5): string
    {
        Log::info("Starting session timeout workflow for user {$userId} with {$timeoutMinutes} minutes timeout");

        // Get the current time at workflow start
        $startTime = WorkflowStub::now();
        
        // Convert minutes to seconds
        $timeoutSeconds = $timeoutMinutes * 60;
        
        // Wait for the timeout period
        yield WorkflowStub::timer($timeoutSeconds);
        
        // Check if user is still active and logged in
        $user = User::find($userId);
        if (!$user) {
            Log::warning("User {$userId} not found during session timeout check");
            return 'User not found';
        }
        
        // Check last activity time
        $lastActivityTime = $user->last_activity_at ?? $user->updated_at;
        $currentTime = WorkflowStub::now();
        $timeSinceLastActivity = $currentTime->diffInSeconds($lastActivityTime);
        
        Log::info("User {$userId} last activity was {$timeSinceLastActivity} seconds ago");
        
        // If user was active recently (within the timeout period), restart the timer
        if ($timeSinceLastActivity < $timeoutSeconds) {
            Log::info("User {$userId} was active recently, restarting session timer");
            
            // Calculate remaining time and restart
            $remainingTime = $timeoutSeconds - $timeSinceLastActivity;
            $newTimeoutMinutes = max(1, ceil($remainingTime / 60));
            
            // Continue with new timeout using continueAsNew
            yield WorkflowStub::continueAsNew($userId, $newTimeoutMinutes);
        }
        
        // User has been inactive - proceed with logout
        Log::info("User {$userId} session timed out due to inactivity");
        
        // Update user session status
        $user->update([
            'session_expired_at' => $currentTime,
            'remember_token' => null // Invalidate remember token
        ]);
        
        // Trigger session expiry event
        $this->triggerSessionExpiry($userId);
        
        return "User {$userId} session expired due to inactivity";
    }
    
    /**
     * Trigger session expiry event for UI updates
     */
    private function triggerSessionExpiry(int $userId): void
    {
        // Dispatch session expiry event for real-time UI updates
        WorkflowStub::sideEffect(function () use ($userId) {
            event(new \App\Events\SessionExpired($userId));
        });
    }
}