<?php

namespace App\Livewire\Admin;

use App\Models\GameLocation;
use App\Models\GameLocationUnlock;
use App\Models\IndoorMap;
use App\Models\IndoorMapSpot;
use App\Models\RaceSession;
use App\Models\RaceStart;
use App\Models\Team;
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
 * Deliberately one screen with no drill-down: every active outpost, every team, one tap to open or
 * close. It is operated while listening to a radio, so anything that costs a second look — picking
 * a team first, switching views — is a cost in the field.
 *
 * **Rows are teams, not accounts** (operator, 20 Sep). What comes over the radio is a team name,
 * while an account may be called anything ("Test" for team A), and a team can hold more than one
 * phone — all of which must see the same post. An account with no team still gets its own row.
 *
 * Each team has its own floor plan (Team Management), and a post only reaches a team through a
 * marker on that plan, so a post that is not on it cannot be opened for that team.
 */
class OutpostAccessPanel extends Component
{
    /** Narrow the rows; an event can run twenty teams at once. */
    public string $search = '';

    /** Indoor posts are the ones this desk governs; outdoor open on their own. */
    public bool $manualOnly = true;

    /** One floor plan's posts and teams only; '' shows everything. */
    public string $plan = '';

    /** Open or close every account of one team at one outpost. */
    public function toggleTeam(int $locationId, int $teamId): void
    {
        $accounts = User::where('team_id', $teamId)->pluck('id');

        if ($accounts->isEmpty()) {
            session()->flash('access_error', 'Tim itu belum punya akun login, jadi belum ada yang bisa dibukakan.');

            return;
        }

        $open = GameLocationUnlock::where('game_location_id', $locationId)
            ->whereIn('user_id', $accounts)
            ->whereNull('revoked_at')
            ->exists();

        if ($open) {
            $this->close($locationId, $accounts->all());

            return;
        }

        if (! $this->mayOpen($locationId, $accounts->first(), $teamId)) {
            return;
        }

        foreach ($accounts as $userId) {
            $this->open($locationId, $userId);
        }
    }

    /** The same, for an account that belongs to no team. */
    public function toggle(int $locationId, int $userId): void
    {
        $row = GameLocationUnlock::where('game_location_id', $locationId)
            ->where('user_id', $userId)
            ->first();

        if ($row && $row->revoked_at === null) {
            $this->close($locationId, [$userId]);

            return;
        }

        if (! $this->mayOpen($locationId, $userId, null)) {
            return;
        }

        $this->open($locationId, $userId);
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
     * Opening a post that sits on some plan, for a team on a different plan, changes nothing the
     * team can see — their floor plan has no marker for it. Refused here as well as in the view, so
     * a stale tab cannot do it. Closing is always allowed, so leftovers can be cleared.
     */
    private function mayOpen(int $locationId, ?int $userId, ?int $teamId): bool
    {
        $postPlans = $this->postPlans()->get($locationId, []);
        if (! $postPlans || ! $userId) {
            return true;
        }

        $user = User::with('team:id,name,indoor_map_id')->find($userId);
        $plan = $user ? $this->planIdsFor(collect([$user]))[$user->id] ?? null : null;

        if (in_array($plan, $postPlans, true)) {
            return true;
        }

        $name = $teamId ? (Team::find($teamId)?->name ?? 'tim itu') : ($user?->name ?? 'akun itu');
        session()->flash('access_error', "Pos itu tidak ada di denah {$name}. Ubah denah timnya di Team Management, atau hubungkan pos ke denah tim itu.");

        return false;
    }

    private function open(int $locationId, int $userId): void
    {
        GameLocationUnlock::updateOrCreate(
            ['game_location_id' => $locationId, 'user_id' => $userId],
            ['granted_by' => Auth::id(), 'granted_at' => now(), 'revoked_at' => null],
        );

        Log::info('Outpost access opened', [
            'game_location_id' => $locationId, 'user_id' => $userId, 'by' => Auth::id(),
        ]);
    }

    /** Rows are revoked rather than deleted: during a live race that record settles disputes. */
    private function close(int $locationId, array $userIds): void
    {
        GameLocationUnlock::where('game_location_id', $locationId)
            ->whereIn('user_id', $userIds)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        Log::info('Outpost access closed', [
            'game_location_id' => $locationId, 'user_ids' => $userIds, 'by' => Auth::id(),
        ]);
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
     * user_id => the plan that account sees, in one pass for the whole desk.
     *
     * The same order as IndoorMap::forUser() — team assignment, the plan the team started on, the
     * newest START code's plan, the first active plan — but with four queries instead of four per
     * account, since the desk re-renders every ten seconds.
     *
     * @param  Collection<int, User>  $users
     * @return array<int, int|null>
     */
    private function planIdsFor(Collection $users): array
    {
        $active = IndoorMap::where('is_active', true)->pluck('id')->map(fn ($id) => (int) $id)->all();

        // orderBy id then keyBy keeps each account's newest session.
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

        $accounts = User::with('team:id,name,indoor_map_id')
            ->where('role', 'user')
            ->orderBy('name')
            ->get(['id', 'name', 'team_id']);

        $accountPlan = $this->planIdsFor($accounts);

        // One row per team — the name the radio uses — then any account that has no team.
        $rows = Team::orderBy('name')->get(['id', 'name', 'indoor_map_id'])
            ->map(function (Team $team) use ($accounts, $accountPlan) {
                $mine = $accounts->where('team_id', $team->id);

                return (object) [
                    'key' => 'team-'.$team->id,
                    'kind' => 'team',
                    'id' => $team->id,
                    'name' => $team->name,
                    'accounts' => $mine->pluck('id')->all(),
                    'account_names' => $mine->pluck('name')->all(),
                    // A team with no account yet has no session either, so its plan is its own
                    // assignment when it has one.
                    'plan' => $mine->isNotEmpty() ? ($accountPlan[$mine->first()->id] ?? null) : $team->indoor_map_id,
                    'assigned' => $team->indoor_map_id !== null,
                ];
            })
            ->concat($accounts->whereNull('team_id')->map(fn (User $u) => (object) [
                'key' => 'user-'.$u->id,
                'kind' => 'user',
                'id' => $u->id,
                'name' => $u->name,
                'accounts' => [$u->id],
                'account_names' => [$u->name],
                'plan' => $accountPlan[$u->id] ?? null,
                'assigned' => false,
            ]))
            ->when($this->search !== '', fn ($all) => $all->filter(
                fn ($r) => str_contains(mb_strtolower($r->name), mb_strtolower($this->search))
                    || collect($r->account_names)->contains(fn ($n) => str_contains(mb_strtolower($n), mb_strtolower($this->search)))
            ))
            ->values();

        $postPlans = $this->postPlans();
        $planNames = IndoorMap::where('is_active', true)->orderBy('name')->pluck('name', 'id');

        if ($this->plan !== '') {
            $wanted = (int) $this->plan;
            $rows = $rows->filter(fn ($r) => $r->plan === $wanted)->values();
            $locations = $locations->filter(fn ($l) => in_array($wanted, $postPlans->get($l->id, []), true))->values();
        }

        // One query for the whole grid rather than one per button; a team counts as open at a post
        // when any of its accounts is.
        $openByAccount = GameLocationUnlock::whereNull('revoked_at')
            ->whereNotNull('granted_at')
            ->get(['game_location_id', 'user_id'])
            ->groupBy('user_id')
            ->map(fn ($u) => $u->pluck('game_location_id')->all());

        $open = $rows->mapWithKeys(fn ($r) => [$r->key => collect($r->accounts)
            ->flatMap(fn ($id) => $openByAccount[$id] ?? [])
            ->unique()->values()->all()]);

        return view('livewire.admin.outpost-access-panel', [
            'locations' => $locations,
            'rows' => $rows,
            'open' => $open,
            'postPlans' => $postPlans,
            'planNames' => $planNames,
        ]);
    }
}
