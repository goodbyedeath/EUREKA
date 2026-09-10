<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Enforce the admin-granted access window on JSON endpoints.
 *
 * UserSessionTimeout covers the page routes, but every /api/* endpoint was
 * unguarded — so the quiz runtime kept accepting answers past expiry, and a
 * bearer token would have outlived the window entirely. This closes both.
 *
 * Always answers JSON: these callers cannot follow an HTML redirect.
 */
class EnsureAccessWindow
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user || ! $user->hasAccessWindow()) {
            return $next($request);
        }

        // A session that predates the grant still needs its clock started.
        $user->startAccessWindow();

        if ($user->accessWindowExpired()) {
            // Revoke the credential rather than merely refusing this request,
            // so a stale token cannot keep being retried.
            if ($token = $user->currentAccessToken()) {
                $token->delete();
            }

            return response()->json([
                'success' => false,
                'expired' => true,
                'access_window_expired' => true,
                // Added alongside the two booleans, not instead of them: existing clients key
                // on those, and every other refusal in v1 now carries a stable `error` string.
                'error' => 'access_window_expired',
                'message' => __('Your access period has ended. Please contact an administrator to be granted access again.'),
                'redirect' => route('login'),
            ], 403);
        }

        return $next($request);
    }
}
