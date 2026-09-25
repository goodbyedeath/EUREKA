<?php

namespace App\Livewire\Admin;

use App\Models\IndoorMap;
use App\Models\RaceSession;
use App\Models\Team;
use App\Models\User;
use App\Services\StationProgress;
use Livewire\Component;

/**
 * Every team's race clock, in one strip.
 *
 * It used to live on Outpost Access; the operator moved it to Team Progress on 25 Sep, which is the
 * screen the crew keeps open on the second monitor. It is a component rather than markup in a page so
 * the clock, the countdown and the bell travel together and cannot drift apart again.
 *
 * The bell itself is in the view: when a team's time runs out it is announced out loud once, because a
 * desk operated while listening to a radio cannot be expected to watch a clock.
 */
class RaceClockStrip extends Component
{
    public function render()
    {
        $accounts = User::where('role', 'user')->get(['id', 'name', 'team_id']);
        $progress = app(StationProgress::class);
        $required = $progress->required();
        $clearedByAccount = $progress->clearedKeysByAccount($accounts->pluck('id'), $required);

        // One query for every run, then grouped: a strip of ten teams must not be ten queries.
        $sessions = RaceSession::with('raceStart')
            ->whereIn('user_id', $accounts->pluck('id'))
            ->whereNotNull('started_at')
            ->orderBy('started_at')
            ->get()
            ->groupBy('user_id');

        $plans = IndoorMap::where('is_active', true)->pluck('name', 'id');
        $neededByPlan = [];

        $standings = Team::orderBy('name')->get(['id', 'name', 'indoor_map_id'])
            ->map(function (Team $team) use ($accounts, $clearedByAccount, $required, $progress, &$neededByPlan, $sessions, $plans) {
                $mine = $accounts->where('team_id', $team->id);

                // Two phones may have split the route, so the team's figure is the union of its accounts.
                $cleared = $mine->pluck('id')
                    ->flatMap(fn ($id) => $clearedByAccount[$id] ?? [])
                    ->unique()->count();

                $session = $mine->pluck('id')
                    ->flatMap(fn ($id) => $sessions[$id] ?? [])
                    ->sortByDesc('started_at')->first();

                // The plan the run is on, else the team's own assignment.
                $planId = $session?->indoor_map_id ?? $team->indoor_map_id;
                $neededByPlan[$planId ?? 0] ??= $progress->requiredCount($planId, $required);

                // A run over its limit is closed by whoever reads it first. The scheduler does it within
                // the minute; this strip is read at the second, and a chip that still says "berjalan"
                // while the bell should be ringing is worse than a write.
                $session?->finishIfOverTime();

                $deadline = $session?->deadlineAt();

                return (object) [
                    'key' => 'team-'.$team->id,
                    'name' => $team->name,
                    'plan' => $planId ? ($plans[$planId] ?? null) : null,
                    'cleared' => $cleared,
                    'needed' => $neededByPlan[$planId ?? 0],
                    'started' => $session !== null,
                    'finished' => $session?->finished_at !== null,
                    'reason' => $session?->finished_reason,
                    'clock' => $session?->elapsedForHumans(),
                    // Sent as an instant, not as "seconds left": the browser counts down between polls,
                    // and a number computed here would be ten seconds stale on arrival.
                    'deadline_at' => $deadline?->toIso8601String(),
                    // The bell rings for a clock that ran out, not for a team that cleared every post.
                    'out_of_time' => $session?->finished_reason === RaceSession::REASON_TIME_LIMIT,
                    'stopped_by_crew' => $session?->finished_reason === RaceSession::REASON_MANUAL,
                ];
            })->values();

        return view('livewire.admin.race-clock-strip', ['standings' => $standings]);
    }
}
