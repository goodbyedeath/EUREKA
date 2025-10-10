<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\QuestLocation;
use App\Models\UserQuestCheckpoint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class QuestLocationController extends Controller
{
    public function index(Request $request)
    {
        $data = $this->getQuestLocationData($request);
        
        return view('user.quest-location-dashboard', $data);
    }

    public function dashboardContent(Request $request)
    {
        $data = $this->getQuestLocationData($request);
        
        return view('user.partials.quest-locations-content', $data);
    }

    public function getQuestLocationData(Request $request)
    {
        $query = QuestLocation::where('is_active', true);
        
        // Load user progress
        $userProgress = $this->loadUserProgress();
        
        // Apply search
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        // Apply status filter
        if ($request->filled('filter_status')) {
            $visitedLocationIds = collect($userProgress)->keys();
            
            if ($request->filter_status === 'visited') {
                $query->whereIn('id', $visitedLocationIds);
            } elseif ($request->filter_status === 'not_visited') {
                $query->whereNotIn('id', $visitedLocationIds);
            }
        }

        // Apply sorting
        switch ($request->get('sort_by', 'name')) {
            case 'points_desc':
                $query->orderBy('quest_points', 'desc');
                break;
            case 'points_asc':
                $query->orderBy('quest_points', 'asc');
                break;
            case 'distance':
                // For distance sorting, we'd need user location
                // For now, fallback to name sorting
                $query->orderBy('name');
                break;
            default:
                $query->orderBy('name');
                break;
        }

        $questLocations = $query->paginate(12);
        
        // Calculate total points
        $totalPoints = $this->calculateTotalPoints($userProgress);
        
        // Calculate distances (if we had user location)
        $userDistances = [];

        return compact(
            'questLocations',
            'userProgress',
            'userDistances',
            'totalPoints'
        );
    }

    public function checkIn(Request $request)
    {
        $request->validate([
            'location_id' => 'required|exists:quest_locations,id',
            'user_latitude' => 'required|numeric',
            'user_longitude' => 'required|numeric'
        ]);

        try {
            $location = QuestLocation::findOrFail($request->location_id);
            $userLat = $request->user_latitude;
            $userLng = $request->user_longitude;

            // Calculate distance to location
            $distance = $this->calculateDistanceBetween(
                $userLat,
                $userLng,
                $location->latitude,
                $location->longitude
            ) * 1000; // Convert to meters

            // Check if user is within radius
            if ($distance > $location->radius) {
                return response()->json([
                    'success' => false,
                    'message' => "You're " . round($distance) . "m away. Get within {$location->radius}m to check in."
                ]);
            }

            // Check if user can still check in (max check-ins limit)
            if ($location->max_check_ins_per_user) {
                $currentCheckIns = UserQuestCheckpoint::where('user_id', Auth::id())
                    ->where('quest_location_id', $request->location_id)
                    ->count();
                
                if ($currentCheckIns >= $location->max_check_ins_per_user) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Maximum check-ins reached for this location.'
                    ]);
                }
            }

            // Create checkpoint
            UserQuestCheckpoint::create([
                'user_id' => Auth::id(),
                'quest_location_id' => $request->location_id,
                'user_latitude' => $userLat,
                'user_longitude' => $userLng,
                'checked_at' => now(),
                'accuracy' => $request->accuracy,
                'distance_from_center' => $distance
            ]);

            // Success message
            $points = $location->quest_points ?? 0;
            $message = $points > 0 
                ? "Check-in successful! +{$points} points earned!" 
                : 'Check-in successful!';

            Log::info('User checked in to quest location', [
                'user_id' => Auth::id(),
                'quest_location_id' => $request->location_id,
                'distance' => $distance,
                'points_earned' => $points
            ]);

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            Log::error('Check-in failed', [
                'user_id' => Auth::id(),
                'quest_location_id' => $request->location_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Check-in failed. Please try again.'
            ]);
        }
    }

    private function loadUserProgress()
    {
        $userId = Auth::id();
        
        // Load user checkpoints with counts
        $checkpoints = UserQuestCheckpoint::where('user_id', $userId)
            ->selectRaw('quest_location_id, COUNT(*) as check_ins_count, MAX(checked_at) as last_checked_at')
            ->groupBy('quest_location_id')
            ->get()
            ->keyBy('quest_location_id');

        return $checkpoints->toArray();
    }

    private function calculateTotalPoints($userProgress): int
    {
        if (empty($userProgress)) {
            return 0;
        }

        $locationIds = array_keys($userProgress);
        
        return QuestLocation::whereIn('id', $locationIds)
            ->sum('quest_points');
    }

    private function calculateDistanceBetween($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng/2) * sin($dLng/2);

        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $distance = $earthRadius * $c;

        return $distance;
    }
}