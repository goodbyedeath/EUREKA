<?php

namespace App\Livewire\Admin;

use App\Models\MapRoute;
use App\Models\MapRouteMarker;
use App\Models\QuestLocation;
use App\Services\TrackerMapSync;
use Livewire\Component;

/**
 * Pick which recorded routes the event uses, and turn a route's pins into real posts.
 *
 * Drawing stays in the tracker app, which is good at it. Everything the game needs — radius,
 * points, check-in, the offline bundle — stays here, where the accounts and scores are.
 */
class MapRouteManager extends Component
{
    /** Defaults for a promoted pin; the post page is where they get tuned afterwards. */
    public int $radius = 30;

    public int $questPoints = 0;

    public function sync(): void
    {
        $result = app(TrackerMapSync::class)->sync();

        session()->flash(
            $result['error'] ? 'route_error' : 'route_msg',
            $result['error'] ?: "Selesai. {$result['routes']} rute dan {$result['markers']} penanda diperbarui dari tracker.",
        );
    }

    public function toggle(int $routeId): void
    {
        $route = MapRoute::findOrFail($routeId);
        $route->update(['is_active' => ! $route->is_active]);

        session()->flash('route_msg', $route->is_active
            ? "\"{$route->name}\" sekarang tampil di peta peserta."
            : "\"{$route->name}\" disembunyikan dari peta peserta.");
    }

    /** Posts nearer than this to a pin are almost certainly the same place. */
    public const SAME_PLACE_METRES = 30;

    /**
     * The nearest active post to a pin, when it is close enough to be the same place.
     *
     * Posts can also be typed straight into Quest Locations, so the crew can easily end up with
     * a pin and a post on one spot. Promoting then would put two posts a few metres apart, each
     * with its own radius and check-in.
     *
     * @return array{post: QuestLocation, metres: int}|null
     */
    public function nearbyPost(MapRouteMarker $marker): ?array
    {
        $nearest = null;

        foreach (QuestLocation::where('is_active', true)->whereNotNull('latitude')->get() as $post) {
            $metres = $this->metresBetween(
                (float) $marker->latitude, (float) $marker->longitude,
                (float) $post->latitude, (float) $post->longitude,
            );

            if ($metres <= self::SAME_PLACE_METRES && (! $nearest || $metres < $nearest['metres'])) {
                $nearest = ['post' => $post, 'metres' => (int) round($metres)];
            }
        }

        return $nearest;
    }

    /** Haversine; a venue is small enough that the earth's radius alone is accurate here. */
    private function metresBetween(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /** Point a pin at a post that already exists, instead of making a second one. */
    public function attach(int $markerId, int $postId): void
    {
        $marker = MapRouteMarker::findOrFail($markerId);
        $post = QuestLocation::findOrFail($postId);
        $marker->update(['quest_location_id' => $post->id]);

        session()->flash('route_msg', "Penanda dihubungkan ke pos \"{$post->name}\". Pos itu yang dipakai; penandanya tidak lagi digambar terpisah.");
    }

    /** Copy a pin into quest_locations, where check-in and points already work. */
    public function promote(int $markerId, bool $force = false): void
    {
        $this->validate([
            'radius' => 'required|integer|min:5|max:500',
            'questPoints' => 'required|integer|min:0|max:10000',
        ]);

        $marker = MapRouteMarker::with('route')->findOrFail($markerId);

        if ($marker->quest_location_id) {
            session()->flash('route_error', 'Penanda ini sudah menjadi pos.');

            return;
        }

        if (! $force && $near = $this->nearbyPost($marker)) {
            session()->flash('route_error', "Sudah ada pos \"{$near['post']->name}\" sekitar {$near['metres']} m dari penanda ini. "
                .'Hubungkan ke pos itu, atau tekan "Tetap buat pos baru" kalau memang dua pos berbeda.');

            return;
        }

        $post = QuestLocation::create([
            'name' => $marker->title ?: 'Pos '.$marker->route->name,
            'description' => (string) $marker->description,
            // Required by the table, and the line players read at the post. The pin's own note is
            // the best first draft; the crew edits it on the Quest Locations page.
            'what_to_do' => (string) $marker->description ?: 'Datang ke titik ini, lalu check-in.',
            'latitude' => $marker->latitude,
            'longitude' => $marker->longitude,
            'radius' => $this->radius,
            'quest_points' => $this->questPoints,
            'marker_color' => $marker->color ?: '#3B82F6',
            'is_active' => true,
            'created_by' => auth()->id(),
        ]);

        $marker->update(['quest_location_id' => $post->id]);

        session()->flash('route_msg', "\"{$post->name}\" dibuat sebagai pos. Atur radius, poin, dan instruksinya di halaman Quest Locations.");
    }

    /** Undo a promotion: the pin comes back, the post is left alone. */
    public function detach(int $markerId): void
    {
        MapRouteMarker::findOrFail($markerId)->update(['quest_location_id' => null]);
        session()->flash('route_msg', 'Hubungan penanda dengan pos dilepas. Posnya tidak dihapus.');
    }

    public function render()
    {
        $routes = MapRoute::with(['markers.questLocation'])->orderByDesc('recorded_at')->orderByDesc('id')->get();

        // Which pins sit on top of a post that already exists, so the crew is told before it
        // makes a second one rather than after.
        $nearby = [];
        foreach ($routes as $route) {
            foreach ($route->markers as $marker) {
                if (! $marker->quest_location_id) {
                    $nearby[$marker->id] = $this->nearbyPost($marker);
                }
            }
        }

        return view('livewire.admin.map-route-manager', [
            'routes' => $routes,
            'nearby' => $nearby,
            'trackerUrl' => app(TrackerMapSync::class)->baseUrl(),
        ]);
    }
}
