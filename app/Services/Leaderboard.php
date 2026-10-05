<?php

namespace App\Services;

use App\Models\GameLocationUnlock;
use App\Models\RaceSession;
use App\Models\Team;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * The board every screen in the venue reads — computed once, not once per screen.
 *
 * `KioskController::leaderboard()` built this per request, and `teamScore()` inside the loop was six
 * queries per team: ~3,000 per poll at 500 teams, multiplied by the LED board, the leaderboard
 * screen and every kiosk in the room (SCALING-PLAN.md §3b). Two changes fix it. The scoring moved to
 * `PointsCalculationService::teamScores()`, which groups instead of looping, and the finished board
 * is cached for a few seconds so the tenth screen of the same second costs nothing.
 *
 * **The clock still ticks.** A cached board would freeze the elapsed time for the length of the TTL
 * and the big screen would jump in 10-second steps, so the cached row carries the three instants the
 * clock is made of and `withLiveClocks()` re-reads it per request through
 * `RaceSession::elapsedBetween()`. No query, same arithmetic, and the ranking it was sorted by stays
 * correct: two running teams on equal points are separated by their start times, and that gap does
 * not change while they run.
 *
 * **A score correction appears at once.** Waiting out a TTL is wrong when the crew has just fixed a
 * number by hand and is standing in front of the screen, so every write that can move a score bumps
 * a version through `bump()` and the next read computes afresh.
 */
class Leaderboard
{
    /** Bumped on any write that moves a score; the board's cache key is built from it. */
    private const VERSION_KEY = 'kiosk:leaderboard:version';

    /**
     * The board, from cache when it is warm.
     *
     * @return array{teams: list<array<string, mixed>>, computed_at: string}
     */
    public function board(): array
    {
        $ttl = (int) config('kiosk.leaderboard_ttl', 10);

        $board = $ttl > 0
            ? Cache::remember($this->cacheKey(), $ttl, fn () => $this->compute())
            : $this->compute();

        return $this->withLiveClocks($board);
    }

    /**
     * Mark the board stale. Called from the writes that can change a score — an answer graded, a
     * facilitator's assessment, a tapped object, a correction by hand, a clock started or stopped.
     *
     * Deliberately a timestamp rather than an increment: a cache that has evicted the version key
     * answers with the default, which is a miss and a recompute, where a failed increment would
     * have gone on serving a stale board.
     */
    public static function bump(): void
    {
        Cache::forever(self::VERSION_KEY, (string) now()->getTimestampMs());
    }

    private function cacheKey(): string
    {
        return 'kiosk:leaderboard:'.Cache::get(self::VERSION_KEY, '0');
    }

    /**
     * The whole board, ranked. Five queries for the scores plus three for the rest, whatever the
     * number of teams.
     *
     * @return array{teams: list<array<string, mixed>>, computed_at: string}
     */
    public function compute(): array
    {
        // The race clock, newest run per account. Indoor events start it at the START scan; an
        // outdoor event simply has none, and the field stays null.
        //
        // Ordered ASCENDING on purpose: keyBy() lets a later row overwrite an earlier one with the
        // same key, so the *last* session read is the one kept. Sorting descending — which reads as
        // "newest first" — therefore keeps the OLDEST run, and a team that restarted an aborted run
        // would show a stale, already-finished clock on the big screen for the rest of the event.
        //
        // raceStart is eager loaded because deadlineAt() reads its time limit: without this, every
        // clock on the board is one more query, which is the same mistake one level down.
        $races = RaceSession::whereNotNull('started_at')
            ->with('raceStart:id,time_limit_minutes')
            ->orderBy('started_at')
            ->orderBy('id')
            ->get()
            ->keyBy('user_id');

        // Indoor 'position': GPS cannot see through a roof, so the outpost the crew has opened for a
        // team is what tells the big screen where that team is.
        $unlocks = GameLocationUnlock::whereNull('revoked_at')
            ->whereNotNull('granted_at')
            ->with('gameLocation:id,name')
            ->get()
            ->groupBy('user_id');

        $teams = Team::select(['id', 'name', 'points', 'initial_points', 'department', 'created_at'])
            // users = the team's login accounts; race sessions and outpost unlocks both hang off
            // those, and indoors they are the only signal of where a team is.
            ->with(['members:id,team_id,name,is_leader', 'users:id,team_id,name'])
            ->get();

        // One grouped pass for every team's score, instead of six queries per team.
        $scores = app(PointsCalculationService::class)->teamScores($teams);

        $rows = $teams
            ->map(function (Team $team) use ($races, $unlocks, $scores) {
                return [
                    'id' => $team->id,
                    'name' => $team->name,
                    // The same number /admin/user-progress and the team's own app show.
                    'points' => (float) $scores[$team->id]['total'],
                    'initial_points' => (float) $team->initial_points,
                    'department' => $team->department,
                    'member_count' => $team->members->count(),
                    'leader' => $team->members->where('is_leader', true)->first()?->name,
                    'members' => $team->members->pluck('name')->toArray(),
                    'created_at' => $team->created_at->format('Y-m-d H:i:s'),

                    // --- added 2026-09-07, so the big screen reflects the indoor build ---

                    // Elapsed run time. Read from the server's instants rather than counted on the
                    // screen, so a kiosk that reloads shows the same number.
                    'race' => $this->raceBlock($team, $races),

                    // Which outposts the crew currently has open for this team.
                    'at_outposts' => $this->outpostsFor($team, $unlocks),
                ];
            })
            ->pipe(fn ($rows) => $this->rank($rows));

        return [
            'teams' => $rows->all(),
            'computed_at' => now()->toIso8601String(),
        ];
    }

    /** @return array<string, mixed>|null */
    private function raceBlock(Team $team, $races): ?array
    {
        foreach ($team->users as $u) {
            if ($r = $races->get($u->id)) {
                return [
                    'started_at' => $r->started_at->toIso8601String(),
                    'elapsed_seconds' => $r->elapsedSeconds(),
                    'elapsed' => $r->elapsedForHumans(),
                    'finished' => $r->finished_at !== null,
                    'finished_at' => $r->finished_at?->toIso8601String(),
                    // 'all_posts' | 'time_limit' | 'manual' | null while it runs. The board shows
                    // the time; this says whether it is a finishing time.
                    'finished_reason' => $r->finished_reason,
                    // Not for display: what withLiveClocks() needs to carry the clock forward
                    // without touching the database again.
                    'deadline_at' => $r->deadlineAt()?->toIso8601String(),
                ];
            }
        }

        return null;                      // outdoor, or not started yet
    }

    /** @return list<array{id:int, name:string}> */
    private function outpostsFor(Team $team, $unlocks): array
    {
        $out = [];

        foreach ($team->users as $u) {
            foreach ($unlocks->get($u->id, collect()) as $row) {
                if ($row->gameLocation) {
                    $out[] = ['id' => $row->gameLocation->id, 'name' => $row->gameLocation->name];
                }
            }
        }

        return $out;
    }

    /**
     * Sort, then number.
     *
     * The winner, when two teams have the same score, is the one that got there faster (operator,
     * 25 Sep). In order: more points; then a team that has finished ahead of one still running,
     * because an unfinished clock is not a finishing time; then the shorter clock; then the name, so
     * the board never flickers between two rows that are equal in every way.
     *
     * The rank is worked out here, once, so the LED board and the leaderboard screen cannot
     * disagree: equal score AND equal time share a rank.
     */
    private function rank($rows)
    {
        $rank = 0;
        $previous = null;

        return $rows
            ->sortBy([
                fn ($a, $b) => $b['points'] <=> $a['points'],
                fn ($a, $b) => (int) ! ($a['race']['finished'] ?? false) <=> (int) ! ($b['race']['finished'] ?? false),
                fn ($a, $b) => ($a['race']['elapsed_seconds'] ?? PHP_INT_MAX) <=> ($b['race']['elapsed_seconds'] ?? PHP_INT_MAX),
                fn ($a, $b) => strcmp($a['name'], $b['name']),
            ])
            ->values()
            ->map(function ($team, $i) use (&$rank, &$previous) {
                $key = [$team['points'], $team['race']['finished'] ?? false, $team['race']['elapsed_seconds'] ?? null];
                if ($key !== $previous) {
                    $rank = $i + 1;
                    $previous = $key;
                }
                $team['rank'] = $rank;

                return $team;
            })
            ->values();
    }

    /**
     * Re-read every running clock from the instants the cached board carries.
     *
     * A finished run is already final and is left exactly as it was computed. Pure arithmetic: no
     * query, and no second definition of "elapsed" — RaceSession owns that.
     *
     * @param  array{teams: list<array<string, mixed>>, computed_at: string}  $board
     * @return array{teams: list<array<string, mixed>>, computed_at: string}
     */
    private function withLiveClocks(array $board): array
    {
        foreach ($board['teams'] as $i => $team) {
            $race = $team['race'] ?? null;

            if ($race === null || ($race['finished'] ?? false)) {
                continue;
            }

            $seconds = RaceSession::elapsedBetween(
                Carbon::parse($race['started_at']),
                null,
                isset($race['deadline_at']) ? Carbon::parse($race['deadline_at']) : null,
            );

            $board['teams'][$i]['race']['elapsed_seconds'] = $seconds;
            $board['teams'][$i]['race']['elapsed'] = RaceSession::clock($seconds);
        }

        return $board;
    }
}
