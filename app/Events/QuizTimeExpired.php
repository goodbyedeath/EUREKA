<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuizTimeExpired implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userId;
    public $quizAttemptId;
    public $finalScore;
    public $duration;
    public $timestamp;

    /**
     * Create a new event instance.
     */
    public function __construct(int $userId, int $quizAttemptId, float $finalScore, int $duration)
    {
        $this->userId = $userId;
        $this->quizAttemptId = $quizAttemptId;
        $this->finalScore = $finalScore;
        $this->duration = $duration;
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
        return 'quiz.time.expired';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        $durationMinutes = floor($this->duration / 60);
        $durationSeconds = $this->duration % 60;
        
        return [
            'quiz_attempt_id' => $this->quizAttemptId,
            'final_score' => $this->finalScore,
            'duration_seconds' => $this->duration,
            'duration_display' => $durationMinutes . ':' . str_pad($durationSeconds, 2, '0', STR_PAD_LEFT),
            'message' => 'Time expired! Quiz automatically submitted.',
            'timestamp' => $this->timestamp,
            'action' => 'auto_submit',
            'redirect_url' => route('quiz.results', $this->quizAttemptId)
        ];
    }
}
