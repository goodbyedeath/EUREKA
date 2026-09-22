<?php

namespace App\Http\Controllers;

use App\Models\IndoorMap;
use Illuminate\Http\Request;

/**
 * The team-facing indoor plan, and the same data as JSON for the native client.
 *
 * Indoor venues defeat GPS, so this replaces the quest map there: a picture of the venue
 * with the outposts marked, each marker revealing a photo or a note when tapped.
 */
class IndoorMapController extends Controller
{
    public function show(Request $request, $id = null)
    {
        $map = $this->resolve($id, $request->user());

        // The plan is the reward for the opening clue. Admins skip it — they need to see
        // the venue to author it — and so does a venue with no clue configured.
        if ($map && $request->user() && ! $request->user()->isAdmin() && filled($map->clue_question)) {
            $session = \App\Models\RaceSession::where('user_id', $request->user()->id)
                ->where('indoor_map_id', $map->id)
                ->first();

            if (! $session || ! $session->hasStarted()) {
                return redirect()->route('user.dashboard')
                    ->with('error', __('Scan the START code first.'));
            }

            if (! $session->clueSolved()) {
                return redirect()->route('user.race.clue', $map->id);
            }
        }

        return view('user.indoor-map', [
            'map' => $map,
            'openIds' => $this->openLocationIdsFor($request->user()),
            'session' => $map && $request->user()
                ? \App\Models\RaceSession::where('user_id', $request->user()->id)
                    ->where('indoor_map_id', $map->id)->first()
                : null,
        ]);
    }

    /**
     * Same shape the web view renders from, so the two cannot drift.
     */
    public function apiShow(Request $request, $id = null)
    {
        $map = $this->resolve($id, $request->user());

        if (! $map) {
            return response()->json([
                'success' => false,
                'message' => 'No indoor map has been published yet.',
            ], 404);
        }

        return response()->json(['success' => true] + $this->payload($map, $request->user()));
    }

    /**
     * The player's own plan, shaped exactly as apiShow() answers — for the offline manifest, so a team
     * that synced can open its floor plan with no signal. Null when the player has no plan to read.
     */
    public function offlinePayloadFor(?\App\Models\User $user): ?array
    {
        $map = $this->resolve(null, $user);

        return $map ? $this->payload($map, $user) : null;
    }

    /** `map` and `spots`, the one shape both the live endpoint and the offline copy use. */
    private function payload(IndoorMap $map, ?\App\Models\User $user): array
    {
        $open = $this->openLocationIdsFor($user);

        return [
            'map' => [
                'id' => $map->id,
                'name' => $map->name,
                'description' => $map->description,
                'image' => $map->imageUrl(),
            ],
            'spots' => $map->activeSpots->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                // Percentages of the image, not pixels — draw at whatever size the screen
                // gives you and the marker still lands on the right room.
                'x' => (float) $s->x,
                'y' => (float) $s->y,
                'shape' => $s->shape,
                'color' => $s->color,
                'size' => (int) $s->size,
                'content' => $s->content,
                'image' => $s->imageUrl(),
                'game_location_id' => $s->game_location_id,
                // A marker only gates something when it stands for an outpost. Without this a
                // decorative or informational marker reported is_open:false and the app drew a
                // lock on it — which is also how an unlinked plan looks entirely locked no
                // matter what the crew opens (operator, 16 Sep).
                'is_post' => $s->game_location_id !== null,
                // Indoor posts are handed out at random, so a team needs to see which one
                // the crew has just opened for them — otherwise the map is a wall of
                // identical markers with no indication of where to go next.
                'is_open' => $s->game_location_id !== null && $open->contains($s->game_location_id),
            ])->values()->all(),
        ];
    }

    /**
     * Which outposts the crew has currently opened for this team.
     */
    private function openLocationIdsFor(?\App\Models\User $user): \Illuminate\Support\Collection
    {
        if (! $user) {
            return collect();
        }

        return \App\Models\GameLocationUnlock::where('user_id', $user->id)
            ->whereNotNull('granted_at')
            ->whereNull('revoked_at')
            ->pluck('game_location_id');
    }

    /**
     * A specific plan when asked for, otherwise the first active one — an event normally
     * runs a single venue, so the team should not have to choose.
     */
    /**
     * Which plan to answer with.
     *
     * Without an id, the player's own plan — their team's when the crew assigned one. With an id,
     * that plan, but a player may only read the one that is theirs: plans differ per team now, and
     * another team's plan is a map of where they are going. Admins author every plan, so they read
     * any of them.
     */
    private function resolve($id, ?\App\Models\User $user = null): ?IndoorMap
    {
        $query = IndoorMap::with(['activeSpots.gameLocation']);

        if (! $id) {
            $own = IndoorMap::forUser($user);

            return $own ? $query->where('is_active', true)->find($own->id) : null;
        }

        $map = $query->where('is_active', true)->find($id);

        if ($map && $user && ! $user->isAdmin()) {
            $own = IndoorMap::forUser($user);

            if ($own && $own->id !== $map->id) {
                return null;
            }
        }

        return $map;
    }
}
