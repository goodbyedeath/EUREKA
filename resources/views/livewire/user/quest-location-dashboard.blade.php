<div class="container mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-6">Quest Locations</h1>


    <!-- Location Permission -->
    @if(!$locationPermissionGranted)
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4 mb-6">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-yellow-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
                <div>
                    <h3 class="font-semibold text-yellow-800 dark:text-yellow-200">Location Permission Required</h3>
                    <p class="text-yellow-700 dark:text-yellow-300 text-sm">Enable GPS and allow location access to use quest features.</p>
                </div>
            </div>
            <button onclick="requestLocation()" 
                    class="mt-3 bg-yellow-500 hover:bg-yellow-600 dark:bg-yellow-600 dark:hover:bg-yellow-700 text-white px-4 py-2 rounded text-sm">
                Enable Location
            </button>
        </div>
    @endif

    <!-- Current Location -->
    @if($locationPermissionGranted && $userLatitude)
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg p-4 mb-6">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-green-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                </svg>
                <div>
                    <span class="font-semibold text-green-800 dark:text-green-200">Current Location:</span>
                    <span class="text-green-700 dark:text-green-300 text-sm">{{ number_format($userLatitude, 6) }}, {{ number_format($userLongitude, 6) }}</span>
                </div>
            </div>
        </div>
    @endif

    <!-- Quest Locations List -->
    <div class="space-y-4">
        @if($questLocations && count($questLocations) > 0)
            @foreach($questLocations as $location)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">

                    <div class="flex flex-col md:flex-row">
                        <!-- Map Section -->
                        <div class="w-full md:w-1/3 h-48 md:h-auto bg-gray-200 dark:bg-gray-700 relative">
                            @if(isset($location->google_map_embed_url) && $location->google_map_embed_url)
                                <iframe 
                                    src="{{ $location->google_map_embed_url }}" 
                                    width="100%" 
                                    height="100%" 
                                    style="border:0;" 
                                    allowfullscreen="" 
                                    loading="lazy"
                                    referrerpolicy="no-referrer-when-downgrade"
                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                </iframe>
                                <!-- Fallback div that shows when iframe fails -->
                                <div class="h-full flex items-center justify-center text-gray-500 dark:text-gray-400 absolute inset-0" style="display: none;">
                                    <div class="text-center">
                                        <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                        <p class="text-sm">Map temporarily unavailable</p>
                                    </div>
                                </div>
                            @else
                                <div class="h-full flex items-center justify-center text-gray-500 dark:text-gray-400">
                                    <div class="text-center">
                                        <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                        <p class="text-sm">No map available</p>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Content Section -->
                        <div class="flex-1 p-4 md:p-6">
                            <div class="flex flex-col md:flex-row md:items-start md:justify-between h-full">
                                <!-- Left Content -->
                                <div class="flex-1 mb-4 md:mb-0 md:mr-6">
                                    <h3 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-2">{{ $location->name ?? 'Unnamed Location' }}</h3>
                                    
                                    @if(isset($location->description) && $location->description)
                                        <p class="text-gray-600 dark:text-gray-400 text-sm mb-3">{{ $location->description }}</p>
                                    @endif
                                    
                                    @if(isset($location->what_to_do) && $location->what_to_do)
                                        <div class="bg-blue-50 dark:bg-blue-900/20 p-3 rounded mb-3">
                                            <h4 class="font-semibold text-blue-800 dark:text-blue-200 text-sm mb-1">Instructions:</h4>
                                            <p class="text-blue-700 dark:text-blue-300 text-sm">{{ $location->what_to_do }}</p>
                                        </div>
                                    @endif

                                    @php
                                        // Get computed values safely
                                        $distance = null;
                                        $withinRadius = false;
                                        $checkedIn = false;
                                        
                                        if ($locationPermissionGranted && isset($locationDistances) && is_array($locationDistances)) {
                                            $distance = $locationDistances[$location->id] ?? null;
                                        }
                                        
                                        if ($locationPermissionGranted && isset($withinRadiusStatus) && is_array($withinRadiusStatus)) {
                                            $withinRadius = $withinRadiusStatus[$location->id] ?? false;
                                        }
                                        
                                        if ($locationPermissionGranted && isset($checkedInStatus) && is_array($checkedInStatus)) {
                                            $checkedIn = $checkedInStatus[$location->id] ?? false;
                                        }
                                        
                                        $locationEnabled = $locationPermissionGranted && $userLatitude;
                                        
                                        // Format distance with comma as decimal separator
                                        $formattedDistance = null;
                                        if ($distance !== null) {
                                            $formattedDistance = number_format($distance, 2, ',', '.') . ' m';
                                        }
                                    @endphp
                                    
                                    
                                    <!-- Distance Info -->
                                    <div class="flex items-center justify-between text-sm mb-3">
                                        <span class="{{ $locationEnabled ? 'text-gray-600 dark:text-gray-400' : 'text-gray-400 dark:text-gray-500' }}">
                                            Distance: {{ $locationEnabled && $formattedDistance ? $formattedDistance : 'Location required' }}
                                        </span>
                                        <span class="px-2 py-1 rounded text-xs font-medium {{ $locationEnabled && $withinRadius ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200' : ($locationEnabled ? 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200' : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400') }}">
                                            {{ $locationEnabled ? ($withinRadius ? 'In Range' : 'Out of Range') : 'Unknown' }}
                                        </span>
                                    </div>

                                    <!-- Check-in Status -->
                                    @if($checkedIn)
                                        <div class="flex items-center mb-3 text-green-600">
                                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                            </svg>
                                            <span class="text-sm font-medium">Checked In</span>
                                        </div>
                                    @endif
                                </div>

                                <!-- Right Side Actions -->
                                <div class="w-full md:w-48 space-y-2">
                                    <!-- Check-in Button -->
                                    @php
                                        $canCheckIn = $locationEnabled && $withinRadius && !$checkedIn;
                                        $checkInDisabled = !$canCheckIn;
                                        
                                        if ($checkedIn) {
                                            $checkInText = 'Already Checked In';
                                            $checkInClass = 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-200 cursor-default border border-green-200 dark:border-green-700';
                                        } elseif (!$locationEnabled) {
                                            $checkInText = 'Enable Location to Check In';
                                            $checkInClass = 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 cursor-not-allowed';
                                        } elseif (!$withinRadius) {
                                            $checkInText = 'Move Closer to Check In';
                                            $checkInClass = 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 cursor-not-allowed';
                                        } else {
                                            $checkInText = 'Check In';
                                            $checkInClass = 'bg-blue-500 hover:bg-blue-600 dark:bg-blue-600 dark:hover:bg-blue-700 text-white cursor-pointer';
                                        }
                                    @endphp
                                    
                                    <button 
                                        wire:click="{{ $canCheckIn ? 'checkInToLocation(' . $location->id . ')' : '' }}"
                                        class="w-full py-2 px-4 rounded text-sm font-medium transition-colors {{ $checkInClass }}"
                                        {{ $checkInDisabled ? 'disabled' : '' }}>
                                        {{ $checkInText }}
                                    </button>


                                    <!-- Status Helper Text -->
                                    @if(!$locationEnabled)
                                        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded p-3 mt-2">
                                            <div class="flex items-center">
                                                <svg class="w-4 h-4 text-yellow-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                                </svg>
                                                <span class="text-yellow-800 dark:text-yellow-200 text-xs">Click "Enable Location" above to activate quest features</span>
                                            </div>
                                        </div>
                                    @elseif($locationEnabled && !$withinRadius && !$checkedIn)
                                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded p-3 mt-2">
                                            <div class="flex items-center">
                                                <svg class="w-4 h-4 text-blue-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                                </svg>
                                                <span class="text-blue-800 dark:text-blue-200 text-xs">Move closer to the location to check in</span>
                                            </div>
                                        </div>
                                    @elseif($checkedIn)
                                        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded p-3 mt-2">
                                            <div class="flex items-center">
                                                <svg class="w-4 h-4 text-green-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                </svg>
                                                <span class="text-green-800 dark:text-green-200 text-xs">You're checked in! Ready for quest activities</span>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        @else
            <div class="text-center py-12">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 0 1-2.827 0l-4.244-4.243a8 8 0 1 1 11.314 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"></path>
                </svg>
                <h3 class="text-lg font-medium text-gray-700 dark:text-gray-300 mb-2">No Quest Locations</h3>
                <p class="text-gray-500 dark:text-gray-400">There are no active quest locations available at the moment.</p>
            </div>
        @endif
    </div>
</div>

<script>
let watchId = null;


function requestLocation() {
    if (!navigator.geolocation) {
        alert('Geolocation is not supported by this browser.');
        return;
    }

    // Get current position
    navigator.geolocation.getCurrentPosition(
        position => {
            @this.call('locationUpdated', position.coords.latitude, position.coords.longitude);
        },
        error => {
            alert('Unable to get your location. Please check your GPS settings.');
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 300000
        }
    );

    // Watch position for updates
    watchId = navigator.geolocation.watchPosition(
        position => {
            @this.call('locationUpdated', position.coords.latitude, position.coords.longitude);
        },
        error => {},
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 60000
        }
    );
}

document.addEventListener('DOMContentLoaded', function() {
    requestLocation();
});

window.addEventListener('beforeunload', () => {
    if (watchId) {
        navigator.geolocation.clearWatch(watchId);
    }
});

</script>