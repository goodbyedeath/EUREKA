<div>
    @if(!$isCompleted)
        {{-- Quiz Header --}}
        <div class="bg-white shadow-sm border-b border-gray-200 p-4 mb-6">
            <div class="max-w-4xl mx-auto">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-xl font-semibold text-gray-900">{{ $questionnaire->title }}</h1>
                        <p class="text-sm text-gray-600 mt-1">
                            Question {{ $currentQuestionIndex + 1 }} of {{ count($questions) }}
                        </p>
                    </div>
                    
                    @if($questionnaire->time_limit && $timeRemaining !== null)
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-clock text-gray-500"></i>
                            <span class="text-sm font-medium {{ $timeRemaining < 300 ? 'text-red-600' : 'text-gray-700' }}" 
                                  id="timer-display">
                                {{ $formattedTimeRemaining }}
                            </span>
                        </div>
                    @endif
                </div>
                
                {{-- Progress Bar --}}
                <div class="mt-4">
                    <div class="flex items-center justify-between text-xs text-gray-600 mb-2">
                        <span>Progress: {{ $progressPercentage }}%</span>
                        <span>Answered: {{ $answeredPercentage }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" 
                             style="width: {{ $progressPercentage }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Question Content --}}
        @if($currentQuestion)
            <div class="max-w-4xl mx-auto px-4">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                    {{-- Question Text --}}
                    <div class="mb-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-2">
                            {{ $currentQuestion['question'] }}
                        </h2>
                        @if($currentQuestion['points'] > 1)
                            <p class="text-sm text-gray-600">
                                <i class="fas fa-star text-yellow-500 mr-1"></i>
                                Worth {{ $currentQuestion['points'] }} points
                            </p>
                        @endif
                    </div>

                    {{-- Answer Input Based on Question Type --}}
                    <div class="mb-6">
                        @if($currentQuestion['type'] === 'multiple_choice')
                            <div class="space-y-3">
                                @foreach($currentQuestion['options'] as $index => $option)
                                    @if(!empty(trim($option)))
                                        <label class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors">
                                            <input type="radio" 
                                                   wire:model.live="answers.{{ $currentQuestion['id'] }}" 
                                                   value="{{ $option }}"
                                                   class="mr-3 text-blue-600 focus:ring-blue-500">
                                            <span class="text-gray-700">{{ $option }}</span>
                                        </label>
                                    @endif
                                @endforeach
                            </div>
                        
                        @elseif($currentQuestion['type'] === 'true_false')
                            <div class="space-y-3">
                                <label class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors">
                                    <input type="radio" 
                                           wire:model.live="answers.{{ $currentQuestion['id'] }}" 
                                           value="true"
                                           class="mr-3 text-blue-600 focus:ring-blue-500">
                                    <span class="text-gray-700">True</span>
                                </label>
                                <label class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors">
                                    <input type="radio" 
                                           wire:model.live="answers.{{ $currentQuestion['id'] }}" 
                                           value="false"
                                           class="mr-3 text-blue-600 focus:ring-blue-500">
                                    <span class="text-gray-700">False</span>
                                </label>
                            </div>
                        
                        @elseif($currentQuestion['type'] === 'text')
                            <div>
                                <textarea wire:model.blur="answers.{{ $currentQuestion['id'] }}" 
                                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                          rows="4"
                                          placeholder="Enter your answer here..."></textarea>
                            </div>
                        @endif
                    </div>

                    {{-- Answer Status Indicator --}}
                    @if($this->isQuestionAnswered($currentQuestion['id']))
                        <div class="flex items-center text-green-600 text-sm mb-4">
                            <i class="fas fa-check-circle mr-2"></i>
                            Answer saved
                        </div>
                    @endif
                </div>

                {{-- Navigation Controls --}}
                <div class="flex items-center justify-between mb-6">
                    <button wire:click="goToPreviousQuestion" 
                            class="flex items-center px-4 py-2 text-gray-600 hover:text-gray-800 disabled:opacity-50 disabled:cursor-not-allowed"
                            {{ !$this->canGoPrevious() ? 'disabled' : '' }}>
                        <i class="fas fa-chevron-left mr-2"></i>
                        Previous
                    </button>

                    <div class="flex space-x-2">
                        @if(!$this->isLastQuestion())
                            <button wire:click="goToNextQuestion" 
                                    class="flex items-center px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
                                    {{ !$this->canGoNext() ? 'disabled' : '' }}>
                                Next
                                <i class="fas fa-chevron-right ml-2"></i>
                            </button>
                        @else
                            <button wire:click="submitQuiz"
                                    wire:confirm="Are you sure you want to submit your quiz? This action cannot be undone."
                                    class="flex items-center px-6 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors">
                                <i class="fas fa-check mr-2"></i>
                                Submit Quiz
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Question Navigation Grid --}}
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="text-sm font-medium text-gray-700 mb-3">Question Navigator</h3>
                    <div class="grid grid-cols-10 gap-2">
                        @foreach($questions as $index => $question)
                            <button wire:click="goToQuestion({{ $index }})"
                                    class="w-8 h-8 text-xs rounded-md font-medium transition-colors
                                           {{ $index === $currentQuestionIndex 
                                              ? 'bg-blue-600 text-white' 
                                              : ($this->isQuestionAnswered($question['id']) 
                                                 ? 'bg-green-100 text-green-800 hover:bg-green-200' 
                                                 : 'bg-white text-gray-600 hover:bg-gray-100') }}">
                                {{ $index + 1 }}
                            </button>
                        @endforeach
                    </div>
                    <div class="flex items-center justify-center space-x-6 mt-3 text-xs text-gray-600">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-blue-600 rounded-sm mr-1"></div>
                            Current
                        </div>
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-green-100 border border-green-300 rounded-sm mr-1"></div>
                            Answered
                        </div>
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-white border border-gray-300 rounded-sm mr-1"></div>
                            Unanswered
                        </div>
                    </div>
                </div>
            </div>
        @endif

    @else
        {{-- Quiz Results --}}
        <div class="max-w-4xl mx-auto px-4">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center">
                <div class="mb-6">
                    @if($autoSubmitted)
                        <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-clock text-yellow-600 text-2xl"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">Time's Up!</h2>
                        <p class="text-gray-600">Your quiz was automatically submitted when the time limit was reached.</p>
                    @else
                        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-check text-green-600 text-2xl"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">Quiz Completed!</h2>
                        <p class="text-gray-600">Thank you for completing the quiz.</p>
                    @endif
                </div>

                {{-- Score Display --}}
                <div class="bg-gray-50 rounded-lg p-6 mb-6">
                    <div class="text-center">
                        <div class="text-4xl font-bold mb-2 {{ $score >= 70 ? 'text-green-600' : 'text-red-600' }}">
                            {{ number_format($score, 1) }}%
                        </div>
                        <p class="text-gray-600 mb-4">Your Score</p>
                        
                        <div class="grid grid-cols-3 gap-4 text-sm">
                            <div>
                                <p class="text-gray-500">Points Earned</p>
                                <p class="font-semibold">{{ $earnedPoints }} / {{ $totalPoints }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Questions Answered</p>
                                <p class="font-semibold">{{ $this->getAnsweredQuestionsCount() }} / {{ count($questions) }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Time Taken</p>
                                <p class="font-semibold">
                                    {{ gmdate('H:i:s', $endTime->diffInSeconds($startTime)) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Performance Indicator --}}
                <div class="mb-6">
                    @if($score >= 90)
                        <div class="text-green-600">
                            <i class="fas fa-trophy text-2xl mb-2"></i>
                            <p class="font-semibold">Excellent Performance!</p>
                        </div>
                    @elseif($score >= 70)
                        <div class="text-blue-600">
                            <i class="fas fa-thumbs-up text-2xl mb-2"></i>
                            <p class="font-semibold">Good Job!</p>
                        </div>
                    @else
                        <div class="text-orange-600">
                            <i class="fas fa-redo text-2xl mb-2"></i>
                            <p class="font-semibold">Keep Practicing!</p>
                        </div>
                    @endif
                </div>

                {{-- Action Buttons --}}
                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    <button wire:click="goToQuizList" 
                            class="px-6 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors">
                        <i class="fas fa-list mr-2"></i>
                        Back to Quiz List
                    </button>
                    
                    @if($questionnaire->max_attempts && $questionnaire->max_attempts > 1)
                        <a href="{{ route('user.quiz-take', ['questionnaireId' => $questionnaire->id]) }}" 
                           class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors inline-flex items-center">
                            <i class="fas fa-redo mr-2"></i>
                            Try Again
                        </a>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Success/Error Messages --}}
    @if(session()->has('success'))
        <div class="fixed top-4 right-4 bg-green-50 border border-green-200 rounded-md p-4 z-50" 
             x-data="{ show: true }" 
             x-show="show" 
             x-init="setTimeout(() => show = false, 5000)"
             x-transition>
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-green-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-green-800">{{ session('success') }}</p>
                </div>
                <div class="ml-auto pl-3">
                    <button @click="show = false" class="text-green-400 hover:text-green-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if(session()->has('error'))
        <div class="fixed top-4 right-4 bg-red-50 border border-red-200 rounded-md p-4 z-50" 
             x-data="{ show: true }" 
             x-show="show" 
             x-init="setTimeout(() => show = false, 5000)"
             x-transition>
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle text-red-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-800">{{ session('error') }}</p>
                </div>
                <div class="ml-auto pl-3">
                    <button @click="show = false" class="text-red-400 hover:text-red-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Loading Overlay --}}
    <div wire:loading.flex class="fixed inset-0 bg-gray-500 bg-opacity-75 z-50 items-center justify-center">
        <div class="bg-white rounded-lg p-6 text-center">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-4"></div>
            <p class="text-gray-600">Processing...</p>
        </div>
    </div>

    {{-- Timer Warning Modal --}}
    @if($questionnaire->time_limit && $timeRemaining !== null && $timeRemaining <= 60 && $timeRemaining > 0 && !$isCompleted)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 z-50 flex items-center justify-center" 
             x-data="{ show: true }" 
             x-show="show"
             x-transition>
            <div class="bg-white rounded-lg p-6 text-center max-w-sm">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Time Warning!</h3>
                <p class="text-gray-600 mb-4">You have less than 1 minute remaining!</p>
                <button @click="show = false" 
                        class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors">
                    Continue
                </button>
            </div>
        </div>
    @endif
</div>

@script
<script>
let timerInterval;
let quizTimingData = @json($this->getQuizTimingData());
let isCompleted = @json($isCompleted);

function calculateTimeRemaining() {
    if (!quizTimingData.hasTimeLimit || isCompleted) {
        return null;
    }
    
    const now = Math.floor(Date.now() / 1000); // Current timestamp in seconds
    const elapsed = now - quizTimingData.startTimestamp;
    const remaining = quizTimingData.timeLimitSeconds - elapsed;
    
    return Math.max(0, remaining);
}

function updateTimerDisplay(seconds) {
    const timerDisplay = document.getElementById('timer-display');
    if (!timerDisplay) return;
    
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;
    const formattedTime = `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
    
    timerDisplay.textContent = formattedTime;
    
    // Change color when time is low
    if (seconds < 300) { // Less than 5 minutes
        timerDisplay.className = 'text-sm font-medium text-red-600';
        timerDisplay.classList.add('timer-warning');
    } else {
        timerDisplay.className = 'text-sm font-medium text-gray-700';
        timerDisplay.classList.remove('timer-warning');
    }
}

function startTimer() {
    if (!quizTimingData.hasTimeLimit || isCompleted) {
        return;
    }
    
    if (timerInterval) {
        clearInterval(timerInterval);
    }
    
    // Calculate and display current time remaining
    let timeRemaining = calculateTimeRemaining();
    
    if (timeRemaining <= 0) {
        updateTimerDisplay(0);
        $wire.call('handleTimeExpiry');
        return;
    }
    
    updateTimerDisplay(timeRemaining);
    
    timerInterval = setInterval(function() {
        // Always calculate from start time to ensure accuracy
        timeRemaining = calculateTimeRemaining();
        
        if (timeRemaining <= 0) {
            clearInterval(timerInterval);
            timeRemaining = 0;
            updateTimerDisplay(0);
            
            // Auto-submit quiz
            $wire.call('handleTimeExpiry');
            return;
        }
        
        updateTimerDisplay(timeRemaining);
        
        // Sync with server every 30 seconds to get fresh timing data
        if (timeRemaining % 30 === 0) {
            $wire.call('getQuizTimingData').then(function(result) {
                if (result && result.hasTimeLimit) {
                    // Update our timing data with fresh server data
                    quizTimingData = result;
                    // Recalculate with new data
                    timeRemaining = calculateTimeRemaining();
                    updateTimerDisplay(timeRemaining);
                }
            }).catch(function(error) {
                console.error('Timer sync error:', error);
            });
        }
    }, 1000);
}

// Start timer when component loads
if (quizTimingData.hasTimeLimit && !isCompleted) {
    startTimer();
}

// Handle Livewire navigation
document.addEventListener('livewire:navigating', function() {
    if (timerInterval) {
        clearInterval(timerInterval);
    }
});

// Handle page visibility changes
document.addEventListener('visibilitychange', function() {
    if (!document.hidden && quizTimingData.hasTimeLimit && !isCompleted) {
        // Page became visible, sync with server and restart timer
        $wire.call('getQuizTimingData').then(function(result) {
            if (result && result.hasTimeLimit) {
                quizTimingData = result;
                
                // Restart timer if needed
                if (!timerInterval) {
                    startTimer();
                }
            }
        });
    }
});

// Prevent accidental page refresh during quiz
if (!isCompleted) {
    window.addEventListener('beforeunload', function(e) {
        e.preventDefault();
        e.returnValue = 'Are you sure you want to leave? Your progress may be lost.';
        return 'Are you sure you want to leave? Your progress may be lost.';
    });
}

// Clean up on quiz completion
$wire.on('quizCompleted', function() {
    isCompleted = true;
    if (timerInterval) {
        clearInterval(timerInterval);
    }
    window.removeEventListener('beforeunload', function() {});
});

// Auto-save functionality
let saveTimeout;
document.addEventListener('input', function(e) {
    if (e.target.tagName === 'TEXTAREA') {
        clearTimeout(saveTimeout);
        saveTimeout = setTimeout(() => {
            $wire.call('flushPendingAnswers');
        }, 2000);
    }
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Prevent F5 refresh during quiz
    if (!isCompleted && (e.key === 'F5' || (e.ctrlKey && e.key === 'r'))) {
        e.preventDefault();
        return false;
    }
    
    // Arrow key navigation
    if (e.altKey) {
        if (e.key === 'ArrowLeft') {
            e.preventDefault();
            $wire.call('goToPreviousQuestion');
        } else if (e.key === 'ArrowRight') {
            e.preventDefault();
            $wire.call('goToNextQuestion');
        }
    }
    
    // Submit with Ctrl+Enter
    if (e.ctrlKey && e.key === 'Enter') {
        e.preventDefault();
        $wire.call('submitQuiz');
    }
});
</script>
@endscript

@push('styles')
<style>
    /* Custom styles for quiz interface */
    .quiz-container {
        min-height: 100vh;
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    }
    
    /* Smooth transitions */
    .transition-all {
        transition: all 0.3s ease;
    }
    
    /* Focus styles */
    input[type="radio"]:focus {
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    
    textarea:focus {
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    
    /* Animation for progress bar */
    @keyframes progress {
        from { width: 0%; }
        to { width: var(--progress-width); }
    }
    
    /* Timer pulse animation when low */
    .timer-warning {
        animation: pulse 1s infinite;
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }
    
    /* Custom scrollbar */
    ::-webkit-scrollbar {
        width: 8px;
    }
    
    ::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }
    
    ::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 4px;
    }
    
    ::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }
</style>
@endpush