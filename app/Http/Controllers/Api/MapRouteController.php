<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MapRoute;
use App\Models\QuestLocation;

/**
 * The route line for the outdoor map.
 *
 * Drawn in the GPS Tracker app, copied here by App\Services\TrackerMapSync and served from
 * EUREKA's own copy — the tracker's export has no authentication, no CDN and no offline story,
 * and during an event a second host is a second thing that can go down.
 */
class MapRouteController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'routes' => $this->routes(),
        ]);
    }

    /**
     * The same routes plus the posts, for the website's own map pages.
     *
     * A pin that became a post is dropped from `markers` so one place gets one symbol — which
     * left the web maps drawing nothing at all, because none of them drew posts. The Android
     * client does not need this: it already reads /quest-locations with distance and check-in.
     */
    public function web()
    {
        return response()->json([
            'success' => true,
            'routes' => $this->routes(),
            'posts' => QuestLocation::where('is_active', true)
                ->whereNotNull('latitude')->whereNotNull('longitude')
                ->orderBy('name')->get()
                ->map(fn (QuestLocation $p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'icon' => $p->icon,
                    'color' => $p->marker_color ?: '#3B82F6',
                    'latitude' => (float) $p->latitude,
                    'longitude' => (float) $p->longitude,
                    'radius' => (int) $p->radius,
                    'points' => (int) $p->quest_points,
                ])->values(),
        ]);
    }

    private function routes()
    {
        return MapRoute::active()->with('markers')->orderBy('id')->get()
            ->map(fn (MapRoute $r) => $r->toMapPayload())->values();
    }
}
