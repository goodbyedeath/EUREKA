<form wire:submit="createQuestionnaire" class="space-y-6">
    <!-- Loading Overlay -->
    <div wire:loading.flex wire:target="createQuestionnaire" 
         class="fixed inset-0 bg-gray-500 bg-opacity-75 z-50 items-center justify-center">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-sm mx-auto">
            <div class="flex items-center space-x-3">
                <svg class="animate-spin h-5 w-5 text-indigo-600 dark:text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-gray-900 dark:text-gray-100">Creating questionnaire...</span>
            </div>
        </div>
    </div>

    <!-- Title Field -->
    <div>
        <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Title <span class="text-red-500">*</span>
        </label>
        <input type="text" 
               wire:model.live.debounce.300ms="title" 
               id="title"
               maxlength="255"
               placeholder="Enter questionnaire title"
               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('title') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror">
        @error('title') 
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> 
        @enderror
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ strlen($title) }}/255 characters</p>
    </div>

    <!-- Description Field -->
    <div>
        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
        <textarea wire:model.live.debounce.300ms="description" 
                  id="description"
                  rows="3"
                  maxlength="1000"
                  placeholder="Enter optional description"
                  class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('description') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"></textarea>
        @error('description') 
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> 
        @enderror
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ strlen($description) }}/1000 characters</p>
    </div>

    <!-- Time Limit and Max Attempts Row -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label for="time_limit" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Time Limit (minutes) <span class="text-red-500">*</span>
            </label>
            <input type="number" 
                   wire:model.live.debounce.500ms="time_limit" 
                   id="time_limit"
                   min="1"
                   max="1440"
                   placeholder="30"
                   class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('time_limit') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror">
            @error('time_limit') 
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> 
            @enderror
        </div>

        <div>
            <label for="max_attempts" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Maximum Attempts
            </label>
            <select wire:model.live="max_attempts" 
                    id="max_attempts"
                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('max_attempts') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror">
                <option value="">Tak terbatas</option>
                @for($i = 1; $i <= 10; $i++)
                    <option value="{{ $i }}">{{ $i }} {{ $i === 1 ? 'attempt' : 'attempts' }}</option>
                @endfor
            </select>
            @error('max_attempts') 
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> 
            @enderror
        </div>
    </div>

    <!-- Date Range Row -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Mulai berlaku
            </label>
            <input type="date" 
                   wire:model.live="start_date" 
                   id="start_date"
                   min="{{ now()->format('Y-m-d') }}"
                   class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('start_date') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror">
            @error('start_date') 
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> 
            @enderror
        </div>

        <div>
            <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Berakhir
            </label>
            <input type="date" 
                   wire:model.live="end_date" 
                   id="end_date"
                   min="{{ $start_date ?: now()->addDay()->format('Y-m-d') }}"
                   class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('end_date') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror">
            @error('end_date') 
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> 
            @enderror
        </div>
    </div>

    <p class="-mt-2 text-xs text-gray-500 dark:text-gray-400">
        Opsional. Kosongkan keduanya agar kuesioner selalu berlaku sampai dinonaktifkan — kalau diisi, kuesioner
        tidak bisa discan di luar tanggal itu.
    </p>

    <!-- Post: the same control the edit form has. Without one, the station gate refuses every scan. -->
    <div class="rounded-md border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-950/30 p-3 space-y-3"
         x-data="{ mode: @entangle('venue_mode') }">
        <div>
            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Pos</p>
            <p class="text-xs text-gray-600 dark:text-gray-400">
                Tim hanya bisa scan kuesioner ini setelah scan QR START dan check-in di pos yang dipilih
                (outdoor: check-in GPS; indoor: pos dibuka kru di Outpost Access). Tanpa pos, kuesioner tidak bisa discan.
            </p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label for="venue_mode" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Venue</label>
                <select id="venue_mode" x-model="mode"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-gray-100">
                    <option value="">— Belum ditentukan —</option>
                    <option value="outdoor">Outdoor (check-in GPS)</option>
                    <option value="indoor">Indoor (dibuka kru)</option>
                </select>
                @error('venue_mode') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>
            <div x-show="mode === 'outdoor'" x-cloak>
                <label for="quest_location_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Pos outdoor (Quest Location)</label>
                <select id="quest_location_id" wire:model="quest_location_id"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-gray-100">
                    <option value="">— Pilih pos —</option>
                    @foreach($questLocations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </select>
                @error('quest_location_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>
            <div x-show="mode === 'indoor'" x-cloak>
                <label for="game_location_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Pos indoor (Game Location)</label>
                <select id="game_location_id" wire:model="game_location_id"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-gray-100">
                    <option value="">— Pilih pos —</option>
                    @foreach($gameLocations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </select>
                @error('game_location_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>

    <!-- Active Status -->
    <div class="flex items-center">
        <input type="checkbox" 
               wire:model.live="is_active" 
               id="is_active"
               class="h-4 w-4 text-indigo-600 dark:text-indigo-400 focus:ring-indigo-500 border-gray-300 dark:border-gray-600 rounded">
        <label for="is_active" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
            Make questionnaire active immediately
        </label>
    </div>

    <div>
        <div class="flex items-center">
            <input type="checkbox" wire:model="counts_toward_finish" id="counts_toward_finish"
                   class="h-4 w-4 text-indigo-600 dark:text-indigo-400 focus:ring-indigo-500 border-gray-300 dark:border-gray-600 rounded">
            <label for="counts_toward_finish" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">Counts toward finishing</label>
        </div>
        <p class="ml-6 text-xs text-gray-500 dark:text-gray-400">
            Biarkan menyala untuk pos biasa. Matikan untuk pos bonus: tetap memberi poin, tapi jam race tim tidak menunggunya.
        </p>
    </div>

    <!-- Action Buttons -->
    <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
        <button type="button" 
                wire:click="$dispatch('close-create-modal')"
                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            Cancel
        </button>
        
        <button type="submit" 
                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
                wire:loading.attr="disabled"
                wire:target="createQuestionnaire">
            <span wire:loading.remove wire:target="createQuestionnaire">Create Questionnaire</span>
            <span wire:loading wire:target="createQuestionnaire" class="flex items-center">
                <svg class="animate-spin h-4 w-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Creating...
            </span>
        </button>
    </div>
</form>