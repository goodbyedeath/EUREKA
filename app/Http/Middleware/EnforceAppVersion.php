<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Refuse an Android build too old to speak the current contract.
 *
 * The client sends `X-App-Version: <versionCode>`. A build below the configured minimum is
 * turned away with 426 and told where to get a newer one, instead of failing somewhere deep in
 * a quiz with a message nobody can act on.
 *
 * **A missing header is allowed through, deliberately.** Every build in the field today predates
 * this check and sends nothing; a strict gate would brick all of them the moment it shipped. The
 * gate only bites once a client announces a version, so it becomes useful as builds roll over
 * rather than on the day it is deployed.
 */
class EnforceAppVersion
{
    public function handle(Request $request, Closure $next)
    {
        $minimum = (int) config('app_release.minimum_code');
        $claimed = $request->header('X-App-Version');

        if ($minimum > 0 && $claimed !== null && ctype_digit((string) $claimed) && (int) $claimed < $minimum) {
            return response()->json([
                'success' => false,
                'error' => 'update_required',
                'message' => 'This version of the app is too old for the current event. Please install the latest build.',
                'your_version' => (int) $claimed,
                'minimum_version' => $minimum,
                'latest_version' => (int) config('app_release.latest_code'),
                'download_url' => config('app_release.download_url'),
            ], 426);
        }

        return $next($request);
    }
}
