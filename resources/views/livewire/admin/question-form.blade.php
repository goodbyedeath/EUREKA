<div class="bg-white border rounded-lg p-6 shadow-sm">
    <h3 class="text-lg font-medium mb-4 text-gray-900">
        @if($isEditing)
            Edit Question
        @else
            Add New Question
        @endif
    </h3>
    
    <!-- Success/Error Messages -->
    @if (session()->has('questions_message'))
        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-md">
            <p class="text-sm text-green-800">{{ session('questions_message') }}</p>
        </div>
    @endif

    @if (session()->has('questions_error'))
        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-md">
            <p class="text-sm text-red-800">{{ session('questions_error') }}</p>
        </div>
    @endif
    
    <form wire:submit="addQuestion" class="space-y-6">
        <!-- Question Text -->
        <div>
            <label for="question-text" class="block text-sm font-medium text-gray-700 mb-1">
                Question <span class="text-red-500">*</span>
            </label>
            <textarea 
                id="question-text"
                wire:model="newQuestion.question" 
                rows="3"
                placeholder="Enter your question here..."
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors duration-200"
            ></textarea>
            @error('newQuestion.question') 
                <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> 
            @enderror
        </div>

        <!-- Question Type and Points -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="question-type" class="block text-sm font-medium text-gray-700 mb-1">
                    Question Type <span class="text-red-500">*</span>
                </label>
                <select 
                    id="question-type"
                    wire:model.live="newQuestion.type" 
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors duration-200"
                >
                    <option value="text">Text Answer</option>
                    <option value="multiple_choice">Multiple Choice</option>
                    <option value="true_false">True/False</option>
                    <option value="fun_game">Fun Game</option>
                </select>
                @error('newQuestion.type') 
                    <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> 
                @enderror
            </div>

            <div>
                <label for="question-points" class="block text-sm font-medium text-gray-700 mb-1">
                    Points <span class="text-red-500">*</span>
                </label>
                <input 
                    type="number" 
                    id="question-points"
                    wire:model="newQuestion.points" 
                    min="1"
                    max="100"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors duration-200"
                >
                @error('newQuestion.points') 
                    <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> 
                @enderror
            </div>
        </div>

        <!-- Multiple Choice Options -->
        @if($newQuestion['type'] === 'multiple_choice')
            <div class="bg-gray-50 p-4 rounded-lg border">
                <div class="flex items-center justify-between mb-3">
                    <label class="block text-sm font-medium text-gray-700">
                        Answer Options <span class="text-red-500">*</span>
                        <span class="text-xs text-gray-500 block mt-1">Minimum 2 options, maximum 8 options</span>
                    </label>
                    @if(count($newQuestion['options']) < 8)
                        <button 
                            type="button" 
                            wire:click="addOption"
                            class="inline-flex items-center px-3 py-1 text-xs font-medium text-indigo-600 bg-indigo-50 border border-indigo-200 rounded-md hover:bg-indigo-100 transition-colors duration-200"
                        >
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Add Option
                        </button>
                    @endif
                </div>
                
                <div class="space-y-3">
                    @foreach($newQuestion['options'] as $index => $option)
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0 w-8 h-8 bg-white border border-gray-300 rounded-full flex items-center justify-center text-sm font-medium text-gray-600">
                                {{ chr(65 + $index) }}
                            </div>
                            <input 
                                type="text" 
                                wire:model.blur="newQuestion.options.{{ $index }}" 
                                placeholder="Enter option {{ chr(65 + $index) }}"
                                maxlength="255"
                                class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors duration-200 @if($newQuestion['correct_answer'] === $option && !empty(trim($option))) border-green-300 bg-green-50 @endif"
                            >
                            @if($newQuestion['correct_answer'] === $option && !empty(trim($option)))
                                <div class="flex-shrink-0 text-green-600" title="Correct Answer">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            @endif
                            @if(count($newQuestion['options']) > 2)
                                <button 
                                    type="button" 
                                    wire:click="removeOption({{ $index }})"
                                    class="flex-shrink-0 text-red-500 hover:text-red-700 transition-colors duration-200 p-1"
                                    title="Remove option"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            @endif
                        </div>
                    @endforeach
                </div>
                
                @error('newQuestion.options') 
                    <div class="text-red-500 text-sm mt-2 flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        {{ $message }}
                    </div>
                @enderror
                
                @php
                    $nonEmptyOptions = array_filter($newQuestion['options'], fn($opt) => !empty(trim($opt)));
                    $duplicates = array_diff_assoc($nonEmptyOptions, array_unique($nonEmptyOptions));
                @endphp
                
                @if(!empty($duplicates))
                    <div class="text-orange-600 text-sm mt-2 flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        Warning: Duplicate options detected. Each option should be unique.
                    </div>
                @endif
            </div>
        @endif

        <!-- Fun Game Fields -->
        @if($newQuestion['type'] === 'fun_game')
            <div class="bg-purple-50 p-4 rounded-lg border border-purple-200">
                <h4 class="text-sm font-medium text-purple-900 mb-4 flex items-center">
                    <i class="fas fa-gamepad mr-2"></i>
                    Fun Game Configuration
                </h4>
                
                <!-- Game Name -->
                <div class="mb-4">
                    <label for="game-name" class="block text-sm font-medium text-gray-700 mb-1">
                        Game Name <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="game-name"
                        wire:model="newQuestion.game_name" 
                        placeholder="Enter the name of the fun game..."
                        maxlength="200"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors duration-200"
                    >
                    @error('newQuestion.game_name') 
                        <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> 
                    @enderror
                </div>

                <!-- Game Description -->
                <div class="mb-4">
                    <label for="game-description" class="block text-sm font-medium text-gray-700 mb-1">
                        Game Instructions/Description <span class="text-red-500">*</span>
                    </label>
                    <textarea 
                        id="game-description"
                        wire:model="newQuestion.description" 
                        rows="4"
                        placeholder="Enter detailed instructions for the game..."
                        maxlength="2000"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors duration-200"
                    ></textarea>
                    @error('newQuestion.description') 
                        <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> 
                    @enderror
                </div>

                <!-- Game Images -->
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <label class="block text-sm font-medium text-gray-700">
                            Game Images
                            <span class="text-xs text-gray-500 block mt-1">Optional - Add up to 10 image URLs</span>
                        </label>
                        @if(count($newQuestion['images']) < 10)
                            <button 
                                type="button" 
                                wire:click="addImage"
                                class="inline-flex items-center px-3 py-1 text-xs font-medium text-purple-600 bg-purple-50 border border-purple-200 rounded-md hover:bg-purple-100 transition-colors duration-200"
                            >
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Add Image
                            </button>
                        @endif
                    </div>
                    
                    @if(count($newQuestion['images']) > 0)
                        <div class="space-y-3">
                            @foreach($newQuestion['images'] as $index => $image)
                                <div class="flex items-center space-x-3">
                                    <div class="flex-shrink-0 w-8 h-8 bg-purple-100 border border-purple-300 rounded-full flex items-center justify-center text-sm font-medium text-purple-600">
                                        {{ $index + 1 }}
                                    </div>
                                    <input 
                                        type="url" 
                                        wire:model="newQuestion.images.{{ $index }}" 
                                        placeholder="Enter image URL (e.g., https://example.com/image.jpg)"
                                        class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors duration-200"
                                    >
                                    <button 
                                        type="button" 
                                        wire:click="removeImage({{ $index }})"
                                        class="flex-shrink-0 text-red-500 hover:text-red-700 transition-colors duration-200 p-1"
                                        title="Remove image"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500 italic">No images added yet. Click "Add Image" to include visual content for your game.</p>
                    @endif
                    
                    @error('newQuestion.images') 
                        <div class="text-red-500 text-sm mt-2 flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-md">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-blue-400 mt-0.5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                        <div class="text-sm text-blue-800">
                            <strong>Note:</strong> Fun games don't require correct answers. Users complete the activity and are assessed manually by administrators using deposit/penalty scoring.
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Correct Answer -->
        @if($newQuestion['type'] !== 'fun_game')
            <div>
                <label for="correct-answer" class="block text-sm font-medium text-gray-700 mb-1">
                    Correct Answer <span class="text-red-500">*</span>
                </label>
            
            @if($newQuestion['type'] === 'true_false')
                <select 
                    id="correct-answer"
                    wire:model="newQuestion.correct_answer" 
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors duration-200"
                >
                    <option value="">Select the correct answer...</option>
                    <option value="true">True</option>
                    <option value="false">False</option>
                </select>
            @elseif($newQuestion['type'] === 'multiple_choice')
                <select 
                    id="correct-answer"
                    wire:model="newQuestion.correct_answer" 
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors duration-200"
                >
                    <option value="">Select the correct option...</option>
                    @foreach($newQuestion['options'] as $option)
                        @if(!empty(trim($option)))
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endif
                    @endforeach
                </select>
                @php
                    $nonEmptyOptions = array_filter($newQuestion['options'], fn($opt) => !empty(trim($opt)));
                @endphp
                @if(count($nonEmptyOptions) < 2)
                    <p class="text-sm text-gray-500 mt-1">Please add at least two non-empty options first.</p>
                @endif
            @else
                <input 
                    type="text" 
                    id="correct-answer"
                    wire:model="newQuestion.correct_answer" 
                    placeholder="Enter the correct answer..."
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors duration-200"
                >
            @endif
            
                @error('newQuestion.correct_answer') 
                    <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> 
                @enderror
            </div>
        @endif

        <!-- Form Actions -->
        <div class="flex justify-between items-center pt-4 border-t border-gray-200">
            <div class="flex space-x-3">
                @if($isEditing)
                    <button 
                        type="button" 
                        wire:click="cancelEdit"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200"
                    >
                        Cancel Edit
                    </button>
                @endif
                <button 
                    type="button" 
                    wire:click="resetNewQuestion"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200"
                >
                    Reset Form
                </button>
            </div>
            
            <button 
                type="submit" 
                class="px-6 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove>
                    @if($isEditing)
                        Update Question
                    @else
                        Add Question
                    @endif
                </span>
                <span wire:loading class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    @if($isEditing)
                        Updating...
                    @else
                        Adding...
                    @endif
                </span>
            </button>
        </div>
    </form>
</div>