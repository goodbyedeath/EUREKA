<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class UserSessionTimeout
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Skip session timeout check for auth routes
        if ($request->is('login') || $request->is('logout') || $request->is('register')) {
            return $next($request);
        }

        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();
        
        // Only apply timeout to regular users, not admins
        if ($user->role !== 'user') {
            return $next($request);
        }

        // Use admin-configured session timeout from database
        $sessionTimeout = $user->session_timeout ?? null;
        
        // If session timeout is null (No timeout), skip timeout check entirely
        if ($sessionTimeout === null) {
            return $next($request);
        }

        $lastActivity = Session::get('last_activity_time');
        $now = now()->timestamp;

        if ($lastActivity) {
            $inactiveTime = $now - $lastActivity;
            
            if ($inactiveTime > $sessionTimeout) {
                // Session has expired
                Auth::logout();
                Session::flush();
                
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Session expired due to inactivity.',
                        'expired' => true
                    ], 419);
                }
                
                return redirect()->route('login')
                    ->with('message', 'Your session has expired due to inactivity. Please log in again.');
            }
        }

        // Update last activity time (only if timeout is configured)
        Session::put('last_activity_time', $now);

        return $next($request);
    }
}