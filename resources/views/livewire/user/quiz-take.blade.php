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
                        <div class="flex items-center space-x-2" 
                             x-data="countdownTimer({{ $timeRemaining }}, '{{ $questionnaire->id }}')" 
                             x-init="init()">
                            <i class="fas fa-clock text-gray-500"></i>
                            <div class="countdown-display">
                                <span x-text="displayTime" 
                                      :class="isWarning ? 'text-sm font-bold text-red-600 animate-pulse' : 'text-sm font-medium text-gray-700'"></span>
                            </div>
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
                                        <label class="flex items-center p-3 border border-gray-200 rounded-lg {{ $attempt->canEditAnswers() ? 'hover:bg-gray-50 cursor-pointer' : 'bg-gray-100 cursor-not-allowed' }} transition-colors">
                                            <input type="radio" 
                                                   wire:model.live="answers.{{ $currentQuestion['id'] }}" 
                                                   value="{{ $option }}"
                                                   {{ $attempt->canEditAnswers() ? '' : 'disabled' }}
                                                   class="mr-3 text-blue-600 focus:ring-blue-500 {{ $attempt->canEditAnswers() ? '' : 'opacity-50' }}">
                                            <span class="text-gray-700 {{ $attempt->canEditAnswers() ? '' : 'opacity-50' }}">{{ $option }}</span>
                                        </label>
                                    @endif
                                @endforeach
                            </div>
                        
                        @elseif($currentQuestion['type'] === 'true_false')
                            <div class="space-y-3">
                                <label class="flex items-center p-3 border border-gray-200 rounded-lg {{ $attempt->canEditAnswers() ? 'hover:bg-gray-50 cursor-pointer' : 'bg-gray-100 cursor-not-allowed' }} transition-colors">
                                    <input type="radio" 
                                           wire:model.live="answers.{{ $currentQuestion['id'] }}" 
                                           value="true"
                                           {{ $attempt->canEditAnswers() ? '' : 'disabled' }}
                                           class="mr-3 text-blue-600 focus:ring-blue-500 {{ $attempt->canEditAnswers() ? '' : 'opacity-50' }}">
                                    <span class="text-gray-700 {{ $attempt->canEditAnswers() ? '' : 'opacity-50' }}">True</span>
                                </label>
                                <label class="flex items-center p-3 border border-gray-200 rounded-lg {{ $attempt->canEditAnswers() ? 'hover:bg-gray-50 cursor-pointer' : 'bg-gray-100 cursor-not-allowed' }} transition-colors">
                                    <input type="radio" 
                                           wire:model.live="answers.{{ $currentQuestion['id'] }}" 
                                           value="false"
                                           {{ $attempt->canEditAnswers() ? '' : 'disabled' }}
                                           class="mr-3 text-blue-600 focus:ring-blue-500 {{ $attempt->canEditAnswers() ? '' : 'opacity-50' }}">
                                    <span class="text-gray-700 {{ $attempt->canEditAnswers() ? '' : 'opacity-50' }}">False</span>
                                </label>
                            </div>
                        
                        @elseif($currentQuestion['type'] === 'text')
                            <div>
                                <textarea wire:model.blur="answers.{{ $currentQuestion['id'] }}" 
                                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $attempt->canEditAnswers() ? '' : 'bg-gray-100 opacity-50' }}"
                                          rows="4"
                                          {{ $attempt->canEditAnswers() ? '' : 'readonly' }}
                                          placeholder="{{ $attempt->canEditAnswers() ? 'Enter your answer here...' : 'Quiz has been submitted - answers cannot be edited' }}"></textarea>
                            </div>
                        @endif
                    </div>

                    {{-- Answer Status Indicator --}}
                    @if($this->isQuestionAnswered($currentQuestion['id']))
                        <div class="flex items-center text-green-600 text-sm mb-4">
                            <i class="fas fa-check-circle mr-2"></i>
                            {{ $attempt->canEditAnswers() ? 'Answer saved' : 'Answer submitted' }}
                        </div>
                    @endif
                    
                    {{-- Quiz Status Warning --}}
                    @if(!$attempt->canEditAnswers())
                        <div class="flex items-center text-orange-600 text-sm mb-4 p-3 bg-orange-50 border border-orange-200 rounded-lg">
                            <i class="fas fa-lock mr-2"></i>
                            This quiz has been submitted and cannot be edited. You can review your answers below.
                        </div>
                    @endif
                </div>

                {{-- Navigation Controls --}}
                <div class="flex items-center justify-between mb-6">
                    <button wire:click="goToPreviousQuestion" 
                            class="flex items-center px-4 py-2 text-gray-600 hover:text-gray-800 disabled:opacity-50 disabled:cursor-not-allowed"
                            {{ !$this->canGoPrevious() || !$attempt->canEditAnswers() ? 'disabled' : '' }}>
                        <i class="fas fa-chevron-left mr-2"></i>
                        Previous
                    </button>

                    <div class="flex space-x-2">
                        @if(!$this->isLastQuestion())
                            <button wire:click="goToNextQuestion" 
                                    class="flex items-center px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                    {{ !$this->canGoNext() || !$attempt->canEditAnswers() ? 'disabled' : '' }}>
                                Next
                                <i class="fas fa-chevron-right ml-2"></i>
                            </button>
                        @else
                            @if($attempt->canSubmit())
                                <button wire:click="submitQuiz"
                                        wire:confirm="Are you sure you want to submit your quiz? This action cannot be undone."
                                        class="flex items-center px-6 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors">
                                    <i class="fas fa-check mr-2"></i>
                                    Submit Quiz
                                </button>
                            @else
                                <div class="flex items-center px-6 py-2 bg-gray-400 text-white rounded-md cursor-not-allowed">
                                    <i class="fas fa-check-circle mr-2"></i>
                                    Quiz Submitted
                                </div>
                            @endif
                        @endif
                    </div>
                </div>

                {{-- Question Navigation Grid --}}
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="text-sm font-medium text-gray-700 mb-3">Question Navigator</h3>
                    <div class="grid grid-cols-10 gap-2">
                        @foreach($questions as $index => $question)
                            <button wire:click="goToQuestion({{ $index }})"
                                    class="w-8 h-8 text-xs rounded-md font-medium transition-colors {{ !$attempt->canEditAnswers() ? 'cursor-not-allowed' : '' }}
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
let quizTimingData = @json($this->getQuizTimingData());
let isCompleted = @json($isCompleted);

// Alpine.js countdown timer component
function countdownTimer(initialSeconds, questionnaireId) {
    return {
        timeRemaining: initialSeconds,
        displayTime: '',
        isWarning: false,
        interval: null,
        questionnaireId: questionnaireId,
        
        init() {
            this.updateDisplay();
            this.startCountdown();
            
            // Handle page visibility changes
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden && !isCompleted) {
                    this.syncWithServer();
                }
            });
            
            // Clean up on quiz completion
            this.$wire.on('quizCompleted', () => {
                this.stopCountdown();
            });
        },
        
        startCountdown() {
            if (this.interval) {
                clearInterval(this.interval);
            }
            
            this.interval = setInterval(() => {
                this.timeRemaining--;
                this.updateDisplay();
                
                if (this.timeRemaining <= 0) {
                    this.handleTimeExpiry();
                    return;
                }
                
                // Sync with server every 30 seconds
                if (this.timeRemaining % 30 === 0) {
                    this.syncWithServer();
                }
                
                // Show warning at 1 minute
                if (this.timeRemaining === 60 && !this.isWarning) {
                    this.showTimeWarning();
                }
            }, 1000);
        },
        
        updateDisplay() {
            const minutes = Math.floor(this.timeRemaining / 60);
            const seconds = this.timeRemaining % 60;
            this.displayTime = `${minutes}:${seconds.toString().padStart(2, '0')}`;
            
            // Update warning state
            this.isWarning = this.timeRemaining < 300; // Less than 5 minutes
        },
        
        syncWithServer() {
            this.$wire.call('getQuizTimingData').then((result) => {
                if (result && result.hasTimeLimit) {
                    const now = Math.floor(Date.now() / 1000);
                    const elapsed = now - result.startTimestamp;
                    const remaining = Math.max(0, result.timeLimitSeconds - elapsed);
                    
                    // Update time remaining if server time differs significantly
                    if (Math.abs(this.timeRemaining - remaining) > 2) {
                        this.timeRemaining = remaining;
                        this.updateDisplay();
                    }
                }
            }).catch((error) => {
                console.error('Timer sync error:', error);
            });
        },
        
        handleTimeExpiry() {
            this.stopCountdown();
            this.timeRemaining = 0;
            this.updateDisplay();
            this.$wire.call('handleTimeExpiry');
        },
        
        stopCountdown() {
            if (this.interval) {
                clearInterval(this.interval);
                this.interval = null;
            }
        },
        
        showTimeWarning() {
            // Create a temporary notification
            const notification = document.createElement('div');
            notification.className = 'fixed top-4 right-4 bg-red-50 border border-red-200 rounded-md p-4 z-50 shadow-lg';
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-clock text-red-500 mr-2"></i>
                    <span class="text-red-800 font-medium">1 minute remaining!</span>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Remove notification after 3 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 3000);
        }
    }
}

// Prevent accidental page refresh during quiz
let beforeUnloadHandler = function(e) {
    e.preventDefault();
    e.returnValue = 'Are you sure you want to leave? Your progress may be lost.';
    return 'Are you sure you want to leave? Your progress may be lost.';
};

// Add the warning if quiz is not completed
if (!isCompleted) {
    window.addEventListener('beforeunload', beforeUnloadHandler);
}

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

// Keyboard shortcuts and prevention
let quizCompleted = isCompleted;

document.addEventListener('keydown', function(e) {
    // Prevent F5 refresh during quiz (but allow after completion)
    if (!quizCompleted && (e.key === 'F5' || (e.ctrlKey && e.key === 'r'))) {
        e.preventDefault();
        return false;
    }
    
    // Arrow key navigation (only during quiz)
    if (!quizCompleted && e.altKey) {
        if (e.key === 'ArrowLeft') {
            e.preventDefault();
            $wire.call('goToPreviousQuestion');
        } else if (e.key === 'ArrowRight') {
            e.preventDefault();
            $wire.call('goToNextQuestion');
        }
    }
    
    // Submit with Ctrl+Enter (only during quiz)
    if (!quizCompleted && e.ctrlKey && e.key === 'Enter') {
        e.preventDefault();
        $wire.call('submitQuiz');
    }
});

// Listen for quiz completion to remove warnings and restrictions
document.addEventListener('livewire:initialized', () => {
    Livewire.on('quizCompleted', () => {
        // Remove the beforeunload warning when quiz is completed
        window.removeEventListener('beforeunload', beforeUnloadHandler);
        // Update completion status
        quizCompleted = true;
    });
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
    
    /* Enhanced countdown display styles */
    .countdown-display {
        min-width: 60px;
        text-align: center;
    }
    
    /* Smooth animation for countdown */
    .countdown-display span {
        transition: all 0.3s ease;
    }
    
    /* Pulse animation for warnings */
    @keyframes pulse {
        0%, 100% { 
            opacity: 1; 
            transform: scale(1);
        }
        50% { 
            opacity: 0.8; 
            transform: scale(1.05);
        }
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