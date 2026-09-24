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
        // 'all_posts' or 'time_limit' — which of the two endings happened. A stopped clock has to be
        // explainable to the team that asks why (operator, 24 Sep).
        'finished_reason',
        'clue_wrong_attempts',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'clue_solved_at' => 'datetime',
        'finished_at' => 'datetime',
        'clue_wrong_attempts' => 'integer',
    ];

    /** The START code this run began from — it carries the time limit. */
    public function raceStart(): BelongsTo
    {
        return $this->belongsTo(RaceStart::class);
    }

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

        // A team, not an account (operator, 23 Sep: ten posts played and the app never closed).
        // A team may hold more than one phone — Outpost Access opens a post for every account of
        // the team, and the score is the team's — so posts cleared on the second phone have to
        // count towards the same finish. Counting per account left a team that split the route
        // across two phones with a clock that could never stop.
        $teamId = User::where('id', $userId)->value('team_id');
        $accountIds = $teamId
            ? User::where('team_id', $teamId)->pluck('id')
            : collect([$userId]);

        $sessions = self::whereIn('user_id', $accountIds)
            ->whereNotNull('started_at')
            ->whereNull('finished_at')
            ->get();

        if ($sessions->isEmpty()) {
            return;
        }

        // Stations, not questionnaires: one questionnaire may be played at several posts, and then
        // clearing it once is not clearing the event. See StationProgress.
        $progress = app(\App\Services\StationProgress::class);
        $required = $progress->required();

        // Nothing required means nothing to finish; leaving the clock running is honest.
        if ($required->isEmpty()) {
            return;
        }

        $planId = $sessions->sortByDesc('started_at')->first()->indoor_map_id;
        $needed = $progress->requiredCount($planId, $required);
        $cleared = $progress->clearedCount($accountIds, $required);

        if ($cleared >= $needed) {
            // Every phone the team is holding stops at once; one left running would go on
            // reporting an elapsed time that no longer means anything.
            self::whereIn('id', $sessions->pluck('id'))
                ->update(['finished_at' => now(), 'finished_reason' => self::REASON_ALL_POSTS]);
            \Illuminate\Support\Facades\Log::info('Race finished', [
                'user_id' => $userId,
                'team_id' => $teamId,
                'race_session_ids' => $sessions->pluck('id')->all(),
                'posts_required' => $needed,
            ]);
        }
    }

    /** The two ways a race ends. */
    public const REASON_ALL_POSTS = 'all_posts';
    public const REASON_TIME_LIMIT = 'time_limit';

    /**
     * When this run must stop, or null when its START code sets no limit.
     *
     * The limit belongs to the code that started the run, so each rotation can be timed differently
     * and a code edited afterwards changes the deadline of the runs it started — deliberately: the
     * crew adjusts one field rather than every team.
     */
    public function deadlineAt(): ?\Illuminate\Support\Carbon
    {
        $minutes = $this->raceStart?->time_limit_minutes;

        return $this->started_at && $minutes ? $this->started_at->copy()->addMinutes((int) $minutes) : null;
    }

    /** The deadline has passed. True whether or not anyone has stamped `finished_at` yet. */
    public function isOverTime(): bool
    {
        $deadline = $this->deadlineAt();

        return $deadline !== null && $deadline->isPast();
    }

    /**
     * Stamp the finish for a run whose time has run out, at the deadline rather than at the moment
     * anybody noticed — a team's official time must not depend on when its phone next polled.
     *
     * Every running session of the same team stops together, as when the last post is cleared.
     *
     * @return bool whether this call closed it
     */
    public function finishIfOverTime(): bool
    {
        if ($this->finished_at !== null || ! $this->isOverTime()) {
            return false;
        }

        $deadline = $this->deadlineAt();
        $teamId = User::where('id', $this->user_id)->value('team_id');
        $accountIds = $teamId
            ? User::where('team_id', $teamId)->pluck('id')
            : collect([$this->user_id]);

        static::whereIn('user_id', $accountIds)
            ->where('indoor_map_id', $this->indoor_map_id)
            ->whereNotNull('started_at')
            ->whereNull('finished_at')
            ->update(['finished_at' => $deadline, 'finished_reason' => self::REASON_TIME_LIMIT]);

        $this->forceFill(['finished_at' => $deadline, 'finished_reason' => self::REASON_TIME_LIMIT]);

        \Illuminate\Support\Facades\Log::info('Race finished (time limit)', [
            'user_id' => $this->user_id, 'team_id' => $teamId,
            'race_session_id' => $this->id, 'deadline' => $deadline->toIso8601String(),
        ]);

        return true;
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
        // Stops at the finish, and never runs past the deadline: a phone that polls late must not
        // report more time than the race allowed.
        $end = $this->finished_at;

        if ($end === null) {
            $deadline = $this->deadlineAt();
            $end = $deadline !== null && $deadline->isPast() ? $deadline : now();
        }

        return (int) max(0, $this->started_at->diffInSeconds($end));
    }

    public function elapsedForHumans(): string
    {
        $s = $this->elapsedSeconds();

        return sprintf('%02d:%02d:%02d', intdiv($s, 3600), intdiv($s % 3600, 60), $s % 60);
    }
}
