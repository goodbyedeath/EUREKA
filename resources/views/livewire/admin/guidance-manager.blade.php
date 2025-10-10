<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:justify-between lg:items-center mb-6 gap-4">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Guidance Management</h1>
        
        <!-- Search and Filter -->
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="flex gap-2">
                <div class="relative">
                    <input type="text" wire:model.live="search" placeholder="Search guidance..." 
                           class="pl-8 pr-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm transition-colors duration-200">
                    <svg class="w-4 h-4 absolute left-2.5 top-2.5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                
                <select wire:model.live="filterStatus" class="border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm transition-colors duration-200">
                    <option value="all">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                
                <select wire:model.live="filterTarget" class="border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm transition-colors duration-200">
                    <option value="all">All Targets</option>
                    <option value="all_users">For All Users</option>
                    <option value="specific_user">For Specific User</option>
                </select>
            </div>
            
            <button wire:click="create" class="bg-blue-500 dark:bg-blue-600 hover:bg-blue-600 dark:hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow whitespace-nowrap transition-colors duration-200">
                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Add Guidance
            </button>
        </div>
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

    <!-- Error Message -->
    @if (session()->has('error'))
        <div class="bg-red-100 dark:bg-red-800 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-200 px-4 py-3 rounded-lg mb-4 transition-colors duration-200">
            <div class="flex">
                <svg class="w-4 h-4 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                {{ session('error') }}
            </div>
        </div>
    @endif

    <!-- Bulk Actions -->
    @if(!empty($selectedGuidance))
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-4 mb-4 transition-colors duration-200">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
            <span class="text-sm text-blue-800 dark:text-blue-200">{{ count($selectedGuidance) }} guidance(s) selected</span>
            <div class="flex gap-2">
                <button wire:click="bulkActivate" class="bg-green-500 dark:bg-green-600 hover:bg-green-600 dark:hover:bg-green-700 text-white px-3 py-1 rounded text-sm transition-colors duration-200">
                    Activate
                </button>
                <button wire:click="bulkDeactivate" class="bg-orange-500 dark:bg-orange-600 hover:bg-orange-600 dark:hover:bg-orange-700 text-white px-3 py-1 rounded text-sm transition-colors duration-200">
                    Deactivate
                </button>
                <button wire:click="bulkDelete" wire:confirm="Are you sure you want to delete selected guidance?" class="bg-red-500 dark:bg-red-600 hover:bg-red-600 dark:hover:bg-red-700 text-white px-3 py-1 rounded text-sm transition-colors duration-200">
                    Delete
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- Guidance Table -->
    <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden transition-colors duration-200">
        <!-- Mobile Card View (Hidden on Desktop) -->
        <div class="block lg:hidden">
            @forelse($guidances as $guidance)
                <div class="border-b border-gray-200 dark:border-gray-700 p-4 transition-colors duration-200">
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0">
                            <input type="checkbox" wire:model="selectedGuidance" value="{{ $guidance->id }}" class="rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-blue-600 mt-1 transition-colors duration-200">
                        </div>
                        <div class="flex-shrink-0">
                            @if($guidance->first_image)
                                <img src="{{ Storage::url($guidance->first_image) }}" alt="{{ $guidance->title }}" 
                                     class="w-16 h-16 rounded-lg object-cover">
                            @else
                                <div class="w-16 h-16 bg-gray-200 dark:bg-gray-600 rounded-lg flex items-center justify-center">
                                    <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between mb-2">
                                <div>
                                    <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ $guidance->title }}</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ Str::limit($guidance->description, 50) }}</p>
                                </div>
                                @if($guidance->is_active)
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 dark:bg-green-800 text-green-800 dark:text-green-200">
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 dark:bg-red-800 text-red-800 dark:text-red-200">
                                        Inactive
                                    </span>
                                @endif
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-400 mb-3">
                                <div>
                                    <span class="font-medium">Images:</span> {{ $guidance->image_count }}
                                </div>
                                <div>
                                    <span class="font-medium">Order:</span> {{ $guidance->sort_order }}
                                </div>
                                <div class="col-span-2">
                                    <span class="font-medium">Target:</span> 
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium 
                                        {{ $guidance->target_type === 'all_users' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                                        {{ $guidance->target_display }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex justify-end space-x-2">
                                <button wire:click="edit({{ $guidance->id }})" 
                                        class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-sm transition-colors duration-200">
                                    <i class="fas fa-edit mr-1"></i>Edit
                                </button>
                                <button wire:click="delete({{ $guidance->id }})" 
                                        wire:confirm="Are you sure you want to delete this guidance?"
                                        class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 text-sm transition-colors duration-200">
                                    <i class="fas fa-trash mr-1"></i>Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-6 text-center text-gray-500 dark:text-gray-400">
                    <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <p class="text-lg font-medium">No guidance found</p>
                    <p class="text-sm">Create your first guidance to get started</p>
                </div>
            @endforelse
        </div>

        <!-- Desktop Table View (Hidden on Mobile) -->
        <div class="hidden lg:block overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 xl:px-6 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <input type="checkbox" wire:model="selectAll" class="rounded border-gray-300 dark:border-gray-600">
                        </th>
                        <th class="px-4 xl:px-6 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Guidance</th>
                        <th class="px-4 xl:px-6 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Images</th>
                        <th class="px-4 xl:px-6 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Target</th>
                        <th class="px-4 xl:px-6 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Order</th>
                        <th class="px-4 xl:px-6 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-4 xl:px-6 py-3 text-center text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200">
                    @forelse($guidances as $guidance)
                    <tr class="hover:bg-gray-50 dark:bg-gray-700">
                        <td class="px-4 xl:px-6 py-4">
                            <input type="checkbox" wire:model="selectedGuidance" value="{{ $guidance->id }}" class="rounded border-gray-300 dark:border-gray-600">
                        </td>
                        <td class="px-4 xl:px-6 py-4">
                            <div class="flex items-center">
                                @if($guidance->first_image)
                                    <img src="{{ Storage::url($guidance->first_image) }}" alt="{{ $guidance->title }}" 
                                         class="w-10 h-10 xl:w-12 xl:h-12 rounded-lg object-cover mr-3 xl:mr-4">
                                @else
                                    <div class="w-10 h-10 xl:w-12 xl:h-12 bg-gray-200 rounded-lg flex items-center justify-center mr-3 xl:mr-4">
                                        <svg class="w-5 h-5 xl:w-6 xl:h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                @endif
                                <div class="min-w-0">
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ $guidance->title }}</div>
                                    <div class="text-xs xl:text-sm text-gray-500 dark:text-gray-400 truncate">{{ Str::limit($guidance->description, 50) }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 xl:px-6 py-4 text-xs xl:text-sm text-gray-900 dark:text-gray-100">
                            {{ $guidance->image_count }} image(s)
                        </td>
                        <td class="px-4 xl:px-6 py-4 text-xs xl:text-sm text-gray-900 dark:text-gray-100">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium 
                                {{ $guidance->target_type === 'all_users' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                                {{ $guidance->target_display }}
                            </span>
                        </td>
                        <td class="px-4 xl:px-6 py-4 text-xs xl:text-sm text-gray-900 dark:text-gray-100">
                            {{ $guidance->sort_order }}
                        </td>
                        <td class="px-4 xl:px-6 py-4">
                            @if($guidance->is_active)
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Active
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    Inactive
                                </span>
                            @endif
                        </td>
                        <td class="px-4 xl:px-6 py-4 text-center">
                            <div class="flex justify-center space-x-1 xl:space-x-2">
                                <button wire:click="edit({{ $guidance->id }})" 
                                        class="text-blue-600 hover:text-blue-800 transition-colors p-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                <button wire:click="delete({{ $guidance->id }})" 
                                        wire:confirm="Are you sure you want to delete this guidance?"
                                        class="text-red-600 hover:text-red-800 transition-colors p-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 xl:px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <p class="text-lg font-medium">No guidance found</p>
                            <p class="text-sm">Create your first guidance to get started</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $guidances->links() }}
    </div>

    <!-- Modal -->
    @if($showModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeModal"></div>
            
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                <form wire:submit.prevent="save">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">
                                {{ $editMode ? 'Edit Guidance' : 'Create New Guidance' }}
                            </h3>
                            <button type="button" wire:click="closeModal" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <div class="grid grid-cols-1 gap-6">
                            <!-- Title -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title</label>
                                <input type="text" wire:model="title" class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                @error('title') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <!-- Description -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description (Optional)</label>
                                <textarea wire:model="description" rows="3" class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                                @error('description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <!-- Images -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Images</label>
                                
                                <!-- Existing Images -->
                                @if(!empty($images))
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                                        @foreach($images as $index => $imagePath)
                                            <div class="relative">
                                                <img src="{{ Storage::url($imagePath) }}" alt="Image {{ $index + 1 }}" class="w-full h-24 object-cover rounded-lg">
                                                <button type="button" wire:click="removeImage('{{ $imagePath }}')" class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-red-600">
                                                    ×
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                <!-- New Image Upload -->
                                <input type="file" wire:model="newImages" multiple accept="image/*" class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <p class="text-xs text-gray-500 mt-1">You can select multiple images. Max 2MB per image.</p>
                                
                                <!-- Upload progress -->
                                <div wire:loading wire:target="newImages" class="mt-2">
                                    <div class="flex items-center text-sm text-blue-600">
                                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Uploading images...
                                    </div>
                                </div>
                                
                                @error('newImages.*') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <!-- Target User -->
                            <div class="space-y-3">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Target Audience</label>
                                
                                <div class="space-y-2">
                                    <div class="flex items-center">
                                        <input type="radio" wire:model="target_type" value="all_users" id="target_all" 
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600">
                                        <label for="target_all" class="ml-2 block text-sm text-gray-900 dark:text-gray-100">All Users</label>
                                    </div>
                                    
                                    <div class="flex items-center">
                                        <input type="radio" wire:model="target_type" value="specific_user" id="target_specific" 
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600">
                                        <label for="target_specific" class="ml-2 block text-sm text-gray-900 dark:text-gray-100">Specific User</label>
                                    </div>
                                </div>
                                
                                @if($target_type === 'specific_user')
                                    <div class="mt-2">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Select User</label>
                                        <select wire:model="target_user_id" class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                            <option value="">Choose a user...</option>
                                            @foreach($users as $user)
                                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                            @endforeach
                                        </select>
                                        @error('target_user_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                @endif
                            </div>

                            <!-- Sort Order and Active Status -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sort Order</label>
                                    <input type="number" wire:model="sort_order" min="0" class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    @error('sort_order') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <div class="flex items-center">
                                    <input type="checkbox" wire:model="is_active" id="is_active" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="is_active" class="ml-2 block text-sm text-gray-900 dark:text-gray-100">Active</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                            {{ $editMode ? 'Update' : 'Create' }}
                        </button>
                        <button type="button" wire:click="closeModal" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>