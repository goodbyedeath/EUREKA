<div>
    <div class="container mx-auto px-4 py-6">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-2">{{ __('games.outdoor_game_locations') }}</h1>
            <p class="text-gray-600 dark:text-gray-400">{{ __('games.explore_outdoor_locations') }}</p>
        </div>

        <!-- Games Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @forelse($games as $game)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
                <!-- Game Image -->
                <div class="relative h-48">
                    @if($game->image_path)
                        <img src="{{ Storage::url($game->image_path) }}" alt="{{ $game->name }}" 
                             class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full bg-gradient-to-br from-blue-400 to-purple-500 flex items-center justify-center">
                            <svg class="w-16 h-16 text-white opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 0 1-2.827 0l-4.244-4.243a8 8 0 1 1 11.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"></path>
                            </svg>
                        </div>
                    @endif
                    
                    <!-- Status Indicator -->
                    <div class="absolute top-3 right-3">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200">
                            {{ __('games.active') }}
                        </span>
                    </div>

                    <!-- Map Badge -->
                    @if($game->map_image_path)
                    <div class="absolute bottom-3 left-3">
                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-600 dark:bg-blue-700 text-white">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                            </svg>
                            360° View
                        </span>
                    </div>
                    @endif
                </div>

                <!-- Game Info -->
                <div class="p-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ $game->name }}</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm mb-3 line-clamp-2">{{ $game->description }}</p>
                    
                    <!-- Stats -->
                    <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-4">
                        <div class="flex items-center">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                            {{ $game->quest_points }} Points
                        </div>
                        <div class="flex items-center">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 0 1-2.827 0l-4.244-4.243a8 8 0 1 1 11.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"></path>
                            </svg>
                            {{ $game->radius }}m Radius
                        </div>
                    </div>

                    <!-- Action Button -->
                    <a href="{{ route('user.panorama.view', $game->id) }}" target="_blank"
                       class="w-full bg-blue-600 dark:bg-blue-700 hover:bg-blue-700 dark:hover:bg-blue-800 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 inline-block text-center">
                        {{ __('games.view_details') }}
                    </a>
                </div>
            </div>
            @empty
            <!-- Empty State -->
            <div class="col-span-full">
                <div class="text-center py-12 bg-white dark:bg-gray-800 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600">
                    <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 0 1-2.827 0l-4.244-4.243a8 8 0 1 1 11.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"></path>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">{{ __('games.no_locations_found') }}</h3>
                    <p class="text-gray-500 dark:text-gray-400">{{ __('games.admin_will_create_locations') }}</p>
                </div>
            </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($games->hasPages())
        <div class="mt-8">
            {{ $games->links() }}
        </div>
        @endif
    </div>

    <!-- Game Detail Modal -->
    @if($showModal && $selectedGame)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-50 dark:bg-gray-700 bg-opacity-75 transition-opacity" wire:click="closeModal"></div>

            <!-- Modal panel -->
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-6xl sm:w-full sm:p-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $selectedGame->name }}</h3>
                        <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $selectedGame->description }}</p>
                    </div>
                    <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-400 focus:outline-none">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Left Column - 360° Panoramic View -->
                    <div class="lg:col-span-2">
                        @if($selectedGame->map_image_path)
                        <div class="bg-gray-100 dark:bg-gray-700 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="text-lg font-semibold flex items-center gap-2">
                                    <i class="fas fa-globe text-blue-600"></i>
                                    360° Panoramic View
                                </h4>
                                <button type="button" onclick="toggleFullscreen()" 
                                        class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                                        title="Toggle Fullscreen">
                                    <i class="fas fa-expand text-sm"></i>
                                </button>
                            </div>
                            
                            <div id="panorama-container" class="relative bg-white dark:bg-gray-800 rounded border overflow-hidden" style="height: 450px;">
                                <div id="panorama-viewer" class="w-full h-full"></div>
                                
                                <!-- Loading indicator -->
                                <div id="panorama-loading" class="absolute inset-0 flex items-center justify-center bg-gray-100 dark:bg-gray-800">
                                    <div class="text-center">
                                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-2"></div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Loading 360° view...</p>
                                    </div>
                                </div>
                                
                                <!-- Controls -->
                                <div class="absolute top-3 right-3 flex flex-col gap-2">
                                    <button type="button" onclick="resetView()" 
                                            class="bg-white dark:bg-gray-800 bg-opacity-90 hover:bg-opacity-100 text-gray-700 dark:text-gray-300 p-2 rounded-lg shadow-sm transition-all hover:scale-105"
                                            title="Reset view">
                                        <i class="fas fa-home text-sm"></i>
                                    </button>
                                    <button type="button" onclick="debugUserPanorama()" 
                                            class="bg-white dark:bg-gray-800 bg-opacity-90 hover:bg-opacity-100 text-gray-700 dark:text-gray-300 p-2 rounded-lg shadow-sm transition-all hover:scale-105"
                                            title="Debug panorama">
                                        <i class="fas fa-bug text-sm"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Instructions -->
                            <div class="text-center text-xs text-gray-500 dark:text-gray-400 mt-3">
                                <p class="font-medium">
                                    <i class="fas fa-mouse text-blue-500 mr-1"></i>
                                    Drag to look around • Scroll to zoom • Click hotspots to explore
                                </p>
                            </div>
                        </div>
                        @else
                        <div class="bg-gray-100 dark:bg-gray-700 rounded-lg p-8 text-center">
                            <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 0 1 3 16.382V5.618a1 1 0 0 1 1.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0 0 21 18.382V7.618a1 1 0 0 0-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                            </svg>
                            <p class="text-gray-500 dark:text-gray-400">No 360° view available</p>
                        </div>
                        @endif
                    </div>

                    <!-- Right Column - Details -->
                    <div class="space-y-4">
                        <!-- What to do -->
                        <div>
                            <h4 class="text-lg font-semibold mb-2">What to do</h4>
                            <p class="text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-700 p-3 rounded">
                                {{ $selectedGame->what_to_do ?? $selectedGame->description }}
                            </p>
                        </div>

                        <!-- Location Info -->
                        <div>
                            <h4 class="text-lg font-semibold mb-2">Location Information</h4>
                            <div class="space-y-2">
                                @if($selectedGame->default_pitch || $selectedGame->default_yaw)
                                <div class="flex items-center text-sm">
                                    <svg class="w-4 h-4 mr-2 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0 1 21 8.618v6.764a1 1 0 0 1-1.447.894L15 14M9 7l6 3m0 0l-6 3m6-3v6"></path>
                                    </svg>
                                    <span>Default View: {{ number_format($selectedGame->default_pitch ?? 0, 1) }}°, {{ number_format($selectedGame->default_yaw ?? 0, 1) }}°</span>
                                </div>
                                @endif
                                <div class="flex items-center text-sm">
                                    <svg class="w-4 h-4 mr-2 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                    </svg>
                                    <span>{{ $selectedGame->quest_points }} Quest Points</span>
                                </div>
                                <div class="flex items-center text-sm">
                                    <svg class="w-4 h-4 mr-2 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"></path>
                                    </svg>
                                    <span>{{ $selectedGame->radius }}m Check-in Radius</span>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="pt-4">
                            <button wire:click="closeModal" 
                                    class="w-full bg-gray-600 text-white py-2 px-4 rounded-lg hover:bg-gray-700 transition-colors text-sm font-medium">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Panorama Viewer Script -->
<script>
let userPanellumViewer = null;

// Initialize panorama when modal opens
document.addEventListener('livewire:init', function() {
    console.log('🔧 User panorama event listeners initialized');
    
    Livewire.on('game-selected', function(data) {
        console.log('🎮 Game selected event received:', data);
        setTimeout(() => initializeUserPanorama(), 500);
    });
    
    Livewire.on('game-modal-cleanup', function() {
        console.log('🧹 Game modal cleanup event received');
        cleanupPanorama();
    });
});

function initializeUserPanorama() {
    console.log('🎯 Initializing user panorama viewer');
    console.log('🔍 DOM elements check:', {
        container: !!document.getElementById('panorama-viewer'),
        loading: !!document.getElementById('panorama-loading'),
        modal: !!document.querySelector('[role="dialog"]')
    });
    
    const container = document.getElementById('panorama-viewer');
    const loading = document.getElementById('panorama-loading');
    
    if (!container) {
        console.error('❌ Panorama container not found');
        return;
    }
    
    // Get panorama URL from selected game
    const panoramaUrl = '{{ $selectedGame->map_image_path ?? '' }}';
    if (!panoramaUrl) {
        console.error('❌ No panorama image available');
        if (loading) loading.innerHTML = '<div class="text-center text-red-600">No 360° image available</div>';
        return;
    }
    
    const fullPanoramaUrl = '/storage/' + panoramaUrl;
    console.log('📸 Loading panorama:', fullPanoramaUrl);
    
    // Check if Panellum is available
    console.log('🔍 Checking Panellum availability:', {
        typeof_pannellum: typeof window.pannellum,
        pannellum_object: window.pannellum
    });
    
    if (typeof window.pannellum === 'undefined') {
        console.error('❌ Panellum library not loaded');
        if (loading) loading.innerHTML = '<div class="text-center text-red-600"><i class="fas fa-exclamation-triangle mr-2"></i>360° viewer library not loaded</div>';
        return;
    }
    
    // Test if image is accessible before initializing Panellum
    const testImg = new Image();
    testImg.onload = function() {
        console.log('✅ Image accessible, initializing viewer');
        initializePanellumViewer(container, loading, fullPanoramaUrl);
    };
    testImg.onerror = function() {
        console.error('❌ Image not accessible:', fullPanoramaUrl);
        if (loading) loading.innerHTML = '<div class="text-center text-red-600"><i class="fas fa-exclamation-triangle mr-2"></i>Failed to load panoramic image</div>';
    };
    testImg.src = fullPanoramaUrl;
}

function initializePanellumViewer(container, loading, fullPanoramaUrl) {
    console.log('🚀 Initializing Panellum viewer with URL:', fullPanoramaUrl);
    
    try {
        // Clean up any existing viewer
        if (userPanellumViewer) {
            try {
                userPanellumViewer.destroy();
            } catch (e) {
                console.warn('Error destroying existing viewer:', e);
            }
            userPanellumViewer = null;
        }
        
        // Clear container
        container.innerHTML = '';
        
        // Create new viewer with enhanced config (following admin pattern)
        console.log('📋 Creating Panellum viewer with enhanced config...');
        
        const panoramaConfig = {
            type: 'equirectangular',
            panorama: fullPanoramaUrl,
            autoLoad: true,
            showControls: true,
            showFullscreenCtrl: false, // We handle fullscreen manually
            showZoomCtrl: true,
            mouseZoom: true,
            keyboardZoom: true,
            draggable: true,
            // Enhanced settings matching admin side
            default: {
                pitch: {{ $selectedGame->default_pitch ?? 0 }},
                yaw: {{ $selectedGame->default_yaw ?? 0 }},
                hfov: 90
            },
            hfov: 90,
            minHfov: 50,
            maxHfov: 120,
            // Pitch constraints for 360° photos
            minPitch: -90,
            maxPitch: 90,
            // Auto-rotation for user experience
            autoRotate: -2,
            autoRotateInactivityDelay: 3000,
            autoRotateStopDelay: 2000,
            // Add compass for orientation reference
            compass: true,
            northOffset: 0,
            hotSpots: [] // Will be populated after load
        };
        
        console.log('🔧 Panorama config:', panoramaConfig);
        userPanellumViewer = window.pannellum.viewer('panorama-viewer', panoramaConfig);
        
        console.log('✅ Panellum viewer created successfully:', userPanellumViewer);
        
        // Add click listener for coordinate detection (like admin side)
        const panoramaContainer = document.getElementById('panorama-viewer');
        if (panoramaContainer) {
            console.log('📍 Adding user panorama click listener for coordinate detection');
            panoramaContainer.addEventListener('click', function(e) {
                console.log('🎯 User panorama clicked - detecting coordinates...');
                
                // ADMIN-PRECISION coordinate detection using mouseEventToCoords
                try {
                    const coords = userPanellumViewer.mouseEventToCoords(e);
                    if (coords && coords.length >= 2 && !isNaN(coords[0]) && !isNaN(coords[1])) {
                        const pitch = parseFloat(coords[0]);
                        const yaw = parseFloat(coords[1]);
                        
                        console.log('📍 PRECISE user click coordinates:');
                        console.log('  - Pitch (vertical):', pitch.toFixed(5) + '°');
                        console.log('  - Yaw (horizontal):', yaw.toFixed(5) + '°');
                        console.log('  - Coordinate source: mouseEventToCoords (ADMIN-PRECISION)');
                        
                        showUserCoordinates(pitch, yaw);
                    }
                } catch (error) {
                    console.log('⚠️ mouseEventToCoords failed, using fallback:', error.message);
                    
                    // Fallback to current view center
                    const pitch = userPanellumViewer.getPitch();
                    const yaw = userPanellumViewer.getYaw();
                    console.log('📍 Fallback coordinates:', pitch.toFixed(2) + '°,', yaw.toFixed(2) + '°');
                    showUserCoordinates(pitch, yaw);
                }
            });
        }

        // Add event listeners
        userPanellumViewer.on('load', function() {
            console.log('🎉 User panorama loaded successfully');
            if (loading) {
                loading.style.display = 'none';
            }
            
            // Load hotspots from database
            loadHotspotsFromDatabase();
        });
        
        userPanellumViewer.on('error', function(err) {
            console.error('❌ User panorama error:', err);
            if (loading) {
                loading.innerHTML = '<div class="text-center text-red-600"><i class="fas fa-exclamation-triangle mr-2"></i>Failed to load 360° view<br><small class="text-xs">' + (err.message || 'Unknown error') + '</small></div>';
            }
        });
        
    } catch (error) {
        console.error('❌ Error creating user panorama viewer:', error);
        if (loading) {
            loading.innerHTML = '<div class="text-center text-red-600"><i class="fas fa-exclamation-triangle mr-2"></i>Error initializing 360° viewer<br><small class="text-xs">' + (error.message || 'Unknown error') + '</small></div>';
        }
    }
}

function loadHotspotsFromDatabase() {
    if (!userPanellumViewer) return;
    
    const gameId = {{ $selectedGame->id ?? 'null' }};
    if (!gameId) return;
    
    console.log('🎯 Loading hotspots for game:', gameId);
    
    // Call Livewire method to get hotspots
    @this.call('getHotspots', gameId)
        .then(hotspots => {
            if (hotspots && hotspots.length > 0) {
                console.log('📍 Loading user hotspots from database...');
                console.log('📊 Hotspot count:', hotspots.length);
                
                hotspots.forEach(hotspot => {
                    try {
                        // ADMIN-PRECISION coordinate detection (EXACT copy from admin panorama/view.blade.php)
                        let pitchDegrees, yawDegrees, coordinateSystem;
                        
                        // Get raw values from database 
                        const rawPitch = parseFloat(hotspot.pitch);
                        const rawYaw = parseFloat(hotspot.yaw);
                        
                        // EXACT admin detection logic - Check if values are in degree range (outside radian limits)
                        if (Math.abs(rawPitch) > 1.571 || Math.abs(rawYaw) > 3.142) {
                            // Values are likely in degrees already
                            pitchDegrees = rawPitch;
                            yawDegrees = rawYaw;
                            coordinateSystem = 'degrees (legacy)';
                        } else {
                            // Values are likely in radians - convert to degrees normally
                            pitchDegrees = rawPitch * (180 / Math.PI);
                            yawDegrees = rawYaw * (180 / Math.PI);
                            coordinateSystem = 'radians (converted)';
                        }
                        
                        // ADMIN validation - Validate final values are reasonable for panorama display
                        if (Math.abs(pitchDegrees) > 90 || Math.abs(yawDegrees) > 180) {
                            console.warn(`⚠️ Invalid coordinates for hotspot ${hotspot.id} after conversion - using defaults`);
                            pitchDegrees = 0;
                            yawDegrees = 0;
                            coordinateSystem = 'defaulted (invalid)';
                        }
                        
                        console.log(`📍 Loading user hotspot ${hotspot.id}:`);
                        console.log(`  - DB Values: pitch=${rawPitch}, yaw=${rawYaw}`);
                        console.log(`  - System detected: ${coordinateSystem}`);
                        console.log(`  - Final degrees: pitch=${pitchDegrees.toFixed(2)}°, yaw=${yawDegrees.toFixed(2)}°`);
                        console.log(`  - Type: ${hotspot.hotspot_type}, Has Image: ${hotspot.has_image || false}`);
                        
                        // ADMIN-STYLE Enhanced Pannellum hotspot configuration with tour features (EXACT copy from admin)
                        const hotspotConfig = {
                            id: 'hotspot-' + hotspot.id,
                            pitch: pitchDegrees,
                            yaw: yawDegrees,
                            type: 'info',
                            text: hotspot.title
                        };
                        
                        // DON'T override CSS classes - use default Pannellum styling for proper positioning
                        
                        // Add tour-specific click handler based on hotspot type
                        if (hotspot.hotspot_type === 'navigation') {
                            hotspotConfig.clickHandlerFunc = function() {
                                handleNavigationHotspot(hotspot.id, hotspot.target_location_id, hotspot.title);
                            };
                        } else if (hotspot.hotspot_type === 'quiz') {
                            hotspotConfig.clickHandlerFunc = function() {
                                handleQuizHotspot(hotspot.id, hotspot.quiz_data, hotspot.title);
                            };
                        } else if (hotspot.hotspot_type === 'info') {
                            // Info hotspot - check if has image
                            hotspotConfig.clickHandlerFunc = function() {
                                showUserInfoPopup(hotspot.id, hotspot.title, hotspot.content, hotspot.has_image ? hotspot.image_url : null);
                            };
                        } else {
                            // Fallback for unknown types
                            hotspotConfig.clickHandlerFunc = function() {
                                showUserInfoPopup(hotspot.id, hotspot.title, hotspot.content, null);
                            };
                        }
                        
                        userPanellumViewer.addHotSpot(hotspotConfig);
                        console.log(`📌 Added user hotspot ${hotspot.id} to Pannellum: ${hotspot.title} at (${pitchDegrees.toFixed(2)}°, ${yawDegrees.toFixed(2)}°)`);
                        
                    } catch (e) {
                        console.warn('Failed to add user hotspot:', hotspot.id, e);
                    }
                });
                console.log(`✅ Loaded ${hotspots.length} tour-enabled hotspots with smart coordinate detection`);
            }
        })
        .catch(error => {
            console.warn('Could not load hotspots:', error);
        });
}

function resetView() {
    if (userPanellumViewer) {
        userPanellumViewer.lookAt(0, 0, 90, 1000);
    }
}

function toggleFullscreen() {
    const container = document.getElementById('panorama-container');
    if (!container) return;

    if (!document.fullscreenElement) {
        container.requestFullscreen().then(() => {
            if (userPanellumViewer) {
                setTimeout(() => userPanellumViewer.resize(), 100);
            }
        });
    } else {
        document.exitFullscreen().then(() => {
            if (userPanellumViewer) {
                setTimeout(() => userPanellumViewer.resize(), 100);
            }
        });
    }
}

function cleanupPanorama() {
    console.log('🧹 Cleaning up panorama viewer');
    if (userPanellumViewer) {
        try {
            userPanellumViewer.destroy();
        } catch (e) {
            console.warn('Error during cleanup:', e);
        }
        userPanellumViewer = null;
    }
}

// Get CSS class based on hotspot type for users
function getUserHotspotCssClass(hotspotType) {
    switch (hotspotType) {
        case 'navigation':
            return 'user-hotspot-navigation';
        case 'quiz':
            return 'user-hotspot-quiz';
        case 'info':
        default:
            return 'user-hotspot-info';
    }
}

// Legacy function name for compatibility
function getHotspotCssClass(hotspotType) {
    return getUserHotspotCssClass(hotspotType);
}

// Show user info popup with image support
function showUserInfoPopup(hotspotId, title, content, imageUrl) {
    console.log('📷 Showing user info popup:', { hotspotId, title, content, imageUrl });
    
    // Create popup content
    let popupContent = `
        <div class="max-w-lg bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 m-4">
            <div class="flex items-start justify-between mb-4">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">${title}</h3>
                <button onclick="closeUserPopup()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 ml-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>`;
    
    // Add image if available
    if (imageUrl) {
        popupContent += `
            <div class="mb-4">
                <img src="${imageUrl}" alt="${title}" class="w-full h-56 object-cover rounded-lg border border-gray-200 dark:border-gray-600 shadow-sm">
            </div>`;
    }
    
    // Add content if available
    if (content && content.trim()) {
        popupContent += `
            <div class="text-gray-700 dark:text-gray-300 leading-relaxed mb-4">
                ${content.replace(/\n/g, '<br>')}
            </div>`;
    }
    
    popupContent += `
            <div class="flex justify-end">
                <button onclick="closeUserPopup()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                    Close
                </button>
            </div>
        </div>`;
    
    // Create popup overlay
    const overlay = document.createElement('div');
    overlay.id = 'user-popup-overlay';
    overlay.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4';
    overlay.innerHTML = popupContent;
    
    // Close on overlay click
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) {
            closeUserPopup();
        }
    });
    
    document.body.appendChild(overlay);
    
    // Log interaction with Livewire
    @this.call('handleHotspotClick', hotspotId, 'info');
}

// Handle navigation hotspot
function handleNavigationHotspot(hotspotId, targetLocationId, title) {
    console.log('🚪 Navigation hotspot clicked:', { hotspotId, targetLocationId, title });
    
    // Show confirmation popup
    const popupContent = `
        <div class="max-w-md bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 m-4">
            <div class="text-center">
                <div class="mb-4">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900/30">
                        <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                        </svg>
                    </div>
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Navigate to Location</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">${title}</p>
                <div class="flex justify-center space-x-3">
                    <button onclick="closeUserPopup()" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg transition-colors">
                        Cancel
                    </button>
                    <button onclick="navigateToLocation(${targetLocationId})" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                        Go There
                    </button>
                </div>
            </div>
        </div>`;
    
    // Create popup overlay
    const overlay = document.createElement('div');
    overlay.id = 'user-popup-overlay';
    overlay.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4';
    overlay.innerHTML = popupContent;
    
    document.body.appendChild(overlay);
    
    // Log interaction with Livewire
    @this.call('handleHotspotClick', hotspotId, 'navigation');
}

// Handle quiz hotspot
function handleQuizHotspot(hotspotId, quizData, title) {
    console.log('❓ Quiz hotspot clicked:', { hotspotId, quizData, title });
    
    if (!quizData || !quizData.question) {
        console.error('Invalid quiz data');
        return;
    }
    
    // Show quiz popup
    let optionsHtml = '';
    if (quizData.options) {
        Object.keys(quizData.options).forEach(key => {
            if (quizData.options[key]) {
                optionsHtml += `
                    <button onclick="submitQuizAnswer(${hotspotId}, '${key}', '${quizData.correct_answer}')" 
                            class="w-full text-left bg-gray-50 dark:bg-gray-700 hover:bg-blue-50 dark:hover:bg-blue-900/30 p-3 rounded-lg border border-gray-200 dark:border-gray-600 transition-colors mb-2">
                        <span class="font-medium">${key.toUpperCase()}.</span> ${quizData.options[key]}
                    </button>`;
            }
        });
    }
    
    const popupContent = `
        <div class="max-w-lg bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 m-4">
            <div class="text-center mb-6">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 dark:bg-yellow-900/30 mb-4">
                    <svg class="h-6 w-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">${title}</h3>
            </div>
            <div class="mb-6">
                <p class="text-gray-700 dark:text-gray-300 mb-4 font-medium">${quizData.question}</p>
                <div class="space-y-2">
                    ${optionsHtml}
                </div>
            </div>
            <div class="text-center">
                <button onclick="closeUserPopup()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 text-sm">
                    Close Quiz
                </button>
            </div>
        </div>`;
    
    // Create popup overlay
    const overlay = document.createElement('div');
    overlay.id = 'user-popup-overlay';
    overlay.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4';
    overlay.innerHTML = popupContent;
    
    document.body.appendChild(overlay);
    
    // Log interaction with Livewire
    @this.call('handleHotspotClick', hotspotId, 'quiz');
}

// Submit quiz answer
function submitQuizAnswer(hotspotId, selectedAnswer, correctAnswer) {
    const isCorrect = selectedAnswer === correctAnswer;
    
    // Show result
    const resultHtml = `
        <div class="max-w-md bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 m-4">
            <div class="text-center">
                <div class="mb-4">
                    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full ${isCorrect ? 'bg-green-100 dark:bg-green-900/30' : 'bg-red-100 dark:bg-red-900/30'}">
                        ${isCorrect ? 
                            '<svg class="h-8 w-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>' :
                            '<svg class="h-8 w-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>'
                        }
                    </div>
                </div>
                <h3 class="text-xl font-semibold ${isCorrect ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200'} mb-2">
                    ${isCorrect ? '🎉 Correct!' : '❌ Incorrect'}
                </h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    ${isCorrect ? 
                        'Great job! You got it right.' : 
                        `The correct answer was ${correctAnswer.toUpperCase()}.`
                    }
                </p>
                <button onclick="closeUserPopup()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors">
                    Continue
                </button>
            </div>
        </div>`;
    
    // Update popup content
    const overlay = document.getElementById('user-popup-overlay');
    if (overlay) {
        overlay.innerHTML = resultHtml;
    }
    
    console.log(`Quiz answer submitted: ${isCorrect ? 'Correct' : 'Incorrect'}`);
}

// Navigate to another location
function navigateToLocation(targetLocationId) {
    console.log('🚀 Navigating to location:', targetLocationId);
    closeUserPopup();
    
    // Here you would typically redirect to the new location
    // For now, we'll show a message
    alert(`Navigation to location ${targetLocationId} would happen here. This feature can be implemented to redirect to the new panorama view.`);
}

// Close user popup
function closeUserPopup() {
    const overlay = document.getElementById('user-popup-overlay');
    if (overlay) {
        overlay.remove();
    }
}

// Show coordinates on click (like admin side)
function showUserCoordinates(pitch, yaw) {
    // Create coordinate display element
    let coordDisplay = document.getElementById('user-coord-display');
    if (!coordDisplay) {
        coordDisplay = document.createElement('div');
        coordDisplay.id = 'user-coord-display';
        coordDisplay.style.cssText = 'position: fixed; top: 80px; left: 20px; z-index: 1000; pointer-events: none;';
        document.body.appendChild(coordDisplay);
    }
    
    coordDisplay.innerHTML = `
        <div class="bg-black bg-opacity-75 text-white px-3 py-2 rounded-lg text-sm font-mono">
            <div class="font-medium text-blue-300">📍 Click Coordinates</div>
            <div>Pitch: ${pitch.toFixed(2)}°</div>
            <div>Yaw: ${yaw.toFixed(2)}°</div>
            <div class="text-xs text-gray-300 mt-1">Admin-precision detection</div>
        </div>
    `;
    
    // Auto-hide after 4 seconds
    setTimeout(() => {
        if (coordDisplay && coordDisplay.parentNode) {
            coordDisplay.innerHTML = '';
        }
    }, 4000);
}

// Get current view angles (like admin side)
function getUserViewAngles() {
    if (userPanellumViewer) {
        return {
            pitch: userPanellumViewer.getPitch(),
            yaw: userPanellumViewer.getYaw(),
            hfov: userPanellumViewer.getHfov()
        };
    }
    return null;
}

function debugUserPanorama() {
    console.log('🔍 === USER PANORAMA DEBUG ===');
    console.log('📦 Panellum available:', typeof window.pannellum);
    console.log('🔧 Panellum object:', window.pannellum);
    console.log('📄 Container found:', !!document.getElementById('panorama-viewer'));
    console.log('🔄 Loading element found:', !!document.getElementById('panorama-loading'));
    console.log('👤 User viewer instance:', userPanellumViewer);
    console.log('🎯 Modal visible:', !!document.querySelector('[role="dialog"]'));
    
    // Show current view angles like admin does
    if (userPanellumViewer) {
        const angles = getUserViewAngles();
        console.log('📐 Current view angles:', angles);
        console.log('🎯 Current Pitch:', angles.pitch.toFixed(2) + '°');
        console.log('🎯 Current Yaw:', angles.yaw.toFixed(2) + '°');
        console.log('🎯 Current HFOV:', angles.hfov.toFixed(2) + '°');
        
        // Show coordinates in UI
        showUserCoordinates(angles.pitch, angles.yaw);
    }
    
    console.log('🖼️ Selected game data:');
    @if($selectedGame)
        console.log('  - ID: {{ $selectedGame->id }}');
        console.log('  - Name: "{{ $selectedGame->name }}"');
        console.log('  - Image path: "{{ $selectedGame->map_image_path }}"');
        console.log('  - Full URL: "{{ Storage::url($selectedGame->map_image_path) }}"');
        
        // Test image accessibility
        const testImg = new Image();
        testImg.onload = function() {
            console.log('✅ User image accessible');
            console.log('📏 Image dimensions:', testImg.naturalWidth + 'x' + testImg.naturalHeight);
            
            // Try manual initialization if viewer doesn't exist
            if (!userPanellumViewer) {
                console.log('🔄 Attempting manual initialization...');
                initializeUserPanorama();
            }
        };
        testImg.onerror = function() {
            console.error('❌ User image NOT accessible');
        };
        testImg.src = '{{ Storage::url($selectedGame->map_image_path) }}';
    @else
        console.log('  - No selected game available');
    @endif
    
    console.log('🔍 === END USER DEBUG ===');
}
</script>

<!-- User Hotspot Styles - ADMIN-PRECISION styling -->
<style>
/* Default hotspot styling for proper positioning - MATCHING ADMIN PATTERNS */
.pnlm-hotspot-base {
    background: linear-gradient(135deg, #3b82f6, #1e40af) !important;
    border: 3px solid white !important;
    border-radius: 50% !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3) !important;
    animation: pulse-hotspot 2s infinite !important;
    width: 20px !important;
    height: 20px !important;
    display: block !important;
    position: absolute !important;
    z-index: 100 !important;
    cursor: pointer !important;
}

/* Ensure all Panellum hotspots are visible and properly positioned */
.pnlm-hotspot {
    background: linear-gradient(135deg, #3b82f6, #1e40af) !important;
    border: 3px solid white !important;
    border-radius: 50% !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3) !important;
    animation: pulse-hotspot 2s infinite !important;
    width: 20px !important;
    height: 20px !important;
    display: block !important;
    position: absolute !important;
    z-index: 100 !important;
    cursor: pointer !important;
}

@keyframes pulse-hotspot {
    0% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.1); opacity: 0.9; }
    100% { transform: scale(1); opacity: 1; }
}

@keyframes bounce-navigation {
    0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
    40% { transform: translateY(-8px); }
    60% { transform: translateY(-4px); }
}

@keyframes glow-quiz {
    0% { 
        transform: scale(1);
        box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
    }
    50% { 
        transform: scale(1.05);
        box-shadow: 0 6px 20px rgba(245, 158, 11, 0.7);
    }
    100% { 
        transform: scale(1);
        box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
    }
}

/* Fullscreen adjustments */
#panorama-container:fullscreen {
    background: black;
}

#panorama-container:fullscreen #panorama-viewer {
    height: 100vh !important;
}
</style>