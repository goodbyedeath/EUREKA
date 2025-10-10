<div class="container mx-auto px-2 sm:px-4 py-4 sm:py-6 min-h-screen">
    <!-- Page Header -->
    <div class="mb-4 sm:mb-6 flex flex-col sm:flex-row gap-4 sm:justify-between sm:items-center">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-gray-100">Game Location Manager</h1>
            <p class="text-sm sm:text-base text-gray-600 dark:text-gray-400">Manage 360° panoramic game locations</p>
        </div>
        <button wire:click="openModal" 
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 sm:px-6 py-2 rounded-lg transition-colors duration-200 text-sm sm:text-base w-full sm:w-auto">
            <i class="fas fa-plus mr-2"></i>Add New Location
        </button>
    </div>

    <!-- Games List -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
        <!-- Mobile Cards (hidden on md+) -->
        <div class="md:hidden">
            @forelse($games as $game)
                <div class="border-b border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex justify-between items-start mb-3">
                        <div class="flex-1">
                            <h3 class="text-base font-medium text-gray-900 dark:text-gray-100">
                                {{ $game->name }}
                            </h3>
                            @if($game->description)
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    {{ substr($game->description, 0, 50) }}{{ strlen($game->description) > 50 ? '...' : '' }}
                                </p>
                            @endif
                        </div>
                        <button wire:click="toggleActive({{ $game->id }})"
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $game->is_active ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100' : 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100' }}">
                            {{ $game->is_active ? 'Active' : 'Inactive' }}
                        </button>
                    </div>
                    
                    @if($game->map_image_path)
                        <div class="flex items-center space-x-2 mb-3">
                            <img src="{{ Storage::url($game->map_image_path) }}" 
                                 alt="360° Preview" 
                                 class="h-12 w-12 rounded-lg object-cover">
                            <a href="{{ route('panorama.view', $game->id) }}" target="_blank"
                               class="inline-block bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-xs">
                                <i class="fas fa-eye mr-1"></i>View 360°
                            </a>
                        </div>
                    @endif
                    
                    @if($game->default_pitch || $game->default_yaw)
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                            <strong>Default View:</strong> Pitch: {{ number_format($game->default_pitch, 1) }}°, Yaw: {{ number_format($game->default_yaw, 1) }}°
                        </div>
                    @endif
                    
                    <div class="flex space-x-2">
                        <button wire:click="openModal({{ $game->id }})"
                                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded text-sm">
                            <i class="fas fa-edit mr-1"></i>Edit
                        </button>
                        <button wire:click="delete({{ $game->id }})"
                                wire:confirm="Are you sure you want to delete this location?"
                                class="flex-1 bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded text-sm">
                            <i class="fas fa-trash mr-1"></i>Delete
                        </button>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                    <i class="fas fa-map-marker-alt text-4xl mb-4"></i>
                    <p class="text-lg font-medium">No game locations yet</p>
                    <p class="text-sm">Create your first 360° game location to get started</p>
                </div>
            @endforelse
        </div>
        
        <!-- Desktop Table (hidden on sm-) -->
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Location
                        </th>
                        <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            360° Image
                        </th>
                        <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Default View
                        </th>
                        <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($games as $game)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ $game->name }}
                                    </div>
                                    @if($game->description)
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ substr($game->description, 0, 50) }}{{ strlen($game->description) > 50 ? '...' : '' }}
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                @if($game->map_image_path)
                                    <div class="flex items-center space-x-2">
                                        <img src="{{ Storage::url($game->map_image_path) }}" 
                                             alt="360° Preview" 
                                             class="h-12 w-12 rounded-lg object-cover">
                                        <a href="{{ route('panorama.view', $game->id) }}" target="_blank"
                                           class="inline-block bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-xs">
                                            <i class="fas fa-eye mr-1"></i>View 360°
                                        </a>
                                    </div>
                                @else
                                    <span class="text-gray-400 text-sm">No image</span>
                                @endif
                            </td>
                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                @if($game->default_pitch || $game->default_yaw)
                                    <div class="space-y-1">
                                        <div>Pitch: {{ number_format($game->default_pitch, 1) }}°</div>
                                        <div>Yaw: {{ number_format($game->default_yaw, 1) }}°</div>
                                    </div>
                                @else
                                    <span class="text-gray-400">Default (0°, 0°)</span>
                                @endif
                            </td>
                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                <button wire:click="toggleActive({{ $game->id }})"
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $game->is_active ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100' : 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100' }}">
                                    {{ $game->is_active ? 'Active' : 'Inactive' }}
                                </button>
                            </td>
                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                <button wire:click="openModal({{ $game->id }})"
                                        class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                    <i class="fas fa-edit mr-1"></i>Edit
                                </button>
                                <button wire:click="delete({{ $game->id }})"
                                        wire:confirm="Are you sure you want to delete this location?"
                                        class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                    <i class="fas fa-trash mr-1"></i>Delete
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 lg:px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                <i class="fas fa-map-marker-alt text-4xl mb-4"></i>
                                <p class="text-lg font-medium">No game locations yet</p>
                                <p>Create your first 360° game location to get started</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        @if($games->hasPages())
            <div class="px-6 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $games->links() }}
            </div>
        @endif
    </div>

    <!-- Add/Edit Modal -->
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-2 sm:px-4">
                <div class="fixed inset-0 bg-black opacity-50" wire:click="closeModal"></div>
                
                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                    <div class="px-4 sm:px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {{ $editingGameId ? 'Edit' : 'Add' }} Game Location
                        </h3>
                    </div>

                    <form wire:submit.prevent="save" enctype="multipart/form-data" class="p-4 sm:p-6 space-y-4 sm:space-y-6">
                        <!-- Name -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Location Name *
                            </label>
                            <input type="text" wire:model="name" id="name"
                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-gray-100">
                            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Description -->
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Description
                            </label>
                            <textarea wire:model="description" id="description" rows="3"
                                      class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-gray-100"></textarea>
                        </div>

                        <!-- 360° Image Upload -->
                        <div>
                            <label for="mapImageUpload" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                360° Panoramic Image (Equirectangular Format Required)
                            </label>
                            <div class="mt-1 mb-2 p-3 bg-blue-50 dark:bg-blue-900 rounded-md">
                                <div class="text-sm text-blue-800 dark:text-blue-200">
                                    <div class="font-semibold mb-1">📋 Image Requirements:</div>
                                    <ul class="text-xs space-y-1">
                                        <li>• <strong>Format:</strong> Must be equirectangular (2:1 aspect ratio)</li>
                                        <li>• <strong>Examples:</strong> 4000×2000px, 3600×1800px, 2048×1024px</li>
                                        <li>• <strong>Content:</strong> 360° photos only (not maps or flat images)</li>
                                        <li>• <strong>Size:</strong> Maximum 10MB</li>
                                    </ul>
                                    <div class="mt-2 text-xs text-blue-600 dark:text-blue-300">
                                        💡 <em>Use the Debug Panel in the viewer to verify your image format</em>
                                    </div>
                                </div>
                            </div>
                            <input type="file" wire:model="mapImageUpload" id="mapImageUpload" accept="image/*"
                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-gray-100">
                            @error('mapImageUpload') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            
                            @if($mapImageUpload)
                                <div class="mt-2">
                                    <p class="text-sm text-green-600">File ready for upload: {{ $mapImageUpload->getClientOriginalName() }}</p>
                                </div>
                            @elseif($map_image_path)
                                <div class="mt-2">
                                    <img src="{{ Storage::url($map_image_path) }}" alt="Current 360° Image" class="h-20 w-20 object-cover rounded">
                                    <p class="text-sm text-gray-600 mt-1">Current image</p>
                                </div>
                            @endif
                        </div>


                        <!-- Panorama Default View -->
                        <div>
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">🎯 Panorama Default View (Optional)</h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="default_yaw" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Yaw (Horizontal) °
                                    </label>
                                    <input type="number" wire:model="default_yaw" id="default_yaw" step="0.01" min="-180" max="180"
                                           class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-gray-100"
                                           placeholder="0 (center)">
                                    @error('default_yaw') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="default_pitch" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Pitch (Vertical) °
                                    </label>
                                    <input type="number" wire:model="default_pitch" id="default_pitch" step="0.01" min="-90" max="90"
                                           class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-gray-100"
                                           placeholder="0 (horizon)">
                                    @error('default_pitch') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="mt-2 text-xs text-gray-600 dark:text-gray-400">
                                💡 Set where users first look when opening the panorama. Leave empty for center view.
                            </div>
                        </div>

                        <!-- Settings -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="target_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Target Type
                                </label>
                                <select wire:model="target_type" id="target_type"
                                        class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-gray-100">
                                    <option value="all_users">All Users</option>
                                    <option value="specific_user">Specific User</option>
                                </select>
                            </div>
                            <div class="flex items-center pt-6">
                                <input type="checkbox" wire:model="is_active" id="is_active"
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="is_active" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                    Active
                                </label>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex flex-col sm:flex-row justify-end gap-3 pt-4 sm:pt-6 border-t border-gray-200 dark:border-gray-700">
                            <button type="button" wire:click="closeModal"
                                    class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 w-full sm:w-auto">
                                Cancel
                            </button>
                            <button type="submit"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 w-full sm:w-auto">
                                {{ $editingGameId ? 'Update' : 'Create' }} Location
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Simple Panorama Viewer Modal -->
    @if($showPanoramaModal && $selectedGame)
        <div class="fixed inset-0 z-50 overflow-y-auto" style="pointer-events: auto;">
            <div class="flex items-center justify-center min-h-screen px-2 sm:px-4 py-4 sm:py-8" 
                 wire:click="closePanoramaModal">
                <div class="fixed inset-0 bg-black opacity-75 transition-opacity duration-300"></div>
                
                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-7xl w-full max-h-[98vh]" 
                     onclick="event.stopPropagation()"
                     style="min-height: 70vh;">
                    <div class="px-4 sm:px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row gap-2 sm:justify-between sm:items-center">
                        <div class="flex-1">
                            <h3 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-gray-100">
                                360° View: {{ $selectedGame->name }}
                            </h3>
                            <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 break-all">
                                Panoramic viewer - Image: {{ basename($selectedGame->map_image_path) }}
                            </p>
                        </div>
                        <button wire:click="closePanoramaModal" 
                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 self-end sm:self-auto">
                            <i class="fas fa-times text-lg sm:text-xl"></i>
                        </button>
                    </div>

                    <div class="p-3 sm:p-6">
                        <!-- Simple Panellum Container -->
                        <div id="admin-panorama-container" class="relative bg-gray-100 dark:bg-gray-700 rounded-lg overflow-hidden"
                             style="height: 50vh; min-height: 300px; max-height: 600px;">
                             
                            <!-- Panellum Viewer -->
                            <div id="admin-panellum-viewer" class="w-full h-full bg-gray-100"></div>
                            
                            <!-- Viewer Controls -->
                            <div class="absolute top-2 sm:top-4 right-2 sm:right-4 flex flex-wrap gap-1 sm:gap-2">
                                <button type="button" onclick="initAdminPanellum()"
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-2 sm:px-3 py-1 rounded text-xs sm:text-sm shadow-sm transition-colors duration-200"
                                        title="Reload 360° View">
                                    <i class="fas fa-sync sm:mr-1"></i><span class="hidden sm:inline"> Reload</span>
                                </button>
                                <button type="button" onclick="resetAdminPanellum()"
                                        class="bg-gray-600 hover:bg-gray-700 text-white px-2 sm:px-3 py-1 rounded text-xs sm:text-sm shadow-sm transition-colors duration-200"
                                        title="Reset View">
                                    <i class="fas fa-redo sm:mr-1"></i><span class="hidden sm:inline"> Reset</span>
                                </button>
                                <button type="button" onclick="toggleFullscreenPanellum()"
                                        class="bg-green-600 hover:bg-green-700 text-white px-2 sm:px-3 py-1 rounded text-xs sm:text-sm shadow-sm transition-colors duration-200"
                                        title="Toggle Fullscreen">
                                    <i class="fas fa-expand sm:mr-1"></i><span class="hidden sm:inline"> Fullscreen</span>
                                </button>
                                <button type="button" onclick="toggleHotspots()"
                                        class="bg-purple-600 hover:bg-purple-700 text-white px-2 sm:px-3 py-1 rounded text-xs sm:text-sm shadow-sm transition-colors duration-200"
                                        title="Toggle Hotspots">
                                    <i class="fas fa-map-marker-alt sm:mr-1"></i><span class="hidden sm:inline"> Hotspots</span>
                                </button>
                                <button type="button" onclick="enableHotspotMode()"
                                        class="bg-orange-600 hover:bg-orange-700 text-white px-2 sm:px-3 py-1 rounded text-xs sm:text-sm shadow-sm transition-colors duration-200"
                                        title="Click to add hotspots">
                                    <i class="fas fa-plus sm:mr-1"></i><span class="hidden sm:inline"> Add</span>
                                </button>
                                <button type="button" onclick="debugPanellumState()"
                                        class="bg-gray-600 hover:bg-gray-700 text-white px-2 sm:px-3 py-1 rounded text-xs sm:text-sm shadow-sm transition-colors duration-200"
                                        title="Debug panorama state">
                                    <i class="fas fa-bug sm:mr-1"></i><span class="hidden sm:inline"> Debug</span>
                                </button>
                            </div>
                        </div>

                        <!-- Enhanced Hotspot Management -->
                        <div class="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-4">
                            <!-- Hotspot Controls -->
                            <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">
                                    <i class="fas fa-map-marker-alt mr-2 text-purple-600"></i>Hotspot Controls
                                </h4>
                                <div class="space-y-3">
                                    <div class="flex flex-col sm:flex-row gap-2">
                                        <button type="button" onclick="clearAllDatabaseHotspots()"
                                                class="bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded text-sm flex-1">
                                            <i class="fas fa-trash mr-1"></i> Clear All
                                        </button>
                                        <button type="button" onclick="exportDatabaseHotspots()"
                                                class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-2 rounded text-sm flex-1">
                                            <i class="fas fa-download mr-1"></i> Export
                                        </button>
                                        <button type="button" onclick="importDatabaseHotspots()"
                                                class="bg-teal-600 hover:bg-teal-700 text-white px-3 py-2 rounded text-sm flex-1">
                                            <i class="fas fa-upload mr-1"></i> Import
                                        </button>
                                    </div>
                                    <div id="hotspot-count" class="text-xs text-gray-600 dark:text-gray-400 text-center">
                                        Hotspots: <span id="count-display">1</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Current Location Info -->
                            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-3">
                                    <i class="fas fa-info-circle mr-2"></i>Location Info
                                </h4>
                                <div class="space-y-2 text-xs">
                                    <div class="text-blue-700 dark:text-blue-300">
                                        <strong>File:</strong> {{ basename($selectedGame->map_image_path) }}
                                    </div>
                                    <div class="text-blue-700 dark:text-blue-300">
                                        <strong>Status:</strong> {{ Storage::disk('public')->exists($selectedGame->map_image_path) ? 'Available' : 'Missing' }}
                                    </div>
                                    @if($selectedGame->default_pitch || $selectedGame->default_yaw)
                                        <div class="text-blue-700 dark:text-blue-300 p-2 bg-blue-100 dark:bg-blue-800/30 rounded">
                                            <strong>Default View Angle:</strong><br>
                                            Pitch: {{ number_format($selectedGame->default_pitch ?? 0, 1) }}°<br>
                                            Yaw: {{ number_format($selectedGame->default_yaw ?? 0, 1) }}°
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <!-- Live Hotspot List -->
                        <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">
                                <i class="fas fa-list mr-2 text-orange-600"></i>Active Hotspots
                            </h4>
                            <div id="hotspot-list" class="space-y-2 max-h-32 overflow-y-auto">
                                <div class="text-xs text-gray-500 dark:text-gray-400 italic">
                                    Hotspots will appear here when added to the panorama
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<!-- Custom Panellum Hotspot Styles -->
<style>
    /* Custom Panellum Hotspot Styles */
    .admin-hotspot-marker {
        background: linear-gradient(135deg, #3b82f6, #1e40af) !important;
        border: 3px solid white !important;
        border-radius: 50% !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3) !important;
        animation: pulse-hotspot 2s infinite !important;
    }
    
    .custom-admin-hotspot {
        background: linear-gradient(135deg, #f59e0b, #d97706) !important;
        border: 3px solid white !important;
        border-radius: 50% !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3) !important;
        animation: bounce-hotspot 3s infinite !important;
    }
    
    @keyframes pulse-hotspot {
        0% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.2); opacity: 0.8; }
        100% { transform: scale(1); opacity: 1; }
    }
    
    @keyframes bounce-hotspot {
        0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
        40% { transform: translateY(-10px); }
        60% { transform: translateY(-5px); }
    }
    
    /* Fullscreen styles */
    #admin-panellum-viewer:-webkit-full-screen {
        width: 100% !important;
        height: 100% !important;
    }
    
    #admin-panellum-viewer:-moz-full-screen {
        width: 100% !important;
        height: 100% !important;
    }
    
    #admin-panellum-viewer:fullscreen {
        width: 100% !important;
        height: 100% !important;
    }
</style>

<!-- Simple Admin Panellum Integration Script -->
<script>
    // Simple functions using existing global Panellum
    function initAdminPanellum() {
        console.log('🎯 Initializing Admin Panellum...');
        
        const container = document.getElementById('admin-panellum-viewer');
        if (!container) {
            console.error('❌ No admin container found');
            return;
        }
        
        // Get image URL from current selected game - fix Blade syntax
        let imageUrl = '';
        @if($selectedGame && $selectedGame->map_image_path)
            imageUrl = '{{ Storage::url($selectedGame->map_image_path) }}';
        @endif
        
        console.log('📸 Image URL:', imageUrl);
        
        if (!imageUrl) {
            console.error('❌ No image URL available');
            container.innerHTML = '<div class="flex items-center justify-center h-full text-yellow-600"><i class="fas fa-exclamation-triangle mr-2"></i>No 360° image selected</div>';
            return;
        }
        
        // Check if global Panellum is available
        console.log('🔍 Checking Panellum availability:', typeof window.pannellum);
        console.log('🌍 Window pannellum object:', window.pannellum);
        
        if (typeof window.pannellum !== 'undefined') {
            try {
                container.innerHTML = ''; // Clear container
                
                const viewer = window.pannellum.viewer('admin-panellum-viewer', {
                    type: 'equirectangular',
                    panorama: imageUrl,
                    autoLoad: true,
                    showControls: true,
                    hfov: 90
                });
                
                viewer.on('load', function() {
                    console.log('✅ Admin panorama loaded successfully!');
                    
                    // Add hotspot if coordinates exist
                    
                    // Update UI components
                    updateHotspotList();
                    updateHotspotCount();
                });
                
                viewer.on('error', function(err) {
                    console.error('❌ Admin panorama error:', err);
                });
                
                window.adminPanellumViewer = viewer;
                
            } catch (e) {
                console.error('❌ Error creating admin viewer:', e);
            }
        } else {
            console.error('❌ Panellum library not available');
            container.innerHTML = '<div class="flex items-center justify-center h-full text-red-600"><i class="fas fa-exclamation-triangle mr-2"></i>Panellum library not loaded</div>';
        }
    }
    
    // Initialize Panellum with explicit image URL (from Livewire event)
    function initAdminPanellumWithImage(imageUrl, existingHotspots = []) {
        const container = document.getElementById('admin-panellum-viewer');
        if (!container || !imageUrl) return;
        
        if (typeof window.pannellum !== 'undefined') {
            try {
                container.innerHTML = ''; // Clear container
                
                // Enhanced configuration with hotspot support
                const config = {
                    type: 'equirectangular',
                    panorama: imageUrl,
                    autoLoad: true,
                    showControls: true,
                    showFullscreenCtrl: false, // We handle fullscreen manually
                    showZoomCtrl: true,
                    mouseZoom: true,
                    keyboardZoom: true,
                    hfov: 90,
                    minHfov: 50,
                    maxHfov: 120,
                    hotSpots: [],
                    hotSpotDebug: false,
                    showTitle: true,
                    author: 'Admin Panel',
                    title: '360° Game Location View'
                };
                
                // Add existing hotspot if coordinates exist (legacy support)
                
                // Add database hotspots
                if (existingHotspots && existingHotspots.length > 0) {
                    existingHotspots.forEach((hotspot, index) => {
                        config.hotSpots.push({
                            id: hotspot.id || `db-hotspot-${index}`,
                            pitch: parseFloat(hotspot.pitch || 0),
                            yaw: parseFloat(hotspot.yaw || 0),
                            type: hotspot.type || 'info',
                            text: hotspot.text || hotspot.title || 'Hotspot',
                            cssClass: hotspot.cssClass || 'custom-admin-hotspot'
                        });
                    });
                }
                
                const viewer = window.pannellum.viewer('admin-panellum-viewer', config);
                
                // Store viewer reference and config
                window.adminPanellumViewer = viewer;
                window.adminPanellumConfig = config;
                window.adminHotspotsVisible = true;
                
                // Add click listener for adding new hotspots
                viewer.on('click', function(event) {
                    console.log('🖱️ PANORAMA CLICK DETECTED - coordinates:', event.pitch.toFixed(2) + '°, ' + event.yaw.toFixed(2) + '°');
                    console.log('🔍 Click event details:', event);
                    console.log('🎯 Current hotspot mode:', window.adminHotspotMode);
                    console.log('🎮 Current game ID:', window.currentGameLocationId);
                    
                    if (window.adminHotspotMode) {
                        console.log('✅ Hotspot mode ACTIVE - proceeding to add hotspot');
                        addHotspotAtClick(event);
                    } else {
                        console.log('⚠️ Hotspot mode DISABLED - enable hotspot mode first');
                    }
                });
                
                // Update UI on load
                viewer.on('load', function() {
                    console.log('🎉 Admin panorama loaded successfully - ready for hotspot placement');
                    console.log('🔧 Click event listener registered on viewer');
                    
                    // Test click event binding
                    setTimeout(() => {
                        console.log('🧪 Testing viewer click event registration...');
                        console.log('📊 Viewer event listeners:', viewer._events);
                    }, 1000);
                    
                    updateHotspotList();
                    updateHotspotCount();
                });

                // Handle fullscreen changes
                document.addEventListener('fullscreenchange', handleFullscreenChange);
                document.addEventListener('webkitfullscreenchange', handleFullscreenChange);
                document.addEventListener('mozfullscreenchange', handleFullscreenChange);
                document.addEventListener('MSFullscreenChange', handleFullscreenChange);
                
            } catch (e) {
                container.innerHTML = '<div class="flex items-center justify-center h-full text-red-600">Error creating 360° viewer</div>';
            }
        } else {
            container.innerHTML = '<div class="flex items-center justify-center h-full text-red-600">Panellum library not loaded</div>';
        }
    }
    
    function resetAdminPanellum() {
        if (window.adminPanellumViewer) {
            try {
                window.adminPanellumViewer.lookAt(0, 0, 90);
            } catch (e) {
            }
        }
    }
    
    // Fullscreen functionality
    function toggleFullscreenPanellum() {
        console.log('🔍 Fullscreen button clicked');
        const container = document.getElementById('admin-panellum-viewer');
        if (!container) {
            console.error('❌ Container not found');
            return;
        }
        console.log('📦 Container found:', container);
        
        if (!document.fullscreenElement) {
            // Enter fullscreen
            console.log('🚀 Entering fullscreen mode');
            if (container.requestFullscreen) {
                container.requestFullscreen().catch(e => console.log('Fullscreen error:', e));
            } else if (container.webkitRequestFullscreen) {
                container.webkitRequestFullscreen();
            } else if (container.mozRequestFullScreen) {
                container.mozRequestFullScreen();
            } else if (container.msRequestFullscreen) {
                container.msRequestFullscreen();
            }
            
            // Update button text
            updateFullscreenButton(true);
        } else {
            // Exit fullscreen
            console.log('🔙 Exiting fullscreen mode');
            if (document.exitFullscreen) {
                document.exitFullscreen().catch(e => console.log('Exit fullscreen error:', e));
            } else if (document.webkitExitFullscreen) {
                document.webkitExitFullscreen();
            } else if (document.mozCancelFullScreen) {
                document.mozCancelFullScreen();
            } else if (document.msExitFullscreen) {
                document.msExitFullscreen();
            }
            
            // Update button text
            updateFullscreenButton(false);
        }
    }
    
    // Update fullscreen button appearance
    function updateFullscreenButton(isFullscreen) {
        const button = document.querySelector('[onclick="toggleFullscreenPanellum()"]');
        if (button) {
            const icon = button.querySelector('i');
            if (isFullscreen) {
                if (icon) icon.className = 'fas fa-compress mr-1';
                button.innerHTML = '<i class="fas fa-compress mr-1"></i> Exit Fullscreen';
            } else {
                if (icon) icon.className = 'fas fa-expand mr-1';
                button.innerHTML = '<i class="fas fa-expand mr-1"></i> Fullscreen';
            }
        }
    }
    
    // Handle fullscreen change events
    function handleFullscreenChange() {
        const isFullscreen = !!(document.fullscreenElement || 
                               document.webkitFullscreenElement || 
                               document.mozFullScreenElement || 
                               document.msFullscreenElement);
        
        console.log('📺 Fullscreen changed:', isFullscreen);
        updateFullscreenButton(isFullscreen);
        
        // Ensure hotspot functionality persists in fullscreen
        if (window.adminPanellumViewer) {
            // Refresh hotspots to ensure they display correctly
            setTimeout(() => {
                if (window.adminHotspotsVisible && window.adminPanellumConfig) {
                    const hotspots = window.adminPanellumConfig.hotSpots || [];
                    hotspots.forEach((hotspot, index) => {
                        try {
                            // Remove and re-add to ensure proper positioning
                            window.adminPanellumViewer.removeHotSpot(`hotspot-${index}`);
                            window.adminPanellumViewer.addHotSpot({
                                ...hotspot,
                                id: `hotspot-${index}`
                            });
                        } catch (e) {
                            // Hotspot might not exist, just add it
                            window.adminPanellumViewer.addHotSpot({
                                ...hotspot,
                                id: `hotspot-${index}`
                            });
                        }
                    });
                }
            }, 500);
        }
    }
    
    // Hotspot management
    function toggleHotspots() {
        console.log('📍 Hotspots button clicked');
        if (!window.adminPanellumViewer) {
            console.error('❌ No Panellum viewer found');
            return;
        }
        console.log('✅ Viewer found, current hotspots visible:', window.adminHotspotsVisible);
        
        if (window.adminHotspotsVisible) {
            // Hide hotspots
            const hotspots = window.adminPanellumConfig.hotSpots || [];
            hotspots.forEach((hotspot, index) => {
                window.adminPanellumViewer.removeHotSpot(`hotspot-${index}`);
            });
            window.adminHotspotsVisible = false;
            updateHotspotButton(false);
        } else {
            // Show hotspots
            const hotspots = window.adminPanellumConfig.hotSpots || [];
            hotspots.forEach((hotspot, index) => {
                window.adminPanellumViewer.addHotSpot({
                    ...hotspot,
                    id: `hotspot-${index}`
                });
            });
            window.adminHotspotsVisible = true;
            updateHotspotButton(true);
        }
    }
    
    // Add hotspot at click position
    function addHotspotAtClick(event) {
        if (!window.adminPanellumViewer || !window.currentGameLocationId || !window.adminHotspotMode) {
            return;
        }
        
        // Get exact coordinates from the click event
        const pitch = parseFloat(event.pitch);
        const yaw = parseFloat(event.yaw);
        
        if (isNaN(pitch) || isNaN(yaw)) {
            console.error('Invalid coordinates:', event);
            return;
        }
        
        const title = `Hotspot (${pitch.toFixed(1)}°, ${yaw.toFixed(1)}°)`;
        
        console.log('📍 Adding hotspot at EXACT coordinates:', {
            clickPitch: pitch,
            clickYaw: yaw,
            precisionPitch: pitch.toFixed(5),
            precisionYaw: yaw.toFixed(5)
        });
        
        // Save to database immediately with exact coordinates
        @this.call('saveHotspot', window.currentGameLocationId, pitch, yaw, title)
            .then((hotspotId) => {
                if (hotspotId && hotspotId !== false) {
                    // Add hotspot to viewer with EXACT same coordinates
                    const hotspot = {
                        id: hotspotId,
                        pitch: pitch, // Use exact pitch from click
                        yaw: yaw,     // Use exact yaw from click
                        type: 'info',
                        text: title,
                        cssClass: 'custom-admin-hotspot'
                    };
                    
                    console.log('✅ Adding hotspot to viewer with coordinates:', {
                        displayPitch: hotspot.pitch,
                        displayYaw: hotspot.yaw,
                        difference: {
                            pitch: Math.abs(pitch - hotspot.pitch),
                            yaw: Math.abs(yaw - hotspot.yaw)
                        }
                    });
                    
                    window.adminPanellumViewer.addHotSpot(hotspot);
                    
                    // Add to config
                    if (!window.adminPanellumConfig.hotSpots) {
                        window.adminPanellumConfig.hotSpots = [];
                    }
                    window.adminPanellumConfig.hotSpots.push(hotspot);
                    
                    // Update UI
                    updateHotspotList();
                    updateHotspotCount();
                    
                    // Auto-disable hotspot mode
                    window.adminHotspotMode = false;
                    const button = document.querySelector('[onclick="enableHotspotMode()"]');
                    if (button) {
                        button.classList.remove('bg-red-600', 'hover:bg-red-700');
                        button.classList.add('bg-orange-600', 'hover:bg-orange-700');
                        button.innerHTML = '<i class="fas fa-plus sm:mr-1"></i><span class="hidden sm:inline"> Add</span>';
                    }
                }
            })
            .catch((error) => {
                console.error('Error saving hotspot:', error);
            });
    }
    
    // Clear all hotspots
    function clearAllHotspots() {
        if (!window.adminPanellumViewer || !window.adminPanellumConfig) return;
        
        // Remove all hotspots from viewer
        const hotspots = window.adminPanellumConfig.hotSpots || [];
        hotspots.forEach(hotspot => {
            try {
                window.adminPanellumViewer.removeHotSpot(hotspot.id);
            } catch (e) {
                // Hotspot might not exist
            }
        });
        
        // Keep only default hotspot (if exists)
        const defaultHotspots = hotspots.filter(h => h.cssClass === 'admin-hotspot-marker');
        window.adminPanellumConfig.hotSpots = defaultHotspots;
        
        // Update UI
        updateHotspotList();
        updateHotspotCount();
        
        console.log('Cleared all custom hotspots');
    }
    
    // Export hotspots to JSON
    function exportHotspots() {
        if (!window.adminPanellumConfig || !window.adminPanellumConfig.hotSpots) {
            alert('No hotspots to export');
            return;
        }
        
        const hotspots = window.adminPanellumConfig.hotSpots.filter(h => h.cssClass !== 'admin-hotspot-marker');
        const data = {
            location: '@if($selectedGame){{ $selectedGame->name }}@endif',
            exported_at: new Date().toISOString(),
            hotspots: hotspots
        };
        
        const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `hotspots-{{ Str::slug($selectedGame->name ?? 'location') }}.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
        
        console.log('Exported hotspots:', hotspots);
    }
    
    // Import hotspots from JSON
    function importHotspots() {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = '.json';
        input.onchange = function(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const data = JSON.parse(e.target.result);
                    if (data.hotspots && Array.isArray(data.hotspots)) {
                        // Add imported hotspots
                        data.hotspots.forEach(hotspot => {
                            const newHotspot = {
                                ...hotspot,
                                id: `hotspot-${Date.now()}-${Math.random()}`,
                                cssClass: 'custom-admin-hotspot'
                            };
                            
                            window.adminPanellumViewer.addHotSpot(newHotspot);
                            window.adminPanellumConfig.hotSpots.push(newHotspot);
                        });
                        
                        updateHotspotList();
                        updateHotspotCount();
                        alert(`Imported ${data.hotspots.length} hotspots`);
                    } else {
                        alert('Invalid hotspot file format');
                    }
                } catch (err) {
                    alert('Error reading hotspot file: ' + err.message);
                }
            };
            reader.readAsText(file);
        };
        input.click();
    }
    
    // Update hotspot count display
    function updateHotspotCount() {
        const countDisplay = document.getElementById('count-display');
        if (countDisplay && window.adminPanellumConfig) {
            const total = window.adminPanellumConfig.hotSpots ? window.adminPanellumConfig.hotSpots.length : 0;
            countDisplay.textContent = total;
        }
    }
    
    // Update hotspot list display
    function updateHotspotList() {
        const listContainer = document.getElementById('hotspot-list');
        if (!listContainer || !window.adminPanellumConfig) return;
        
        const hotspots = window.adminPanellumConfig.hotSpots || [];
        
        if (hotspots.length === 0) {
            listContainer.innerHTML = '<div class="text-xs text-gray-500 dark:text-gray-400 italic">No hotspots added yet</div>';
            return;
        }
        
        const hotspotItems = hotspots.map((hotspot, index) => {
            const isDefault = hotspot.cssClass === 'admin-hotspot-marker' || hotspot.id === 'default-hotspot';
            const isDatabase = !isDefault && !isNaN(parseInt(hotspot.id)); // Database IDs are integers
            const typeIcon = isDefault ? 'fa-home' : (isDatabase ? 'fa-database' : 'fa-map-marker-alt');
            const typeColor = isDefault ? 'text-blue-600' : (isDatabase ? 'text-green-600' : 'text-orange-600');
            
            return `
                <div class="flex items-center justify-between p-2 bg-white dark:bg-gray-600 rounded text-xs">
                    <div class="flex items-center space-x-2">
                        <i class="fas ${typeIcon} ${typeColor}"></i>
                        <span class="font-medium">${hotspot.text || 'Hotspot'}</span>
                        ${isDatabase ? '<span class="text-xs text-green-500">(DB)</span>' : ''}
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="text-gray-500 dark:text-gray-400">
                            ${Math.round(hotspot.pitch)}°, ${Math.round(hotspot.yaw)}°
                        </span>
                        ${!isDefault ? `<button onclick="removeHotspot('${hotspot.id}')" class="text-red-600 hover:text-red-800" title="Remove"><i class="fas fa-times"></i></button>` : ''}
                    </div>
                </div>
            `;
        }).join('');
        
        listContainer.innerHTML = hotspotItems;
    }
    
    // Remove individual hotspot
    function removeHotspot(hotspotId) {
        if (!window.adminPanellumViewer || !window.adminPanellumConfig) return;
        
        // Delete from database via Livewire
        @this.call('deleteHotspot', hotspotId)
            .then((success) => {
                if (success) {
                    try {
                        window.adminPanellumViewer.removeHotSpot(hotspotId);
                        window.adminPanellumConfig.hotSpots = window.adminPanellumConfig.hotSpots.filter(h => h.id != hotspotId);
                        
                        updateHotspotList();
                        updateHotspotCount();
                        
                        console.log('Removed hotspot from database:', hotspotId);
                    } catch (e) {
                        console.error('Error removing hotspot from viewer:', e);
                    }
                } else {
                    console.error('Failed to delete hotspot from database');
                }
            })
            .catch((error) => {
                console.error('Error deleting hotspot:', error);
            });
    }
    
    // Clear all database hotspots
    function clearAllDatabaseHotspots() {
        if (!window.currentGameLocationId) {
            console.error('No game location ID available');
            return;
        }
        
        if (!confirm('Are you sure you want to delete all hotspots? This cannot be undone.')) {
            return;
        }
        
        @this.call('clearAllHotspots', window.currentGameLocationId)
            .then((deletedCount) => {
                if (deletedCount !== false) {
                    // Clear from viewer
                    if (window.adminPanellumViewer && window.adminPanellumConfig) {
                        const hotspots = window.adminPanellumConfig.hotSpots || [];
                        hotspots.forEach(hotspot => {
                            try {
                                if (hotspot.id !== 'default-hotspot') { // Keep legacy default hotspot
                                    window.adminPanellumViewer.removeHotSpot(hotspot.id);
                                }
                            } catch (e) {
                                // Hotspot might not exist
                            }
                        });
                        
                        // Keep only default hotspot
                        window.adminPanellumConfig.hotSpots = hotspots.filter(h => h.id === 'default-hotspot');
                        
                        updateHotspotList();
                        updateHotspotCount();
                    }
                    
                    console.log(`Cleared ${deletedCount} hotspots from database`);
                } else {
                    console.error('Failed to clear hotspots from database');
                }
            })
            .catch((error) => {
                console.error('Error clearing hotspots:', error);
            });
    }
    
    // Export database hotspots
    function exportDatabaseHotspots() {
        if (!window.currentGameLocationId) {
            console.error('No game location ID available');
            return;
        }
        
        @this.call('exportHotspots', window.currentGameLocationId)
            .then((data) => {
                if (data) {
                    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `hotspots-${data.location_name || 'location'}-${new Date().toISOString().split('T')[0]}.json`;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                    
                    console.log('Exported hotspots:', data);
                } else {
                    console.error('Failed to export hotspots');
                }
            })
            .catch((error) => {
                console.error('Error exporting hotspots:', error);
            });
    }
    
    // Import database hotspots
    function importDatabaseHotspots() {
        if (!window.currentGameLocationId) {
            console.error('No game location ID available');
            return;
        }
        
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = '.json';
        input.onchange = function(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const data = JSON.parse(e.target.result);
                    const hotspots = data.hotspots || [];
                    
                    if (!Array.isArray(hotspots)) {
                        alert('Invalid hotspot file format');
                        return;
                    }
                    
                    @this.call('importHotspots', window.currentGameLocationId, hotspots)
                        .then((importedCount) => {
                            if (importedCount !== false) {
                                // Refresh the entire panorama to load new hotspots
                                setTimeout(() => {
                                    location.reload(); // Simple solution - reload the page to refresh hotspots
                                }, 1000);
                                
                                console.log(`Imported ${importedCount} hotspots to database`);
                            } else {
                                console.error('Failed to import hotspots to database');
                            }
                        })
                        .catch((error) => {
                            console.error('Error importing hotspots:', error);
                        });
                        
                } catch (err) {
                    alert('Error reading hotspot file: ' + err.message);
                }
            };
            reader.readAsText(file);
        };
        input.click();
    }
    
    // Update hotspot button appearance
    function updateHotspotButton(visible) {
        const button = document.querySelector('[onclick="toggleHotspots()"]');
        if (button) {
            const icon = button.querySelector('i');
            if (visible) {
                button.classList.remove('bg-purple-600', 'hover:bg-purple-700');
                button.classList.add('bg-yellow-600', 'hover:bg-yellow-700');
                if (icon) icon.className = 'fas fa-eye mr-1';
            } else {
                button.classList.remove('bg-yellow-600', 'hover:bg-yellow-700');
                button.classList.add('bg-purple-600', 'hover:bg-purple-700');
                if (icon) icon.className = 'fas fa-map-marker-alt mr-1';
            }
        }
    }
    
    // Enable hotspot creation mode
    function enableHotspotMode() {
        console.log('🎯 Add Hotspot button clicked');
        window.adminHotspotMode = !window.adminHotspotMode;
        console.log('Hotspot mode now:', window.adminHotspotMode);
        console.log('Viewer available:', !!window.adminPanellumViewer);
        console.log('Game location ID:', window.currentGameLocationId);
        
        const button = document.querySelector('[onclick="enableHotspotMode()"]');
        if (button) {
            if (window.adminHotspotMode) {
                button.classList.remove('bg-orange-600', 'hover:bg-orange-700');
                button.classList.add('bg-red-600', 'hover:bg-red-700');
                button.innerHTML = '<i class="fas fa-times sm:mr-1"></i><span class="hidden sm:inline"> Exit Mode</span>';
                
                // Show instruction
                if (window.adminPanellumViewer) {
                    console.log('✅ Hotspot mode enabled - Click anywhere in the panorama to add a hotspot');
                    alert('Hotspot mode enabled! Click anywhere in the panorama to add a hotspot.');
                } else {
                    console.error('❌ Panorama viewer not ready');
                    alert('Panorama viewer not ready. Please wait for it to load.');
                }
            } else {
                button.classList.remove('bg-red-600', 'hover:bg-red-700');
                button.classList.add('bg-orange-600', 'hover:bg-orange-700');
                button.innerHTML = '<i class="fas fa-plus sm:mr-1"></i><span class="hidden sm:inline"> Add</span>';
                console.log('Hotspot mode disabled');
            }
        }
    }
    
    // Debug function
    function debugPanellum() {
        console.log('=== PANELLUM DEBUG ===');
        console.log('Panellum available:', typeof window.pannellum);
        console.log('Container found:', !!document.getElementById('admin-panellum-viewer'));
        console.log('Current viewer:', window.adminPanellumViewer);
        console.log('Hotspot mode:', window.adminHotspotMode);
        console.log('Game location ID:', window.currentGameLocationId);
    }
    
    // Enhanced debug function
    function debugPanellumState() {
        console.log('=== PANORAMA STATE ===');
        console.log('Viewer exists:', !!window.adminPanellumViewer);
        console.log('Hotspot mode:', window.adminHotspotMode);
        console.log('Game ID:', window.currentGameLocationId);
        
        if (window.adminPanellumViewer) {
            console.log('Current view - Pitch:', window.adminPanellumViewer.getPitch(), 'Yaw:', window.adminPanellumViewer.getYaw());
        }
    }
    
    
    // Auto-initialize when modal opens
    document.addEventListener('livewire:init', function() {
        Livewire.on('panorama-modal-opened', function(eventData) {
            // Extract data from array if needed
            let data = eventData;
            if (Array.isArray(eventData) && eventData.length > 0) {
                data = eventData[0];
            }
            
            // Store the current game location ID
            window.currentGameLocationId = data.gameId;
            
            setTimeout(function() {
                if (data && data.imagePath) {
                    const imageUrl = '/storage/' + data.imagePath;
                    initAdminPanellumWithImage(imageUrl, data.hotspots || []);
                } else {
                    initAdminPanellum();
                }
            }, 500);
        });
        
        Livewire.on('panorama-modal-closed', function() {
            if (window.adminPanellumViewer) {
                try {
                    window.adminPanellumViewer.destroy();
                } catch (e) {}
                window.adminPanellumViewer = null;
            }
        });
    });

</script>

<!-- Simple Admin Hotspot Styling -->
<style>
    .admin-hotspot {
        background: #dc2626 !important;
        border: 2px solid white !important;
        border-radius: 50% !important;
        width: 16px !important;
        height: 16px !important;
    }
    
    .admin-hotspot:hover {
        transform: scale(1.3);
    }
    
    /* Responsive table adjustments */
    @media (max-width: 768px) {
        .table-responsive {
            font-size: 14px;
        }
    }
    
    @media (max-width: 640px) {
        .table-responsive {
            font-size: 12px;
        }
    }
    
    /* Mobile-friendly modal height */
    @media (max-height: 600px) {
        #admin-panorama-container {
            height: 300px !important;
        }
    }
    
    @media (max-height: 400px) {
        #admin-panorama-container {
            height: 200px !important;
        }
    }
</style>