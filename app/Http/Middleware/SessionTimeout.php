<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SessionTimeout
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();
        
        // Only apply session timeout to regular users, not admins
        if ($user->role !== 'user' || !$user->session_timeout) {
            return $next($request);
        }

        $lastActivity = Session::get('last_activity', now()->timestamp);
        $currentTime = now()->timestamp;
        $sessionTimeout = $user->session_timeout;

        // Check if session has expired
        if (($currentTime - $lastActivity) > $sessionTimeout) {
            Auth::logout();
            Session::flush();
            
            // For AJAX requests, return JSON response
            if ($request->expectsJson() || $request->is('livewire/*')) {
                return response()->json([
                    'message' => 'Session expired due to inactivity. Please login again.',
                    'session_expired' => true
                ], 401);
            }
            
            // For regular requests, redirect to login
            return redirect()->route('login')->with('error', 'Session expired due to inactivity. Please login again.');
        }

        // Update last activity timestamp
        Session::put('last_activity', $currentTime);

        return $next($request);
    }
}