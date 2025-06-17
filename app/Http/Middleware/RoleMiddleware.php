<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, $role)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();
        
        switch ($role) {
            case 'admin':
                if (!$user->isAdmin()) {
                    abort(403, 'Admin access required');
                }
                break;
                
            case 'user':
                if ($user->isAdmin()) {
                    // Redirect admin to admin dashboard instead of denying access
                    return redirect()->route('admin.dashboard');
                }
                break;
                
            default:
                abort(403, 'Invalid role specified');
        }

        return $next($request);
    }
}