<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizAttempt extends Model
{
    // Status constants
    public const STATUS_STARTED = 'started';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_ABANDONED = 'abandoned';

    protected $fillable = [
        'user_id',
        'questionnaire_id',
        'started_at',
        'completed_at',
        'total_score',
        'total_time_seconds',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'total_score' => 'integer',
            'total_time_seconds' => 'integer',
        ];
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function questionnaire(): BelongsTo
    {
        return $this->belongsTo(Questionnaire::class);
    }

    public function userAnswers(): HasMany
    {
        return $this->hasMany(UserAnswer::class);
    }

    public function gameAssessments(): HasMany
    {
        return $this->hasMany(GameAssessment::class);
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeStarted($query)
    {
        return $query->where('status', self::STATUS_STARTED);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Helper methods
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isStarted(): bool
    {
        return $this->status === self::STATUS_STARTED;
    }

    public function isAbandoned(): bool
    {
        return $this->status === self::STATUS_ABANDONED;
    }

    public function getDurationInSeconds(): int
    {
        return $this->total_time_seconds;
    }

    public function getFormattedDuration(): string
    {
        $seconds = $this->total_time_seconds;
        
        if ($seconds < 60) {
            return $seconds . ' seconds';
        }
        
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        
        if ($minutes < 60) {
            return $minutes . 'm ' . $remainingSeconds . 's';
        }
        
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        
        return $hours . 'h ' . $remainingMinutes . 'm ' . $remainingSeconds . 's';
    }

    public function getScorePercentage(): float
    {
        if (!$this->questionnaire || !$this->questionnaire->total_points) {
            return 0;
        }
        
        // Get base points for this user's team
        $basePoints = $this->getBasePoints();
        
        // Calculate earned points (total_score - base points)
        $earnedPoints = max(0, $this->total_score - $basePoints);
        
        // Calculate percentage based on earned points vs possible points
        return round(($earnedPoints / $this->questionnaire->total_points) * 100, 1);
    }
    
    public function getBasePoints(): int
    {
        // Base points from user's team initial points
        if (!$this->user || !$this->user->team) {
            return 1000; // Default base points
        }
        
        return $this->user->team->initial_points ?? 1000;
    }

    /**
     * Check if answers can be edited (only allowed if quiz is not completed)
     */
    public function canEditAnswers(): bool
    {
        return $this->status === self::STATUS_STARTED;
    }

    /**
     * Check if quiz can be submitted (only if started and not already completed)
     */
    public function canSubmit(): bool
    {
        return $this->status === self::STATUS_STARTED;
    }
}