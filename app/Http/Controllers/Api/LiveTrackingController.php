<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserQuestCheckpoint;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LiveTrackingController extends Controller
{
    /**
     * Update user's live GPS position
     */
    public function updatePosition(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $user = $request->user();
        
        // Create or update live tracking entry (quest_location_id = null for live tracking)
        UserQuestCheckpoint::updateOrCreate(
            [
                'user_id' => $user->id,
                'quest_location_id' => null, // null indicates live tracking
            ],
            [
                'user_latitude' => $request->latitude,
                'user_longitude' => $request->longitude,
                'checked_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Position updated successfully',
            'data' => [
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'timestamp' => now()->toISOString(),
            ]
        ]);
    }

    /**
     * Get current live positions for all teams
     */
    public function getCurrentPositions(): JsonResponse
    {
        $teams = Team::select(['id', 'name', 'department'])
            ->with(['members' => function($query) {
                $query->select('id', 'team_id', 'name');
            }])
            ->get()
            ->map(function ($team) {
                // Get live tracking entries (quest_location_id = null) from last 5 minutes
                $livePositions = UserQuestCheckpoint::whereHas('user', function($query) use ($team) {
                    $query->where('team_id', $team->id);
                })
                ->whereNull('quest_location_id') // Live tracking entries
                ->where('checked_at', '>=', now()->subMinutes(30)) // Last 30 minutes
                ->with(['user:id,name'])
                ->latest('checked_at')
                ->get();

                $activeMembers = [];
                foreach ($livePositions as $position) {
                    $activeMembers[] = [
                        'user_id' => $position->user_id,
                        'name' => $position->user->name,
                        'latitude' => (float) $position->user_latitude,
                        'longitude' => (float) $position->user_longitude,
                        'last_update' => $position->checked_at->diffForHumans(),
                        'timestamp' => $position->checked_at->toISOString(),
                    ];
                }

                return [
                    'id' => $team->id,
                    'name' => $team->name,
                    'department' => $team->department,
                    'active_members' => $activeMembers,
                    'member_count' => count($activeMembers),
                ];
            })
            ->filter(function ($team) {
                return $team['member_count'] > 0; // Only teams with active positions
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'teams' => $teams,
                'total_active_teams' => $teams->count(),
                'updated_at' => now()->toISOString(),
            ]
        ]);
    }
}