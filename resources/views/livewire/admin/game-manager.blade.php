<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:justify-between lg:items-center mb-6 gap-4">
        <h1 class="text-2xl font-bold text-gray-800">{{ __('games.title') }}</h1>
        
        <!-- Search and Filter -->
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="flex gap-2">
                <div class="relative">
                    <input type="text" wire:model.live="search" placeholder="{{ __('games.search_locations') }}" 
                           class="pl-8 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                    <svg class="w-4 h-4 absolute left-2.5 top-2.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                
                <select wire:model.live="filterStatus" class="border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                    <option value="all">{{ __('games.all_status') }}</option>
                    <option value="active">{{ __('games.filter_active') }}</option>
                    <option value="inactive">{{ __('games.filter_inactive') }}</option>
                </select>
            </div>
            
            <button wire:click="create" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg shadow whitespace-nowrap">
                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                {{ __('games.add_location') }}
            </button>
        </div>
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

    <!-- Error Message -->
    @if (session()->has('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4">
            <div class="flex">
                <svg class="w-4 h-4 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                {{ session('error') }}
            </div>
        </div>
    @endif

    <!-- Bulk Actions -->
    @if(!empty($selectedGames))
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
            <span class="text-sm text-blue-800">{{ __('games.selected_count', ['count' => count($selectedGames)]) }}</span>
            <div class="flex gap-2">
                <button wire:click="bulkActivate" class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-sm">
                    {{ __('games.activate') }}
                </button>
                <button wire:click="bulkDeactivate" class="bg-orange-500 hover:bg-orange-600 text-white px-3 py-1 rounded text-sm">
                    {{ __('games.deactivate') }}
                </button>
                <button wire:click="bulkDelete" wire:confirm="{{ __('games.confirm_bulk_delete') }}" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm">
                    {{ __('common.delete') }}
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- Games Table -->
    <div class="bg-white shadow-lg rounded-lg overflow-hidden">
        <table class="min-w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">
                        <input type="checkbox" wire:model="selectAll" class="rounded border-gray-300">
                    </th>
                    <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">{{ __('games.game') }}</th>
                    <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">{{ __('games.images') }}</th>
                    <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">{{ __('games.coordinates') }}</th>
                    <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">{{ __('games.points') }}</th>
                    <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">{{ __('games.status') }}</th>
                    <th class="px-6 py-3 text-center text-sm font-medium text-gray-500 uppercase tracking-wider">{{ __('games.actions') }}</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($games as $game)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <input type="checkbox" wire:model="selectedGames" value="{{ $game->id }}" class="rounded border-gray-300">
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            @if($game->image_path)
                                <img src="{{ Storage::url($game->image_path) }}" alt="{{ $game->name }}" 
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
                                <div class="text-sm font-medium text-gray-900">{{ $game->name }}</div>
                                <div class="text-sm text-gray-500">{{ Str::limit($game->description, 50) }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex space-x-2">
                            @if($game->image_path)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ __('games.regular') }}
                                </span>
                            @endif
                            @if($game->map_image_path)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    {{ __('games.map') }}
                                </span>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        @if($game->coordinate_x && $game->coordinate_y)
                            <div>Map: {{ $game->coordinate_x }}, {{ $game->coordinate_y }}</div>
                        @else
                            <div class="text-gray-400">Not set</div>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        {{ $game->quest_points }} pts
                    </td>
                    <td class="px-6 py-4">
                        @if($game->is_active)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                {{ __('games.active') }}
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                {{ __('games.inactive') }}
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-center">
                        <div class="flex justify-center space-x-2">
                            <button wire:click="edit({{ $game->id }})" 
                                    class="text-blue-600 hover:text-blue-800 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </button>
                            <button wire:click="delete({{ $game->id }})" 
                                    wire:confirm="{{ __('games.confirm_delete') }}"
                                    class="text-red-600 hover:text-red-800 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                        <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <p class="text-lg font-medium">{{ __('games.no_locations_found') }}</p>
                        <p class="text-sm">{{ __('games.create_first_location') }}</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $games->links() }}
    </div>

    <!-- Modal -->
    @if($showModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeModal"></div>
            
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                <form wire:submit.prevent="save">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900">
                                {{ $editMode ? __('games.edit') : __('games.create') }}
                            </h3>
                            <button type="button" wire:click="closeModal" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Left Column -->
                            <div class="space-y-4">
                                <!-- Name -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('games.name') }}</label>
                                    <input type="text" wire:model="name" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="{{ __('games.name_placeholder') }}">
                                    @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <!-- Description -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('games.description') }}</label>
                                    <textarea wire:model="description" rows="3" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="{{ __('games.description_placeholder') }}"></textarea>
                                    @error('description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <!-- What to do -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('games.what_to_do') }}</label>
                                    <textarea wire:model="what_to_do" rows="3" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="{{ __('games.what_to_do_placeholder') }}"></textarea>
                                    @error('what_to_do') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <!-- Map Coordinates (Auto-calculated) -->
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('games.coordinate_x') }} <span class="text-xs text-gray-500">(Auto-calculated)</span></label>
                                        <input type="number" wire:model="coordinate_x" readonly class="w-full border border-gray-300 rounded-md px-3 py-2 bg-gray-50 text-gray-600 focus:outline-none" placeholder="Drag red dot on map">
                                        @error('coordinate_x') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('games.coordinate_y') }} <span class="text-xs text-gray-500">(Auto-calculated)</span></label>
                                        <input type="number" wire:model="coordinate_y" readonly class="w-full border border-gray-300 rounded-md px-3 py-2 bg-gray-50 text-gray-600 focus:outline-none" placeholder="Drag red dot on map">
                                        @error('coordinate_y') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column -->
                            <div class="space-y-4">

                                <!-- Settings -->
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('games.radius') }}</label>
                                        <input type="number" wire:model="radius" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                        @error('radius') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('games.quest_points') }}</label>
                                        <input type="number" wire:model="quest_points" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                        @error('quest_points') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('games.max_check_ins') }}</label>
                                    <input type="number" wire:model="max_check_ins_per_user" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    @error('max_check_ins_per_user') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <!-- Regular Image -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('games.regular_image') }}</label>
                                    @if($existing_image_path && !$pending_remove_image)
                                        <div class="mb-2">
                                            <img src="{{ Storage::url($existing_image_path) }}" alt="Current Image" class="w-24 h-16 object-cover rounded border">
                                            <button type="button" wire:click="removeImage('regular')" class="text-red-500 text-xs hover:text-red-700 ml-2">{{ __('games.remove') }}</button>
                                        </div>
                                    @elseif($existing_image_path && $pending_remove_image)
                                        <div class="mb-2 p-3 bg-red-50 border border-red-200 rounded-lg">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center">
                                                    <div class="w-24 h-16 bg-red-100 border border-red-300 rounded flex items-center justify-center opacity-50">
                                                        <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                        </svg>
                                                    </div>
                                                    <div class="ml-3">
                                                        <p class="text-sm font-medium text-red-800">Image marked for removal</p>
                                                        <p class="text-xs text-red-600">Will be deleted when you save</p>
                                                    </div>
                                                </div>
                                                <button type="button" wire:click="undoRemoveImage('regular')" class="text-blue-600 text-xs hover:text-blue-800 font-medium">{{ __('games.undo_remove') }}</button>
                                            </div>
                                        </div>
                                    @endif
                                    <input type="file" wire:model="image" accept="image/*" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    
                                    <!-- Upload progress indicator -->
                                    <div wire:loading wire:target="image" class="mt-2">
                                        <div class="flex items-center text-sm text-blue-600">
                                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Uploading image...
                                        </div>
                                    </div>
                                    
                                    <!-- New image preview -->
                                    @if($image && !$pending_remove_image)
                                        <div class="mt-3 p-3 bg-green-50 border border-green-200 rounded-lg">
                                            <div class="flex items-center">
                                                <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                <div>
                                                    <p class="text-sm font-medium text-green-800">New image ready</p>
                                                    <p class="text-xs text-green-600">{{ $image->getClientOriginalName() }} ({{ number_format($image->getSize() / 1024, 1) }} KB)</p>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                    
                                    @error('image') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <!-- Map Image with Interactive Positioning -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('games.map_image') }}</label>
                                    
                                    <!-- Map Preview and Coordinate Setter -->
                                    @if($existing_map_image_path && !$pending_remove_map_image)
                                        <div class="mb-4">
                                            <div class="relative inline-block border-2 border-gray-200 rounded-lg overflow-hidden bg-gray-50 map-container-enhanced">
                                                <img id="admin-map-image" 
                                                     src="{{ Storage::url($existing_map_image_path) }}" 
                                                     alt="Interactive Map" 
                                                     class="max-w-full max-h-64 object-contain cursor-crosshair"
                                                     style="user-select: none;">
                                                
                                                <!-- Draggable Red Dot Marker -->
                                                <div id="admin-location-marker" 
                                                     class="absolute w-5 h-5 bg-red-500 rounded-full border-2 border-white shadow-lg cursor-grab hover:cursor-grabbing"
                                                     style="left: {{ $coordinate_x ?: 50 }}px; top: {{ $coordinate_y ?: 50 }}px; z-index: 10; transform: translate(-50%, -50%);"
                                                     title="Drag me to set the game location!"
                                                     data-interactjs-draggable="true">
                                                    <!-- Inner dot for better visibility -->
                                                    <div class="absolute inset-0.5 bg-red-600 rounded-full"></div>
                                                </div>
                                            </div>
                                            
                                            <div class="mt-2 text-sm text-gray-600">
                                                <p class="font-medium text-blue-600">🎯 {{ __('games.interactive_positioning') }}</p>
                                                <div class="mt-1 space-y-1">
                                                    <p>• <strong>Click</strong> anywhere on the map to place the red dot</p>
                                                    <p>• <strong>Drag</strong> the red dot to fine-tune its position</p>
                                                    <p>• <strong>Coordinates</strong> will be calculated automatically</p>
                                                    <p class="text-green-600">• Current position: <span id="coordinate-display">{{ $coordinate_x ?? 'Not set' }}, {{ $coordinate_y ?? 'Not set' }}</span></p>
                                                </div>
                                            </div>
                                            
                                            <button type="button" wire:click="removeImage('map')" class="text-red-500 text-xs hover:text-red-700 mt-2">{{ __('games.remove') }}</button>
                                        </div>
                                    @elseif($existing_map_image_path && $pending_remove_map_image)
                                        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                                            <div class="flex items-center justify-between mb-3">
                                                <div class="flex items-center">
                                                    <div class="w-16 h-16 bg-red-100 border border-red-300 rounded flex items-center justify-center opacity-50">
                                                        <svg class="w-8 h-8 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                                                        </svg>
                                                    </div>
                                                    <div class="ml-3">
                                                        <p class="text-sm font-medium text-red-800">Map image marked for removal</p>
                                                        <p class="text-xs text-red-600">Will be deleted when you save. Coordinates will be reset.</p>
                                                    </div>
                                                </div>
                                                <button type="button" wire:click="undoRemoveImage('map')" class="text-blue-600 text-xs hover:text-blue-800 font-medium">{{ __('games.undo_remove') }}</button>
                                            </div>
                                            <div class="text-xs text-red-600 bg-red-100 p-2 rounded">
                                                ⚠️ Removing the map image will also reset the coordinate positioning. You'll need to upload a new map and set coordinates again.
                                            </div>
                                        </div>
                                    @endif
                                    
                                    <input type="file" wire:model="map_image" accept="image/*" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" onchange="setTimeout(() => { if (typeof initializeAdminMapInteraction === 'function') initializeAdminMapInteraction(); }, 1000)">
                                    
                                    <!-- Upload progress indicator -->
                                    <div wire:loading wire:target="map_image" class="mt-2">
                                        <div class="flex items-center text-sm text-blue-600">
                                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Uploading map image...
                                        </div>
                                    </div>
                                    
                                    <!-- New map image preview -->
                                    @if($map_image && !$pending_remove_map_image)
                                        <div class="mt-3 p-3 bg-green-50 border border-green-200 rounded-lg">
                                            <div class="flex items-center">
                                                <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                <div>
                                                    <p class="text-sm font-medium text-green-800">New map image ready</p>
                                                    <p class="text-xs text-green-600">{{ $map_image->getClientOriginalName() }} ({{ number_format($map_image->getSize() / 1024, 1) }} KB)</p>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                    
                                    @error('map_image') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>


                                <!-- Active Status -->
                                <div class="flex items-center">
                                    <input type="checkbox" wire:model="is_active" id="is_active" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="is_active" class="ml-2 block text-sm text-gray-900">{{ __('games.is_active') }}</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                            {{ $editMode ? __('common.update') : __('common.create') }}
                        </button>
                        <button type="button" wire:click="closeModal" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            {{ __('common.cancel') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- InteractJS Map Positioning Styles -->
    <style>
        @keyframes ripple {
            0% {
                transform: translate(-50%, -50%) scale(0);
                opacity: 1;
            }
            100% {
                transform: translate(-50%, -50%) scale(4);
                opacity: 0;
            }
        }
        
        @keyframes successPulse {
            0% { transform: translate(-50%, -50%) scale(1); }
            50% { transform: translate(-50%, -50%) scale(1.3); }
            100% { transform: translate(-50%, -50%) scale(1); }
        }
        
        .map-container-enhanced {
            cursor: crosshair;
            user-select: none;
        }
        
        .map-container-enhanced.dragging {
            cursor: grabbing !important;
        }
        
        #admin-location-marker {
            cursor: grab;
            touch-action: none;
            pointer-events: auto;
            user-select: none;
            transition: box-shadow 0.2s ease;
        }
        
        #admin-location-marker:hover {
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
        }
        
        #admin-location-marker.dragging {
            cursor: grabbing;
            z-index: 1000;
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.5);
        }
        
        /* Ensure inner dot doesn't interfere with dragging */
        #admin-location-marker > div {
            pointer-events: none;
        }
        
        .ripple-effect {
            pointer-events: none;
            animation: ripple 0.6s ease-out;
        }
    </style>

    <!-- Interactive Map Positioning Script with InteractJS -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        initializeAdminMapInteraction();
        
        // Re-initialize when Livewire updates the DOM
        document.addEventListener('livewire:navigated', function() {
            setTimeout(initializeAdminMapInteraction, 200);
        });
    });

    // Re-initialize when modal is opened/updated
    if (typeof Livewire !== 'undefined') {
        Livewire.hook('morph.updated', ({ el }) => {
            // Check if the map image or marker was added/updated
            if (el.querySelector('#admin-map-image') || el.querySelector('#admin-location-marker')) {
                setTimeout(initializeAdminMapInteraction, 200);
            }
        });
        
        // Also listen for Livewire component updates
        Livewire.hook('component.updated', ({ component, el }) => {
            if (el.querySelector('#admin-map-image')) {
                setTimeout(initializeAdminMapInteraction, 200);
            }
        });
        
        // Listen for custom map interaction ready event
        document.addEventListener('livewire:map-interaction-ready', function() {
            setTimeout(initializeAdminMapInteraction, 100);
        });
    }

    function initializeAdminMapInteraction() {
        console.log('Attempting to initialize admin map interaction...');
        
        // Check if InteractJS is available
        if (typeof window.AdminMapManager === 'undefined') {
            console.warn('AdminMapManager not available, skipping InteractJS initialization');
            return;
        }
        
        // Look for existing map elements
        const mapImage = document.getElementById('admin-map-image');
        const marker = document.getElementById('admin-location-marker');
        
        console.log('Map elements found:', { mapImage: !!mapImage, marker: !!marker });
        
        if (!mapImage || !marker) {
            // Elements don't exist yet (probably creating new location or no map uploaded)
            // Set up a MutationObserver to initialize when elements are added
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList') {
                        const newMapImage = document.getElementById('admin-map-image');
                        const newMarker = document.getElementById('admin-location-marker');
                        
                        if (newMapImage && newMarker) {
                            // Elements now exist, initialize InteractJS
                            initializeInteractJS(newMapImage, newMarker);
                            observer.disconnect(); // Stop observing
                        }
                    }
                });
            });
            
            // Start observing the modal content for changes
            const modalContent = document.querySelector('.bg-white.px-4.pt-5.pb-4');
            if (modalContent) {
                observer.observe(modalContent, {
                    childList: true,
                    subtree: true
                });
            }
            return;
        }
        
        // Elements exist, initialize immediately
        initializeInteractJS(mapImage, marker);
    }
    
    function initializeInteractJS(mapImage, marker) {
        console.log('Initializing InteractJS for elements:', { mapImage: mapImage.id, marker: marker.id });
        
        // Clean up any existing instance first
        if (window.AdminMapManager && typeof window.AdminMapManager.destroy === 'function') {
            window.AdminMapManager.destroy('admin-location-marker');
        }
        
        // Initialize InteractJS drag and drop
        const instance = window.AdminMapManager.initialize('admin-map-image', 'admin-location-marker', {
            onEnd: function(event, marker, container) {
                // Calculate coordinates relative to image
                const rect = container.getBoundingClientRect();
                const markerRect = marker.getBoundingClientRect();
                const x = markerRect.left - rect.left + (markerRect.width / 2);
                const y = markerRect.top - rect.top + (markerRect.height / 2);
                
                // Update Livewire component
                updateCoordinates(Math.round(x), Math.round(y));
            }
        });
        
        if (instance) {
            console.log('InteractJS initialized successfully');
        } else {
            console.warn('InteractJS initialization failed');
        }
    }
    
    function updateCoordinates(x, y) {
        // Update the Livewire component properties
        if (typeof Livewire !== 'undefined') {
            @this.set('coordinate_x', x);
            @this.set('coordinate_y', y);
        }
        
        // Update visual feedback
        updateCoordinateDisplay(x, y);
        showCoordinateUpdate(x, y);
    }
    
    function updateCoordinateDisplay(x, y) {
        // Update the real-time coordinate display
        const coordinateDisplay = document.getElementById('coordinate-display');
        if (coordinateDisplay) {
            coordinateDisplay.textContent = `${x}, ${y}`;
            coordinateDisplay.parentElement.style.color = '#10b981'; // Green color
        }
    }
    
    function showCoordinateUpdate(x, y) {
        // Add brief flash animation to coordinate display
        const coordinateDisplay = document.getElementById('coordinate-display');
        if (coordinateDisplay) {
            coordinateDisplay.style.animation = 'coordinateFlash 0.5s ease-out';
            setTimeout(() => {
                coordinateDisplay.style.animation = '';
            }, 500);
        }
    }
    </script>
</div>
