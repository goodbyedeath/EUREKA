<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\UserQuestCheckpoint;
use Illuminate\Support\Facades\DB;

class KioskController extends Controller
{
    /**
     * The leaderboard every kiosk and the LED board read.
     *
     * The computation moved to App\Services\Leaderboard, where it is done once for the whole venue
     * and cached for a few seconds (SCALING-PLAN.md §3b). It used to be built here per request, with
     * six scoring queries per team inside the loop — ~3,000 queries a poll at 500 teams, times every
     * screen in the room. Ranking, the tie-break and the live clock all live in that service now;
     * this method's job is the envelope.
     */
    public function leaderboard()
    {
        try {
            $board = app(\App\Services\Leaderboard::class)->board();
            $teams = collect($board['teams']);

            return response()->json([
                'success' => true,
                'data' => [
                    'teams' => $teams,
                    'total_teams' => $teams->count(),
                    'updated_at' => now()->format('Y-m-d H:i:s'),
                    // When the scores themselves were last worked out, as opposed to when this
                    // request was served. A screen showing a number nobody can reproduce is
                    // usually a screen reading a board computed seconds ago.
                    'computed_at' => $board['computed_at'],
                    'highest_score' => $teams->first()['points'] ?? 0,
                    'lowest_score' => $teams->last()['points'] ?? 0,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch leaderboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Where each team was last seen, for the kiosk map.
     *
     * Two N+1s used to live here: a checkpoint query per team and a six-query score per team
     * (SCALING-PLAN.md §3b). Both are now grouped — the latest check-in per team in two queries,
     * the scores in five for the whole event — so this costs the same whether there are five teams
     * or five hundred.
     */
    public function locations()
    {
        try {
            $teams = Team::select(['id', 'name', 'points', 'initial_points', 'department'])
                ->with(['members:id,team_id,name,is_leader', 'users:id,team_id'])
                ->get();

            $checkpoints = $this->latestCheckpointPerTeam($teams);
            $scores = app(\App\Services\PointsCalculationService::class)->teamScores($teams);

            $teams = $teams
                ->map(function (Team $team) use ($checkpoints, $scores) {
                    $latestCheckpoint = $checkpoints[$team->id] ?? null;

                    $location = null;
                    if ($latestCheckpoint) {
                        // Prioritize quest location coordinates for consistency, fallback to user coordinates
                        $lat = $latestCheckpoint->questLocation?->latitude ?? $latestCheckpoint->user_latitude;
                        $lng = $latestCheckpoint->questLocation?->longitude ?? $latestCheckpoint->user_longitude;

                        if ($lat && $lng) {
                            // Determine location name based on whether it's auto-tracking or quest check-in
                            $isAutoTracking = ! $latestCheckpoint->quest_location_id;

                            $location = [
                                'latitude' => (float) $lat,
                                'longitude' => (float) $lng,
                                'location_name' => $isAutoTracking
                                    ? 'Current Position'
                                    : ($latestCheckpoint->questLocation?->name ?? 'Quest Location'),
                                'address' => $isAutoTracking
                                    ? 'Live Tracking'
                                    : ($latestCheckpoint->questLocation?->description ?? 'Quest Location'),
                                'last_checkin' => $latestCheckpoint->checked_at->format('Y-m-d H:i:s'),
                                'last_checkin_human' => $latestCheckpoint->checked_at->diffForHumans(),
                                'checked_by' => $latestCheckpoint->user->name ?? 'Team Member',
                                'is_live_tracking' => $isAutoTracking,
                            ];
                        }
                    }

                    return [
                        'id' => $team->id,
                        'name' => $team->name,
                        // The same number /admin/user-progress and the team's own app show.
                        'points' => (float) $scores[$team->id]['total'],
                        'department' => $team->department,
                        'member_count' => $team->members->count(),
                        'leader' => $team->members->where('is_leader', true)->first()?->name,
                        'location' => $location,
                    ];
                })
                // Only teams the map can actually place.
                ->filter(fn ($team) => $team['location'] !== null)
                ->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'teams' => $teams,
                    'total_teams_with_location' => $teams->count(),
                    'updated_at' => now()->format('Y-m-d H:i:s'),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch team locations data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * The newest check-in for each team, in two queries instead of one per team.
     *
     * Read as MAX(checked_at) per team first, then only the rows at those instants: a live event
     * writes an auto-tracking row every few seconds per phone, so pulling the whole table and
     * sorting it in PHP would be worse than the N+1 it replaced. `checked_at` rather than the id,
     * because an offline upload arrives out of order and the newest row is not the newest id.
     *
     * @param  \Illuminate\Support\Collection<int, Team>  $teams
     * @return array<int, UserQuestCheckpoint>
     */
    private function latestCheckpointPerTeam($teams): array
    {
        $ids = $teams->pluck('id')->all();

        if (! $ids) {
            return [];
        }

        $latest = DB::table('user_quest_checkpoints as c')
            ->join('users as u', 'u.id', '=', 'c.user_id')
            ->whereIn('u.team_id', $ids)
            ->groupBy('u.team_id')
            ->selectRaw('u.team_id as team_id, MAX(c.checked_at) as at')
            ->pluck('at', 'team_id');

        if ($latest->isEmpty()) {
            return [];
        }

        $rows = UserQuestCheckpoint::with([
                'questLocation:id,name,description,latitude,longitude',
                'user:id,name,team_id',
            ])
            ->whereIn('checked_at', $latest->values()->unique()->all())
            ->whereHas('user', fn ($q) => $q->whereIn('team_id', $ids))
            ->get();

        $out = [];

        foreach ($rows->groupBy(fn (UserQuestCheckpoint $c) => $c->user?->team_id) as $teamId => $group) {
            if ($teamId) {
                // Every row here is at some team's maximum, and a team's own maximum is among them,
                // so the newest of its group is that maximum.
                $out[(int) $teamId] = $group->sortByDesc('checked_at')->first();
            }
        }

        return $out;
    }

    /**
     * Get combined data for kiosk (both leaderboard and locations)
     */
    public function data()
    {
        try {
            $leaderboardResponse = $this->leaderboard();
            $locationsResponse = $this->locations();

            if (!$leaderboardResponse->getData()->success || !$locationsResponse->getData()->success) {
                throw new \Exception('Failed to fetch kiosk data');
            }

            // Live positions ride along here rather than being a second call.
            //
            // The LED board polled /api/kiosk/data and /api/live/positions separately, on
            // two different timers, for one screen — ten requests a minute where six will
            // do. Both read the same database within milliseconds of each other, so there
            // was never a reason to ask twice.
            $positions = app(LiveTrackingController::class)->getCurrentPositions();

            return response()->json([
                'success' => true,
                'data' => [
                    'leaderboard' => $leaderboardResponse->getData()->data,
                    'locations' => $locationsResponse->getData()->data,
                    'live_positions' => $positions->getData()->data ?? null,
                    'updated_at' => now()->format('Y-m-d H:i:s'),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch kiosk data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    
}