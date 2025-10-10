<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class SessionController extends Controller
{
    /**
     * Get current user's session timeout setting
     */
    public function getSessionTimeout()
    {
        $user = Auth::user();
        
        if (!$user || $user->role !== 'user') {
            return response()->json(['timeout' => null]);
        }
        
        return response()->json([
            'timeout' => $user->getSessionTimeout(),
            'formatted' => $user->getFormattedSessionTimeout()
        ]);
    }
    
    /**
     * Extend current session
     */
    public function extendSession(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Not authenticated'], 401);
        }
        
        // Update last activity timestamp
        Session::put('last_activity', now()->timestamp);
        
        return response()->json([
            'message' => 'Session extended successfully',
            'last_activity' => now()->timestamp
        ]);
    }
    
    /**
     * Get session status
     */
    public function getSessionStatus()
    {
        if (!Auth::check()) {
            return response()->json(['authenticated' => false], 401);
        }
        
        $user = Auth::user();
        $lastActivity = Session::get('last_activity', now()->timestamp);
        $currentTime = now()->timestamp;
        $timeRemaining = null;
        
        if ($user->role === 'user' && $user->getSessionTimeout()) {
            $timeRemaining = $user->getSessionTimeout() - ($currentTime - $lastActivity);
            
            if ($timeRemaining <= 0) {
                Auth::logout();
                Session::flush();
                
                return response()->json([
                    'authenticated' => false,
                    'message' => 'Session expired'
                ], 401);
            }
        }
        
        return response()->json([
            'authenticated' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role
            ],
            'session' => [
                'timeout' => $user->getSessionTimeout(),
                'time_remaining' => $timeRemaining,
                'last_activity' => $lastActivity
            ]
        ]);
    }
}