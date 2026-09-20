<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserGuideSection;

/**
 * The player's how-to, for the app's Panduan page.
 *
 * Served from the server so the crew can fix a sentence between events without a new build — they
 * are the ones who hear what people ask at the start line. Cache it: the same list is in
 * /offline/manifest, so a synced phone can still read the guide with no signal.
 */
class UserGuideController extends Controller
{
    public function index()
    {
        $sections = UserGuideSection::active()->ordered()->get();

        return response()->json([
            'success' => true,
            'updated_at' => $sections->max('updated_at')?->toIso8601String(),
            'sections' => $sections->map(fn (UserGuideSection $s) => $s->toApiPayload())->values(),
        ]);
    }
}
