<div class="bg-white border rounded-lg p-6 shadow-sm">
    <h3 class="text-lg font-medium mb-4 text-gray-900">Add New Question</h3>
    
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
                    </label>
                    <button 
                        type="button" 
                        wire:click="addOption"
                        class="text-sm text-indigo-600 hover:text-indigo-500 font-medium transition-colors duration-200"
                    >
                        + Add Option
                    </button>
                </div>
                
                <div class="space-y-2">
                    @foreach($newQuestion['options'] as $index => $option)
                        <div class="flex items-center space-x-2">
                            <div class="flex-shrink-0 w-8 h-8 bg-white border border-gray-300 rounded-full flex items-center justify-center text-sm font-medium text-gray-600">
                                {{ $index + 1 }}
                            </div>
                            <input 
                                type="text" 
                                wire:model="newQuestion.options.{{ $index }}" 
                                placeholder="Option {{ $index + 1 }}"
                                class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors duration-200"
                            >
                            @if(count($newQuestion['options']) > 2)
                                <button 
                                    type="button" 
                                    wire:click="removeOption({{ $index }})"
                                    class="flex-shrink-0 text-red-500 hover:text-red-700 transition-colors duration-200"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            @endif
                        </div>
                    @endforeach
                </div>
                
                @error('newQuestion.options') 
                    <span class="text-red-500 text-sm mt-2 block">{{ $message }}</span> 
                @enderror
            </div>
        @endif

        <!-- Correct Answer -->
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

        <!-- Form Actions -->
        <div class="flex justify-between items-center pt-4 border-t border-gray-200">
            <button 
                type="button" 
                wire:click="resetNewQuestion"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200"
            >
                Reset Form
            </button>
            
            <button 
                type="submit" 
                class="px-6 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove>Add Question</span>
                <span wire:loading class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Adding...
                </span>
            </button>
        </div>
    </form>
</div>