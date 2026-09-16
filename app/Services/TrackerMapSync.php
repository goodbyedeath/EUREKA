<?php

namespace App\Services;

use App\Models\MapRoute;
use App\Models\MapRouteMarker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Copy routes recorded in the GPS Tracker app into EUREKA.
 *
 * The browser used to call tracker.questerra-series.com directly from three pages. That endpoint
 * has no authentication, no CDN in front of it and no offline story, and during an event it is a
 * second host that has to stay up. Pulling server-side instead gives one source for every map and
 * for the APK's offline bundle, and it keeps working when the tracker is down.
 */
class TrackerMapSync
{
    /** ~3 m: enough to drop GPS jitter while a path still follows the road. */
    private const SIMPLIFY_EPSILON = 0.00003;

    /** A route longer than this is thinned further; the APK holds it in memory and draws it live. */
    private const MAX_POINTS = 500;

    public function baseUrl(): ?string
    {
        $url = config('services.tracker.base_url');

        return $url ? rtrim($url, '/') : null;
    }

    /**
     * @return array{routes:int, markers:int, error:?string}
     */
    public function sync(int $limit = 25): array
    {
        $base = $this->baseUrl();
        if (! $base) {
            return ['routes' => 0, 'markers' => 0, 'error' => 'Alamat tracker belum diatur (TRACKER_BASE_URL).'];
        }

        try {
            $response = Http::timeout(15)->acceptJson()->get($base.'/api/export/map-data', [
                'include_active' => 'false',      // a run still in progress is not a finished route
                'include_completed' => 'true',
                'limit' => $limit,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Tracker map sync failed', ['error' => $e->getMessage()]);

            return ['routes' => 0, 'markers' => 0, 'error' => 'Tracker tidak bisa dihubungi: '.$e->getMessage()];
        }

        if (! $response->successful()) {
            return ['routes' => 0, 'markers' => 0, 'error' => 'Tracker menjawab HTTP '.$response->status().'.'];
        }

        $sessions = $response->json('data');
        if (! is_array($sessions)) {
            return ['routes' => 0, 'markers' => 0, 'error' => 'Balasan tracker tidak dikenali.'];
        }

        $routes = 0;
        $markers = 0;

        foreach ($sessions as $session) {
            $id = (int) ($session['session_id'] ?? 0);
            if (! $id) {
                continue;
            }

            $points = $this->linePoints($session['route_points'] ?? []);
            if (count($points) < 2) {
                continue;                          // nothing to draw
            }

            DB::transaction(function () use ($session, $id, $points, &$routes, &$markers) {
                $route = MapRoute::updateOrCreate(
                    ['tracker_session_id' => $id],
                    [
                        'name' => trim((string) ($session['session_name'] ?? '')) ?: 'Rute #'.$id,
                        'recorded_by' => $session['user']['name'] ?? null,
                        'distance_m' => (int) round((float) ($session['distance'] ?? 0)),
                        'points' => $points,
                        'point_count' => count($points),
                        'recorded_at' => $this->timestamp($session['started_at'] ?? null),
                        'synced_at' => now(),
                    ],
                );
                $routes++;
                $markers += $this->syncMarkers($route, $session['markers'] ?? []);
            });
        }

        return ['routes' => $routes, 'markers' => $markers, 'error' => null];
    }

    private function syncMarkers(MapRoute $route, $list): int
    {
        if (! is_array($list)) {
            return 0;
        }

        $seen = [];
        $position = 0;

        foreach ($list as $marker) {
            if (! isset($marker['lat'], $marker['lng'])) {
                continue;
            }

            $key = MapRouteMarker::keyFor($marker);
            $seen[] = $key;

            MapRouteMarker::updateOrCreate(
                ['map_route_id' => $route->id, 'source_key' => $key],
                [
                    'title' => $marker['title'] ?? null,
                    'description' => $marker['description'] ?? null,
                    'icon' => mb_substr((string) ($marker['icon'] ?? ''), 0, 16) ?: null,
                    'color' => $this->hex($marker['color'] ?? null),
                    'latitude' => (float) $marker['lat'],
                    'longitude' => (float) $marker['lng'],
                    'position' => $position++,
                ],
            );
        }

        // A pin deleted in the tracker disappears here too — unless it has become a post, which is
        // EUREKA's own record by then and must not vanish under a running game.
        MapRouteMarker::where('map_route_id', $route->id)
            ->whereNotIn('source_key', $seen ?: [''])
            ->whereNull('quest_location_id')
            ->delete();

        return count($seen);
    }

    /** @return array<int, array{0: float, 1: float}> [lng, lat] pairs, simplified. */
    private function linePoints($raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $points = [];
        foreach ($raw as $p) {
            if (! isset($p['lat'], $p['lng'])) {
                continue;
            }
            $points[] = [(float) $p['lng'], (float) $p['lat']];
        }

        $points = $this->simplify($points, self::SIMPLIFY_EPSILON);

        // Still too many after simplifying: keep every nth, plus the last one.
        if (count($points) > self::MAX_POINTS) {
            $step = (int) ceil(count($points) / self::MAX_POINTS);
            $thinned = [];
            foreach ($points as $i => $p) {
                if ($i % $step === 0) {
                    $thinned[] = $p;
                }
            }
            $thinned[] = end($points);
            $points = $thinned;
        }

        return array_map(fn ($p) => [round($p[0], 6), round($p[1], 6)], $points);
    }

    /** Ramer–Douglas–Peucker, iterative so a long recording cannot blow the stack. */
    private function simplify(array $points, float $epsilon): array
    {
        $n = count($points);
        if ($n < 3) {
            return $points;
        }

        $keep = array_fill(0, $n, false);
        $keep[0] = $keep[$n - 1] = true;
        $stack = [[0, $n - 1]];

        while ($stack) {
            [$first, $last] = array_pop($stack);
            $maxDist = 0.0;
            $index = $first;

            for ($i = $first + 1; $i < $last; $i++) {
                $d = $this->perpendicularDistance($points[$i], $points[$first], $points[$last]);
                if ($d > $maxDist) {
                    $maxDist = $d;
                    $index = $i;
                }
            }

            if ($maxDist > $epsilon) {
                $keep[$index] = true;
                $stack[] = [$first, $index];
                $stack[] = [$index, $last];
            }
        }

        $out = [];
        foreach ($points as $i => $p) {
            if ($keep[$i]) {
                $out[] = $p;
            }
        }

        return $out;
    }

    private function perpendicularDistance(array $p, array $a, array $b): float
    {
        [$px, $py] = $p;
        [$ax, $ay] = $a;
        [$bx, $by] = $b;

        $dx = $bx - $ax;
        $dy = $by - $ay;

        if ($dx == 0.0 && $dy == 0.0) {
            return sqrt(($px - $ax) ** 2 + ($py - $ay) ** 2);
        }

        $t = (($px - $ax) * $dx + ($py - $ay) * $dy) / ($dx * $dx + $dy * $dy);
        $t = max(0.0, min(1.0, $t));

        return sqrt(($px - ($ax + $t * $dx)) ** 2 + ($py - ($ay + $t * $dy)) ** 2);
    }

    private function hex($value): ?string
    {
        $value = is_string($value) ? trim($value) : '';

        return preg_match('/^#[0-9a-f]{3,8}$/i', $value) ? $value : null;
    }

    private function timestamp($value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->toDateTimeString();
        } catch (\Throwable) {
            return null;
        }
    }
}
