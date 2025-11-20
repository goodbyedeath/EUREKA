<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameAssessment extends Model
{
    protected $fillable = [
        'quiz_attempt_id',
        'question_id',
        'user_id',
        'assessed_by',
        'deposit',
        'penalty',
        'additional_points',
        'notes',
        'total_deposit',
        'is_assessed',
        'assessed_at',
        'facilitator_photo',
        'facilitator_photo_captured_at'
    ];

    protected function casts(): array
    {
        return [
            'deposit' => 'integer',
            'penalty' => 'integer',
            'additional_points' => 'integer',
            'total_deposit' => 'integer',
            'is_assessed' => 'boolean',
            'assessed_at' => 'datetime',
            'facilitator_photo_captured_at' => 'datetime'
        ];
    }

    public function quizAttempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assessedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }

    /**
     * Calculate and update total deposit
     */
    public function calculateTotalDeposit(): void
    {
        $this->total_deposit = $this->deposit - $this->penalty;
        $this->save();
    }

    /**
     * Mark assessment as completed
     */
    public function markAsAssessed(int $assessorId): void
    {
        $this->update([
            'is_assessed' => true,
            'assessed_by' => $assessorId,
            'assessed_at' => now()
        ]);
    }

    /**
     * Scope for assessed records
     */
    public function scopeAssessed($query)
    {
        return $query->where('is_assessed', true);
    }

    /**
     * Scope for pending assessments
     */
    public function scopePending($query)
    {
        return $query->where('is_assessed', false);
    }
}