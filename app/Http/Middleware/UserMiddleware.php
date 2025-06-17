<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class UserMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check() || auth()->user()->isAdmin()) {
            abort(403, 'Access denied');
        }

        return $next($request);
    }
}
