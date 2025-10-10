<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuizTimeWarning implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userId;
    public $quizAttemptId;
    public $remainingSeconds;
    public $timestamp;

    /**
     * Create a new event instance.
     */
    public function __construct(int $userId, int $quizAttemptId, int $remainingSeconds)
    {
        $this->userId = $userId;
        $this->quizAttemptId = $quizAttemptId;
        $this->remainingSeconds = $remainingSeconds;
        $this->timestamp = now()->toISOString();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.quiz.' . $this->userId),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'quiz.time.warning';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        $minutes = floor($this->remainingSeconds / 60);
        $seconds = $this->remainingSeconds % 60;
        
        return [
            'quiz_attempt_id' => $this->quizAttemptId,
            'remaining_seconds' => $this->remainingSeconds,
            'remaining_minutes' => $minutes,
            'display_time' => $minutes . ':' . str_pad($seconds, 2, '0', STR_PAD_LEFT),
            'message' => $minutes > 0 
                ? "Warning: {$minutes} minute(s) remaining!" 
                : "Warning: {$this->remainingSeconds} seconds remaining!",
            'timestamp' => $this->timestamp,
            'urgency' => $this->remainingSeconds <= 60 ? 'critical' : 'warning'
        ];
    }
}
