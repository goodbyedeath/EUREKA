<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-6 space-y-4 lg:space-y-0">
        <div class="flex-1">
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200">Quest Locations</h1>
            
            <!-- Admin Location Display -->
            <div class="mt-2 flex items-center space-x-4">
                @if($locationDetected)
                    <div class="flex items-center space-x-2 text-sm text-green-600 dark:text-green-400">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="font-medium">Current Location:</span>
                        <span>{{ number_format($adminLatitude, 6) }}, {{ number_format($adminLongitude, 6) }}</span>
                        @if($locationAccuracy)
                            <span class="text-xs opacity-75">(±{{ round($locationAccuracy) }}m)</span>
                        @endif
                        @if($lastLocationUpdate)
                            <span class="text-xs opacity-75">Updated: {{ $lastLocationUpdate }}</span>
                        @endif
                    </div>
                @elseif($locationError)
                    <div class="flex items-center space-x-2 text-sm text-red-600 dark:text-red-400">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        <span>Location Error: {{ $locationError }}</span>
                    </div>
                @else
                    <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                        <svg class="w-4 h-4 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                        </svg>
                        <span>Detecting location...</span>
                    </div>
                @endif
            </div>
        </div>

        <button wire:click="create" class="bg-blue-500 dark:bg-blue-600 hover:bg-blue-600 dark:hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow transition-colors duration-200">
            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Add Location
        </button>
    </div>

    <!-- Success Message -->
    @if (session()->has('message'))
        <div class="bg-green-100 dark:bg-green-800 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-200 px-4 py-3 rounded-lg mb-4 transition-colors duration-200">
            <div class="flex">
                <svg class="w-4 h-4 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                {{ session('message') }}
            </div>
        </div>
    @endif

    <!-- Locations Table -->
    <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden transition-colors duration-200">
        <!-- Mobile Card View (Hidden on Desktop) -->
        <div class="block lg:hidden space-y-4 p-4">
            @forelse($questLocations as $location)
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-white dark:bg-gray-800 transition-colors duration-200">
                    <div class="flex items-start space-x-3 mb-3">
                        @if($location->image_path)
                            <img src="{{ Storage::url($location->image_path) }}" alt="{{ $location->name }}" 
                                 class="w-16 h-16 rounded-lg object-cover flex-shrink-0">
                        @else
                            <div class="w-16 h-16 bg-gray-200 dark:bg-gray-600 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                            </div>
                        @endif
                        <div class="flex-1 min-w-0">
                            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-1">{{ $location->name }}</h3>
                            @if($location->description)
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">{{ Str::limit($location->description, 50) }}</p>
                            @endif
                            @if($location->quest_points > 0)
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-800 text-blue-800 dark:text-blue-200">
                                    {{ $location->quest_points }} points
                                </span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-3 text-sm mb-3">
                        <div>
                            <span class="font-medium text-gray-500 dark:text-gray-400 ">Coordinates:</span>
                            <div class="text-xs text-gray-900 dark:text-gray-100 ">{{ $location->latitude }}, {{ $location->longitude }}</div>
                        </div>
                        <div>
                            <span class="font-medium text-gray-500 dark:text-gray-400 ">Radius:</span>
                            <div class="text-xs text-gray-900 dark:text-gray-100 ">{{ $location->radius }}m</div>
                        </div>
                        <div>
                            <span class="font-medium text-gray-500 dark:text-gray-400 ">Creator:</span>
                            <div class="text-xs text-gray-900 dark:text-gray-100 ">{{ $location->creator ? $location->creator->name : 'System' }}</div>
                        </div>
                        <div>
                            <span class="font-medium text-gray-500 dark:text-gray-400 ">Status:</span>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium transition-colors duration-200
                                {{ $location->is_active ? 'bg-green-100 dark:bg-green-800 text-green-800 dark:text-green-200' : 'bg-red-100 dark:bg-red-800 text-red-800 dark:text-red-200' }}">
                                {{ $location->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <button onclick="openMapModal({{ $location->latitude }}, {{ $location->longitude }}, '{{ addslashes($location->name) }}')" 
                           class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-sm transition-colors duration-200">
                            <i class="fas fa-map-marker-alt mr-1"></i>View in Maps
                        </button>
                        <div class="flex space-x-2">
                            <button wire:click="edit({{ $location->id }})" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 text-sm">
                                <i class="fas fa-edit mr-1"></i>Edit
                            </button>
                            <button wire:click="delete({{ $location->id }})" 
                                    wire:confirm="Are you sure you want to delete this location?"
                                    class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 text-sm transition-colors duration-200">
                                <i class="fas fa-trash mr-1"></i>Delete
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-8 text-gray-500 dark:text-gray-400 ">
                    <svg class="w-12 h-12 mx-auto mb-4 text-gray-300 " fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <p class="text-lg font-medium text-gray-900 dark:text-gray-100 ">No quest locations found</p>
                    <p class="text-sm">Create your first quest location to get started.</p>
                </div>
            @endforelse
        </div>

        <!-- Desktop Table View (Hidden on Mobile) -->
        <div class="hidden lg:block">
            <table class="min-w-full">
                <thead class="bg-gray-50 dark:bg-gray-700 transition-colors duration-200">
                    <tr>
                        <th class="px-4 xl:px-6 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Location</th>
                        <th class="px-4 xl:px-6 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Coordinates</th>
                        <th class="px-4 xl:px-6 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden xl:table-cell">Settings</th>
                        <th class="px-4 xl:px-6 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-4 xl:px-6 py-3 text-center text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700 transition-colors duration-200">
                @forelse($questLocations as $location)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            @if($location->image_path)
                                <img src="{{ Storage::url($location->image_path) }}" alt="{{ $location->name }}" 
                                     class="w-12 h-12 rounded-lg object-cover mr-4">
                            @else
                                <div class="w-12 h-12 bg-gray-200 dark:bg-gray-600 rounded-lg flex items-center justify-center mr-4">
                                    <svg class="w-6 h-6 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                </div>
                            @endif
                            <div>
                                <div class="font-medium text-gray-900 dark:text-gray-100">{{ $location->name }}</div>
                                @if($location->description)
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ Str::limit($location->description, 60) }}</div>
                                @endif
                                @if($location->quest_points > 0)
                                    <div class="text-sm text-blue-600 dark:text-blue-400 font-medium">{{ $location->quest_points }} points</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900 dark:text-gray-100">
                            {{ number_format($location->latitude, 6) }},<br>
                            {{ number_format($location->longitude, 6) }}
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900 dark:text-gray-100">
                            <div>Radius: <span class="font-medium">{{ $location->radius }}m</span></div>
                            @if($location->max_check_ins_per_user)
                                <div>Max check-ins: <span class="font-medium">{{ $location->max_check_ins_per_user }}</span></div>
                            @else
                                <div class="text-gray-500 dark:text-gray-400">Unlimited check-ins</div>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full transition-colors duration-200 {{ $location->is_active ? 'bg-green-100 dark:bg-green-800 text-green-800 dark:text-green-200' : 'bg-red-100 dark:bg-red-800 text-red-800 dark:text-red-200' }}">
                            {{ $location->is_active ? 'Active' : 'Inactive' }}
                        </span>
                        @if($location->created_by)
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Created by: {{ $location->creator->name ?? 'User #' . $location->created_by }}
                            </div>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-center">
                        <div class="flex justify-center space-x-2">
                            <button onclick="openMapModal({{ $location->latitude }}, {{ $location->longitude }}, '{{ addslashes($location->name) }}')" 
                                   class="text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-300 transition-colors duration-200"
                                   title="View on Map">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                            </button>
                            <button wire:click="edit({{ $location->id }})" 
                                    class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors duration-200" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </button>
                            <button wire:click="delete({{ $location->id }})" 
                                    wire:confirm="Are you sure you want to delete this location? This action cannot be undone."
                                    class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 transition-colors duration-200" title="Delete">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center">
                        <div class="text-gray-500 dark:text-gray-400 ">
                            <svg class="w-12 h-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <p class="text-lg font-medium text-gray-900 dark:text-gray-100">No quest locations found</p>
                            <p class="mt-1">Create your first location to get started!</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        
        @if($questLocations->hasPages())
            <div class="px-6 py-3 border-t">
                {{ $questLocations->links() }}
            </div>
        @endif
    </div>

    <!-- Create/Edit Modal -->
    @if($showModal)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black opacity-50" wire:click="closeModal"></div>
            
            <!-- Modal -->
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full max-h-screen overflow-y-auto transition-colors duration-200">
                <form wire:submit="save">
                    <!-- Header -->
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700 transition-colors duration-200">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {{ $editMode ? 'Edit Location' : 'Add New Location' }}
                        </h3>
                    </div>

                    <!-- Body -->
                    <div class="px-6 py-4 space-y-6">
                        
                        <!-- Image Upload Section -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Location Image</label>
                            
                            @if($existing_image_path)
                                <div class="mb-3">
                                    <img src="{{ Storage::url($existing_image_path) }}" alt="Current image" 
                                         class="w-32 h-32 object-cover rounded-lg border">
                                    <button type="button" wire:click="removeImage" 
                                            class="mt-2 text-sm text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 transition-colors duration-200">
                                        Remove current image
                                    </button>
                                </div>
                            @endif
                            
                            <input type="file" wire:model="image" accept="image/*"
                                   class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors duration-200">
                            @error('image') 
                                <span class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</span> 
                            @enderror
                            
                            @if($image)
                                <div class="mt-2">
                                    <img src="{{ $image->temporaryUrl() }}" alt="Preview" 
                                         class="w-32 h-32 object-cover rounded-lg border">
                                </div>
                            @endif
                        </div>

                        <!-- Basic Information -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Name -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name *</label>
                                <input type="text" wire:model="name" 
                                       class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors duration-200">
                                @error('name') 
                                    <span class="text-red-500 dark:text-red-400 text-xs">{{ $errors->first('name') }}</span> 
                                @enderror
                            </div>

                            <!-- Quest Points -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Quest Points *</label>
                                <input type="number" wire:model="quest_points" min="0" step="1"
                                       class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors duration-200">
                                @error('quest_points') 
                                    <span class="text-red-500 dark:text-red-400 text-xs">{{ $errors->first('quest_points') }}</span> 
                                @enderror
                            </div>

                            <!-- Max Check-ins -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Max Check-ins per User</label>
                                <input type="number" wire:model="max_check_ins_per_user" min="1" step="1" 
                                       placeholder="Leave empty for unlimited"
                                       class="w-full border rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('max_check_ins_per_user') 
                                    <span class="text-red-500 text-xs">{{ $errors->first('max_check_ins_per_user') }}</span> 
                                @enderror
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Leave empty for unlimited check-ins</p>
                            </div>
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description *</label>
                            <textarea wire:model="description" rows="3"
                                      class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors duration-200"
                                      placeholder="Describe this location..."></textarea>
                            @error('description') 
                                <span class="text-red-500 dark:text-red-400 text-xs">{{ $errors->first('description') }}</span> 
                            @enderror
                        </div>

                        <!-- Instructions -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Instructions *</label>
                            <textarea wire:model="what_to_do" rows="3"
                                      class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors duration-200"
                                      placeholder="What should users do at this location?"></textarea>
                            @error('what_to_do') 
                                <span class="text-red-500 dark:text-red-400 text-xs">{{ $errors->first('what_to_do') }}</span> 
                            @enderror
                        </div>

                        <!-- Map Preview -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Map Preview</label>
                            <div class="bg-gray-100 dark:bg-gray-700 rounded-lg p-4">
                                <div id="mapPreview" class="w-full h-64 bg-gray-200 dark:bg-gray-600 rounded-lg flex items-center justify-center">
                                    <span class="text-gray-500 dark:text-gray-400">Map will show here when coordinates are entered</span>
                                </div>
                                <button type="button" onclick="showMapPicker(); initializeMapOnModalShow()" class="mt-2 text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 inline-flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    Pick location on map
                                </button>
                                <button type="button" onclick="testMap()" class="mt-2 ml-4 text-sm text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-300 inline-flex items-center">
                                    Test Map
                                </button>
                            </div>
                        </div>

                        <!-- Coordinates -->
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Coordinates *</label>
                                @if($locationDetected)
                                    <button type="button" wire:click="useCurrentLocation"
                                            class="inline-flex items-center px-3 py-1 bg-green-600 hover:bg-green-700 text-white text-xs rounded-md transition-colors duration-200">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                                        </svg>
                                        Use Current Location
                                    </button>
                                @endif
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Latitude *</label>
                                    <input type="number" step="any" wire:model="latitude" 
                                           placeholder="-6.1751"
                                           class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors duration-200">
                                    @error('latitude') 
                                        <span class="text-red-500 dark:text-red-400 text-xs">{{ $errors->first('latitude') }}</span> 
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Longitude *</label>
                                    <input type="number" step="any" wire:model="longitude" 
                                           placeholder="106.8650"
                                           class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors duration-200">
                                    @error('longitude') 
                                        <span class="text-red-500 dark:text-red-400 text-xs">{{ $errors->first('longitude') }}</span> 
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Radius -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Check-in Radius (meters) *</label>
                            <input type="number" wire:model="radius" min="10" max="1000"
                                   class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors duration-200">
                            @error('radius') 
                                <span class="text-red-500 dark:text-red-400 text-xs">{{ $errors->first('radius') }}</span> 
                            @enderror
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Recommended: 50-100m for buildings, 10-30m for specific spots</p>
                        </div>

                        <!-- Active Status -->
                        <div class="flex items-center space-x-3">
                            <div class="flex items-center">
                                <input type="checkbox" wire:model="is_active" id="is_active" 
                                       class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                                <label for="is_active" class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Location is Active
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700 flex justify-end space-x-3 transition-colors duration-200">
                        <button type="button" wire:click="closeModal" 
                                class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors duration-200">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-blue-500 dark:bg-blue-600 text-white rounded-md hover:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 flex items-center transition-colors duration-200">
                            <svg wire:loading wire:target="save" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ $editMode ? 'Update Location' : 'Save Location' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Map Modal -->
    <div id="mapModal" class="fixed inset-0 z-50 overflow-y-auto hidden">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black opacity-50" onclick="closeMapModal()"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100" id="mapModalTitle">Location Map</h3>
                    <div class="flex items-center space-x-2">
                        <button onclick="openAdminFullscreenMap()" 
                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors" 
                                title="Fullscreen Map"
                                id="fullscreenBtn">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path>
                            </svg>
                        </button>
                        <button onclick="closeMapModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="p-0">
                    <div id="mapContainer" class="w-full h-96"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Admin Fullscreen Map Modal -->
    <div id="adminFullscreenMapModal" class="hidden fixed inset-0 bg-black bg-opacity-75 z-[60] flex items-center justify-center">
        <div class="relative w-full h-full max-w-7xl max-h-screen m-4">
            <!-- Close button -->
            <button onclick="closeAdminFullscreenMap()" 
                    class="absolute top-4 right-4 z-10 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white rounded-full p-2 shadow-lg transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
            
            <!-- Map container -->
            <div id="adminFullscreenMapContainer" class="w-full h-full bg-gray-200 dark:bg-gray-700 rounded-lg overflow-hidden">
                <div class="flex items-center justify-center h-full text-gray-500">
                    <p>Loading map...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Map Picker Modal -->
    <div id="mapPickerModal" class="fixed inset-0 z-50 overflow-y-auto hidden">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black opacity-50" onclick="closeMapPicker()"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Pick Location</h3>
                    <button onclick="closeMapPicker()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="p-0">
                    <div id="mapPicker" class="w-full h-96"></div>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end space-x-3">
                    <button onclick="closeMapPicker()" class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700">
                        Cancel
                    </button>
                    <button onclick="useSelectedLocation()" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                        Use This Location
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let map = null;
let mapPicker = null;
let selectedMarker = null;
let selectedLat = null;
let selectedLng = null;

async function openMapModal(lat, lng, title) {
    document.getElementById('mapModal').classList.remove('hidden');
    document.getElementById('mapModalTitle').textContent = title || 'Location Map';
    
    // Load MapLibre GL JS if not already loaded
    if (!window.maplibregl) {
        try {
            await window.loadMapUtils();
        } catch (error) {
            console.error('Failed to load map utilities:', error);
            return;
        }
    }
    
    setTimeout(() => {
        if (map) {
            map.remove();
        }
        
        map = new window.maplibregl.Map({
            container: 'mapContainer',
            style: {
                'version': 8,
                'sources': {
                    'osm': {
                        'type': 'raster',
                        'tiles': [
                            'https://a.tile.openstreetmap.org/{z}/{x}/{y}.png'
                        ],
                        'tileSize': 256,
                        'attribution': '© OpenStreetMap contributors'
                    }
                },
                'layers': [
                    {
                        'id': 'osm',
                        'type': 'raster',
                        'source': 'osm'
                    }
                ]
            },
            center: [lng, lat],
            zoom: 15
        });
        
        new window.maplibregl.Marker()
            .setLngLat([lng, lat])
            .addTo(map);
            
        map.addControl(new window.maplibregl.NavigationControl());
    }, 100);
}

function closeMapModal() {
    document.getElementById('mapModal').classList.add('hidden');
    if (map) {
        map.remove();
        map = null;
    }
}

async function showMapPicker() {
    console.log('showMapPicker called');
    const modal = document.getElementById('mapPickerModal');
    if (!modal) {
        console.error('mapPickerModal not found');
        return;
    }
    
    // Load MapLibre GL JS if not already loaded
    if (!window.maplibregl) {
        try {
            await window.loadMapUtils();
        } catch (error) {
            console.error('Failed to load map utilities:', error);
            return;
        }
    }
    
    modal.classList.remove('hidden');
    
    setTimeout(() => {
        if (mapPicker) {
            mapPicker.remove();
            mapPicker = null;
        }
        
        // Find coordinate inputs
        const { lat: latInput, lng: lngInput } = findCoordinateInputs();
        
        const lat = (latInput && latInput.value) ? parseFloat(latInput.value) : -6.2088;
        const lng = (lngInput && lngInput.value) ? parseFloat(lngInput.value) : 106.8456;
        
        console.log('Initializing map picker with coordinates:', lat, lng);
        
        try {
            mapPicker = new window.maplibregl.Map({
                container: 'mapPicker',
                style: {
                    'version': 8,
                    'sources': {
                        'osm': {
                            'type': 'raster',
                            'tiles': [
                                'https://a.tile.openstreetmap.org/{z}/{x}/{y}.png'
                            ],
                            'tileSize': 256,
                            'attribution': '© OpenStreetMap contributors'
                        }
                    },
                    'layers': [
                        {
                            'id': 'osm',
                            'type': 'raster',
                            'source': 'osm'
                        }
                    ]
                },
                center: [lng, lat],
                zoom: 13
            });
            
            if (selectedMarker) {
                selectedMarker.remove();
            }
            
            selectedMarker = new window.maplibregl.Marker({ 
                draggable: true,
                color: '#3B82F6'
            })
                .setLngLat([lng, lat])
                .addTo(mapPicker);
                
            selectedLat = lat;
            selectedLng = lng;
            
            selectedMarker.on('dragend', () => {
                const lngLat = selectedMarker.getLngLat();
                selectedLat = lngLat.lat;
                selectedLng = lngLat.lng;
                console.log('Marker dragged to:', selectedLat, selectedLng);
            });
            
            mapPicker.on('click', (e) => {
                selectedLat = e.lngLat.lat;
                selectedLng = e.lngLat.lng;
                selectedMarker.setLngLat([selectedLng, selectedLat]);
                console.log('Map clicked at:', selectedLat, selectedLng);
            });
                
            mapPicker.addControl(new window.maplibregl.NavigationControl());
            
            mapPicker.on('load', () => {
                console.log('Map picker loaded successfully');
            });
            
        } catch (error) {
            console.error('Error initializing map picker:', error);
        }
    }, 200);
}

function closeMapPicker() {
    document.getElementById('mapPickerModal').classList.add('hidden');
    if (mapPicker) {
        mapPicker.remove();
        mapPicker = null;
    }
}

function useSelectedLocation() {
    console.log('Using selected location:', selectedLat, selectedLng);
    
    if (selectedLat && selectedLng) {
        // Find coordinate inputs
        const { lat: latInput, lng: lngInput } = findCoordinateInputs();
        
        if (latInput && lngInput) {
            latInput.value = selectedLat.toFixed(6);
            lngInput.value = selectedLng.toFixed(6);
            
            // Trigger multiple events to ensure Livewire updates
            ['input', 'change', 'blur'].forEach(eventType => {
                latInput.dispatchEvent(new Event(eventType, { bubbles: true }));
                lngInput.dispatchEvent(new Event(eventType, { bubbles: true }));
            });
            
            // Force Livewire update
            if (window.Livewire) {
                window.Livewire.dispatch('coordinatesUpdated', {
                    latitude: selectedLat,
                    longitude: selectedLng
                });
            }
            
            updateMapPreview();
            console.log('Coordinates updated successfully');
        } else {
            console.error('Could not find coordinate input fields');
        }
    } else {
        console.error('No coordinates selected');
    }
    
    closeMapPicker();
}

function findCoordinateInputs() {
    // Multiple strategies to find the inputs
    const strategies = [
        () => ({
            lat: document.querySelector('input[wire\\:model="latitude"]'),
            lng: document.querySelector('input[wire\\:model="longitude"]')
        }),
        () => ({
            lat: document.querySelector('input[placeholder="-6.1751"]'),
            lng: document.querySelector('input[placeholder="106.8650"]')
        }),
        () => {
            const inputs = Array.from(document.querySelectorAll('input[type="number"]'));
            return {
                lat: inputs.find(input => input.placeholder === '-6.1751' || 
                    (input.previousElementSibling && input.previousElementSibling.textContent.toLowerCase().includes('latitude'))),
                lng: inputs.find(input => input.placeholder === '106.8650' || 
                    (input.previousElementSibling && input.previousElementSibling.textContent.toLowerCase().includes('longitude')))
            };
        }
    ];
    
    for (const strategy of strategies) {
        const result = strategy();
        if (result.lat && result.lng) {
            console.log('Found coordinate inputs using strategy', strategies.indexOf(strategy) + 1);
            return result;
        }
    }
    
    console.log('Could not find coordinate inputs');
    return { lat: null, lng: null };
}

async function updateMapPreview() {
    console.log('updateMapPreview called');
    
    const { lat: latInput, lng: lngInput } = findCoordinateInputs();
    
    if (!latInput || !lngInput) {
        console.log('Coordinate inputs not found');
        return;
    }
    
    const lat = parseFloat(latInput.value);
    const lng = parseFloat(lngInput.value);
    
    console.log('Coordinates found:', lat, lng);
    
    const mapPreview = document.getElementById('mapPreview');
    if (!mapPreview) {
        console.log('mapPreview element not found');
        return;
    }
    
    if (lat && lng && !isNaN(lat) && !isNaN(lng) && lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180) {
        // Clear existing content
        mapPreview.innerHTML = '';
        
        // Ensure MapLibre is loaded
        if (typeof window.maplibregl === 'undefined') {
            // Try to load it dynamically
            try {
                if (window.loadMapUtils) {
                    await window.loadMapUtils();
                } else {
                    console.error('MapLibre GL JS not loaded');
                    mapPreview.innerHTML = '<div class="flex items-center justify-center h-full text-red-500">Map library not loaded</div>';
                    return;
                }
            } catch (error) {
                console.error('Failed to load MapLibre:', error);
                mapPreview.innerHTML = '<div class="flex items-center justify-center h-full text-red-500">Map library not loaded</div>';
                return;
            }
        }
        
        try {
            console.log('Creating map with coordinates:', lng, lat);
            const previewMap = new window.maplibregl.Map({
                container: mapPreview,
                style: {
                    'version': 8,
                    'sources': {
                        'osm': {
                            'type': 'raster',
                            'tiles': [
                                'https://a.tile.openstreetmap.org/{z}/{x}/{y}.png'
                            ],
                            'tileSize': 256,
                            'attribution': '© OpenStreetMap contributors'
                        }
                    },
                    'layers': [
                        {
                            'id': 'osm',
                            'type': 'raster',
                            'source': 'osm'
                        }
                    ]
                },
                center: [lng, lat],
                zoom: 15,
                interactive: false
            });
            
            // Add marker after map loads
            previewMap.on('load', () => {
                new window.maplibregl.Marker({ color: '#3B82F6' })
                    .setLngLat([lng, lat])
                    .addTo(previewMap);
                console.log('Map marker added');
            });
            
            console.log('Map preview created successfully');
        } catch (error) {
            console.error('Error creating map preview:', error);
            mapPreview.innerHTML = '<div class="flex items-center justify-center h-full text-red-500">Map error: ' + error.message + '</div>';
        }
    } else {
        mapPreview.innerHTML = '<div class="flex items-center justify-center h-full text-gray-500">Enter valid coordinates to show map preview</div>';
    }
}

// Setup coordinate listeners
function setupCoordinateListeners() {
    console.log('Setting up coordinate listeners...');
    
    const { lat: latInput, lng: lngInput } = findCoordinateInputs();
    
    if (latInput && lngInput) {
        // Remove existing listeners to prevent duplicates
        latInput.removeEventListener('input', updateMapPreview);
        lngInput.removeEventListener('input', updateMapPreview);
        latInput.removeEventListener('change', updateMapPreview);
        lngInput.removeEventListener('change', updateMapPreview);
        
        // Add new listeners
        ['input', 'change'].forEach(eventType => {
            latInput.addEventListener(eventType, updateMapPreview);
            lngInput.addEventListener(eventType, updateMapPreview);
        });
        
        console.log('Coordinate listeners set up successfully');
        
        // Initial preview update if coordinates exist
        setTimeout(updateMapPreview, 500);
        return true;
    }
    
    console.log('Could not set up coordinate listeners - inputs not found');
    return false;
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', setupCoordinateListeners);

// Re-initialize after Livewire updates
document.addEventListener('livewire:morph.updated', function() {
    console.log('Livewire updated, reinitializing map listeners');
    setTimeout(setupCoordinateListeners, 100);
});

// Also try to initialize when modal is shown
function initializeMapOnModalShow() {
    setTimeout(() => {
        setupCoordinateListeners();
        updateMapPreview();
    }, 200);
}

// Test function to check if MapLibre works with hardcoded coordinates
async function testMap() {
    console.log('Testing map functionality...');
    
    const mapPreview = document.getElementById('mapPreview');
    if (!mapPreview) {
        console.error('mapPreview element not found');
        alert('Map container not found');
        return;
    }
    
    // Check if MapLibre is loaded
    if (typeof window.maplibregl === 'undefined') {
        // Try to load it dynamically
        try {
            if (window.loadMapUtils) {
                await window.loadMapUtils();
            } else {
                console.error('MapLibre GL JS not loaded');
                alert('MapLibre library not loaded. Check network connection.');
                return;
            }
        } catch (error) {
            console.error('Failed to load MapLibre:', error);
            alert('MapLibre library not loaded. Check network connection.');
            return;
        }
    }
    
    console.log('MapLibre GL JS loaded successfully');
    
    // Clear existing content
    mapPreview.innerHTML = '';
    
    try {
        // Test with Jakarta coordinates
        const testMap = new window.maplibregl.Map({
            container: mapPreview,
            style: {
                'version': 8,
                'sources': {
                    'osm': {
                        'type': 'raster',
                        'tiles': [
                            'https://a.tile.openstreetmap.org/{z}/{x}/{y}.png'
                        ],
                        'tileSize': 256,
                        'attribution': '© OpenStreetMap contributors'
                    }
                },
                'layers': [
                    {
                        'id': 'osm',
                        'type': 'raster',
                        'source': 'osm'
                    }
                ]
            },
            center: [106.8456, -6.2088], // Jakarta
            zoom: 12,
            interactive: true
        });
        
        testMap.on('load', () => {
            new window.maplibregl.Marker({ color: '#FF0000' })
                .setLngLat([106.8456, -6.2088])
                .addTo(testMap);
            console.log('Test map loaded successfully');
            alert('Map test successful! MapLibre is working.');
        });
        
        testMap.on('error', (error) => {
            console.error('Map error:', error);
            alert('Map error: ' + error.error.message);
        });
        
    } catch (error) {
        console.error('Error creating test map:', error);
        alert('Error creating test map: ' + error.message);
    }
}

// Admin Fullscreen Map Functionality
let adminFullscreenMap = null;
let currentMapData = null; // Store current map data for fullscreen

function openAdminFullscreenMap() {
    const modal = document.getElementById('adminFullscreenMapModal');
    const mapContainer = document.getElementById('adminFullscreenMapContainer');
    
    if (!modal || !mapContainer) {
        console.error('Admin fullscreen map elements not found');
        return;
    }

    // Get current map data from the existing map modal
    const mapTitle = document.getElementById('mapModalTitle')?.textContent || 'Location Map';
    
    // Try to get coordinates from the existing map instance or fallback
    let lat = -6.2088; // Jakarta default
    let lng = 106.8456;
    
    if (map && map.getCenter) {
        const center = map.getCenter();
        lat = center.lat;
        lng = center.lng;
    }

    // Show modal
    modal.classList.remove('hidden');
    
    // Clear previous map
    mapContainer.innerHTML = '';

    // Initialize map after a small delay to ensure modal is visible
    setTimeout(async () => {
        try {
            // Check if MapLibre is loaded
            if (typeof window.maplibregl === 'undefined') {
                try {
                    await window.loadMapUtils();
                } catch (error) {
                    console.error('Failed to load map utilities:', error);
                    mapContainer.innerHTML = '<div class="flex items-center justify-center h-full text-red-500"><p>Map library not loaded</p></div>';
                    return;
                }
            }

            adminFullscreenMap = new window.maplibregl.Map({
                container: mapContainer,
                style: {
                    'version': 8,
                    'sources': {
                        'osm': {
                            'type': 'raster',
                            'tiles': [
                                'https://a.tile.openstreetmap.org/{z}/{x}/{y}.png'
                            ],
                            'tileSize': 256,
                            'attribution': '© OpenStreetMap contributors'
                        }
                    },
                    'layers': [
                        {
                            'id': 'osm',
                            'type': 'raster',
                            'source': 'osm'
                        }
                    ]
                },
                center: [lng, lat],
                zoom: 16,
                interactive: true
            });

            adminFullscreenMap.on('load', () => {
                // Add location marker
                new window.maplibregl.Marker({ color: '#EF4444' })
                    .setLngLat([lng, lat])
                    .addTo(adminFullscreenMap);

                // Add navigation controls
                adminFullscreenMap.addControl(new window.maplibregl.NavigationControl());

                console.log('Admin fullscreen map loaded successfully');
            });

            adminFullscreenMap.on('error', (error) => {
                console.error('Admin fullscreen map error:', error);
                mapContainer.innerHTML = '<div class="flex items-center justify-center h-full text-red-500"><p>Map error: ' + error.error.message + '</p></div>';
            });

        } catch (error) {
            console.error('Error creating admin fullscreen map:', error);
            mapContainer.innerHTML = '<div class="flex items-center justify-center h-full text-red-500"><p>Error creating map: ' + error.message + '</p></div>';
        }
    }, 100);
}

function closeAdminFullscreenMap() {
    const modal = document.getElementById('adminFullscreenMapModal');
    if (modal) {
        modal.classList.add('hidden');
    }
    
    if (adminFullscreenMap) {
        adminFullscreenMap.remove();
        adminFullscreenMap = null;
    }
}

// Close admin fullscreen map when clicking outside
document.addEventListener('click', function(e) {
    const modal = document.getElementById('adminFullscreenMapModal');
    if (modal && e.target === modal) {
        closeAdminFullscreenMap();
    }
});

// Close admin fullscreen map with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAdminFullscreenMap();
    }
});

// Admin Location Detection Functionality
let locationWatchId = null;
let locationRefreshInterval = null;

function initializeAdminLocationDetection() {
    if (!navigator.geolocation) {
        console.error('Geolocation is not supported by this browser.');
        @this.setLocationError('Geolocation is not supported by this browser.');
        return;
    }

    // Start location detection immediately
    requestLocationPermission();
    
    // Set up 10-second refresh interval
    startLocationRefresh();
}

function requestLocationPermission() {
    const options = {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 0
    };

    navigator.geolocation.getCurrentPosition(
        function(position) {
            updateLocationDisplay(position);
        },
        function(error) {
            handleLocationError(error);
        },
        options
    );
}

function updateLocationDisplay(position) {
    const latitude = position.coords.latitude;
    const longitude = position.coords.longitude;
    const accuracy = position.coords.accuracy;

    console.log('Location updated:', { latitude, longitude, accuracy });

    // Update Livewire component
    @this.updateAdminLocation(latitude, longitude, accuracy);
}

function handleLocationError(error) {
    let errorMessage = '';
    
    switch(error.code) {
        case error.PERMISSION_DENIED:
            errorMessage = "Location access denied by user.";
            break;
        case error.POSITION_UNAVAILABLE:
            errorMessage = "Location information is unavailable.";
            break;
        case error.TIMEOUT:
            errorMessage = "Location request timed out.";
            break;
        default:
            errorMessage = "An unknown error occurred while retrieving location.";
            break;
    }
    
    console.error('Location error:', errorMessage);
    @this.setLocationError(errorMessage);
}

function startLocationRefresh() {
    // Clear any existing interval
    if (locationRefreshInterval) {
        clearInterval(locationRefreshInterval);
    }
    
    // Set up 10-second refresh interval
    locationRefreshInterval = setInterval(function() {
        requestLocationPermission();
    }, 10000); // 10 seconds

    console.log('Location refresh started (every 10 seconds)');
}

function stopLocationRefresh() {
    if (locationRefreshInterval) {
        clearInterval(locationRefreshInterval);
        locationRefreshInterval = null;
        console.log('Location refresh stopped');
    }
    
    if (locationWatchId) {
        navigator.geolocation.clearWatch(locationWatchId);
        locationWatchId = null;
    }
}

// Initialize location detection when page loads
document.addEventListener('DOMContentLoaded', function() {
    initializeAdminLocationDetection();
});

// Restart location detection after Livewire updates
document.addEventListener('livewire:morph.updated', function() {
    // Small delay to ensure DOM is ready
    setTimeout(function() {
        initializeAdminLocationDetection();
    }, 100);
});

// Clean up intervals when page is unloaded
window.addEventListener('beforeunload', function() {
    stopLocationRefresh();
});
</script>