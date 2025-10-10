<?php

namespace App\Livewire\User;

use App\Models\QuestLocation;
use App\Models\UserQuestCheckpoint;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class QuestLocationDashboard extends Component
{
    use WithPagination;

    // Search and filter properties
    public $search = '';
    public $filterStatus = '';
    public $sortBy = 'name';

    // User location data
    public $userLatitude;
    public $userLongitude; 
    public $locationPermissionGranted = false;
    public $locationAccuracy;

    // User progress tracking
    public $userProgress = [];
    public $userDistances = [];
    public $totalPoints = 0;

    // Map modal
    public $selectedLocation = null;
    
    // Location details modal
    public $showDetailsModal = false;
    public $selectedLocationDetails = null;
    
    // Fullscreen map modal
    public $showFullscreenModal = false;
    public $selectedFullscreenLocation = null;
    
    // Calculated data for template
    public $locationDistances = [];
    public $withinRadiusStatus = [];
    public $checkedInStatus = [];
    
    // Route finding
    public $routeData = [];
    public $selectedRouteLocation = null;
    public $showRouteModal = false;

    protected $listeners = [
        'locationUpdated',
        'showLocationMap',
        'findRoute'
    ];

    protected $queryString = [
        'search' => ['except' => ''],
        'filterStatus' => ['except' => ''],
        'sortBy' => ['except' => 'name']
    ];

    public function mount()
    {
        $this->loadUserProgress();
        $this->requestLocationOnMount();
    }

    public function render()
    {
        $questLocations = $this->getFilteredQuestLocations();
        $this->totalPoints = $this->calculateTotalPoints();

        return view('livewire.user.quest-location-dashboard', [
            'questLocations' => $questLocations,
            'userProgress' => $this->userProgress,
            'userDistances' => $this->userDistances,
            'totalPoints' => $this->totalPoints
        ]);
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterStatus()
    {
        $this->resetPage();
    }

    public function updatedSortBy()
    {
        $this->resetPage();
    }

    private function getFilteredQuestLocations()
    {
        $query = QuestLocation::where('is_active', true);

        // Apply search
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        // Apply status filter
        if ($this->filterStatus) {
            $visitedLocationIds = collect($this->userProgress)->keys();
            
            if ($this->filterStatus === 'visited') {
                $query->whereIn('id', $visitedLocationIds);
            } elseif ($this->filterStatus === 'not_visited') {
                $query->whereNotIn('id', $visitedLocationIds);
            }
        }

        // Apply sorting
        switch ($this->sortBy) {
            case 'points_desc':
                $query->orderBy('quest_points', 'desc');
                break;
            case 'points_asc':
                $query->orderBy('quest_points', 'asc');
                break;
            case 'distance':
                if ($this->userLatitude && $this->userLongitude) {
                    $query->selectRaw('*, (
                        6371 * acos(
                            cos(radians(?)) * 
                            cos(radians(latitude)) * 
                            cos(radians(longitude) - radians(?)) + 
                            sin(radians(?)) * 
                            sin(radians(latitude))
                        )
                    ) * 1000 as distance', [
                        $this->userLatitude,
                        $this->userLongitude,
                        $this->userLatitude
                    ])->orderBy('distance');
                } else {
                    $query->orderBy('name');
                }
                break;
            default:
                $query->orderBy('name');
                break;
        }

        return $query->paginate(12);
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

        $this->userProgress = $checkpoints->toArray();
    }

    private function requestLocationOnMount()
    {
        $this->dispatch('requestLocation');
    }

    #[On('locationUpdated')]
    public function locationUpdated($latitude, $longitude, $accuracy = null)
    {
        $this->userLatitude = $latitude;
        $this->userLongitude = $longitude;
        $this->locationAccuracy = $accuracy;
        $this->locationPermissionGranted = true;

        $this->calculateDistances();

        Log::info('User location updated', [
            'user_id' => Auth::id(),
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy' => $accuracy,
            'permission_granted' => $this->locationPermissionGranted
        ]);

        // Flash success message to show location is working
        session()->flash('success', 'Location updated successfully!');
    }

    public function showLocationError($message)
    {
        session()->flash('error', $message);
        
        Log::warning('User location error displayed', [
            'user_id' => Auth::id(),
            'error_message' => $message
        ]);
    }

    public function updateLocationSilently($latitude, $longitude, $accuracy = null)
    {
        // Update internal location properties
        $this->userLatitude = $latitude;
        $this->userLongitude = $longitude;
        $this->locationAccuracy = $accuracy;
        $this->locationPermissionGranted = true;

        // Store location update in database with special quest location for tracking
        try {
            // Create or update a special "current location" checkpoint
            UserQuestCheckpoint::updateOrCreate(
                [
                    'user_id' => Auth::id(),
                    'quest_location_id' => null, // null indicates this is a location tracking entry
                ],
                [
                    'user_latitude' => $latitude,
                    'user_longitude' => $longitude,
                    'checked_at' => now(),
                ]
            );

            $this->calculateDistances();

            Log::info('Silent location update stored', [
                'user_id' => Auth::id(),
                'latitude' => $latitude,
                'longitude' => $longitude,
                'accuracy' => $accuracy
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to store silent location update', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
        }
    }

    private function calculateDistances()
    {
        if (!$this->userLatitude || !$this->userLongitude) {
            return;
        }

        $locations = QuestLocation::where('is_active', true)->get();
        
        foreach ($locations as $location) {
            $distance = $this->calculateDistanceBetween(
                $this->userLatitude,
                $this->userLongitude,
                $location->latitude,
                $location->longitude
            ) * 1000; // Convert to meters
            
            $this->userDistances[$location->id] = $distance;
            $this->locationDistances[$location->id] = $distance;
            
            // Check if within radius
            $this->withinRadiusStatus[$location->id] = $distance <= $location->radius;
            
            // Check if already checked in
            $this->checkedInStatus[$location->id] = isset($this->userProgress[$location->id]);
        }
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

    public function viewOnMap($locationId)
    {
        $this->selectedLocation = QuestLocation::find($locationId);
        
        if ($this->selectedLocation) {
            $this->dispatch('showLocationMap', [
                'latitude' => $this->selectedLocation->latitude,
                'longitude' => $this->selectedLocation->longitude,
                'name' => $this->selectedLocation->name,
                'radius' => $this->selectedLocation->radius ?? 50
            ]);
        }
    }

    public function showLocationDetails($locationId)
    {
        $location = QuestLocation::find($locationId);
        if ($location) {
            $this->selectedLocationDetails = $location;
            $this->showDetailsModal = true;
            
            // Trigger location update when opening location details
            $this->dispatch('updateLocationOnModalOpen');
        }
    }

    public function closeDetailsModal()
    {
        $this->showDetailsModal = false;
        $this->selectedLocationDetails = null;
    }

    public function showFullscreenMap($locationId)
    {
        $location = QuestLocation::find($locationId);
        if ($location && $location->has_valid_coordinates) {
            $this->selectedFullscreenLocation = $location;
            $this->showFullscreenModal = true;
            
            // Trigger location update when opening fullscreen map
            $this->dispatch('updateLocationOnModalOpen');
        }
    }

    public function closeFullscreenMap()
    {
        $this->showFullscreenModal = false;
        $this->selectedFullscreenLocation = null;
    }

    public function checkInToLocation($locationId)
    {
        // Use the existing startCheckIn method which already handles all the logic
        $this->startCheckIn($locationId);
        
        // Close the modal after attempting check-in
        $this->closeDetailsModal();
    }

    public function closeMap()
    {
        $this->selectedLocation = null;
    }

    public function startCheckIn($locationId)
    {
        try {
            $location = QuestLocation::findOrFail($locationId);

            // Check if location permission is granted
            if (!$this->locationPermissionGranted || !$this->userLatitude || !$this->userLongitude) {
                Log::warning('Location check failed for check-in', [
                    'user_id' => Auth::id(),
                    'location_permission_granted' => $this->locationPermissionGranted,
                    'user_latitude' => $this->userLatitude,
                    'user_longitude' => $this->userLongitude,
                    'location_id' => $locationId
                ]);
                
                $debugInfo = "Debug: Permission=" . ($this->locationPermissionGranted ? 'Yes' : 'No') . 
                           ", Lat=" . ($this->userLatitude ?: 'Missing') . 
                           ", Lng=" . ($this->userLongitude ?: 'Missing');
                
                session()->flash('error', 'Location access required. Please enable GPS and refresh the page. ' . $debugInfo);
                return;
            }

            // Calculate distance to location
            $distance = $this->calculateDistanceBetween(
                $this->userLatitude,
                $this->userLongitude,
                $location->latitude,
                $location->longitude
            ) * 1000; // Convert to meters

            // Check if user is within radius
            if ($distance > $location->radius) {
                session()->flash('error', "You're {$distance}m away. Get within {$location->radius}m to check in.");
                return;
            }

            // Check if user can still check in (max check-ins limit)
            if ($location->max_check_ins_per_user) {
                $currentCheckIns = $this->userProgress[$locationId]['check_ins_count'] ?? 0;
                
                if ($currentCheckIns >= $location->max_check_ins_per_user) {
                    session()->flash('error', 'Maximum check-ins reached for this location.');
                    return;
                }
            }

            // Create checkpoint
            $checkpoint = UserQuestCheckpoint::create([
                'user_id' => Auth::id(),
                'quest_location_id' => $locationId,
                'user_latitude' => $this->userLatitude,
                'user_longitude' => $this->userLongitude,
                'checked_at' => now(),
            ]);

            Log::info('Checkpoint created with location data', [
                'checkpoint_id' => $checkpoint->id,
                'user_id' => Auth::id(),
                'quest_location_id' => $locationId,
                'user_latitude' => $this->userLatitude,
                'user_longitude' => $this->userLongitude,
            ]);

            // Update user progress
            $this->loadUserProgress();

            // Success message
            $points = $location->quest_points ?? 0;
            $message = $points > 0 
                ? "Check-in successful! +{$points} points earned!" 
                : 'Check-in successful!';

            session()->flash('success', $message);

            Log::info('User checked in to quest location', [
                'user_id' => Auth::id(),
                'quest_location_id' => $locationId,
                'distance' => $distance,
                'points_earned' => $points
            ]);

        } catch (\Exception $e) {
            Log::error('Check-in failed', [
                'user_id' => Auth::id(),
                'quest_location_id' => $locationId,
                'error' => $e->getMessage()
            ]);

            session()->flash('error', 'Check-in failed. Please try again.');
        }
    }

    private function calculateTotalPoints(): int
    {
        if (empty($this->userProgress)) {
            return 0;
        }

        $locationIds = array_keys($this->userProgress);
        
        return QuestLocation::whereIn('id', $locationIds)
            ->sum('quest_points');
    }

    public function findShortestRoute($locationId)
    {
        $location = QuestLocation::find($locationId);
        
        if (!$location || !$this->locationPermissionGranted) {
            session()->flash('error', 'Location access required for route finding.');
            return;
        }

        try {
            // Get route data from OpenRouteService
            $routeData = $this->fetchRouteFromAPI(
                $this->userLatitude, 
                $this->userLongitude,
                $location->latitude,
                $location->longitude
            );

            if ($routeData) {
                $this->routeData = $routeData;
                $this->selectedRouteLocation = $location;
                $this->showRouteModal = true;
                
                // Dispatch event to show route on map
                $this->dispatch('showRoute', [
                    'routeData' => $routeData,
                    'destination' => [
                        'lat' => $location->latitude,
                        'lng' => $location->longitude,
                        'name' => $location->name
                    ]
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Route finding failed', [
                'user_id' => Auth::id(),
                'location_id' => $locationId,
                'error' => $e->getMessage()
            ]);
            
            session()->flash('error', 'Unable to find route. Please try again.');
        }
    }

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

        $response = \Http::withHeaders([
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

    public function closeRouteModal()
    {
        $this->showRouteModal = false;
        $this->routeData = [];
        $this->selectedRouteLocation = null;
    }

    public function getRoutingInstructions()
    {
        if (empty($this->routeData['instructions'])) {
            return [];
        }

        return collect($this->routeData['instructions'])->map(function ($step) {
            return [
                'instruction' => $step['instruction'] ?? 'Continue',
                'distance' => $step['distance'] ?? 0,
                'duration' => $step['duration'] ?? 0
            ];
        })->toArray();
    }
}