<div>
    <div class="container mx-auto px-4 py-6">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800 mb-2">{{ __('games.outdoor_game_locations') }}</h1>
            <p class="text-gray-600">{{ __('games.explore_outdoor_locations') }}</p>
        </div>

        <!-- Games Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @forelse($games as $game)
            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
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
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        {{ __('games.active') }}
                    </span>
                </div>

                <!-- Map Badge -->
                @if($game->map_image_path)
                <div class="absolute bottom-3 left-3">
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-600 text-white">
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                        </svg>
                        {{ __('games.interactive_map') }}
                    </span>
                </div>
                @endif
            </div>

            <!-- Game Info -->
            <div class="p-4">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ $game->name }}</h3>
                <p class="text-gray-600 text-sm mb-3 line-clamp-2">{{ $game->description }}</p>
                
                <!-- Stats -->
                <div class="flex items-center justify-between text-xs text-gray-500 mb-4">
                    <div class="flex items-center">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                        {{ $game->quest_points }} {{ __('games.quest_points_label') }}
                    </div>
                    <div class="flex items-center">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 0 1-2.827 0l-4.244-4.243a8 8 0 1 1 11.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"></path>
                        </svg>
                        {{ $game->radius }}m {{ __('games.checkin_radius') }}
                    </div>
                </div>

                <!-- Action Button -->
                <button wire:click="viewGame({{ $game->id }})" 
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    {{ __('games.view_details') }}
                </button>
            </div>
        </div>
        @empty
        <!-- Empty State -->
        <div class="col-span-full">
            <div class="text-center py-12 bg-white rounded-lg border-2 border-dashed border-gray-300">
                <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 0 1-2.827 0l-4.244-4.243a8 8 0 1 1 11.314 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"></path>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">{{ __('games.no_locations_found') }}</h3>
                <p class="text-gray-500">{{ __('games.admin_will_create_locations') }}</p>
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
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeModal"></div>

            <!-- Modal panel -->
            <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full sm:p-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">{{ $selectedGame->name }}</h3>
                        <p class="text-gray-600 mt-1">{{ $selectedGame->description }}</p>
                    </div>
                    <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Left Column - Map -->
                    <div>
                        @if($selectedGame->map_image_path)
                        <div class="bg-gray-100 rounded-lg p-4">
                            <h4 class="text-lg font-semibold mb-3">{{ __('games.interactive_map') }}</h4>
                            <div id="map-container" class="relative bg-white rounded border overflow-hidden" style="height: 400px;">
                                <div id="map-wrapper" class="relative w-full h-full">
                                    <img id="game-map" 
                                         src="{{ Storage::url($selectedGame->map_image_path) }}" 
                                         alt="{{ $selectedGame->name }} Map" 
                                         class="w-full h-full object-contain"
                                         style="user-select: none; -webkit-user-select: none; -moz-user-select: none; -ms-user-select: none;"
                                         draggable="false">
                                    
                                    <!-- Location marker -->
                                    @if($selectedGame->coordinate_x && $selectedGame->coordinate_y)
                                    <div id="location-marker" 
                                         class="absolute w-4 h-4 bg-red-500 rounded-full border-2 border-white shadow-lg pointer-events-none transform -translate-x-1/2 -translate-y-1/2"
                                         style="left: {{ $selectedGame->coordinate_x }}px; top: {{ $selectedGame->coordinate_y }}px;">
                                    </div>
                                    @endif
                                </div>


                                <!-- Fullscreen Toggle -->
                                <button type="button" id="fullscreen-toggle" class="game-controls fullscreen-btn" title="Toggle Fullscreen" aria-label="Toggle Fullscreen" onclick="handleFullscreenClick()">
                                    <svg class="fullscreen-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path>
                                    </svg>
                                    <svg class="exit-fullscreen-icon hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9V4.5M9 9H4.5M9 9L3.5 3.5M15 9h4.5M15 9V4.5M15 9l5.5-5.5M9 15v4.5M9 15H4.5M9 15l-5.5 5.5M15 15h4.5M15 15v4.5m0 0l5.5 5.5"></path>
                                    </svg>
                                </button>

                            </div>
                            
                            <!-- Instructions -->
                            <div class="text-center text-xs text-gray-500 mt-3">
                                <p class="font-medium">{{ __('games.interactive_map_controls') }}</p>
                                <p>🖱️ Click fullscreen button for better viewing</p>
                            </div>
                        </div>
                        @else
                        <div class="bg-gray-100 rounded-lg p-8 text-center">
                            <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 0 1 3 16.382V5.618a1 1 0 0 1 1.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0 0 21 18.382V7.618a1 1 0 0 0-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                            </svg>
                            <p class="text-gray-500">{{ __('games.no_map_available') }}</p>
                        </div>
                        @endif
                    </div>

                    <!-- Right Column - Details -->
                    <div class="space-y-4">
                        <!-- What to do -->
                        <div>
                            <h4 class="text-lg font-semibold mb-2">{{ __('games.what_to_do') }}</h4>
                            <p class="text-gray-700 bg-gray-50 p-3 rounded">{{ $selectedGame->what_to_do }}</p>
                        </div>

                        <!-- Location Info -->
                        <div>
                            <h4 class="text-lg font-semibold mb-2">{{ __('games.location_information') }}</h4>
                            <div class="space-y-2">
                                @if($selectedGame->coordinate_x && $selectedGame->coordinate_y)
                                <div class="flex items-center text-sm">
                                    <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 0 1 3 16.382V5.618a1 1 0 0 1 1.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0 0 21 18.382V7.618a1 1 0 0 0-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                                    </svg>
                                    <span>{{ __('games.map_coordinates_label') }}: {{ $selectedGame->coordinate_x }}, {{ $selectedGame->coordinate_y }}</span>
                                </div>
                                @endif
                                <div class="flex items-center text-sm">
                                    <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                    </svg>
                                    <span>{{ $selectedGame->quest_points }} {{ __('games.quest_points_label') }}</span>
                                </div>
                                <div class="flex items-center text-sm">
                                    <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"></path>
                                    </svg>
                                    <span>{{ $selectedGame->radius }}m {{ __('games.checkin_radius') }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="pt-4">
                            <button wire:click="closeModal" 
                                    class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors text-sm font-medium">
                                {{ __('games.close_details') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Enhanced Styles -->
    <style>
        /* Base styling for all game controls */
        .game-controls {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }


        /* Fullscreen button */
        .fullscreen-btn {
            position: absolute;
            bottom: 16px;
            right: 16px;
            width: 48px;
            height: 48px;
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            color: #374151;
            z-index: 1001;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transform: translateY(10px);
        }

        #map-container:hover .fullscreen-btn {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
            transform: translateY(0);
        }

        .fullscreen-btn:hover {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
            transform: scale(1.05);
        }

        .fullscreen-btn:active {
            transform: scale(0.95);
        }

        .fullscreen-btn svg {
            width: 20px;
            height: 20px;
        }


        /* Fullscreen mode styles */
        #map-container:fullscreen .fullscreen-btn,
        #map-container:-webkit-full-screen .fullscreen-btn,
        #map-container:-moz-full-screen .fullscreen-btn {
            bottom: 20px !important;
            right: 20px !important;
        }

        /* Icon switching for fullscreen */
        #map-container:fullscreen .fullscreen-icon,
        #map-container:-webkit-full-screen .fullscreen-icon,
        #map-container:-moz-full-screen .fullscreen-icon {
            display: none;
        }

        #map-container:fullscreen .exit-fullscreen-icon,
        #map-container:-webkit-full-screen .exit-fullscreen-icon,
        #map-container:-moz-full-screen .exit-fullscreen-icon {
            display: block !important;
        }

        /* Panzoom cursor styles */
        .panzoom-enabled {
            cursor: grab;
        }

        .panzoom-enabled:active {
            cursor: grabbing;
        }

        /* Responsive adjustments */
        @media (max-width: 640px) {
            .fullscreen-btn {
                width: 44px;
                height: 44px;
            }
            
            .fullscreen-btn svg {
                width: 18px;
                height: 18px;
            }
        }
    </style>

    <!-- Map Script -->
    <script>
        // Note: handleFullscreenClick is defined in app.js
    </script>
</div>

</div>