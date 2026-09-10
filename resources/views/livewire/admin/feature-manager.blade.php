<div class="container mx-auto px-4 py-6">

    @unless($showEditModal)
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-2">Feature Management</h1>
        <p class="text-gray-600 dark:text-gray-400">Control which features are available to teams</p>
    </div>

    <!-- Bulk Actions -->
    <div class="mb-6 flex flex-wrap gap-4">
        <button wire:click="bulkToggle('enable_all')" 
                class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Enable All
        </button>
        
        <button wire:click="bulkToggle('disable_all')" 
                class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
            Disable All
        </button>
        
        <button wire:click="refreshFeatures" 
                class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            Refresh
        </button>
    </div>

    <!-- Features Grid -->
    <div class="grid gap-6">
        @forelse($features as $feature)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="p-6">
                <div class="flex items-start justify-between">
                    <!-- Feature Info -->
                    <div class="flex-1">
                        <div class="flex items-center space-x-3 mb-2">
                            <!-- Feature Icon -->
                            @if($feature->icon)
                            <div class="w-10 h-10 rounded-lg {{ $this->getColorClasses($feature->color) }} flex items-center justify-center">
                                <i class="{{ $feature->icon }} text-lg"></i>
                            </div>
                            @endif
                            
                            <!-- Feature Name & Status -->
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    {{ $feature->feature_name }}
                                </h3>
                                <div class="flex items-center space-x-2">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full {{ $feature->is_enabled ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400' }}">
                                        {{ $feature->is_enabled ? 'Enabled' : 'Disabled' }}
                                    </span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        Order: {{ $feature->sort_order }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Description -->
                        @if($feature->description)
                        <p class="text-gray-600 dark:text-gray-400 mb-3">{{ $feature->description }}</p>
                        @endif
                        
                        <!-- Route Info -->
                        @if($feature->route)
                        <div class="flex items-center text-sm text-gray-500 dark:text-gray-400 mb-3">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Route: {{ $feature->route }}
                        </div>
                        @endif
                    </div>
                    
                    <!-- Actions -->
                    <div class="flex items-center space-x-2 ml-4">
                        <!-- Reorder buttons -->
                        <div class="flex flex-col space-y-1">
                            <button wire:click="moveUp({{ $feature->id }})" 
                                    class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                    title="Move Up">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                </svg>
                            </button>
                            <button wire:click="moveDown({{ $feature->id }})" 
                                    class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                    title="Move Down">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                        </div>
                        
                        <!-- Edit button -->
                        <button wire:click="editFeature({{ $feature->id }})" 
                                class="p-2 text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                                title="Edit Feature">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </button>
                        
                        <!-- Toggle button -->
                        <button wire:click="toggleFeature({{ $feature->id }})" 
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 {{ $feature->is_enabled ? 'bg-green-600' : 'bg-gray-200 dark:bg-gray-700' }}"
                                title="{{ $feature->is_enabled ? 'Disable' : 'Enable' }} Feature">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $feature->is_enabled ? 'translate-x-6' : 'translate-x-1' }}"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="text-center py-12 bg-white dark:bg-gray-800 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600">
            <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">No Features Found</h3>
            <p class="text-gray-500 dark:text-gray-400">Run the feature seeder to populate default features.</p>
        </div>
        @endforelse
    </div>

    @endunless

    <!-- Edit Modal -->
    @if($showEditModal && $editingFeature)
    {{-- A page, not a modal. The card wrapper matters: unwrapping the overlay also
         removes the panel, and the form would otherwise sit on the bare background. --}}
    <div class="mb-4">
        <button type="button" wire:click="closeEditModal"
                class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100">
            &larr; Back to features
        </button>
    </div>
    <div class="max-w-3xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-5 sm:p-6">
                <form wire:submit.prevent="updateFeature">
                    <div class="mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Edit Feature</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Modify feature settings</p>
                    </div>

                    <!-- Feature Name -->
                    <div class="mb-4">
                        <label for="feature_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Feature Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="feature_name" 
                               wire:model="feature_name"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-100"
                               placeholder="Enter feature name">
                        @error('feature_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Description -->
                    <div class="mb-4">
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Description
                        </label>
                        <textarea id="description" 
                                  wire:model="description"
                                  rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-100"
                                  placeholder="Enter feature description"></textarea>
                        @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Sort Order -->
                    <div class="mb-6">
                        <label for="sort_order" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Sort Order <span class="text-red-500">*</span>
                        </label>
                        <input type="number" 
                               id="sort_order" 
                               wire:model="sort_order"
                               min="0"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-100"
                               placeholder="0">
                        @error('sort_order') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end space-x-3">
                        <button type="button" 
                                wire:click="closeEditModal"
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-600 rounded-md hover:bg-gray-200 dark:hover:bg-gray-500 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" 
                                wire:loading.attr="disabled"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 transition-colors disabled:opacity-50">
                            <span wire:loading.remove>Update Feature</span>
                            <span wire:loading>Updating...</span>
                        </button>
                    </div>
                </form>
    </div>
    @endif
</div>