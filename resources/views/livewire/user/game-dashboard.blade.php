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
                        <div class="w-full h-full bg-gradient-to-br from-blue-400 to-purple-500 flex items-center justify-center">
                            <svg class="w-16 h-16 text-white opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 0 1-2.827 0l-4.244-4.243a8 8 0 1 1 11.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"></path>
                            </svg>
                        </div>

                    <!-- Status Indicator -->
                    <div class="absolute top-3 right-3">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200">
                            {{ __('games.active') }}
                        </span>
                    </div>

                </div>

                <!-- Game Info -->
                <div class="p-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ $game->name }}</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm mb-3 line-clamp-2">{{ $game->description }}</p>

                    <!-- Stats -->
                    <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-4">
                        <div class="flex items-center">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 0 1-2.827 0l-4.244-4.243a8 8 0 1 1 11.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"></path>
                            </svg>
                            {{ $game->radius }}m Radius
                        </div>
                    </div>

                    <!-- Action Button -->
                    {{-- usesAr() needs both the flag and an uploaded model, so a half-set-up
                         outpost sends
                         them elsewhere rather than open an empty camera. --}}
                    <a href="{{ route('user.ar.view', $game->id) }}"
                       class="w-full {{ $game->usesAr() ? 'bg-purple-600 dark:bg-purple-700 hover:bg-purple-700 dark:hover:bg-purple-800 focus:ring-purple-500' : 'bg-blue-600 dark:bg-blue-700 hover:bg-blue-700 dark:hover:bg-blue-800 focus:ring-blue-500' }} text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 inline-block text-center">
                        <i class="fas fa-cube mr-1"></i>{{ __('View in 3D') }}
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
</div>
