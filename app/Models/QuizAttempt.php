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

    /**
     * Fill in the duration whenever an attempt is completed.
     *
     * There are several ways an attempt finishes — submit, auto-submit on timeout, the
     * facilitator's assessment form — and each used to compute the duration itself, so
     * one of them writing nothing (or a negative) was invisible until a report was run.
     * Doing it here means every path, including any added later, records the same number.
     */
    protected static function booted(): void
    {
        static::saving(function (self $attempt) {
            if ($attempt->status !== self::STATUS_COMPLETED || ! $attempt->started_at) {
                return;
            }

            // Respect a value a caller deliberately set, but never keep a nonsensical one.
            if ($attempt->total_time_seconds > 0) {
                return;
            }

            $end = $attempt->completed_at ?? now();

            // Past to future: Carbon 3 diffs are signed floats, and the reverse phrasing
            // silently yields a negative duration.
            $attempt->total_time_seconds = (int) max(0, $attempt->started_at->diffInSeconds($end));
        });

        static::saved(function (self $attempt) {
            if ($attempt->status === self::STATUS_COMPLETED
                && ($attempt->wasRecentlyCreated || $attempt->wasChanged('status'))) {
                RaceSession::maybeFinishFor($attempt->user_id);
            }
        });
    }

    /**
     * The race clock stops itself once the last counting post is cleared.
     *
     * Hooked here rather than in a controller because several paths finish an
     * attempt — submit, auto-submit on timeout, the facilitator's form — and a rule
     * about finishing should not depend on which one ran.
     */
    protected $fillable = [
        'user_id',
        'questionnaire_id',
        'started_at',
        'completed_at',
        'total_score',
        'total_time_seconds',
        'status',
        'verification_photo',
        'photo_captured_at',
        'timer_workflow_id',
        'timer_started_at',
        'auto_submitted',
        'submission_reason',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'photo_captured_at' => 'datetime',
            'timer_started_at' => 'datetime',
            'auto_submitted' => 'boolean',
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