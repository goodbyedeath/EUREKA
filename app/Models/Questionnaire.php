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
        // The post a team must check in at before this questionnaire opens (App\Services\StationGate).
        'venue_mode',
        'quest_location_id',
        'game_location_id',
        // True: openable at every active post, including ones added later. See posts().
        'all_posts',
        'time_limit',
        'created_by',
        'start_date',
        'end_date',
        'max_attempts',
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
            'all_posts' => 'boolean',
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

    /** The extra posts this questionnaire may also be opened at, beyond `game_location_id`. */
    public function extraPosts(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(GameLocation::class);
    }

    /**
     * Every post this questionnaire may be opened at, newest rule first.
     *
     * One questionnaire, many posts: the two floor plans walk the same booths in a different order,
     * and some questionnaires (a group photo, a closing question) belong at all of them. `all_posts`
     * takes in posts created later too, which a list of links cannot.
     *
     * Outdoor questionnaires are unaffected — their post is the quest location.
     *
     * @return list<int>
     */
    public function postIds(): array
    {
        if ($this->venue_mode !== 'indoor') {
            return [];
        }

        if ($this->all_posts) {
            return GameLocation::where('is_active', true)->pluck('id')->all();
        }

        return collect([$this->game_location_id])
            ->merge($this->relationLoaded('extraPosts')
                ? $this->extraPosts->pluck('id')
                : $this->extraPosts()->pluck('game_locations.id'))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /** Whether this questionnaire is opened at more than one post — the case the post-scoped rules exist for. */
    public function servesManyPosts(): bool
    {
        return $this->all_posts || count($this->postIds()) > 1;
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

    /**
     * Completed attempts by this team.
     *
     * A questionnaire shared by several posts counts per post, so `max_attempts: 1` means once at
     * each post rather than once in the whole event — otherwise sharing it would leave every post
     * after the first with nothing to play. A questionnaire with one post counts as it always did,
     * including the rows from before attempts recorded a post at all.
     */
    public function getUserAttemptCount($userId, ?int $postId = null): int
    {
        return $this->quizAttempts()
            ->where('user_id', $userId)
            ->when($postId && $this->servesManyPosts(), fn ($q) => $q->where('game_location_id', $postId))
            ->where('status', QuizAttempt::STATUS_COMPLETED)
            ->count();
    }

    public function canUserAttempt($userId, ?int $postId = null): bool
    {
        if (!$this->max_attempts) {
            return true;
        }

        return $this->getUserAttemptCount($userId, $postId) < $this->max_attempts;
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

    /** Outdoor post: the team checks in here by GPS. */
    public function questLocation(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(QuestLocation::class);
    }

    /** Indoor post: the crew opens it for the team on Outpost Access. */
    public function gameLocation(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(GameLocation::class);
    }
}