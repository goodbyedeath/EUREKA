<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200">Quest Locations</h1>
        <div class="flex items-center space-x-3">
            @if($totalPoints > 0)
                <div class="bg-blue-100 dark:bg-blue-800 px-4 py-2 rounded-lg">
                    <span class="text-blue-800 dark:text-blue-200 font-semibold">
                        Total Points: {{ $totalPoints }}
                    </span>
                </div>
            @endif
            <div class="text-sm text-gray-600 dark:text-gray-400">
                {{ $questLocations->count() }} locations available
            </div>
        </div>
    </div>

    <!-- Filter and Search -->
    <div class="mb-6">
        <form method="GET" action="{{ route('user.quest-locations') }}" class="flex flex-col md:flex-row gap-4">
            <div class="flex-1">
                <input type="text" 
                       name="search" 
                       value="{{ request('search') }}"
                       placeholder="Search locations..." 
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 transition-colors">
            </div>
            <div class="flex gap-2">
                <select name="filter_status" 
                        class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                    <option value="">All Locations</option>
                    <option value="visited" {{ request('filter_status') == 'visited' ? 'selected' : '' }}>Visited</option>
                    <option value="not_visited" {{ request('filter_status') == 'not_visited' ? 'selected' : '' }}>Not Visited</option>
                </select>
                <select name="sort_by" 
                        class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                    <option value="name" {{ request('sort_by') == 'name' ? 'selected' : '' }}>Sort by Name</option>
                    <option value="points_desc" {{ request('sort_by') == 'points_desc' ? 'selected' : '' }}>Highest Points</option>
                    <option value="points_asc" {{ request('sort_by') == 'points_asc' ? 'selected' : '' }}>Lowest Points</option>
                    <option value="distance" {{ request('sort_by') == 'distance' ? 'selected' : '' }}>Distance</option>
                </select>
                <button type="submit" class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg">
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Quest Locations Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($questLocations as $location)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md hover:shadow-lg transition-all duration-300 overflow-hidden">
                <!-- Location Image -->
                <div class="relative h-48 bg-gray-200 dark:bg-gray-600">
                    @if($location->image_path)
                        <img src="{{ Storage::url($location->image_path) }}" 
                             alt="{{ $location->name }}" 
                             class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full flex items-center justify-center">
                            <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                    @endif
                    
                    <!-- Status Badge -->
                    @if(isset($userProgress[$location->id]))
                        <div class="absolute top-3 right-3">
                            <span class="bg-green-500 text-white px-2 py-1 text-xs font-medium rounded-full">
                                 Completed
                            </span>
                        </div>
                    @endif
                    
                    <!-- Points Badge -->
                    @if($location->quest_points > 0)
                        <div class="absolute top-3 left-3">
                            <span class="bg-blue-500 text-white px-2 py-1 text-xs font-medium rounded-full">
                                {{ $location->quest_points }} pts
                            </span>
                        </div>
                    @endif
                </div>

                <!-- Location Content -->
                <div class="p-6">
                    <div class="flex justify-between items-start mb-3">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {{ $location->name }}
                        </h3>
                        @if(isset($userDistances[$location->id]))
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                {{ number_format($userDistances[$location->id], 1) }}km
                            </span>
                        @endif
                    </div>

                    @if($location->description)
                        <p class="text-gray-600 dark:text-gray-400 text-sm mb-4 line-clamp-3">
                            {{ $location->description }}
                        </p>
                    @endif

                    <!-- Location Details -->
                    <div class="space-y-2 mb-4">
                        <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            {{ number_format($location->latitude, 6) }}, {{ number_format($location->longitude, 6) }}
                        </div>
                        
                        @if($location->radius)
                            <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4a1 1 0 011-1h4m0 16H5a1 1 0 01-1-1v-4m16 4h-4m4-4v4a1 1 0 01-1 1m-4-16V4a1 1 0 011-1h4v4"></path>
                                </svg>
                                Check-in radius: {{ $location->radius }}m
                            </div>
                        @endif

                        @if($location->max_check_ins_per_user)
                            <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Max check-ins: {{ $location->max_check_ins_per_user }}
                                @if(isset($userProgress[$location->id]))
                                    ({{ $userProgress[$location->id]->check_ins_count ?? 0 }} used)
                                @endif
                            </div>
                        @endif
                    </div>

                    <!-- What to Do Section -->
                    @if($location->what_to_do)
                        <div class="mb-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-1">What to do:</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $location->what_to_do }}</p>
                        </div>
                    @endif

                    <!-- Action Buttons -->
                    <div class="flex gap-2">
                        <button onclick="openMapModal({{ $location->latitude }}, {{ $location->longitude }}, '{{ addslashes($location->name) }}', {{ $location->radius ?? 50 }}, '{{ $location->marker_color ?? '#EF4444' }}')"
                                class="flex-1 bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 flex items-center justify-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            View Map
                        </button>
                        
                        @if(!isset($userProgress[$location->id]) || 
                            ($location->max_check_ins_per_user && 
                             ($userProgress[$location->id]->check_ins_count ?? 0) < $location->max_check_ins_per_user) ||
                            !$location->max_check_ins_per_user)
                            <button onclick="checkIn({{ $location->id }})" 
                                    class="flex-1 bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 flex items-center justify-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Check In
                            </button>
                        @else
                            <button disabled 
                                    class="flex-1 bg-gray-400 text-white px-4 py-2 rounded-lg text-sm font-medium cursor-not-allowed">
                                Completed
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full">
                <div class="text-center py-12">
                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">No quest locations found</h3>
                    <p class="text-gray-500 dark:text-gray-400">
                        @if(request('search'))
                            Try adjusting your search terms or filters.
                        @else
                            Check back later for new quest locations!
                        @endif
                    </p>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($questLocations->hasPages())
        <div class="mt-8">
            {{ $questLocations->appends(request()->query())->links() }}
        </div>
    @endif
</div>

<!-- Map Modal -->
<div id="mapModal" class="fixed inset-0 z-50 overflow-y-auto hidden">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black opacity-50" onclick="closeMapModal()"></div>
        
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <div>
                    <h3 id="modalLocationName" class="text-lg font-semibold text-gray-900 dark:text-gray-100"></h3>
                    <p id="modalLocationPoints" class="text-sm text-blue-600 dark:text-blue-400"></p>
                </div>
                <button onclick="closeMapModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Map Container -->
            <div class="p-0">
                <div id="mapContainer" class="w-full h-96"></div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end space-x-3">
                <button onclick="closeMapModal()" 
                        class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Success/Error Messages -->
@if (session('success'))
    <div class="fixed top-4 right-4 z-50 bg-green-100 dark:bg-green-800 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-200 px-4 py-3 rounded-lg shadow-lg">
        <div class="flex">
            <svg class="w-4 h-4 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
            </svg>
            {{ session('success') }}
        </div>
    </div>
@endif

@if (session('error'))
    <div class="fixed top-4 right-4 z-50 bg-red-100 dark:bg-red-800 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-200 px-4 py-3 rounded-lg shadow-lg">
        <div class="flex">
            <svg class="w-4 h-4 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
            </svg>
            {{ session('error') }}
        </div>
    </div>
@endif

{{-- JavaScript functions are now defined globally in the dashboard --}}