<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\QuestLocation;
use App\Models\UserQuestCheckpoint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

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
            $message = 'Check-in successful!';

            Log::info('User checked in to quest location', [
                'user_id' => Auth::id(),
                'quest_location_id' => $request->location_id,
                'distance' => $distance
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
        // Quest location points feature removed
        return 0;
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

    /**
     * Get filtered and sorted quest locations via AJAX
     */
    public function getLocations(Request $request)
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

        // Apply sorting with user GPS coordinates
        $userLat = $request->input('user_latitude');
        $userLng = $request->input('user_longitude');

        switch ($request->get('sort_by', 'name')) {
            case 'distance':
                if ($userLat && $userLng) {
                    $query->selectRaw('*, (
                        6371 * acos(
                            cos(radians(?)) *
                            cos(radians(latitude)) *
                            cos(radians(longitude) - radians(?)) +
                            sin(radians(?)) *
                            sin(radians(latitude))
                        )
                    ) * 1000 as distance', [
                        $userLat,
                        $userLng,
                        $userLat
                    ])->orderBy('distance');
                } else {
                    $query->orderBy('name');
                }
                break;
            default:
                $query->orderBy('name');
                break;
        }

        $questLocations = $query->paginate(12);

        // Calculate distances and status for each location
        $locationsData = [];
        foreach ($questLocations as $location) {
            $locationData = $location->toArray();

            if ($userLat && $userLng) {
                $distance = $this->calculateDistanceBetween(
                    $userLat,
                    $userLng,
                    $location->latitude,
                    $location->longitude
                ) * 1000; // Convert to meters

                $locationData['distance'] = $distance;
                $locationData['within_radius'] = $distance <= $location->radius;
            } else {
                $locationData['distance'] = null;
                $locationData['within_radius'] = false;
            }

            $locationData['checked_in'] = isset($userProgress[$location->id]);
            $locationData['check_ins_count'] = $userProgress[$location->id]['check_ins_count'] ?? 0;
            $locationData['last_checked_at'] = $userProgress[$location->id]['last_checked_at'] ?? null;

            $locationsData[] = $locationData;
        }

        return response()->json([
            'success' => true,
            'locations' => $locationsData,
            'pagination' => [
                'current_page' => $questLocations->currentPage(),
                'last_page' => $questLocations->lastPage(),
                'per_page' => $questLocations->perPage(),
                'total' => $questLocations->total(),
            ],
            'total_points' => $this->calculateTotalPoints($userProgress),
        ]);
    }

    /**
     * Update user GPS location silently
     */
    public function updateLocation(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'accuracy' => 'nullable|numeric'
        ]);

        try {
            // Store location update in database for tracking
            UserQuestCheckpoint::updateOrCreate(
                [
                    'user_id' => Auth::id(),
                    'quest_location_id' => null, // null indicates this is a location tracking entry
                ],
                [
                    'user_latitude' => $request->latitude,
                    'user_longitude' => $request->longitude,
                    'checked_at' => now(),
                ]
            );

            Log::info('Silent location update stored', [
                'user_id' => Auth::id(),
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'accuracy' => $request->accuracy
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Location updated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to store location update', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update location'
            ], 500);
        }
    }

    /**
     * Find shortest route to a quest location
     */
    public function getRoute(Request $request)
    {
        $request->validate([
            'location_id' => 'required|exists:quest_locations,id',
            'user_latitude' => 'required|numeric',
            'user_longitude' => 'required|numeric'
        ]);

        $location = QuestLocation::findOrFail($request->location_id);

        try {
            // Get route data from OpenRouteService
            $routeData = $this->fetchRouteFromAPI(
                $request->user_latitude,
                $request->user_longitude,
                $location->latitude,
                $location->longitude
            );

            if ($routeData) {
                return response()->json([
                    'success' => true,
                    'route' => $routeData,
                    'destination' => [
                        'lat' => $location->latitude,
                        'lng' => $location->longitude,
                        'name' => $location->name
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Unable to find route'
            ], 500);

        } catch (\Exception $e) {
            Log::error('Route finding failed', [
                'user_id' => Auth::id(),
                'location_id' => $request->location_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to find route. Please try again.'
            ], 500);
        }
    }

    /**
     * Fetch route from OpenRouteService API
     */
    private function fetchRouteFromAPI($startLat, $startLng, $endLat, $endLng)
    {
        // Using OpenRouteService (free tier: 2000 requests/day)
        $apiKey = env('OPENROUTE_API_KEY', 'eyJvcmciOiI1YjNjZTM1OTc4NTExMTAwMDFjZjYyNDgiLCJpZCI6Ijc4ZjAyMDIyYmIyMDRlNDRiZmVjYWVkZGI3M2NjZjBlIiwiaCI6Im11cm11cjY0In0=');

        $url = "https://api.openrouteservice.org/v2/directions/foot-walking";

        $data = [
            'coordinates' => [
                [$startLng, $startLat],
                [$endLng, $endLat]
            ],
            'format' => 'geojson',
            'instructions' => true,
            'elevation' => false
        ];

        $response = Http::withHeaders([
            'Authorization' => $apiKey,
            'Content-Type' => 'application/json'
        ])->timeout(10)->post($url, $data);

        if ($response->successful()) {
            $routeData = $response->json();

            // Extract useful information
            $feature = $routeData['features'][0] ?? null;
            if ($feature) {
                return [
                    'coordinates' => $feature['geometry']['coordinates'],
                    'distance' => $feature['properties']['summary']['distance'] ?? 0,
                    'duration' => $feature['properties']['summary']['duration'] ?? 0,
                    'instructions' => $feature['properties']['segments'][0]['steps'] ?? [],
                    'geojson' => $routeData
                ];
            }
        }

        // Fallback: return straight line route
        return [
            'coordinates' => [
                [$startLng, $startLat],
                [$endLng, $endLat]
            ],
            'distance' => $this->calculateDistanceBetween($startLat, $startLng, $endLat, $endLng) * 1000,
            'duration' => null,
            'instructions' => [
                ['instruction' => 'Head straight to destination', 'distance' => 0]
            ],
            'geojson' => null,
            'fallback' => true
        ];
    }
}