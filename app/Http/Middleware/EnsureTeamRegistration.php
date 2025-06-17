<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Team;
use Symfony\Component\HttpFoundation\Response;

class EnsureTeamRegistration
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip check for admin users
        if (auth()->user()->isAdmin()) {
            return $next($request);
        }

        // Check if user is already on team registration page
        if ($request->routeIs('team.registration')) {
            return $next($request);
        }

        // Check if the authenticated user has already registered a team
        $userHasRegisteredTeam = Team::where('created_by', auth()->id())->exists();

        if (!$userHasRegisteredTeam) {
            // Redirect to team registration with a message
            return redirect()->route('team.registration')
                ->with('info', 'Silakan lengkapi pendaftaran tim terlebih dahulu untuk melanjutkan.');
        }

        return $next($request);
    }
}