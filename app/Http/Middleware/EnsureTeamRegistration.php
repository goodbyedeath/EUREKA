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

        // Check if the authenticated user has a team (either created or assigned to one)
        $user = auth()->user();
        $userHasTeam = $user->team_id || Team::where('created_by', $user->id)->exists();

        if (!$userHasTeam) {
            // A JSON caller cannot follow an HTML redirect: it receives 302 and then a
            // login-shaped page, which reads as a session problem rather than a missing
            // team. Answer in the same shape the rest of the API refuses in.
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'team_registration_required',
                    'message' => __('Silakan lengkapi pendaftaran tim terlebih dahulu untuk melanjutkan.'),
                    'redirect' => route('team.registration'),
                ], 403);
            }

            // Redirect to team registration with a message
            return redirect()->route('team.registration')
                ->with('info', 'Silakan lengkapi pendaftaran tim terlebih dahulu untuk melanjutkan.');
        }

        return $next($request);
    }
}