<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Questionnaire extends Model
{
    protected $fillable = [
        'title',
        'description',
        'qr_code',
        'is_active',
        'time_limit',
        'created_by',
        'start_date',
        'end_date',
        'max_attempts',
        'pass_percentage',
        'total_points',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'time_limit' => 'integer',
            'max_attempts' => 'integer',
            'pass_percentage' => 'decimal:2',
            'total_points' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('order');
    }

    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWithQuestions($query)
    {
        return $query->whereHas('questions');
    }

    public function scopeByQrCode($query, $qrCode)
    {
        return $query->where('qr_code', $qrCode);
    }

    // Accessors
    public function getQrCodeUrlAttribute(): string
    {
        return route('quiz.start', ['questionnaireId' => $this->id]);
    }

    // Helper methods
    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function hasQuestions(): bool
    {
        return $this->questions()->exists();
    }

    public function isAvailable(): bool
    {
        $now = now();
        
        if (!$this->is_active) {
            return false;
        }

        if ($this->start_date && $now->lt($this->start_date)) {
            return false;
        }

        if ($this->end_date && $now->gt($this->end_date)) {
            return false;
        }

        return true;
    }

    public function getUserAttemptCount($userId): int
    {
        return $this->quizAttempts()
            ->where('user_id', $userId)
            ->where('status', QuizAttempt::STATUS_COMPLETED)
            ->count();
    }

    public function canUserAttempt($userId): bool
    {
        if (!$this->max_attempts) {
            return true;
        }

        return $this->getUserAttemptCount($userId) < $this->max_attempts;
    }

    public function getUserInProgressAttempt($userId): ?QuizAttempt
    {
        return $this->quizAttempts()
            ->where('user_id', $userId)
            ->where('status', QuizAttempt::STATUS_STARTED)
            ->first();
    }

    public function hasUserCompleted($userId): bool
    {
        return $this->quizAttempts()
            ->where('user_id', $userId)
            ->where('status', QuizAttempt::STATUS_COMPLETED)
            ->exists();
    }

    public function getUserCompletedAttempts($userId)
    {
        return $this->quizAttempts()
            ->where('user_id', $userId)
            ->completed()
            ->get();
    }

    public function getUserBestScore($userId): ?int
    {
        return $this->quizAttempts()
            ->where('user_id', $userId)
            ->completed()
            ->max('total_score');
    }

    public function getUserAverageScore($userId): ?float
    {
        return $this->quizAttempts()
            ->where('user_id', $userId)
            ->completed()
            ->avg('total_score');
    }
}