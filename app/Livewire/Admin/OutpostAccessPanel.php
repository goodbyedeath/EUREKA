<?php

namespace App\Livewire\Admin;

use App\Models\GameLocation;
use App\Models\GameLocationUnlock;
use App\Models\IndoorMap;
use App\Models\IndoorMapSpot;
use App\Models\RaceSession;
use App\Models\RaceStart;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

/**
 * The live control desk for indoor outposts.
 *
 * Indoor posts are handed out in a random order, so nothing in the database holds a
 * sequence — the crew do, and this is where their decision reaches the app.
 *
 * Deliberately one screen with no drill-down: every active outpost, every registered team,
 * one tap to open or close. It is operated while listening to a radio, so anything that
 * costs a second look — picking a team first, switching views — is a cost in the field.
 *
 * Since 16 Sep each team can have its own floor plan (Team Management). A post only reaches
 * a team through a marker on that team's plan, so the desk knows each team's plan: a post that
 * is not on it is shown but cannot be opened for that team, and a plan filter narrows the
 * desk to one route when several run at once.
 */
class OutpostAccessPanel extends Component
{
    /** Narrow the team buttons; an event can run twenty teams at once. */
    public string $search = '';

    /** Indoor posts are the ones this desk governs; outdoor open on their own. */
    public bool $manualOnly = true;

    /** One floor plan's posts and teams only; '' shows everything. */
    public string $plan = '';

    /**
     * Open or close one team's 3D camera at one outpost.
     *
     * Rows are revoked rather than deleted, so "who opened what, when" survives the event —
     * during a live race that record is the only way to settle a dispute afterwards.
     */
    public function toggle(int $locationId, int $userId): void
    {
        $row = GameLocationUnlock::where('game_location_id', $locationId)
            ->where('user_id', $userId)
            ->first();

        if ($row && $row->revoked_at === null) {
            $row->update(['revoked_at' => now()]);
            $action = 'closed';
        } else {
            // Opening a post that sits on some plan, for a team on a different plan, changes
            // nothing the team can see — their floor plan has no marker for it. Refuse it here
            // too, not only in the view, so a stale tab cannot do it. Closing is always allowed.
            $postPlans = $this->postPlans()->get($locationId, []);
            $team = User::with('team:id,name,indoor_map_id')->find($userId);

            if ($team && $postPlans && ! in_array($this->planIdsFor(collect([$team]))[$team->id] ?? null, $postPlans, true)) {
                session()->flash('access_error', "Pos itu tidak ada di denah {$team->name}. Ubah denah timnya di Team Management, atau hubungkan pos ke denah tim itu.");

                return;
            }

            GameLocationUnlock::updateOrCreate(
                ['game_location_id' => $locationId, 'user_id' => $userId],
                ['granted_by' => Auth::id(), 'granted_at' => now(), 'revoked_at' => null],
            );
            $action = 'opened';
        }

        Log::info("Outpost access {$action}", [
            'game_location_id' => $locationId, 'user_id' => $userId, 'by' => Auth::id(),
        ]);
    }

    /** Close every team at one outpost — used when a wave has finished and moved on. */
    public function closeAll(int $locationId): void
    {
        GameLocationUnlock::where('game_location_id', $locationId)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        session()->flash('access_msg', __('Closed for every team at that outpost.'));
    }

    /**
     * game_location_id => [indoor_map_id, …]: the plans that carry a marker for each post.
     *
     * A post on no plan is absent — it is an AR outpost reached another way, and the desk does
     * not restrict it.
     */
    private function postPlans(): Collection
    {
        return IndoorMapSpot::where('is_active', true)
            ->whereNotNull('game_location_id')
            ->get(['game_location_id', 'indoor_map_id'])
            ->groupBy('game_location_id')
            ->map(fn ($rows) => $rows->pluck('indoor_map_id')->map(fn ($id) => (int) $id)->unique()->values()->all());
    }

    /**
     * user_id => the plan that team sees, in one pass for the whole desk.
     *
     * The same order as IndoorMap::forUser() — team assignment, the plan the team started on,
     * the newest START code's plan, the first active plan — but with four queries instead of
     * four per team, since the desk re-renders every ten seconds.
     *
     * @return array<int, int|null>
     */
    private function planIdsFor(Collection $users): array
    {
        $active = IndoorMap::where('is_active', true)->pluck('id')->map(fn ($id) => (int) $id)->all();

        // orderBy id then keyBy keeps each user's newest session.
        $fromSession = RaceSession::whereIn('user_id', $users->pluck('id'))
            ->whereNotNull('indoor_map_id')
            ->orderBy('id')
            ->get(['user_id', 'indoor_map_id'])
            ->keyBy('user_id');

        $fromStart = RaceStart::where('is_active', true)->whereNotNull('indoor_map_id')->orderByDesc('id')->value('indoor_map_id');
        $first = IndoorMap::where('is_active', true)->orderBy('name')->value('id');

        $out = [];
        foreach ($users as $user) {
            $candidates = [
                $user->team?->indoor_map_id,
                $fromSession[$user->id]->indoor_map_id ?? null,
                $fromStart,
                $first,
            ];

            $out[$user->id] = null;
            foreach ($candidates as $id) {
                if ($id && in_array((int) $id, $active, true)) {
                    $out[$user->id] = (int) $id;
                    break;
                }
            }
        }

        return $out;
    }

    public function render()
    {
        $locations = GameLocation::where('is_active', true)
            ->when($this->manualOnly, fn ($q) => $q->where('access_mode', GameLocation::ACCESS_MANUAL))
            ->orderBy('name')
            ->get(['id', 'name', 'access_mode']);

        // Competing teams are `users` rows with role 'user' — one account per team.
        $teams = User::with('team:id,name,indoor_map_id')
            ->where('role', 'user')
            ->when($this->search !== '', fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
            ->orderBy('name')
            ->get(['id', 'name', 'team_id']);

        $postPlans = $this->postPlans();
        $teamPlan = $this->planIdsFor($teams);
        $planNames = IndoorMap::where('is_active', true)->orderBy('name')->pluck('name', 'id');

        if ($this->plan !== '') {
            $wanted = (int) $this->plan;
            $teams = $teams->filter(fn ($t) => ($teamPlan[$t->id] ?? null) === $wanted)->values();
            $locations = $locations->filter(fn ($l) => in_array($wanted, $postPlans->get($l->id, []), true))->values();
        }

        // One query for the whole grid rather than one per button.
        $open = GameLocationUnlock::whereNull('revoked_at')
            ->whereNotNull('granted_at')
            ->get(['game_location_id', 'user_id'])
            ->groupBy('user_id')
            ->map(fn ($rows) => $rows->pluck('game_location_id')->all());

        return view('livewire.admin.outpost-access-panel', [
            'locations' => $locations,
            'teams' => $teams,
            // user_id => [locationId, ...]; lets a button know both its own state and
            // whether that team is already standing at some other post.
            'open' => $open,
            'postPlans' => $postPlans,
            'teamPlan' => $teamPlan,
            'planNames' => $planNames,
        ]);
    }
}
