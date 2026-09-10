<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * What build the client should be running.
 *
 * Public and cheap: an app asks this at launch, compares against its own versionCode, and either
 * carries on, suggests an update, or — when it is below `minimum_code` — stops and sends the
 * player to the download. Asking here is how an app finds out BEFORE a quiz fails halfway.
 */
class AppReleaseController extends Controller
{
    public function show()
    {
        // What the client last reported wins over the config default, which cannot keep up:
        // builds go to a shared Drive folder this server cannot see, so the hardcoded value
        // was four releases stale within a day of being written.
        $reported = \App\Models\AppRelease::latest();

        return response()->json([
            'success' => true,
            'latest_version' => $reported?->version_code ?? (int) config('app_release.latest_code'),
            'latest_name' => $reported?->version_name ?? config('app_release.latest_name'),
            // 0 means no gate is armed. Anything higher and the API itself refuses older
            // builds with 426 update_required.
            'minimum_version' => (int) config('app_release.minimum_code'),
            'download_url' => $reported?->download_url ?: config('app_release.download_url'),
            'notes' => $reported?->notes ?: config('app_release.notes'),
            'reported_at' => $reported?->created_at?->toIso8601String(),
        ]);
    }

    /**
     * The client announcing a build it has published.
     *
     * Only `latest` is settable from here. `minimum_code` stays in config: a remotely settable
     * minimum is one request away from locking every team out of a live event, and no convenience
     * is worth handing that switch to the network.
     *
     * Monotonic — a code at or below the highest already recorded is rejected. Releases only go
     * up, and that alone stops the endpoint being used to walk the number backwards.
     */
    public function report(Request $request)
    {
        $data = $request->validate([
            'version_code' => 'required|integer|min:1|max:100000',
            'version_name' => 'required|string|max:32',
            'download_url' => 'nullable|url|max:500',
            'notes' => 'nullable|string|max:2000',
        ]);

        $highest = \App\Models\AppRelease::latest()?->version_code ?? 0;

        if ($data['version_code'] <= $highest) {
            return response()->json([
                'success' => false,
                'error' => 'not_newer',
                'message' => "Version {$data['version_code']} is not newer than the recorded {$highest}.",
                'recorded_version' => $highest,
            ], 409);
        }

        $row = \App\Models\AppRelease::create($data);

        return response()->json([
            'success' => true,
            'latest_version' => $row->version_code,
            'message' => 'Recorded. /app/release now reports this build.',
        ], 201);
    }
}
