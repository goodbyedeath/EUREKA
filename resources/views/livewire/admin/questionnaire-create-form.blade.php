<form wire:submit="createQuestionnaire" class="space-y-6">
    <!-- Loading Overlay -->
    <div wire:loading.flex wire:target="createQuestionnaire" 
         class="fixed inset-0 bg-gray-500 bg-opacity-75 z-50 items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-sm mx-auto">
            <div class="flex items-center space-x-3">
                <svg class="animate-spin h-5 w-5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-gray-900">Creating questionnaire...</span>
            </div>
        </div>
    </div>

    <!-- Title Field -->
    <div>
        <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
            Title <span class="text-red-500">*</span>
        </label>
        <input type="text" 
               wire:model.live.debounce.300ms="title" 
               id="title"
               maxlength="255"
               placeholder="Enter questionnaire title"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('title') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror">
        @error('title') 
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
        @enderror
        <p class="mt-1 text-xs text-gray-500">{{ strlen($title) }}/255 characters</p>
    </div>

    <!-- Description Field -->
    <div>
        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
        <textarea wire:model.live.debounce.300ms="description" 
                  id="description"
                  rows="3"
                  maxlength="1000"
                  placeholder="Enter optional description"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('description') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"></textarea>
        @error('description') 
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
        @enderror
        <p class="mt-1 text-xs text-gray-500">{{ strlen($description) }}/1000 characters</p>
    </div>

    <!-- Photo Upload Field -->
    <div>
        <label for="photo" class="block text-sm font-medium text-gray-700 mb-1">Photo</label>
        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:border-indigo-400 transition-colors">
            <div class="space-y-1 text-center">
                @if($photo)
                    <div class="mb-4">
                        @php
                            $showPreview = true;
                            try {
                                $tempUrl = $photo->temporaryUrl();
                            } catch (\Exception $e) {
                                $showPreview = false;
                            }
                        @endphp
                        
                        @if($showPreview)
                            <img src="{{ $tempUrl }}" class="mx-auto h-32 w-32 object-cover rounded-lg">
                            <p class="mt-2 text-sm text-gray-600">{{ $photo->getClientOriginalName() }}</p>
                        @else
                            <div class="text-center">
                                <i class="fas fa-image text-gray-400 text-2xl"></i>
                                <p class="mt-2 text-sm text-gray-600">Photo selected: {{ $photo->getClientOriginalName() }}</p>
                            </div>
                        @endif
                        
                        <button type="button" wire:click="$set('photo', null)" class="mt-2 text-sm text-red-600 hover:text-red-800">
                            Remove Photo
                        </button>
                    </div>
                @else
                    <i class="fas fa-cloud-upload-alt text-gray-400 text-3xl"></i>
                    <div class="text-sm text-gray-600">
                        <label for="photo" class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                            <span>Upload a photo</span>
                            <input wire:model="photo" id="photo" name="photo" type="file" class="sr-only" accept="image/*">
                        </label>
                        <p class="pl-1">or drag and drop</p>
                    </div>
                    <p class="text-xs text-gray-500">PNG, JPG up to 2MB</p>
                @endif
            </div>
        </div>
        @error('photo') 
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p> 
        @enderror
        <p class="mt-1 text-xs text-gray-500">Optional: Add a photo that will be displayed when users view this questionnaire</p>
    </div>

    <!-- Time Limit and Max Attempts Row -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label for="time_limit" class="block text-sm font-medium text-gray-700 mb-1">
                Time Limit (minutes) <span class="text-red-500">*</span>
            </label>
            <input type="number" 
                   wire:model.live="time_limit" 
                   id="time_limit"
                   min="1"
                   max="300"
                   placeholder="30"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('time_limit') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror">
            @error('time_limit') 
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
            @enderror
        </div>

        <div>
            <label for="max_attempts" class="block text-sm font-medium text-gray-700 mb-1">
                Maximum Attempts <span class="text-red-500">*</span>
            </label>
            <select wire:model.live="max_attempts" 
                    id="max_attempts"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('max_attempts') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror">
                @for($i = 1; $i <= 10; $i++)
                    <option value="{{ $i }}">{{ $i }} {{ $i === 1 ? 'attempt' : 'attempts' }}</option>
                @endfor
            </select>
            @error('max_attempts') 
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
            @enderror
        </div>
    </div>

    <!-- Date Range Row -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">
                Start Date <span class="text-red-500">*</span>
            </label>
            <input type="date" 
                   wire:model.live="start_date" 
                   id="start_date"
                   min="{{ now()->format('Y-m-d') }}"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('start_date') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror">
            @error('start_date') 
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
            @enderror
        </div>

        <div>
            <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">
                End Date <span class="text-red-500">*</span>
            </label>
            <input type="date" 
                   wire:model.live="end_date" 
                   id="end_date"
                   min="{{ $start_date ?: now()->addDay()->format('Y-m-d') }}"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('end_date') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror">
            @error('end_date') 
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
            @enderror
        </div>
    </div>

    <!-- Active Status -->
    <div class="flex items-center">
        <input type="checkbox" 
               wire:model.live="is_active" 
               id="is_active"
               class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
        <label for="is_active" class="ml-2 block text-sm text-gray-700">
            Make questionnaire active immediately
        </label>
    </div>

    <!-- Action Buttons -->
    <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
        <button type="button" 
                wire:click="$dispatch('close-create-modal')"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
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