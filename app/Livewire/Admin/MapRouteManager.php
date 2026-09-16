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

    /** Copy a pin into quest_locations, where check-in and points already work. */
    public function promote(int $markerId): void
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
        return view('livewire.admin.map-route-manager', [
            'routes' => MapRoute::with(['markers.questLocation'])->orderByDesc('recorded_at')->orderByDesc('id')->get(),
            'trackerUrl' => app(TrackerMapSync::class)->baseUrl(),
        ]);
    }
}
