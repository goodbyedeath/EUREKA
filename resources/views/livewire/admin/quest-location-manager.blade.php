<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Quest Locations</h1>
        <button wire:click="create" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg shadow">
            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Add Location
        </button>
    </div>

    <!-- Success Message -->
    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4">
            <div class="flex">
                <svg class="w-4 h-4 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                {{ session('message') }}
            </div>
        </div>
    @endif

    <!-- Locations Table -->
    <div class="bg-white shadow-lg rounded-lg overflow-hidden">
        <table class="min-w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">Location</th>
                    <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">Coordinates</th>
                    <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">Settings</th>
                    <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-center text-sm font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($questLocations as $location)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            @if($location->image_path)
                                <img src="{{ Storage::url($location->image_path) }}" alt="{{ $location->name }}" 
                                     class="w-12 h-12 rounded-lg object-cover mr-4">
                            @else
                                <div class="w-12 h-12 bg-gray-200 rounded-lg flex items-center justify-center mr-4">
                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                </div>
                            @endif
                            <div>
                                <div class="font-medium text-gray-900">{{ $location->name }}</div>
                                @if($location->description)
                                    <div class="text-sm text-gray-500">{{ Str::limit($location->description, 60) }}</div>
                                @endif
                                @if($location->quest_points > 0)
                                    <div class="text-sm text-blue-600 font-medium">{{ $location->quest_points }} points</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900">
                            {{ number_format($location->latitude, 6) }},<br>
                            {{ number_format($location->longitude, 6) }}
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900">
                            <div>Radius: <span class="font-medium">{{ $location->radius }}m</span></div>
                            @if($location->max_check_ins_per_user)
                                <div>Max check-ins: <span class="font-medium">{{ $location->max_check_ins_per_user }}</span></div>
                            @else
                                <div class="text-gray-500">Unlimited check-ins</div>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $location->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $location->is_active ? 'Active' : 'Inactive' }}
                        </span>
                        @if($location->created_by)
                            <div class="text-xs text-gray-500 mt-1">
                                Created by: {{ $location->creator->name ?? 'User #' . $location->created_by }}
                            </div>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-center">
                        <div class="flex justify-center space-x-2">
                            @if($location->google_map_embed_url)
                                <a href="https://www.google.com/maps/search/?api=1&query={{ $location->latitude }},{{ $location->longitude }}" 
                                   target="_blank"
                                   class="text-green-600 hover:text-green-800"
                                   title="View on Google Maps">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                </a>
                            @endif
                            <button wire:click="edit({{ $location->id }})" 
                                    class="text-blue-600 hover:text-blue-800" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </button>
                            <button wire:click="delete({{ $location->id }})" 
                                    wire:confirm="Are you sure you want to delete this location? This action cannot be undone."
                                    class="text-red-600 hover:text-red-800" title="Delete">
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
                        <div class="text-gray-500">
                            <svg class="w-12 h-12 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <p class="text-lg font-medium">No quest locations found</p>
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
            <div class="relative bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-screen overflow-y-auto">
                <form wire:submit="save">
                    <!-- Header -->
                    <div class="px-6 py-4 border-b bg-gray-50">
                        <h3 class="text-lg font-semibold text-gray-900">
                            {{ $editMode ? 'Edit Location' : 'Add New Location' }}
                        </h3>
                    </div>

                    <!-- Body -->
                    <div class="px-6 py-4 space-y-6">
                        
                        <!-- Image Upload Section -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Location Image</label>
                            
                            @if($existing_image_path)
                                <div class="mb-3">
                                    <img src="{{ Storage::url($existing_image_path) }}" alt="Current image" 
                                         class="w-32 h-32 object-cover rounded-lg border">
                                    <button type="button" wire:click="removeImage" 
                                            class="mt-2 text-sm text-red-600 hover:text-red-800">
                                        Remove current image
                                    </button>
                                </div>
                            @endif
                            
                            <input type="file" wire:model="image" accept="image/*"
                                   class="w-full border rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('image') 
                                <span class="text-red-500 text-xs mt-1">{{ $message }}</span> 
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
                                <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                                <input type="text" wire:model="name" 
                                       class="w-full border rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('name') 
                                    <span class="text-red-500 text-xs">{{ $errors->first('name') }}</span> 
                                @enderror
                            </div>

                            <!-- Quest Points -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Quest Points *</label>
                                <input type="number" wire:model="quest_points" min="0"
                                       class="w-full border rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('quest_points') 
                                    <span class="text-red-500 text-xs">{{ $errors->first('quest_points') }}</span> 
                                @enderror
                            </div>

                            <!-- Max Check-ins -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Max Check-ins per User</label>
                                <input type="number" wire:model="max_check_ins_per_user" min="1" 
                                       placeholder="Leave empty for unlimited"
                                       class="w-full border rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('max_check_ins_per_user') 
                                    <span class="text-red-500 text-xs">{{ $errors->first('max_check_ins_per_user') }}</span> 
                                @enderror
                                <p class="text-xs text-gray-500 mt-1">Leave empty for unlimited check-ins</p>
                            </div>
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description *</label>
                            <textarea wire:model="description" rows="3"
                                      class="w-full border rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                      placeholder="Describe this location..."></textarea>
                            @error('description') 
                                <span class="text-red-500 text-xs">{{ $errors->first('description') }}</span> 
                            @enderror
                        </div>

                        <!-- Instructions -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Instructions *</label>
                            <textarea wire:model="what_to_do" rows="3"
                                      class="w-full border rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                      placeholder="What should users do at this location?"></textarea>
                            @error('what_to_do') 
                                <span class="text-red-500 text-xs">{{ $errors->first('what_to_do') }}</span> 
                            @enderror
                        </div>

                        <!-- Google Maps URL -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Google Maps URL</label>
                            <textarea wire:model="google_map_embed_url" wire:blur="extractCoordinates" rows="3"
                                      class="w-full border rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                      placeholder="Paste the full iframe code or just the URL..."></textarea>
                            @error('google_map_embed_url') 
                                <span class="text-red-500 text-xs">{{ $errors->first('google_map_embed_url') }}</span> 
                            @enderror
                            
                            <!-- Coordinate extraction feedback -->
                            @if(session()->has('coordinate_extracted'))
                                <div class="text-green-600 text-xs mt-1 bg-green-50 p-2 rounded">
                                    ✓ {{ session('coordinate_extracted') }}
                                </div>
                            @endif
                            @if(session()->has('coordinate_error'))
                                <div class="text-yellow-600 text-xs mt-1 bg-yellow-50 p-2 rounded">
                                    ⚠ {{ session('coordinate_error') }}
                                </div>
                            @endif
                            
                            <div class="text-xs text-gray-500 mt-2 bg-gray-50 p-3 rounded">
                                <p class="font-medium mb-2">How to get Google Maps URL:</p>
                                <p><strong>Option 1:</strong> Share link from Google Maps and paste the URL</p>
                                <p><strong>Option 2:</strong> Copy the embed iframe code and paste it here</p>
                                <p class="mt-2 text-green-600">💡 Coordinates will be automatically extracted when you paste!</p>
                            </div>
                        </div>

                        <!-- Coordinates -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Latitude *</label>
                                <input type="number" step="any" wire:model="latitude" 
                                       placeholder="-6.1751"
                                       class="w-full border rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('latitude') 
                                    <span class="text-red-500 text-xs">{{ $errors->first('latitude') }}</span> 
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Longitude *</label>
                                <input type="number" step="any" wire:model="longitude" 
                                       placeholder="106.8650"
                                       class="w-full border rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('longitude') 
                                    <span class="text-red-500 text-xs">{{ $errors->first('longitude') }}</span> 
                                @enderror
                            </div>
                        </div>

                        <!-- Radius -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Check-in Radius (meters) *</label>
                            <input type="number" wire:model="radius" min="10" max="1000"
                                   class="w-full border rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('radius') 
                                <span class="text-red-500 text-xs">{{ $errors->first('radius') }}</span> 
                            @enderror
                            <p class="text-xs text-gray-500 mt-1">Recommended: 50-100m for buildings, 10-30m for specific spots</p>
                        </div>

                        <!-- Active Status -->
                        <div class="flex items-center space-x-3">
                            <div class="flex items-center">
                                <input type="checkbox" wire:model="is_active" id="is_active" 
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <label for="is_active" class="ml-2 text-sm font-medium text-gray-700">
                                    Location is Active
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="px-6 py-4 border-t bg-gray-50 flex justify-end space-x-3">
                        <button type="button" wire:click="closeModal" 
                                class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 flex items-center">
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
</div>