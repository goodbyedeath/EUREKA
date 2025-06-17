<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleBasedAccessMiddleware
{
    /**
     * Unified role-based access control middleware
     */
    public function handle(Request $request, Closure $next, string $role = null): Response
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            return redirect()->route('login');
        }

        $user = auth()->user();

        // If no specific role required, just need to be authenticated
        if (!$role) {
            return $next($request);
        }

        // Handle specific role requirements
        switch ($role) {
            case 'admin':
                if (!$user->isAdmin()) {
                    return $this->handleUnauthorized($request, 'Admin access required');
                }
                break;

            case 'user':
                if (!$user->isUser()) {
                    // Redirect admin to admin dashboard instead of denying access
                    if ($user->isAdmin()) {
                        return redirect()->route('admin.dashboard');
                    }
                    return $this->handleUnauthorized($request, 'User access required');
                }
                break;

            case 'admin_or_user':
                // Allow both admin and user roles
                if (!$user->isAdmin() && !$user->isUser()) {
                    return $this->handleUnauthorized($request, 'Valid user role required');
                }
                break;

            default:
                return $this->handleUnauthorized($request, 'Invalid role specified');
        }

        return $next($request);
    }

    /**
     * Handle unauthorized access
     */
    private function handleUnauthorized(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 403);
        }

        abort(403, $message);
    }
}