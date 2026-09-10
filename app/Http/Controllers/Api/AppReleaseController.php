<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

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
        return response()->json([
            'success' => true,
            'latest_version' => (int) config('app_release.latest_code'),
            'latest_name' => config('app_release.latest_name'),
            // 0 means no gate is armed. Anything higher and the API itself refuses older
            // builds with 426 update_required.
            'minimum_version' => (int) config('app_release.minimum_code'),
            'download_url' => config('app_release.download_url'),
            'notes' => config('app_release.notes'),
        ]);
    }
}
