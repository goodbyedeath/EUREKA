<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class UserSessionTimeout
{
    /**
     * Enforce the admin-granted access window.
     *
     * This used to be an *inactivity* timeout, which reset on every request — so a
     * team that kept using the app never timed out at all. It is now an absolute
     * window: the clock starts at first login and, once it runs out, the user is
     * logged out and cannot log back in until an admin grants access again.
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->is('login') || $request->is('logout') || $request->is('register')) {
            return $next($request);
        }

        if (! Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // Admins are never time-limited; they are the ones handing out the windows.
        if (! $user->hasAccessWindow()) {
            return $next($request);
        }

        // A session that survived from before the grant existed still needs a clock.
        $user->startAccessWindow();

        if ($user->accessWindowExpired()) {
            Auth::logout();
            Session::flush();

            $message = __('Your access period has ended. Please contact an administrator to be granted access again.');

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'expired' => true,
                    'redirect' => route('login'),
                ], 419);
            }

            return redirect()->route('login')->with('error', $message);
        }

        // Surfaced to the UI so a countdown can be shown without another query.
        Session::put('access_window_ends_at', optional($user->accessWindowEndsAt())->timestamp);

        return $next($request);
    }
}
