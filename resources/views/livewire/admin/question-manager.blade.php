<div class="space-y-6">
    <!-- Question Form -->
    <livewire:admin.question-form :questionnaire="$questionnaire" />
    
    <!-- Questions List -->
    <div class="space-y-4">
        <h3 class="text-lg font-medium">Questions ({{ count($questions) }})</h3>
        
        @if(session('questions_message'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                {{ session('questions_message') }}
            </div>
        @endif

        @forelse($questions as $question)
            <div class="border rounded-lg p-4 bg-gray-50">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <h4 class="font-medium">{{ $question['question'] }}</h4>
                        <p class="text-sm text-gray-600 mt-1">
                            Type: {{ ucfirst(str_replace('_', ' ', $question['type'])) }} | 
                            Points: {{ $question['points'] }}
                        </p>
                        
                        @if($question['type'] === 'multiple_choice' && $question['options'])
                            <div class="mt-2">
                                <p class="text-sm font-medium text-gray-700">Options:</p>
                                <ul class="text-sm text-gray-600 ml-4">
                                    @foreach($question['options'] as $option)
                                        <li class="list-disc">
                                            {{ $option }}
                                            @if($option === $question['correct_answer'])
                                                <span class="text-green-600 font-medium">(Correct)</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @else
                            <p class="text-sm text-gray-600 mt-1">
                                Correct Answer: <span class="font-medium">{{ $question['correct_answer'] }}</span>
                            </p>
                        @endif
                    </div>
                    
                    <div class="flex space-x-2">
                        <button wire:click="editQuestion({{ $question['id'] }})" 
                                class="text-indigo-600 hover:text-indigo-800">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </button>
                        <button wire:click="deleteQuestion({{ $question['id'] }})" 
                                wire:confirm="Are you sure you want to delete this question?"
                                class="text-red-600 hover:text-red-800">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <p class="text-gray-500 text-center py-8">No questions added yet.</p>
        @endforelse
    </div>
</div>