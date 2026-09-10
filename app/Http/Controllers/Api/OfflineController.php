<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GameLocation;
use App\Models\QuestLocation;
use Illuminate\Support\Facades\Storage;

class OfflineController extends Controller
{
    /**
     * Everything a team leader's device needs cached before they walk out of signal.
     *
     * Deliberately excludes quiz content: questionnaires are QR-gated, so shipping
     * them ahead of time would hand the team the whole hunt. Answers are graded
     * server-side, so an offline device only ever queues them.
     */
    public function manifest()
    {
        $questLocations = QuestLocation::where('is_active', true)
            ->get(['id', 'name', 'latitude', 'longitude', 'radius', 'marker_color', 'image_path', 'map_image_path']);

        // Ordered to match the web dashboard, so a native client rebuilding that
        // screen from this manifest lists the outposts in the same order.
        $gameLocations = GameLocation::where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->with(['arModel', 'questLocation', 'hotspots.arModel'])
            ->get(['id', 'name', 'ar_model_path', 'ar_model_id', 'ar_sky_path', 'experience_type',
                'latitude', 'longitude', 'radius', 'quest_location_id']);

        // Every model a team could see: the location default plus any object that
        // overrides it. Resolved through modelUrl() so library-backed and legacy
        // path-backed models both appear — reading ar_model_path alone silently
        // dropped library models and left those outposts blank offline.
        $models = $gameLocations
            ->flatMap(fn ($g) => array_merge(
                [$g->modelUrl()],
                $g->ar_sky_path ? [Storage::disk('public')->url($g->ar_sky_path)] : [],
                $g->hotspots->map(fn ($h) => $h->modelUrl())->all()
            ))
            ->filter()
            ->unique()
            ->values();

        // Quest-location photos only. Game locations carry no imagery now — they are
        // purely the 3D experience.
        $images = collect()
            ->merge($questLocations->pluck('image_path'))
            ->merge($questLocations->pluck('map_image_path'))
            ->filter()
            ->unique()
            ->map(fn ($path) => Storage::disk('public')->url($path))
            ->values();

        // The pages themselves, not just their assets. Caching three.js and the .glb
        // is not enough: without the HTML that mounts them, an AR outpost opened out
        // of signal has an engine and a model and nothing to run them.
        $pages = collect([route('user.game-dashboard')])
            ->merge($gameLocations->filter->usesAr()->map(fn ($g) => route('user.ar.view', $g->id)))
            ->values();

        return response()->json([
            'success' => true,
            'bounds' => $this->boundsFor($questLocations),
            'images' => $images,
            'models' => $models,
            'pages' => $pages,
            'quest_locations' => $questLocations->map(fn ($l) => [
                'id' => $l->id,
                'name' => $l->name,
                'latitude' => (float) $l->latitude,
                'longitude' => (float) $l->longitude,
                'radius' => (int) $l->radius,
                'marker_color' => $l->marker_color,
            ]),
            // The dashboard's own rows. 'pages' caches the rendered Livewire HTML,
            // which only a browser can use; a native client needs the records the
            // screen is built from. uses_ar mirrors the gate in ArExperienceController
            // so an offline client never offers an outpost the server would 404.
            'game_locations' => $gameLocations->map(fn ($g) => [
                'id' => $g->id,
                'name' => $g->name,
                'experience_type' => $g->experience_type,
                'model' => $g->modelUrl(),
                'uses_ar' => $g->usesAr(),
                // Lets an offline client show distance and refuse to open the scene away
                // from the outpost without needing the network. Null until an admin binds it.
                'latitude' => $g->resolvedLatitude(),
                'longitude' => $g->resolvedLongitude(),
                'radius' => $g->resolvedRadius(),
                'coordinate_source' => $g->coordinateSource(),
                'quest_location_id' => $g->quest_location_id,
            ])->values(),
            'counts' => [
                'quest_locations' => $questLocations->count(),
                'game_locations' => $gameLocations->count(),
                'images' => $images->count(),
                'models' => $models->count(),
                'pages' => $pages->count(),
            ],
        ]);
    }

    /**
     * Bounding box around every active post, padded so the map still has context
     * when a team walks slightly outside the course.
     */
    private function boundsFor($locations): ?array
    {
        $lats = $locations->pluck('latitude')->filter()->map(fn ($v) => (float) $v);
        $lngs = $locations->pluck('longitude')->filter()->map(fn ($v) => (float) $v);

        if ($lats->isEmpty() || $lngs->isEmpty()) {
            return null;
        }

        // ~350m of padding; enough to cover approach routes without exploding the
        // tile count at high zoom.
        $pad = 0.003;

        return [
            'north' => $lats->max() + $pad,
            'south' => $lats->min() - $pad,
            'east' => $lngs->max() + $pad,
            'west' => $lngs->min() - $pad,
        ];
    }
}
