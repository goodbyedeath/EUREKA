<?php

namespace App\Livewire\Admin;

use App\Models\GameLocation;
use App\Models\GameLocationUnlock;
use App\Models\User;
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
 */
class OutpostAccessPanel extends Component
{
    /** Narrow the team buttons; an event can run twenty teams at once. */
    public string $search = '';

    /** Indoor posts are the ones this desk governs; outdoor open on their own. */
    public bool $manualOnly = true;

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

    public function render()
    {
        $locations = GameLocation::where('is_active', true)
            ->when($this->manualOnly, fn ($q) => $q->where('access_mode', GameLocation::ACCESS_MANUAL))
            ->orderBy('name')
            ->get(['id', 'name', 'access_mode']);

        // Competing teams are `users` rows with role 'user' — one account per team.
        $teams = User::where('role', 'user')
            ->when($this->search !== '', fn ($q) => $q->where('name', 'like', '%' . $this->search . '%'))
            ->orderBy('name')
            ->get(['id', 'name']);

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
        ]);
    }
}
