<div>
    {{-- DEBUG: Show all questions data --}}
    <div class="bg-red-100 border border-red-300 rounded p-3 m-4">
        <strong>DEBUG - All Questions:</strong><br>
        Total questions: {{ count($questions ?? []) }}<br>
        @if(isset($questions))
            @foreach($questions as $index => $q)
                Question {{ $index + 1 }}: Type={{ $q['type'] ?? 'N/A' }}, 
                Images={{ !empty($q['images']) ? 'YES(' . count($q['images']) . ')' : 'NO' }}<br>
            @endforeach
        @endif
        Current Question Index: {{ $currentQuestionIndex ?? 'N/A' }}
    </div>

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
                    
                    @php
                        $showTimer = $questionnaire->time_limit && isset($timeRemaining) && $timeRemaining > 0;
                        $showTimeUp = $questionnaire->time_limit && isset($timeRemaining) && $timeRemaining <= 0;
                        $safeTimeRemaining = isset($timeRemaining) ? max(0, (int) $timeRemaining) : 0;
                    @endphp
                    
                    @if($showTimer)
                        <div class="flex items-center space-x-2 bg-white rounded-lg px-3 py-2 shadow-sm border" 
                             x-data="countdownTimer({{ $safeTimeRemaining }}, '{{ $questionnaire->id }}')" 
                             x-init="init()">
                            <i class="fas fa-clock" :class="isWarning ? 'text-red-500' : 'text-blue-500'"></i>
                            <span x-text="displayTime" 
                                  :class="isWarning ? 'font-bold text-red-600' : 'font-semibold text-gray-800'"></span>
                        </div>
                    @elseif($showTimeUp)
                        <div class="flex items-center space-x-2 bg-red-50 rounded-lg px-3 py-2 border border-red-200">
                            <i class="fas fa-clock text-red-500"></i>
                            <span class="font-bold text-red-800">Time's Up!</span>
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
                        
                        @elseif($currentQuestion['type'] === 'fun_game')
                            {{-- DEBUG: Show current question data --}}
                            <div class="mb-4 p-3 bg-yellow-100 border border-yellow-300 rounded">
                                <strong>DEBUG - Current Question Data:</strong><br>
                                Type: {{ $currentQuestion['type'] ?? 'N/A' }}<br>
                                Images: {{ !empty($currentQuestion['images']) ? 'YES (' . count($currentQuestion['images']) . ')' : 'NO' }}<br>
                                @if(!empty($currentQuestion['images']))
                                    Image paths: 
                                    @foreach($currentQuestion['images'] as $img)
                                        <br>- {{ $img }}
                                    @endforeach
                                @endif
                            </div>
                            
                            <div class="fun-game-container">
                                {{-- Game Header --}}
                                <div class="bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-lg p-6 mb-6">
                                    <div class="flex items-center justify-between mb-4">
                                        <h2 class="text-2xl font-bold">{{ $currentQuestion['game_name'] ?? 'Fun Game' }}</h2>
                                        <div class="flex items-center space-x-2">
                                            <i class="fas fa-gamepad text-2xl"></i>
                                        </div>
                                    </div>
                                    
                                    {{-- Timer for fun game --}}
                                    @if($questionnaire->time_limit)
                                        <div class="flex items-center text-sm opacity-90">
                                            <i class="fas fa-clock mr-2"></i>
                                            <span>Time Limit: {{ $questionnaire->time_limit }} minutes</span>
                                        </div>
                                    @endif
                                </div>

                                {{-- Game Images --}}
                                @if(!empty($currentQuestion['images']))
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                                        @foreach($currentQuestion['images'] as $image)
                                            @php
                                                // The image path already includes the full path like 'games/question-images/filename.jpg'
                                                $imageUrl = asset('storage/' . $image);
                                            @endphp
                                            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                                                <img src="{{ $imageUrl }}" 
                                                     alt="Game Image" 
                                                     class="w-full h-48 object-cover hover:scale-105 transition-transform cursor-pointer"
                                                     onclick="console.log('Image clicked:', '{{ $imageUrl }}'); openImageModal('{{ $imageUrl }}')"
                                                     onerror="console.log('Image failed to load:', '{{ $imageUrl }}'); this.style.display='none'; this.nextElementSibling.style.display='block';"
                                                     onload="console.log('Image loaded successfully:', '{{ $imageUrl }}')">
                                                <div style="display:none;" class="w-full h-48 bg-gray-200 flex items-center justify-center text-gray-500">
                                                    <div class="text-center">
                                                        <i class="fas fa-image text-4xl mb-2"></i>
                                                        <p class="text-sm">Image not found</p>
                                                        <p class="text-xs">{{ $image }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                {{-- Game Instructions --}}
                                @if(!empty($currentQuestion['description']))
                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
                                        <h3 class="text-lg font-semibold text-blue-900 mb-3 flex items-center">
                                            <i class="fas fa-info-circle mr-2"></i>
                                            Game Instructions
                                        </h3>
                                        <div class="text-blue-800 whitespace-pre-line">{{ $currentQuestion['description'] }}</div>
                                    </div>
                                @endif

                                {{-- Game Status --}}
                                <div class="bg-white rounded-lg border-2 border-dashed border-gray-300 p-8 text-center">
                                    @if(!$this->isGameCompleted($currentQuestion['id']))
                                        <div class="mb-6">
                                            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                                <i class="fas fa-play text-green-600 text-2xl"></i>
                                            </div>
                                            <h3 class="text-xl font-semibold text-gray-900 mb-2">Game in Progress</h3>
                                            <p class="text-gray-600 mb-6">Follow the instructions above to complete this fun game!</p>
                                            
                                            @if($attempt->canEditAnswers())
                                                <button wire:click="completeGame({{ $currentQuestion['id'] }})"
                                                        class="bg-green-600 hover:bg-green-700 text-white px-8 py-3 rounded-lg font-medium transition-colors duration-200">
                                                    <i class="fas fa-flag-checkered mr-2"></i>
                                                    Complete Game
                                                </button>
                                            @endif
                                        </div>
                                    @else
                                        @php
                                            $assessment = App\Models\GameAssessment::where('quiz_attempt_id', $attempt->id)
                                                ->where('question_id', $currentQuestion['id'])
                                                ->where('user_id', auth()->id())
                                                ->first();
                                        @endphp
                                        
                                        <div class="mb-6">
                                            @if($assessment && $assessment->is_assessed)
                                                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                                    <i class="fas fa-trophy text-green-600 text-2xl"></i>
                                                </div>
                                                <h3 class="text-xl font-semibold text-gray-900 mb-2">Game Assessed!</h3>
                                                <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                                                    <div class="grid grid-cols-2 gap-4 text-sm">
                                                        <div>
                                                            <span class="font-medium text-green-800">Deposit:</span>
                                                            <span class="text-green-700">{{ $assessment->deposit ?? 0 }} points</span>
                                                        </div>
                                                        <div>
                                                            <span class="font-medium text-red-800">Penalty:</span>
                                                            <span class="text-red-700">{{ $assessment->penalty ?? 0 }} points</span>
                                                        </div>
                                                        <div class="col-span-2 border-t border-green-300 pt-2 mt-2">
                                                            <span class="font-bold text-green-800">Total Score:</span>
                                                            <span class="font-bold text-green-700">{{ $assessment->total_deposit ?? 0 }} points</span>
                                                        </div>
                                                    </div>
                                                    @if($assessment->notes)
                                                        <div class="mt-3 pt-3 border-t border-green-300">
                                                            <p class="text-sm text-green-800"><strong>Assessor Notes:</strong></p>
                                                            <p class="text-sm text-green-700 mt-1">{{ $assessment->notes }}</p>
                                                        </div>
                                                    @endif
                                                </div>
                                            @else
                                                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                                    <i class="fas fa-clock text-blue-600 text-2xl"></i>
                                                </div>
                                                <h3 class="text-xl font-semibold text-gray-900 mb-2">Game Completed!</h3>
                                                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                                    <div class="flex items-center text-yellow-800">
                                                        <i class="fas fa-hourglass-half mr-2"></i>
                                                        <span class="font-medium">Waiting for assessment...</span>
                                                    </div>
                                                    <p class="text-yellow-700 text-sm mt-2">Your game performance will be evaluated by an assessor. Assessment results will show your final score for this game.</p>
                                                    
                                                    @if($assessment && !$assessment->is_assessed)
                                                        <div class="mt-4 pt-3 border-t border-yellow-300">
                                                            <a href="{{ route('game.assessment', ['assessmentId' => $assessment->id]) }}" 
                                                               class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                                                                <i class="fas fa-clipboard-list mr-2"></i>
                                                                Continue Assessment
                                                            </a>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
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
                            {{-- For fun-game-only quizzes, show completion status instead of submit button --}}
                            @php
                                $isFunGameOnly = collect($questions)->every(fn($q) => $q['type'] === 'fun_game');
                            @endphp
                            
                            @if($isFunGameOnly)
                                {{-- Fun-game quiz auto-completes, show status --}}
                                @php
                                    $allGamesCompleted = collect($questions)->every(fn($q) => $q['type'] !== 'fun_game' || $this->isGameCompleted($q['id']));
                                @endphp
                                
                                @if($allGamesCompleted)
                                    <div class="flex items-center px-6 py-2 bg-green-600 text-white rounded-md">
                                        <i class="fas fa-check-circle mr-2"></i>
                                        All Games Completed
                                    </div>
                                @else
                                    <div class="flex items-center px-6 py-2 bg-yellow-600 text-white rounded-md">
                                        <i class="fas fa-gamepad mr-2"></i>
                                        Complete All Games
                                    </div>
                                @endif
                            @else
                                {{-- Regular quiz with submit button --}}
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
                                                 : 'bg-white text-gray-600 hover:bg-gray-100') }}"
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


    {{-- Navigation Prevention Warning Modal --}}
    @if($quizLocked && !$isCompleted)
        <div class="fixed bottom-4 left-4 bg-yellow-50 border border-yellow-200 rounded-lg p-4 shadow-lg z-40 max-w-sm" 
             x-data="{ show: true }" 
             x-show="show"
             x-transition>
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="fas fa-lock text-yellow-600"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-800 font-medium">Quiz in Progress</p>
                    <p class="text-xs text-yellow-700 mt-1">Navigation is locked until you complete all questions and submit your answers.</p>
                </div>
                <button @click="show = false" class="ml-auto text-yellow-600 hover:text-yellow-800">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>
        </div>
    @endif

    {{-- Image Modal for Fun Games --}}
    <div id="imageModal" class="fixed inset-0 bg-black bg-opacity-75 z-50 hidden" style="display: none;">
        <div class="flex items-center justify-center min-h-screen p-4" onclick="closeImageModal()">
            <div class="relative max-w-4xl max-h-full" onclick="event.stopPropagation()">
                <img id="modalImage" src="" alt="Game Image" class="max-w-full max-h-full object-contain rounded-lg">
                <button onclick="closeImageModal()" class="absolute top-2 right-2 bg-black bg-opacity-50 text-white w-8 h-8 rounded-full flex items-center justify-center hover:bg-opacity-75 transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Simple and reliable countdown timer based on Edureka best practices
@php
    $timerStartTime = isset($attempt) && $attempt && $attempt->started_at ? $attempt->started_at->getTimestamp() : null;
    $timerLimitSeconds = isset($questionnaire) && $questionnaire->time_limit ? $questionnaire->time_limit * 60 : null;
    $timerCompleted = isset($isCompleted) ? $isCompleted : false;
@endphp

// Define these as global variables before Alpine initializes
window.quizStartTime = {{ $timerStartTime ?? 'null' }};
window.timeLimitSeconds = {{ $timerLimitSeconds ?? 'null' }};
window.isQuizCompleted = {{ $timerCompleted ? 'true' : 'false' }};

// Debug timer data
console.log('Timer initialization:', {
    quizStartTime: window.quizStartTime,
    timeLimitSeconds: window.timeLimitSeconds,
    isQuizCompleted: window.isQuizCompleted
});

// Compact countdown timer component - define globally before Alpine loads
window.countdownTimer = function(initialSeconds, questionnaireId) {
    return {
        timeRemaining: initialSeconds,
        displayTime: '',
        isWarning: false,
        timerInterval: null,
        
        init() {
            // Validate timer data before starting
            if (!window.timeLimitSeconds || !window.quizStartTime || window.isQuizCompleted) {
                console.warn('Timer initialization skipped:', {
                    timeLimitSeconds: window.timeLimitSeconds,
                    quizStartTime: window.quizStartTime,
                    isQuizCompleted: window.isQuizCompleted
                });
                // Set fallback display
                this.displayTime = this.timeRemaining > 0 ? this.formatTime(this.timeRemaining) : '0:00';
                return;
            }
            
            this.updateDisplay();
            this.startTimer();
            
            // Sync on page visibility change
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden && !window.isQuizCompleted) {
                    this.syncTime();
                }
            });
            
            // Cleanup on completion - use wire instead of $wire for better compatibility
            if (this.$wire) {
                this.$wire.on('quizCompleted', () => {
                    this.stopTimer();
                });
            }
        },
        
        formatTime(seconds) {
            const minutes = Math.floor(Math.max(0, seconds) / 60);
            const secs = Math.max(0, seconds) % 60;
            return `${minutes}:${secs.toString().padStart(2, '0')}`;
        },
        
        startTimer() {
            if (this.timerInterval) {
                clearInterval(this.timerInterval);
            }
            
            this.timerInterval = setInterval(() => {
                if (window.isQuizCompleted) {
                    this.stopTimer();
                    return;
                }
                
                // Calculate actual time remaining based on server time
                this.calculateTimeRemaining();
                this.updateDisplay();
                
                if (this.timeRemaining <= 0) {
                    this.handleTimeExpiry();
                }
                
                // Show warning at 5 minutes and 1 minute
                if ((this.timeRemaining === 300 || this.timeRemaining === 60) && !this.isWarning) {
                    this.showWarning();
                }
            }, 1000);
        },
        
        calculateTimeRemaining() {
            if (!window.timeLimitSeconds || !window.quizStartTime || typeof window.timeLimitSeconds !== 'number' || typeof window.quizStartTime !== 'number') {
                console.warn('Invalid timer data:', { timeLimitSeconds: window.timeLimitSeconds, quizStartTime: window.quizStartTime });
                return;
            }
            
            const currentTime = Math.floor(Date.now() / 1000);
            const elapsed = currentTime - window.quizStartTime;
            this.timeRemaining = Math.max(0, window.timeLimitSeconds - elapsed);
            
            // Debug log every 30 seconds
            if (elapsed % 30 === 0) {
                console.log('Timer sync:', {
                    currentTime,
                    quizStartTime: window.quizStartTime,
                    elapsed,
                    timeLimitSeconds: window.timeLimitSeconds,
                    timeRemaining: this.timeRemaining
                });
            }
        },
        
        updateDisplay() {
            this.displayTime = this.formatTime(this.timeRemaining);
            
            // Warning when less than 5 minutes
            this.isWarning = this.timeRemaining <= 300 && this.timeRemaining > 0;
        },
        
        syncTime() {
            // Simple sync - recalculate from server start time
            this.calculateTimeRemaining();
            this.updateDisplay();
        },
        
        handleTimeExpiry() {
            this.stopTimer();
            this.timeRemaining = 0;
            this.updateDisplay();
            
            // Auto-submit quiz
            if (this.$wire) {
                this.$wire.call('handleTimeExpiry').then(() => {
                    alert('Time is up! Your quiz has been submitted automatically.');
                });
            }
        },
        
        stopTimer() {
            if (this.timerInterval) {
                clearInterval(this.timerInterval);
                this.timerInterval = null;
            }
        },
        
        showWarning() {
            const timeText = this.timeRemaining === 300 ? '5 minutes' : '1 minute';
            
            // Simple alert for time warning
            if (confirm(`${timeText} remaining! Click OK to continue.`)) {
                this.isWarning = true;
            }
        }
    }
}

// Prevent accidental page refresh during quiz
let beforeUnloadHandler = function(e) {
    if (!window.isQuizCompleted && @json($quizLocked)) {
        e.preventDefault();
        e.returnValue = 'Quiz in progress! You cannot leave until all questions are completed and submitted.';
        return 'Quiz in progress! You cannot leave until all questions are completed and submitted.';
    }
};

// Add the warning if quiz is not completed
if (!window.isQuizCompleted && @json($quizLocked)) {
    window.addEventListener('beforeunload', beforeUnloadHandler);
}

// Block back button navigation during quiz
let isNavigationBlocked = @json($quizLocked) && !window.isQuizCompleted;

if (isNavigationBlocked) {
    // Push a dummy state to prevent back button navigation
    history.pushState(null, '', location.href);
    
    window.addEventListener('popstate', function(event) {
        if (isNavigationBlocked) {
            // Push state again to prevent going back
            history.pushState(null, '', location.href);
            
            // Show warning
            if (confirm('Quiz in progress! Are you sure you want to abandon this quiz? Your progress will be lost.')) {
                // Allow exit if user confirms
                if (window.Livewire && typeof $wire !== 'undefined') {
                    $wire.call('forceExit');
                }
            }
        }
    });
}

// Block common keyboard shortcuts that could navigate away
document.addEventListener('keydown', function(e) {
    if (isNavigationBlocked) {
        // Block Alt+Left (back), Alt+Right (forward)
        if (e.altKey && (e.key === 'ArrowLeft' || e.key === 'ArrowRight')) {
            e.preventDefault();
            return false;
        }
        
        // Block Ctrl+W (close tab), but allow Ctrl+Shift+W
        if (e.ctrlKey && e.key === 'w' && !e.shiftKey) {
            e.preventDefault();
            return false;
        }
        
        // Block F1-F12 function keys that might cause navigation
        if (e.key.startsWith('F') && e.key.length <= 3) {
            e.preventDefault();
            return false;
        }
    }
});

// Disable right-click context menu during quiz
if (isNavigationBlocked) {
    document.addEventListener('contextmenu', function(e) {
        e.preventDefault();
        return false;
    });
}

// Auto-save functionality
let saveTimeout;
document.addEventListener('input', function(e) {
    if (e.target.tagName === 'TEXTAREA') {
        clearTimeout(saveTimeout);
        saveTimeout = setTimeout(() => {
            if (window.Livewire && typeof $wire !== 'undefined') {
                $wire.call('flushPendingAnswers');
            }
        }, 2000);
    }
});

// Keyboard shortcuts and prevention
let quizCompleted = window.isQuizCompleted;

document.addEventListener('keydown', function(e) {
    // Prevent F5 refresh during quiz (but allow after completion)
    if (!quizCompleted && (e.key === 'F5' || (e.ctrlKey && e.key === 'r'))) {
        e.preventDefault();
        return false;
    }
    
    // Arrow key navigation (only during quiz)
    if (!window.isQuizCompleted && e.altKey) {
        if (e.key === 'ArrowLeft') {
            e.preventDefault();
            if (window.Livewire) {
                $wire.call('goToPreviousQuestion');
            }
        } else if (e.key === 'ArrowRight') {
            e.preventDefault();
            if (window.Livewire) {
                $wire.call('goToNextQuestion');
            }
        }
    }
    
    // Submit with Ctrl+Enter (only during quiz)
    if (!window.isQuizCompleted && e.ctrlKey && e.key === 'Enter') {
        e.preventDefault();
        if (window.Livewire) {
            $wire.call('submitQuiz');
        }
    }
});

// Listen for quiz completion to remove warnings and restrictions
document.addEventListener('livewire:initialized', () => {
    Livewire.on('quizCompleted', () => {
        // Remove the beforeunload warning when quiz is completed
        window.removeEventListener('beforeunload', beforeUnloadHandler);
        // Update completion status
        window.isQuizCompleted = true;
        isNavigationBlocked = false;
        
        // Re-enable right-click context menu
        document.removeEventListener('contextmenu', function(e) {
            e.preventDefault();
            return false;
        });
        
        // Show completion notification
        const notification = document.createElement('div');
        notification.className = 'fixed top-4 right-4 bg-green-50 border border-green-200 rounded-md p-4 z-50 shadow-lg';
        notification.innerHTML = `
            <div class="flex items-center">
                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                <span class="text-green-800 font-medium">Quiz completed! Navigation unlocked.</span>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Remove notification after 3 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 3000);
    });
});

// Image modal functions for fun games - make them globally available
window.openImageModal = function(imageSrc) {
    const modal = document.getElementById('imageModal');
    const modalImage = document.getElementById('modalImage');
    if (modal && modalImage) {
        modalImage.src = imageSrc;
        modal.classList.remove('hidden');
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden'; // Prevent scrolling
        console.log('Opening image modal with:', imageSrc);
    } else {
        console.error('Image modal elements not found');
    }
}

window.closeImageModal = function() {
    const modal = document.getElementById('imageModal');
    if (modal) {
        modal.classList.add('hidden');
        modal.style.display = 'none';
        document.body.style.overflow = ''; // Restore scrolling
        console.log('Closing image modal');
    }
}

// Also add keyboard support for closing modal
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('imageModal');
        if (modal && !modal.classList.contains('hidden')) {
            closeImageModal();
        }
    }
});
</script>
@endpush

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