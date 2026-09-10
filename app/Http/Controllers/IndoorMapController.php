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
        $map = $this->resolve($id);

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
        $map = $this->resolve($id);

        if (! $map) {
            return response()->json([
                'success' => false,
                'message' => 'No indoor map has been published yet.',
            ], 404);
        }

        $open = $this->openLocationIdsFor($request->user());

        return response()->json([
            'success' => true,
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
                // Indoor posts are handed out at random, so a team needs to see which one
                // the crew has just opened for them — otherwise the map is a wall of
                // identical markers with no indication of where to go next.
                'is_open' => $s->game_location_id !== null && $open->contains($s->game_location_id),
            ])->values(),
        ]);
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
    private function resolve($id): ?IndoorMap
    {
        $query = IndoorMap::with(['activeSpots.gameLocation']);

        return $id
            ? $query->where('is_active', true)->find($id)
            : $query->where('is_active', true)->orderBy('name')->first();
    }
}
