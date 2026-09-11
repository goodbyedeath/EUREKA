<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guidance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * The event briefing, for players who only have the app.
 *
 * Guidance existed solely as a Livewire page. The website is admin-and-kiosk only now, so a team
 * holding just the APK could never read the thing they are told to read — the instructions for the
 * event they are playing. The client team reported this as blocking and they were right.
 *
 * The query mirrors `App\Livewire\User\GuidanceView` exactly: `active()`, then `forUser()`, then
 * `ordered()`. `forUser()` is the one that matters — without it a guidance aimed at one team is
 * served to everyone, which at an event means handing one team another team's instructions.
 */
class GuidanceController extends Controller
{
    public function index(Request $request)
    {
        $rows = Guidance::active()
            ->forUser($request->user()->id)
            ->ordered()
            ->get();

        return response()->json([
            'success' => true,
            'count' => $rows->count(),
            'guidances' => $rows->map(fn (Guidance $g) => $this->shape($g))->values(),
        ]);
    }

    public function show(Request $request, int $id)
    {
        $row = Guidance::active()
            ->forUser($request->user()->id)
            ->find($id);

        // Not-found and not-for-you are answered the same way on purpose: a distinct "exists but
        // not yours" would tell one team that another team has a briefing it cannot see.
        if (! $row) {
            return response()->json([
                'success' => false,
                'error' => 'guidance_not_found',
                'message' => 'That briefing is not available to you.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'guidance' => $this->shape($row),
        ]);
    }

    /**
     * Image paths are stored relative; the app caches for offline and cannot resolve a relative
     * path from a cold start, so they go out absolute.
     */
    private function shape(Guidance $g): array
    {
        return [
            'id' => $g->id,
            'title' => $g->title,
            'description' => $g->description,
            'images' => collect($g->images ?? [])
                ->map(fn ($p) => str_starts_with((string) $p, 'http')
                    ? $p
                    : Storage::disk('public')->url($p))
                ->values(),
            'sort_order' => (int) $g->sort_order,
            'updated_at' => $g->updated_at?->toIso8601String(),
        ];
    }
}
