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
 * **When the gate is armed, the header is required.** An earlier version of this let a request
 * with no header through, to protect builds already installed — but there are none: the client is
 * still in development and reaches only its own team. Protecting a population that does not exist
 * cost a permanent bypass, because anything armed can then be skipped by simply omitting the
 * header. A gate with a documented way around it is worse than no gate, since it invites trust it
 * has not earned.
 *
 * The gate is off by default (`minimum_code` 0), so nothing is refused until someone arms it —
 * deliberately, before an event rather than during one.
 */
class EnforceAppVersion
{
    public function handle(Request $request, Closure $next)
    {
        $minimum = (int) config('app_release.minimum_code');
        $claimed = $request->header('X-App-Version');

        if ($minimum > 0 && ! (ctype_digit((string) $claimed) && (int) $claimed >= $minimum)) {
            return response()->json([
                'success' => false,
                'error' => 'update_required',
                'message' => $claimed === null
                    ? 'This app did not identify its version. Please install the latest build.'
                    : 'This version of the app is too old for the current event. Please install the latest build.',
                'your_version' => ctype_digit((string) $claimed) ? (int) $claimed : null,
                'minimum_version' => $minimum,
                'latest_version' => (int) config('app_release.latest_code'),
                'download_url' => config('app_release.download_url'),
            ], 426);
        }

        return $next($request);
    }
}
