<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One team's run at one indoor venue: when they started, when they solved the opening
 * clue, and when they finished.
 *
 * The clock starts at the START scan and is read from `started_at` rather than counted
 * down on the device — a phone that sleeps, reloads or loses signal must not gain or lose
 * time, which is the same reason the quiz timer is server-derived.
 */
class RaceSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'indoor_map_id',
        'race_start_id',
        'started_at',
        'clue_solved_at',
        'finished_at',
        'clue_wrong_attempts',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'clue_solved_at' => 'datetime',
        'finished_at' => 'datetime',
        'clue_wrong_attempts' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function indoorMap(): BelongsTo
    {
        return $this->belongsTo(IndoorMap::class);
    }

    /**
     * Stop this team's clock once they have cleared every post that counts.
     *
     * Called after any attempt is saved as completed. Cheap enough to run each time —
     * two queries, and it returns immediately when there is no running session, which is
     * the case for every outdoor event and for every team that has already finished.
     *
     * "Every post that counts" is read from `counts_toward_finish`, never inferred: an
     * event with a bonus post or an optional one would otherwise never finish anybody.
     */
    public static function maybeFinishFor(?int $userId): void
    {
        if (! $userId) {
            return;
        }

        $session = self::where('user_id', $userId)
            ->whereNotNull('started_at')
            ->whereNull('finished_at')
            ->latest('started_at')
            ->first();

        if (! $session) {
            return;
        }

        $required = Questionnaire::where('is_active', true)
            ->where('counts_toward_finish', true)
            ->pluck('id');

        // Nothing required means nothing to finish; leaving the clock running is honest.
        if ($required->isEmpty()) {
            return;
        }

        $cleared = QuizAttempt::where('user_id', $userId)
            ->where('status', QuizAttempt::STATUS_COMPLETED)
            ->whereIn('questionnaire_id', $required)
            ->distinct()
            ->count('questionnaire_id');

        if ($cleared >= $required->count()) {
            $session->update(['finished_at' => now()]);
            \Illuminate\Support\Facades\Log::info('Race finished', [
                'user_id' => $userId,
                'race_session_id' => $session->id,
                'posts_required' => $required->count(),
            ]);
        }
    }

    public function hasStarted(): bool
    {
        return $this->started_at !== null;
    }

    public function clueSolved(): bool
    {
        return $this->clue_solved_at !== null;
    }

    /**
     * Seconds on the clock. Stops at finish; keeps running until then.
     */
    public function elapsedSeconds(): int
    {
        if (! $this->started_at) {
            return 0;
        }

        // Carbon 3 returns a SIGNED FLOAT. The direction here is right — past to future,
        // so positive — but it still has to be cast, and clamped in case a finish time
        // ever lands before a start. See the Carbon 3 note: now()->diffInSeconds($past)
        // is the natural phrasing and the wrong one.
        return (int) max(0, $this->started_at->diffInSeconds($this->finished_at ?? now()));
    }

    public function elapsedForHumans(): string
    {
        $s = $this->elapsedSeconds();

        return sprintf('%02d:%02d:%02d', intdiv($s, 3600), intdiv($s % 3600, 60), $s % 60);
    }
}
