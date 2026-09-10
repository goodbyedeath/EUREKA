<div class="container mx-auto px-2 sm:px-4 py-4 sm:py-6 min-h-screen">

    @unless($showModal)
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
                    
                    @include('partials.ar-controls', ['game' => $game])
                    
                    
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
                            3D / AR
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
                                @include('partials.ar-controls', ['game' => $game])
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
                            <td colspan="4" class="px-4 lg:px-6 py-12 text-center text-gray-500 dark:text-gray-400">
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

    @endunless

    <!-- Add/Edit Modal -->
    @if($showModal)
    {{-- A page, not a modal. The card wrapper matters: unwrapping the overlay also
         removes the panel, and the form would otherwise sit on the bare background. --}}
    <div class="mb-4">
        <button type="button" wire:click="closeModal"
                class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100">
            &larr; Back to outposts
        </button>
    </div>
    <div class="max-w-3xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-5 sm:p-6">
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

                        {{-- Mode first, then the post — the mode decides whether the post's
                             coordinates matter at all, so asking for it second reads backwards.

                             `x-model` mirrors the choice locally so the panel answers instantly;
                             `wire:model` stays deferred and carries it on Save. No request is
                             made while an admin is simply choosing between two options. --}}
                        <div class="sm:col-span-2 space-y-4" x-data="{ mode: @js($access_mode ?: 'geofence') }">

                            <div>
                                <label for="access_mode" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    3D Camera opens by
                                </label>
                                <select wire:model="access_mode" x-model="mode" id="access_mode"
                                        class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-gray-100">
                                    <option value="geofence">Outdoor — automatically, inside the post's radius</option>
                                    <option value="manual">Indoor — opened by crew, per team</option>
                                </select>

                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-show="mode === 'geofence'" x-cloak>
                                    The team's phone must report a position inside the post's radius before the
                                    3D camera will open.
                                </p>
                                <p class="mt-2 text-xs px-3 py-2 rounded-md bg-teal-50 dark:bg-teal-900/20 border border-teal-200 dark:border-teal-800 text-teal-900 dark:text-teal-200"
                                   x-show="mode === 'manual'" x-cloak>
                                    <strong>No GPS anywhere in this mode</strong> — not for the team, and not for you
                                    while placing objects. Satellites do not reach through a roof, which is the whole
                                    reason a person does the gating. Open this outpost per team from
                                    <span class="font-medium">Outpost Access</span> during the event.
                                </p>
                            </div>

                            <div>
                                <label for="quest_location_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Quest Location (post)
                                    <span class="font-normal text-gray-400" x-show="mode === 'manual'" x-cloak>— optional indoors</span>
                                </label>
                                <select wire:model="quest_location_id" id="quest_location_id"
                                        class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-gray-100">
                                    <option value="">— not linked to a post —</option>
                                    @foreach($questLocations as $ql)
                                        <option value="{{ $ql->id }}">{{ $ql->name }} ({{ $ql->radius }} m)</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-show="mode === 'geofence'" x-cloak>
                                    The post's coordinates decide where this AR scene will open. Leave unlinked to
                                    pin the spot from the AR view instead.
                                </p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-show="mode === 'manual'" x-cloak>
                                    Only used for reporting — the coordinates are ignored while the mode is Indoor.
                                </p>
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
                                    <option value="all_users">All Teams</option>
                                    <option value="specific_user">Specific Team</option>
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
    @endif
</div>


<!-- Simple Admin Hotspot Styling -->
<style>
    
    
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
    
</style>