{{-- resources/views/livewire/team-members/create-form.blade.php --}}
<form wire:submit.prevent="save" class="space-y-4">
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700">Name *</label>
        <input type="text" wire:model="name" id="name" 
               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
        @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
    </div>

    <div>
        <label for="email" class="block text-sm font-medium text-gray-700">Email *</label>
        <input type="email" wire:model="email" id="email" 
               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
        @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
    </div>

    <div>
        <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
        <input type="text" wire:model="phone" id="phone" 
               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
        @error('phone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
    </div>

    <div>
        <label for="position" class="block text-sm font-medium text-gray-700">Position *</label>
        <input type="text" wire:model="position" id="position" 
               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
        @error('position') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
    </div>

    <div class="flex items-center">
        <input type="checkbox" wire:model="is_leader" id="is_leader" 
               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
        <label for="is_leader" class="ml-2 block text-sm text-gray-900">Team Leader</label>
    </div>

    <div class="flex justify-end space-x-3 pt-4">
        <button type="button" wire:click="$dispatch('closeModal')" 
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
            Cancel
        </button>
        <button type="submit" 
                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
            Add Member
        </button>
    </div>
</form>