<?php

namespace App\Http\Middleware;

use App\Services\WorkflowTimerService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class WorkflowTimerMiddleware
{
    protected WorkflowTimerService $timerService;

    public function __construct(WorkflowTimerService $timerService)
    {
        $this->timerService = $timerService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        // Only handle for authenticated users
        if (Auth::check()) {
            $user = Auth::user();
            
            // Only manage session timers for regular users (not admins)
            if ($user->role === 'user') {
                
                // If this is a user activity (not AJAX for timer updates), reset the session timer
                if ($this->isUserActivity($request)) {
                    $this->timerService->resetSessionTimer($user);
                } else {
                    // For non-activity requests, just start timer if it doesn't exist
                    if (!$user->session_workflow_id) {
                        $this->timerService->startSessionTimer($user);
                    }
                }
            }
        }

        return $response;
    }

    /**
     * Determine if this request represents genuine user activity
     * (not automated requests like timer updates, heartbeats, etc.)
     */
    private function isUserActivity(Request $request): bool
    {
        // Don't count AJAX requests for timer updates as user activity
        $excludedPaths = [
            'api/session-config',
            'api/user/session-timeout',
            'api/user/session-status',
            'api/user/extend-session',
            'livewire/message', // Exclude most Livewire updates
        ];
        
        $path = $request->path();
        
        // Exclude timer-related API calls
        foreach ($excludedPaths as $excludedPath) {
            if (str_contains($path, $excludedPath)) {
                return false;
            }
        }
        
        // Exclude requests that are just for timer updates
        if ($request->isMethod('POST') && $request->has('_token')) {
            $serverParams = $request->server->all();
            $httpAccept = $serverParams['HTTP_ACCEPT'] ?? '';
            
            // If it's an AJAX request requesting JSON (likely timer update)
            if ($request->ajax() || str_contains($httpAccept, 'application/json')) {
                return false;
            }
        }
        
        // Count GET requests to user pages as activity
        if ($request->isMethod('GET') && str_starts_with($path, 'user/')) {
            return true;
        }
        
        // Count form submissions as activity
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return true;
        }
        
        return false;
    }
}
