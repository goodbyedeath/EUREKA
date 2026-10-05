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
                self::closeFinishedPost($attempt);
                RaceSession::maybeFinishFor($attempt->user_id);
            }
        });
    }

    /**
     * A post whose questionnaire has just been answered is closed for that team.
     *
     * Operator, 23 Sep: a finished post must not be openable again from the floor plan. Revoking
     * the access is the part that binds — the AR gate then answers `awaiting_unlock` and the
     * station gate `checkin_required`, whatever the app has cached — while `is_done` on
     * `/indoor-map` is how the team sees it. Indoor only: outdoor posts are governed by the GPS
     * radius, which is not ours to revoke.
     *
     * The crew can always reopen it in Outpost Access; the row is revoked, never deleted.
     */
    private static function closeFinishedPost(self $attempt): void
    {
        $questionnaire = $attempt->questionnaire ?? Questionnaire::find($attempt->questionnaire_id);

        if (! $questionnaire || $questionnaire->venue_mode !== 'indoor') {
            return;
        }

        // The post it was played at; older attempts carry none, so fall back to the questionnaire's.
        $postId = (int) ($attempt->game_location_id ?: $questionnaire->game_location_id);

        if (! $postId) {
            return;
        }

        // A post may carry more than one questionnaire. Closing it on the first would lock the team
        // out of the second, so it closes only when everything there has been answered.
        $teamId = User::where('id', $attempt->user_id)->value('team_id');
        $accountIds = $teamId
            ? User::where('team_id', $teamId)->pluck('id')
            : collect([$attempt->user_id]);

        if (app(\App\Services\StationProgress::class)->finishedPosts($accountIds)->contains($postId)) {
            GameLocationUnlock::closeForTeamAt($postId, (int) $attempt->user_id);
        }
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
        // Named by the phone that made it, so a retried offline upload finds this attempt
        // instead of starting a second one. Null for everything started online.
        'client_token',
        'started_offline',
        // The post this attempt was played at. Only meaningful for a questionnaire shared by
        // several posts, where it is what keeps the plays apart; null everywhere else.
        'game_location_id',
        'started_at',
        'completed_at',
        'total_score',
        'total_time_seconds',
        'status',
        'verification_photo',
        'photo_captured_at',
        // Why a hand-in has no photo: no_camera | permission_denied | capture_timeout | hardware_error.
        // Set only when the client could not take one at all (client report #45); the admin sees it.
        'photo_missing_reason',
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
            return 0; // No team, no starting balance
        }
        
        return $this->user->team->initial_points ?? 0;
    }

    /**
     * Check if answers can be edited (only allowed if quiz is not completed)
     */
    /**
     * Where this attempt's clock is measured from, or null when no clock has started.
     *
     * Both columns are nullable, so every caller has to answer the same question; when each
     * answered it inline, the ones that forgot the null crashed the endpoint outright instead of
     * letting the team carry on working.
     */
    public function timerOrigin(): ?\Carbon\CarbonInterface
    {
        return $this->timer_started_at ?? $this->started_at;
    }

    /** True once a timed attempt's clock has run out. Untimed attempts never expire. */
    public function isTimeExpired(): bool
    {
        $limit = $this->questionnaire?->time_limit;
        if (! $limit) {
            return false;
        }
        $start = $this->timerOrigin();

        return $start !== null && $start->diffInSeconds(now(), false) >= $limit * 60;
    }

    /**
     * The user's live question session — started, unfinished, clock still running — at a station
     * other than $exceptQuestionnaireId. Operator rule: a session is left only by finishing it or
     * by running out of time, so while one is live no other station may be opened.
     */
    public static function liveSessionFor(int $userId, ?int $exceptQuestionnaireId = null): ?self
    {
        return static::with('questionnaire:id,title,time_limit')
            ->where('user_id', $userId)
            ->when($exceptQuestionnaireId, fn ($q) => $q->where('questionnaire_id', '!=', $exceptQuestionnaireId))
            ->where('status', self::STATUS_STARTED)
            ->whereNull('completed_at')
            ->orderByDesc('started_at')
            ->get()
            ->first(fn (self $a) => $a->questionnaire !== null && ! $a->isTimeExpired());
    }

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