<?php

namespace App\Livewire\User;

use App\Models\QuestLocation;
use App\Models\UserQuestCheckpoint;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class QuestLocationDashboard extends Component
{
    // Location data
    public $userLatitude;
    public $userLongitude;
    public $locationPermissionGranted = false;
    public $locationAccuracy;
    public $lastLocationUpdate;

    // Quest data
    public $questLocations;
    public $locationDistances = [];
    public $withinRadiusStatus = [];
    public $checkedInStatus = [];
    public $checkInCounts = [];

    // UI state
    public $selectedLocationId;
    public $showScanner = false;
    public $loadingLocation = false;
    public $sortBy = 'distance'; // distance, name, status, points
    public $filterStatus = 'all'; // all, available, completed, nearby
    public $showMap = false;

    // Performance settings
    public $maxDistance = 50000; // Only load locations within 50km
    public $locationUpdateThrottle = 30; // Seconds between location updates

    protected $listeners = [
        'locationUpdated',
        'checkInToLocation',
        'openScanner',
        'refreshData' => 'updateLocationData'
    ];

    public function mount()
    {
        $this->loadQuestLocations();
        $this->loadUserCheckInData();
        $this->requestLocationOnMount();
    }

    public function render()
    {
        return view('livewire.user.quest-location-dashboard', [
            'filteredLocations' => $this->filteredLocations,
            'hasActiveLocations' => $this->questLocations->where('is_active', true)->count() > 0,
            'stats' => $this->stats
        ]);
    }

    // Computed Properties for Performance
    #[Computed]
    public function stats()
    {
        return [
            'total_locations' => $this->questLocations->count(),
            'completed_locations' => collect($this->checkedInStatus)->filter()->count(),
            'total_points' => $this->calculateTotalPoints(),
            'nearby_locations' => $this->getNearbyLocationsCount(),
            'available_points' => $this->getAvailablePoints()
        ];
    }

    #[Computed]
    public function filteredLocations()
    {
        $locations = $this->questLocations;

        // Apply filters
        switch ($this->filterStatus) {
            case 'available':
                $locations = $locations->filter(fn($location) => !($this->checkedInStatus[$location->id] ?? false));
                break;
            case 'completed':
                $locations = $locations->filter(fn($location) => $this->checkedInStatus[$location->id] ?? false);
                break;
            case 'nearby':
                $locations = $locations->filter(fn($location) => 
                    isset($this->locationDistances[$location->id]) && 
                    $this->locationDistances[$location->id] <= 1000 // Within 1km
                );
                break;
        }

        // Apply sorting
        return $locations->sortBy(function ($location) {
            return match($this->sortBy) {
                'distance' => $this->locationDistances[$location->id] ?? PHP_INT_MAX,
                'name' => $location->name,
                'status' => ($this->checkedInStatus[$location->id] ?? false) ? 1 : 0,
                'points' => -$location->quest_points, // Descending
                default => $location->id
            };
        })->values();
    }

    // Location Management with Throttling
    #[On('locationUpdated')]
    public function locationUpdated($latitude, $longitude, $accuracy = null)
    {
        // Throttle location updates to prevent excessive calculations
        if ($this->lastLocationUpdate && 
            Carbon::parse($this->lastLocationUpdate)->addSeconds($this->locationUpdateThrottle)->isFuture()) {
            return;
        }

        $this->userLatitude = $latitude;
        $this->userLongitude = $longitude;
        $this->locationAccuracy = $accuracy;
        $this->locationPermissionGranted = true;
        $this->lastLocationUpdate = now();
        $this->loadingLocation = false;

        Log::info('Location updated', [
            'user_id' => Auth::id(),
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy' => $accuracy
        ]);

        $this->updateLocationData();
        $this->dispatch('locationObtained');
    }

    public function requestLocation()
    {
        $this->loadingLocation = true;
        $this->dispatch('requestLocation');
    }

    private function requestLocationOnMount()
    {
        $this->dispatch('autoRequestLocation');
    }

    // Optimized Data Loading
    private function loadQuestLocations()
    {
        $query = QuestLocation::active()->orderBy('name');
        
        // If user location is available, only load nearby locations
        if ($this->userLatitude && $this->userLongitude) {
            $query->nearUser($this->userLatitude, $this->userLongitude, $this->maxDistance);
        }
        
        $this->questLocations = $query->get();
    }

    private function loadUserCheckInData()
    {
        $userId = Auth::id();
        $locationIds = $this->questLocations->pluck('id');
        
        // Batch load user check-in data
        $checkpoints = UserQuestCheckpoint::where('user_id', $userId)
            ->whereIn('quest_location_id', $locationIds)
            ->selectRaw('quest_location_id, COUNT(*) as count, MIN(created_at) as first_checkin')
            ->groupBy('quest_location_id')
            ->get()
            ->keyBy('quest_location_id');

        foreach ($locationIds as $locationId) {
            $checkpoint = $checkpoints->get($locationId);
            $this->checkedInStatus[$locationId] = $checkpoint !== null;
            $this->checkInCounts[$locationId] = $checkpoint->count ?? 0;
        }
    }

    private function updateLocationData()
    {
        if (!$this->locationPermissionGranted || !$this->userLatitude || !$this->userLongitude) {
            return;
        }

        // Update only distance-related data
        foreach ($this->questLocations as $location) {
            $locationId = $location->id;
            
            // Calculate distance
            $this->locationDistances[$locationId] = $location->calculateDistance(
                $this->userLatitude, 
                $this->userLongitude
            );
            
            // Check if within radius
            $this->withinRadiusStatus[$locationId] = $location->isWithinRadius(
                $this->userLatitude, 
                $this->userLongitude
            );
        }
    }

    // Enhanced Check-in Process
    #[On('checkInToLocation')]
    public function checkInToLocation($questLocationId)
    {
        try {
            if (!$this->validateLocationForCheckIn()) {
                return;
            }

            $questLocation = QuestLocation::findOrFail($questLocationId);

            if (!$this->validateCheckInEligibility($questLocation)) {
                return;
            }

            // Create checkpoint with additional metadata
            $checkpoint = UserQuestCheckpoint::create([
                'user_id' => Auth::id(),
                'quest_location_id' => $questLocationId,
                'user_latitude' => $this->userLatitude,
                'user_longitude' => $this->userLongitude,
                'checked_at' => now(),
                'accuracy' => $this->locationAccuracy,
                'distance_from_center' => $this->locationDistances[$questLocationId] ?? null,
                'device_info' => request()->userAgent()
            ]);

            // Update local state
            $this->checkedInStatus[$questLocationId] = true;
            $this->checkInCounts[$questLocationId] = ($this->checkInCounts[$questLocationId] ?? 0) + 1;

            // Clear related caches
            $this->clearLocationCaches($questLocationId);

            // Enhanced success message
            $points = $questLocation->quest_points ?? 0;
            $totalPoints = $this->calculateTotalPoints();
            $message = $points > 0 
                ? "Check-in successful! +{$points} points (Total: {$totalPoints})" 
                : 'Check-in successful!';

            $this->dispatch('showAlert', [
                'type' => 'success',
                'message' => $message,
                'duration' => 5000
            ]);

            // Trigger celebration animation if high points
            if ($points >= 100) {
                $this->dispatch('celebrateCheckIn');
            }

            Log::info('User checked in to quest location', [
                'user_id' => Auth::id(),
                'quest_location_id' => $questLocationId,
                'checkpoint_id' => $checkpoint->id,
                'distance' => $this->locationDistances[$questLocationId] ?? null,
                'points_earned' => $points
            ]);

        } catch (\Exception $e) {
            Log::error('Check-in failed', [
                'user_id' => Auth::id(),
                'quest_location_id' => $questLocationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->dispatch('showAlert', [
                'type' => 'error',
                'message' => 'Check-in failed. Please try again.'
            ]);
        }
    }

    private function validateLocationForCheckIn(): bool
    {
        if (!$this->userLatitude || !$this->userLongitude) {
            $this->dispatch('showAlert', [
                'type' => 'error',
                'message' => 'Location not detected. Please enable GPS and try again.'
            ]);
            return false;
        }

        // Check location accuracy
        if ($this->locationAccuracy && $this->locationAccuracy > 100) {
            $this->dispatch('showAlert', [
                'type' => 'warning',
                'message' => 'GPS accuracy is low. Please wait for better signal or move to an open area.'
            ]);
        }

        return true;
    }

    private function validateCheckInEligibility(QuestLocation $questLocation): bool
    {
        // Check if user is within radius
        if (!$questLocation->isWithinRadius($this->userLatitude, $this->userLongitude)) {
            $distance = $questLocation->calculateDistance($this->userLatitude, $this->userLongitude);
            $this->dispatch('showAlert', [
                'type' => 'error',
                'message' => "You're {$distance}m away. Get within {$questLocation->radius}m to check in."
            ]);
            return false;
        }

        // Check if user can still check in
        if (!$questLocation->canUserCheckIn(Auth::id())) {
            $maxCheckIns = $questLocation->max_check_ins_per_user ?? 1;
            $this->dispatch('showAlert', [
                'type' => 'warning',
                'message' => "Maximum check-ins reached ({$maxCheckIns}) for this location."
            ]);
            return false;
        }

        return true;
    }

    // Helper Methods
    private function calculateTotalPoints(): int
    {
        return $this->questLocations
            ->whereIn('id', array_keys(array_filter($this->checkedInStatus)))
            ->sum('quest_points');
    }

    private function getAvailablePoints(): int
    {
        return $this->questLocations
            ->whereNotIn('id', array_keys(array_filter($this->checkedInStatus)))
            ->sum('quest_points');
    }

    private function getNearbyLocationsCount(): int
    {
        if (!$this->locationPermissionGranted) return 0;
        
        return collect($this->locationDistances)
            ->filter(fn($distance) => $distance <= 1000)
            ->count();
    }

    private function clearLocationCaches($locationId): void
    {
        Cache::forget("quest_location_{$locationId}_total_checkins");
        Cache::forget("quest_location_{$locationId}_unique_users");
    }

    // UI Actions
    public function toggleMap()
    {
        $this->showMap = !$this->showMap;
        if ($this->showMap) {
            $this->dispatch('initializeMap', [
                'locations' => $this->questLocations->toArray(),
                'userLocation' => [
                    'lat' => $this->userLatitude,
                    'lng' => $this->userLongitude
                ]
            ]);
        }
    }

    public function focusLocation($locationId)
    {
        $location = $this->questLocations->find($locationId);
        if ($location) {
            $this->dispatch('focusMapLocation', [
                'lat' => $location->latitude,
                'lng' => $location->longitude,
                'name' => $location->name
            ]);
        }
    }

    // Utility methods for template
    public function getDistanceFor($locationId): ?float
    {
        return $this->locationDistances[$locationId] ?? null;
    }

    public function getWithinRadiusFor($locationId): bool
    {
        return $this->withinRadiusStatus[$locationId] ?? false;
    }

    public function getCheckedInFor($locationId): bool
    {
        return $this->checkedInStatus[$locationId] ?? false;
    }

    public function getCheckInCountFor($locationId): int
    {
        return $this->checkInCounts[$locationId] ?? 0;
    }

    public function canCheckInFor($locationId): bool
    {
        if (!$this->locationPermissionGranted) return false;
        
        $location = $this->questLocations->find($locationId);
        if (!$location) return false;

        return $location->canUserCheckIn(Auth::id()) && 
               ($this->withinRadiusStatus[$locationId] ?? false);
    }

    public function getLocationStatusFor($locationId): string
    {
        if ($this->getCheckedInFor($locationId)) {
            return 'completed';
        }
        
        if ($this->getWithinRadiusFor($locationId)) {
            return 'available';
        }
        
        $distance = $this->getDistanceFor($locationId);
        if ($distance && $distance <= 1000) {
            return 'nearby';
        }
        
        return 'distant';
    }

    // Data refresh
    public function refreshData()
    {
        $this->loadQuestLocations();
        $this->loadUserCheckInData();
        $this->updateLocationData();
        
        $this->dispatch('showAlert', [
            'type' => 'info',
            'message' => 'Data refreshed successfully!'
        ]);
    }
}