<?php
namespace App\Models;

use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Questionnaire extends Model
{
    /**
     * The key /api/quiz/start caches this questionnaire under, for an hour.
     *
     * It lives here, next to the events that clear it, because nothing cleared it before:
     * an admin who switched a questionnaire off, moved its date window or corrected its
     * time limit on the morning of an event kept serving the old values to the app — and
     * kept letting teams start — for up to sixty minutes.
     */
    public static function apiCacheKey(int $id): string
    {
        return "questionnaire_{$id}";
    }

    protected static function booted(): void
    {
        static::saved(fn (self $q) => Cache::forget(self::apiCacheKey($q->id)));
        static::deleted(fn (self $q) => Cache::forget(self::apiCacheKey($q->id)));
    }

    protected $fillable = [
        'title',
        'description',
        'photo_path',
        'qr_code',
        'is_active',
        // False for a bonus post: it still scores, but does not hold the clock open.
        'counts_toward_finish',
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
            'counts_toward_finish' => 'boolean',
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