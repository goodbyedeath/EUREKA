<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MapRoute;

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
            'routes' => MapRoute::active()->with('markers')->orderBy('id')->get()
                ->map(fn (MapRoute $r) => $r->toMapPayload())->values(),
        ]);
    }
}
