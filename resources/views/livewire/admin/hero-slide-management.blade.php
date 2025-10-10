<div class="space-y-6">
    <!-- Success Message -->
    @if (session()->has('success'))
        <div class="bg-green-100 dark:bg-green-800 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-200 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    <!-- Header with Add Button -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Hero Slides Management</h2>
            <p class="text-gray-600 dark:text-gray-400">Manage carousel slides for the landing page</p>
        </div>
        <button wire:click="openCreateForm" class="bg-blue-600 dark:bg-blue-600 hover:bg-blue-700 dark:hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-300">
            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Add New Slide
        </button>
    </div>

    <!-- Slides List -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden transition-colors duration-200">
        @if($slides->count() > 0)
            <!-- Mobile Card View (Hidden on Desktop) -->
            <div class="block lg:hidden space-y-4">
                @foreach($slides as $slide)
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 shadow-sm transition-colors duration-200">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center space-x-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                                    Order: {{ $slide->order }}
                                </span>
                                <div class="flex space-x-1">
                                    @if(!$loop->first)
                                        <button wire:click="moveSlideUp({{ $slide->id }})" class="text-gray-400 dark:text-gray-500 hover:text-blue-600 dark:hover:text-blue-400 transition-colors duration-200">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                            </svg>
                                        </button>
                                    @endif
                                    @if(!$loop->last)
                                        <button wire:click="moveSlideDown({{ $slide->id }})" class="text-gray-400 dark:text-gray-500 hover:text-blue-600 dark:hover:text-blue-400 transition-colors duration-200">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <label class="inline-flex items-center">
                                    <input type="checkbox" wire:change="toggleSlideStatus({{ $slide->id }})" 
                                           {{ $slide->is_active ? 'checked' : '' }} 
                                           class="rounded border-gray-300 text-indigo-600 shadow-sm">
                                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Active</span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-1">{{ $slide->title }}</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ Str::limit($slide->subtitle, 50) }}</p>
                        </div>
                        
                        <div class="mb-3">
                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Background:</span>
                            @if($slide->background_image)
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 dark:bg-blue-800 text-blue-800 dark:text-blue-200 ml-1">
                                    Image
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-purple-100 dark:bg-purple-800 text-purple-800 dark:text-purple-200 ml-1">
                                    Gradient
                                </span>
                            @endif
                        </div>
                        
                        <div class="flex justify-end space-x-2">
                            <button wire:click="openEditForm({{ $slide->id }})" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-sm transition-colors duration-200">
                                <i class="fas fa-edit mr-1"></i>Edit
                            </button>
                            <button wire:click="deleteSlide({{ $slide->id }})" 
                                    wire:confirm="Are you sure you want to delete this slide?"
                                    class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 text-sm transition-colors duration-200">
                                <i class="fas fa-trash mr-1"></i>Delete
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Desktop Table View (Hidden on Mobile) -->
            <div class="hidden lg:block overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700 transition-colors duration-200">
                        <tr>
                            <th class="px-4 xl:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Order</th>
                            <th class="px-4 xl:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Title</th>
                            <th class="px-4 xl:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden xl:table-cell">Subtitle</th>
                            <th class="px-4 xl:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Background</th>
                            <th class="px-4 xl:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-4 xl:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700 transition-colors duration-200">
                        @foreach($slides as $slide)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center space-x-2">
                                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $slide->order }}</span>
                                        <div class="flex flex-col space-y-1">
                                            @if(!$loop->first)
                                                <button wire:click="moveSlideUp({{ $slide->id }})" class="text-gray-400 dark:text-gray-500 hover:text-blue-600 dark:hover:text-blue-400 transition-colors duration-200">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                                    </svg>
                                                </button>
                                            @endif
                                            @if(!$loop->last)
                                                <button wire:click="moveSlideDown({{ $slide->id }})" class="text-gray-400 dark:text-gray-500 hover:text-blue-600 dark:hover:text-blue-400 transition-colors duration-200">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $slide->title }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ Str::limit($slide->subtitle, 100) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        @if($slide->background_image)
                                            <img src="{{ asset('storage/' . $slide->background_image) }}" 
                                                 alt="Background preview" 
                                                 class="h-10 w-16 object-cover rounded border mr-2">
                                            <span class="text-xs text-green-600 dark:text-green-400 font-medium">Image</span>
                                        @else
                                            <div class="h-10 w-16 bg-gradient-to-r {{ $slide->background_gradient }} rounded border mr-2"></div>
                                            <span class="text-xs text-blue-600 dark:text-blue-400 font-medium">Gradient</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <button wire:click="toggleSlideStatus({{ $slide->id }})" 
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium transition duration-200
                                                   {{ $slide->is_active ? 'bg-green-100 dark:bg-green-800 text-green-800 dark:text-green-200 hover:bg-green-200 dark:hover:bg-green-700' : 'bg-red-100 dark:bg-red-800 text-red-800 dark:text-red-200 hover:bg-red-200 dark:hover:bg-red-700' }}">
                                        {{ $slide->is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <button wire:click="openEditForm({{ $slide->id }})" 
                                                class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 transition-colors duration-200">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>
                                        <button wire:click="deleteSlide({{ $slide->id }})" 
                                                onclick="return confirm('Are you sure you want to delete this slide?')"
                                                class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 transition-colors duration-200">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No slides found</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by creating your first hero slide.</p>
                <div class="mt-6">
                    <button wire:click="openCreateForm" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 dark:bg-blue-600 hover:bg-blue-700 dark:hover:bg-blue-700 transition-colors duration-200">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Add New Slide
                    </button>
                </div>
            </div>
        @endif
    </div>

    <!-- Form Modal -->
    @if($showForm)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" 
             wire:key="modal-{{ $editingSlide ? $editingSlide->id : 'new' }}">
            <div class="relative top-20 mx-auto p-5 border border-gray-300 dark:border-gray-600 w-11/12 max-w-4xl shadow-lg rounded-md bg-white dark:bg-gray-800"
                 wire:loading.class="opacity-75 pointer-events-none"
                 wire:target="saveSlide,closeForm,removeBackgroundImage">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                        {{ $editingSlide ? 'Edit Hero Slide' : 'Create New Hero Slide' }}
                    </h3>
                    
                    <form wire:submit.prevent="saveSlide" class="space-y-6" wire:ignore.self>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Title -->
                            <div class="md:col-span-2">
                                <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Title</label>
                                <input type="text" wire:model="title" id="title" 
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-colors duration-200"
                                       placeholder="Enter slide title">
                                @error('title') <span class="text-red-500 dark:text-red-400 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- Subtitle -->
                            <div class="md:col-span-2">
                                <label for="subtitle" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Subtitle</label>
                                <textarea wire:model="subtitle" id="subtitle" rows="3"
                                          class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-colors duration-200"
                                          placeholder="Enter slide subtitle"></textarea>
                                @error('subtitle') <span class="text-red-500 dark:text-red-400 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- Primary Button -->
                            <div>
                                <label for="primary_button_text" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Primary Button Text</label>
                                <input type="text" wire:model="primary_button_text" id="primary_button_text" 
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-colors duration-200">
                                @error('primary_button_text') <span class="text-red-500 dark:text-red-400 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label for="primary_button_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Primary Button URL</label>
                                <input type="text" wire:model="primary_button_url" id="primary_button_url" 
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-colors duration-200">
                                @error('primary_button_url') <span class="text-red-500 dark:text-red-400 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- Secondary Button -->
                            <div>
                                <label for="secondary_button_text" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Secondary Button Text (Optional)</label>
                                <input type="text" wire:model="secondary_button_text" id="secondary_button_text" 
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-colors duration-200">
                                @error('secondary_button_text') <span class="text-red-500 dark:text-red-400 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label for="secondary_button_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Secondary Button URL (Optional)</label>
                                <input type="text" wire:model="secondary_button_url" id="secondary_button_url" 
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-colors duration-200">
                                @error('secondary_button_url') <span class="text-red-500 dark:text-red-400 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- Background Image Upload -->
                            <div class="md:col-span-2">
                                <label for="background_image" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Background Image (Optional)</label>
                                <div class="mt-1">
                                    <input type="file" wire:model="background_image" id="background_image" 
                                           accept="image/*"
                                           class="block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 dark:file:bg-blue-900 file:text-blue-700 dark:file:text-blue-300 hover:file:bg-blue-100 dark:hover:file:bg-blue-800 transition-colors duration-200">
                                </div>
                                @error('background_image') <span class="text-red-500 dark:text-red-400 text-sm">{{ $message }}</span> @enderror
                                
                                <!-- Show existing image if editing -->
                                @if($existing_background_image)
                                    <div class="mt-3 flex items-start space-x-4" wire:ignore.self>
                                        <div class="flex-shrink-0">
                                            <img src="{{ asset('storage/' . $existing_background_image) }}" 
                                                 alt="Current background" 
                                                 class="h-20 w-32 object-cover rounded-lg border border-gray-300">
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm text-gray-600 dark:text-gray-400">Current background image</p>
                                            <button type="button" wire:click="removeBackgroundImage" 
                                                    onclick="return confirm('Are you sure you want to remove this background image?')"
                                                    class="mt-1 text-sm text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 transition-colors duration-200">
                                                Remove Image
                                            </button>
                                        </div>
                                    </div>
                                @endif
                                
                                <!-- Show preview of new upload -->
                                @if($background_image)
                                    <div class="mt-3" wire:ignore.self>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Preview of new image:</p>
                                        <img src="{{ $background_image->temporaryUrl() }}" 
                                             alt="Preview" 
                                             class="h-20 w-32 object-cover rounded-lg border border-gray-300">
                                    </div>
                                @endif
                                
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Upload an image to use as background. If no image is uploaded, the gradient background will be used. Max size: 2MB.</p>
                            </div>

                            <!-- Background Gradient -->
                            <div class="md:col-span-2">
                                <label for="background_gradient" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fallback Background Gradient</label>
                                <select wire:model="background_gradient" id="background_gradient" 
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-colors duration-200">
                                    <option value="from-blue-600 via-purple-600 to-indigo-800">Blue to Purple</option>
                                    <option value="from-green-500 via-teal-500 to-blue-600">Green to Blue</option>
                                    <option value="from-purple-600 via-pink-500 to-red-500">Purple to Red</option>
                                    <option value="from-orange-500 via-red-500 to-pink-500">Orange to Pink</option>
                                    <option value="from-gray-700 via-gray-900 to-black">Dark Gray</option>
                                </select>
                                @error('background_gradient') <span class="text-red-500 dark:text-red-400 text-sm">{{ $message }}</span> @enderror
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">This gradient will be used if no background image is uploaded, or as overlay if image has transparency.</p>
                            </div>

                            <!-- Order and Status -->
                            <div>
                                <label for="order" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Order</label>
                                <input type="number" wire:model="order" id="order" min="0"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-colors duration-200">
                                @error('order') <span class="text-red-500 dark:text-red-400 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- Active Status -->
                            <div class="flex items-center">
                                <input type="checkbox" wire:model="is_active" id="is_active" 
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 rounded transition-colors duration-200">
                                <label for="is_active" class="ml-2 block text-sm text-gray-900 dark:text-gray-100">Active</label>
                            </div>

                            <!-- Icon SVG -->
                            <div class="md:col-span-2">
                                <label for="icon_svg" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Icon SVG (Optional)</label>
                                <textarea wire:model="icon_svg" id="icon_svg" rows="3"
                                          class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-colors duration-200"
                                          placeholder="Enter SVG code for icon"></textarea>
                                @error('icon_svg') <span class="text-red-500 dark:text-red-400 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- Element Order Section -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Element Display Order</label>
                                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mb-3">Configure the order of slide elements:</p>
                                    
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label for="icon_position" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Icon Position</label>
                                            <select wire:model="icon_position" id="icon_position" class="block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                                <option value="0">1st (Top)</option>
                                                <option value="1">2nd</option>
                                                <option value="2">3rd</option>
                                                <option value="3">4th</option>
                                                <option value="4">5th (Bottom)</option>
                                            </select>
                                        </div>
                                        
                                        <div>
                                            <label for="title_position" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title Position</label>
                                            <select wire:model="title_position" id="title_position" class="block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                                <option value="0">1st (Top)</option>
                                                <option value="1">2nd</option>
                                                <option value="2">3rd</option>
                                                <option value="3">4th</option>
                                                <option value="4">5th (Bottom)</option>
                                            </select>
                                        </div>
                                        
                                        <div>
                                            <label for="subtitle_position" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Subtitle Position</label>
                                            <select wire:model="subtitle_position" id="subtitle_position" class="block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                                <option value="0">1st (Top)</option>
                                                <option value="1">2nd</option>
                                                <option value="2">3rd</option>
                                                <option value="3">4th</option>
                                                <option value="4">5th (Bottom)</option>
                                            </select>
                                        </div>
                                        
                                        <div>
                                            <label for="primary_button_position" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Primary Button Position</label>
                                            <select wire:model="primary_button_position" id="primary_button_position" class="block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                                <option value="0">1st (Top)</option>
                                                <option value="1">2nd</option>
                                                <option value="2">3rd</option>
                                                <option value="3">4th</option>
                                                <option value="4">5th (Bottom)</option>
                                            </select>
                                        </div>
                                        
                                        <div>
                                            <label for="secondary_button_position" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Secondary Button Position</label>
                                            <select wire:model="secondary_button_position" id="secondary_button_position" class="block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                                <option value="0">1st (Top)</option>
                                                <option value="1">2nd</option>
                                                <option value="2">3rd</option>
                                                <option value="3">4th</option>
                                                <option value="4">5th (Bottom)</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <button type="button" wire:click="resetElementPositions" class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 underline">
                                            Reset to Default Order
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3">
                            <button type="button" wire:click="closeForm" 
                                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                                Cancel
                            </button>
                            <button type="submit" 
                                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 dark:bg-blue-600 hover:bg-blue-700 dark:hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                                {{ $editingSlide ? 'Update Slide' : 'Create Slide' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

</div>