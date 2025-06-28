<div class="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-purple-50">
    {{-- Enhanced Floating Background Elements --}}
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -right-40 w-80 h-80 bg-gradient-to-br from-blue-200/30 to-purple-200/30 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-gradient-to-br from-purple-200/30 to-pink-200/30 rounded-full blur-3xl animate-pulse" style="animation-delay: 2s;"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-gradient-to-br from-indigo-200/20 to-blue-200/20 rounded-full blur-3xl animate-pulse" style="animation-delay: 4s;"></div>
    </div>

    @if(!$isCompleted)
        {{-- Enhanced Quiz Header --}}
        <div class="sticky top-0 z-40 backdrop-blur-xl bg-white/80 border-b border-white/20 shadow-lg">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="py-4">
                    {{-- Header Content --}}
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        {{-- Quiz Info --}}
                        <div class="flex-1">
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
                                    <i class="fas fa-brain text-white text-xl"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h1 class="text-xl lg:text-2xl font-bold text-gray-900 truncate">{{ $questionnaire->title }}</h1>
                                    <div class="flex flex-wrap items-center gap-3 mt-1 text-sm text-gray-600">
                                        <span class="flex items-center gap-1">
                                            <i class="fas fa-question-circle text-indigo-500"></i>
                                            Question {{ $currentQuestionIndex + 1 }} of {{ count($questions) }}
                                        </span>
                                        @if($questionnaire->time_limit)
                                            <span class="flex items-center gap-1">
                                                <i class="fas fa-clock text-purple-500"></i>
                                                {{ $questionnaire->time_limit }} min limit
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Enhanced Timer Display --}}
                        @php
                            $showTimer = $questionnaire->time_limit && isset($timeRemaining) && $timeRemaining > 0;
                            $showTimeUp = $questionnaire->time_limit && isset($timeRemaining) && $timeRemaining <= 0;
                            $safeTimeRemaining = isset($timeRemaining) ? max(0, (int) $timeRemaining) : 0;
                        @endphp
                        
                        @if($showTimer)
                            <div class="timer-container-enhanced" 
                                 x-data="{
                                    timeRemaining: {{ $safeTimeRemaining }},
                                    displayTime: '{{ sprintf("%d:%02d", floor($safeTimeRemaining / 60), $safeTimeRemaining % 60) }}',
                                    isWarning: {{ $safeTimeRemaining <= 300 ? 'true' : 'false' }},
                                    isCritical: {{ $safeTimeRemaining <= 60 ? 'true' : 'false' }},
                                    timerInterval: null,
                                    progressPercentage: {{ $questionnaire->time_limit ? round(($safeTimeRemaining / ($questionnaire->time_limit * 60)) * 100, 2) : 100 }},
                                    totalTimeLimit: {{ $questionnaire->time_limit * 60 }},
                                    init() {
                                        this.startTimer();
                                    },
                                    startTimer() {
                                        if (window.timeLimitSeconds && window.quizStartTime && !window.isQuizCompleted) {
                                            this.timerInterval = setInterval(() => {
                                                this.calculateTimeRemaining();
                                                if (this.timeRemaining <= 0) {
                                                    this.handleTimeExpiry();
                                                }
                                            }, 1000);
                                        }
                                    },
                                    calculateTimeRemaining() {
                                        if (window.timeLimitSeconds && window.quizStartTime) {
                                            const currentTime = Math.floor(Date.now() / 1000);
                                            const elapsed = currentTime - window.quizStartTime;
                                            this.timeRemaining = Math.max(0, window.timeLimitSeconds - elapsed);
                                            this.displayTime = this.formatTime(this.timeRemaining);
                                            
                                            const wasWarning = this.isWarning;
                                            const wasCritical = this.isCritical;
                                            this.isWarning = this.timeRemaining <= 300 && this.timeRemaining > 0;
                                            this.isCritical = this.timeRemaining <= 60 && this.timeRemaining > 0;
                                            
                                            this.progressPercentage = this.totalTimeLimit > 0 ? 
                                                Math.round((this.timeRemaining / this.totalTimeLimit) * 100) : 0;
                                            
                                            if (!wasWarning && this.isWarning) {
                                                this.triggerWarningAlert('5 minutes remaining!');
                                            }
                                            if (!wasCritical && this.isCritical) {
                                                this.triggerWarningAlert('1 minute remaining!');
                                            }
                                        }
                                    },
                                    formatTime(seconds) {
                                        const minutes = Math.floor(Math.max(0, seconds) / 60);
                                        const secs = Math.max(0, seconds) % 60;
                                        return minutes + ':' + secs.toString().padStart(2, '0');
                                    },
                                    handleTimeExpiry() {
                                        if (this.timerInterval) {
                                            clearInterval(this.timerInterval);
                                            this.timerInterval = null;
                                        }
                                        this.timeRemaining = 0;
                                        this.displayTime = '0:00';
                                        this.progressPercentage = 0;
                                        
                                        if (this.$wire) {
                                            this.$wire.call('handleTimeExpiry');
                                        }
                                    },
                                    triggerWarningAlert(message) {
                                        const notification = document.createElement('div');
                                        notification.className = 'timer-alert-notification';
                                        notification.innerHTML = `
                                            <div class='flex items-center gap-3'>
                                                <i class='fas fa-exclamation-triangle text-amber-400'></i>
                                                <span class='font-medium'>${message}</span>
                                            </div>
                                        `;
                                        document.body.appendChild(notification);
                                        
                                        setTimeout(() => {
                                            if (notification.parentNode) {
                                                notification.parentNode.removeChild(notification);
                                            }
                                        }, 4000);
                                    }
                                 }">
                                
                                <div class="timer-widget-enhanced" 
                                     :class="{
                                         'timer-normal': !isWarning && !isCritical,
                                         'timer-warning': isWarning && !isCritical,
                                         'timer-critical': isCritical
                                     }">
                                    
                                    {{-- Timer Display --}}
                                    <div class="flex items-center gap-3 mb-3">
                                        <div class="timer-icon-container">
                                            <i :class="{
                                                'fas fa-stopwatch text-blue-500': !isWarning && !isCritical,
                                                'fas fa-clock text-amber-500': isWarning && !isCritical,
                                                'fas fa-exclamation-triangle text-red-500': isCritical
                                            }" class="text-lg"></i>
                                        </div>
                                        <div class="flex-1">
                                            <div class="timer-display-enhanced" x-text="displayTime"></div>
                                            <div class="timer-status-enhanced" x-text="isCritical ? 'CRITICAL!' : (isWarning ? 'Warning' : 'Active')"></div>
                                        </div>
                                        <div class="timer-percentage-enhanced" x-text="`${progressPercentage}%`"></div>
                                    </div>
                                    
                                    {{-- Progress Bar --}}
                                    <div class="timer-progress-enhanced">
                                        <div class="timer-progress-bar-enhanced" 
                                             :style="`width: ${progressPercentage}%`"
                                             :class="{
                                                 'bg-gradient-to-r from-blue-500 to-blue-600': !isWarning && !isCritical,
                                                 'bg-gradient-to-r from-amber-500 to-amber-600': isWarning && !isCritical,
                                                 'bg-gradient-to-r from-red-500 to-red-600': isCritical
                                             }"></div>
                                    </div>
                                </div>
                            </div>
                        @elseif($showTimeUp)
                            <div class="flex items-center gap-2 px-4 py-2 bg-red-50 border border-red-200 rounded-xl text-red-700">
                                <i class="fas fa-clock"></i>
                                <span class="font-semibold">Time's Up!</span>
                            </div>
                        @endif
                    </div>
                    
                    {{-- Enhanced Progress Bar --}}
                    <div class="mt-4">
                        <div class="flex items-center justify-between text-sm text-gray-600 mb-2">
                            <span class="flex items-center gap-2">
                                <i class="fas fa-chart-line text-indigo-500"></i>
                                Progress: {{ $progressPercentage }}%
                            </span>
                            <span class="flex items-center gap-2">
                                <i class="fas fa-check-circle text-emerald-500"></i>
                                Answered: {{ $answeredPercentage }}%
                            </span>
                        </div>
                        <div class="relative">
                            <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                                <div class="bg-gradient-to-r from-indigo-500 to-purple-600 h-full rounded-full transition-all duration-500 ease-out shadow-sm" 
                                     style="width: {{ $progressPercentage }}%"></div>
                            </div>
                            <div class="absolute inset-0 bg-gradient-to-r from-emerald-500/30 to-emerald-600/30 rounded-full transition-all duration-500" 
                                 style="width: {{ $answeredPercentage }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Enhanced Question Content --}}
        @if($currentQuestion)
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-xl border border-white/20 overflow-hidden">
                    {{-- Question Header --}}
                    <div class="bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 p-6 text-white">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <span class="bg-white/20 backdrop-blur-sm px-3 py-1 rounded-full text-sm font-medium">
                                        Question {{ $currentQuestionIndex + 1 }}
                                    </span>
                                    @if($currentQuestion['points'] > 1)
                                        <span class="bg-yellow-400/20 backdrop-blur-sm px-3 py-1 rounded-full text-sm font-medium flex items-center gap-1">
                                            <i class="fas fa-star text-yellow-300"></i>
                                            {{ $currentQuestion['points'] }} points
                                        </span>
                                    @endif
                                </div>
                                <h2 class="text-xl lg:text-2xl font-bold leading-relaxed">
                                    {{ $currentQuestion['question'] }}
                                </h2>
                            </div>
                            <div class="ml-4 flex items-center justify-center w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl">
                                <i class="fas fa-{{ $currentQuestion['type'] === 'multiple_choice' ? 'list-ul' : ($currentQuestion['type'] === 'true_false' ? 'toggle-on' : ($currentQuestion['type'] === 'fun_game' ? 'gamepad' : 'edit')) }} text-xl"></i>
                            </div>
                        </div>
                    </div>

                    {{-- Question Content --}}
                    <div class="p-6 lg:p-8">
                        @if($currentQuestion['type'] === 'multiple_choice')
                            <div class="space-y-3">
                                @foreach($currentQuestion['options'] as $index => $option)
                                    @if(!empty(trim($option)))
                                        <label class="group block {{ $attempt->canEditAnswers() ? 'cursor-pointer' : 'cursor-not-allowed' }} transition-all duration-300">
                                            <div class="flex items-start gap-4 p-4 rounded-2xl border-2 transition-all duration-300 {{ $attempt->canEditAnswers() ? 'border-gray-200 hover:border-indigo-300 hover:bg-indigo-50/50' : 'border-gray-100 bg-gray-50' }}">
                                                <div class="relative flex-shrink-0 mt-1">
                                                    <input type="radio" 
                                                           wire:model.live="answers.{{ $currentQuestion['id'] }}" 
                                                           value="{{ $option }}"
                                                           {{ $attempt->canEditAnswers() ? '' : 'disabled' }}
                                                           class="w-5 h-5 text-indigo-600 border-2 border-gray-300 focus:ring-indigo-500 focus:ring-2 {{ $attempt->canEditAnswers() ? '' : 'opacity-50' }}">
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <span class="text-gray-800 font-medium leading-relaxed {{ $attempt->canEditAnswers() ? '' : 'opacity-50' }}">{{ $option }}</span>
                                                </div>
                                                <div class="flex-shrink-0">
                                                    <div class="w-6 h-6 rounded-full border-2 border-gray-300 flex items-center justify-center group-hover:border-indigo-400 transition-colors">
                                                        <div class="w-2 h-2 rounded-full bg-indigo-500 opacity-0 group-hover:opacity-50 transition-opacity"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </label>
                                    @endif
                                @endforeach
                            </div>
                        
                        @elseif($currentQuestion['type'] === 'true_false')
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <label class="group block {{ $attempt->canEditAnswers() ? 'cursor-pointer' : 'cursor-not-allowed' }} transition-all duration-300">
                                    <div class="flex items-center gap-4 p-6 rounded-2xl border-2 transition-all duration-300 {{ $attempt->canEditAnswers() ? 'border-gray-200 hover:border-emerald-300 hover:bg-emerald-50/50' : 'border-gray-100 bg-gray-50' }}">
                                        <input type="radio" 
                                               wire:model.live="answers.{{ $currentQuestion['id'] }}" 
                                               value="true"
                                               {{ $attempt->canEditAnswers() ? '' : 'disabled' }}
                                               class="w-5 h-5 text-emerald-600 border-2 border-gray-300 focus:ring-emerald-500 focus:ring-2 {{ $attempt->canEditAnswers() ? '' : 'opacity-50' }}">
                                        <div class="flex items-center gap-3">
                                            <i class="fas fa-check-circle text-emerald-500 text-xl"></i>
                                            <span class="text-lg font-semibold text-gray-800 {{ $attempt->canEditAnswers() ? '' : 'opacity-50' }}">True</span>
                                        </div>
                                    </div>
                                </label>
                                <label class="group block {{ $attempt->canEditAnswers() ? 'cursor-pointer' : 'cursor-not-allowed' }} transition-all duration-300">
                                    <div class="flex items-center gap-4 p-6 rounded-2xl border-2 transition-all duration-300 {{ $attempt->canEditAnswers() ? 'border-gray-200 hover:border-red-300 hover:bg-red-50/50' : 'border-gray-100 bg-gray-50' }}">
                                        <input type="radio" 
                                               wire:model.live="answers.{{ $currentQuestion['id'] }}" 
                                               value="false"
                                               {{ $attempt->canEditAnswers() ? '' : 'disabled' }}
                                               class="w-5 h-5 text-red-600 border-2 border-gray-300 focus:ring-red-500 focus:ring-2 {{ $attempt->canEditAnswers() ? '' : 'opacity-50' }}">
                                        <div class="flex items-center gap-3">
                                            <i class="fas fa-times-circle text-red-500 text-xl"></i>
                                            <span class="text-lg font-semibold text-gray-800 {{ $attempt->canEditAnswers() ? '' : 'opacity-50' }}">False</span>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        
                        @elseif($currentQuestion['type'] === 'text')
                            <div class="space-y-4">
                                <div class="relative">
                                    <textarea wire:model.blur="answers.{{ $currentQuestion['id'] }}" 
                                              class="w-full px-4 py-4 border-2 border-gray-200 rounded-2xl focus:outline-none focus:ring-4 focus:ring-indigo-100 focus:border-indigo-400 transition-all duration-300 resize-none {{ $attempt->canEditAnswers() ? 'bg-white' : 'bg-gray-50 opacity-50' }}"
                                              rows="6"
                                              {{ $attempt->canEditAnswers() ? '' : 'readonly' }}
                                              placeholder="{{ $attempt->canEditAnswers() ? 'Type your answer here...' : 'Quiz has been submitted - answers cannot be edited' }}"></textarea>
                                    <div class="absolute bottom-3 right-3 text-xs text-gray-400">
                                        <i class="fas fa-edit"></i>
                                    </div>
                                </div>
                            </div>
                        
                        @elseif($currentQuestion['type'] === 'fun_game')
                            <div class="space-y-6">
                                {{-- Enhanced Game Header --}}
                                <div class="bg-gradient-to-r from-purple-500 via-pink-500 to-red-500 rounded-3xl p-8 text-white relative overflow-hidden">
                                    <div class="absolute inset-0 bg-black/10"></div>
                                    <div class="relative z-10">
                                        <div class="flex items-center justify-between mb-4">
                                            <h3 class="text-2xl lg:text-3xl font-bold">{{ $currentQuestion['game_name'] ?? 'Fun Game Challenge' }}</h3>
                                            <div class="flex items-center gap-2">
                                                <i class="fas fa-gamepad text-3xl"></i>
                                            </div>
                                        </div>
                                        
                                        @if($questionnaire->time_limit)
                                            <div class="flex items-center gap-2 text-white/90">
                                                <i class="fas fa-clock"></i>
                                                <span class="font-medium">Time Limit: {{ $questionnaire->time_limit }} minutes</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Game Images --}}
                                @if(!empty($currentQuestion['images']))
                                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                                        @foreach($currentQuestion['images'] as $image)
                                            @php $imageUrl = asset('storage/' . $image); @endphp
                                            <div class="group relative bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 cursor-pointer" onclick="openImageModal('{{ $imageUrl }}')">
                                                <div class="aspect-w-16 aspect-h-12">
                                                    <img src="{{ $imageUrl }}" 
                                                         alt="Game Image" 
                                                         class="w-full h-48 object-cover group-hover:scale-105 transition-transform duration-300"
                                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                    <div style="display:none;" class="w-full h-48 bg-gray-100 flex items-center justify-center text-gray-500">
                                                        <div class="text-center">
                                                            <i class="fas fa-image text-4xl mb-2"></i>
                                                            <p class="text-sm font-medium">Image not found</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-all duration-300 flex items-center justify-center">
                                                    <div class="opacity-0 group-hover:opacity-100 transition-opacity">
                                                        <i class="fas fa-search-plus text-white text-2xl"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                {{-- Game Instructions --}}
                                @if(!empty($currentQuestion['description']))
                                    <div class="bg-blue-50 border border-blue-200 rounded-2xl p-6">
                                        <h4 class="text-lg font-semibold text-blue-900 mb-3 flex items-center gap-2">
                                            <i class="fas fa-info-circle"></i>
                                            Game Instructions
                                        </h4>
                                        <div class="text-blue-800 leading-relaxed whitespace-pre-line">{{ $currentQuestion['description'] }}</div>
                                    </div>
                                @endif

                                {{-- Enhanced Game Status --}}
                                <div class="bg-gradient-to-br from-gray-50 to-gray-100 border-2 border-dashed border-gray-300 rounded-2xl p-8">
                                    @if(!$this->isGameCompleted($currentQuestion['id']))
                                        <div class="text-center">
                                            <div class="w-20 h-20 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
                                                <i class="fas fa-play text-white text-2xl"></i>
                                            </div>
                                            <h4 class="text-xl font-bold text-gray-900 mb-2">Game in Progress</h4>
                                            <p class="text-gray-600 mb-6 max-w-md mx-auto">Follow the instructions above to complete this challenge!</p>
                                            
                                            @if($attempt->canEditAnswers())
                                                <button wire:click="completeGame({{ $currentQuestion['id'] }})"
                                                        class="inline-flex items-center gap-2 bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white px-8 py-4 rounded-2xl font-semibold shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                                                    <i class="fas fa-flag-checkered"></i>
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
                                        
                                        <div class="text-center">
                                            @if($assessment && $assessment->is_assessed)
                                                <div class="w-20 h-20 bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
                                                    <i class="fas fa-trophy text-white text-2xl"></i>
                                                </div>
                                                <h4 class="text-xl font-bold text-gray-900 mb-4">Game Complete & Assessed!</h4>
                                                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200 max-w-md mx-auto">
                                                    <div class="grid grid-cols-2 gap-4 text-sm mb-4">
                                                        <div class="text-center">
                                                            <div class="text-emerald-600 font-bold text-lg">{{ $assessment->deposit ?? 0 }}</div>
                                                            <div class="text-gray-600">Deposit Points</div>
                                                        </div>
                                                        <div class="text-center">
                                                            <div class="text-red-600 font-bold text-lg">{{ $assessment->penalty ?? 0 }}</div>
                                                            <div class="text-gray-600">Penalty Points</div>
                                                        </div>
                                                    </div>
                                                    <div class="border-t border-gray-200 pt-4">
                                                        <div class="text-center">
                                                            <div class="text-indigo-600 font-bold text-xl">{{ $assessment->total_deposit ?? 0 }}</div>
                                                            <div class="text-gray-600 font-medium">Total Score</div>
                                                        </div>
                                                    </div>
                                                    @if($assessment->notes)
                                                        <div class="mt-4 pt-4 border-t border-gray-200">
                                                            <div class="text-left">
                                                                <p class="text-sm font-medium text-gray-700 mb-1">Assessor Notes:</p>
                                                                <p class="text-sm text-gray-600 bg-gray-50 rounded-lg p-3">{{ $assessment->notes }}</p>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            @else
                                                <div class="w-20 h-20 bg-gradient-to-br from-blue-400 to-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
                                                    <i class="fas fa-clock text-white text-2xl"></i>
                                                </div>
                                                <h4 class="text-xl font-bold text-gray-900 mb-4">Game Completed!</h4>
                                                <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6 max-w-md mx-auto">
                                                    <div class="flex items-center justify-center gap-3 text-amber-800 mb-3">
                                                        <i class="fas fa-hourglass-half"></i>
                                                        <span class="font-semibold">Waiting for assessment...</span>
                                                    </div>
                                                    <p class="text-amber-700 text-sm leading-relaxed">Your game performance will be evaluated by an assessor. Assessment results will show your final score.</p>
                                                    
                                                    @if($assessment && !$assessment->is_assessed)
                                                        <div class="mt-4">
                                                            <a href="{{ route('game.assessment', ['assessmentId' => $assessment->id]) }}" 
                                                               class="inline-flex items-center gap-2 bg-gradient-to-r from-blue-500 to-blue-600 text-white px-6 py-3 rounded-xl font-medium hover:from-blue-600 hover:to-blue-700 transition-all duration-300">
                                                                <i class="fas fa-clipboard-list"></i>
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

                        {{-- Answer Status Indicator --}}
                        @if($this->isQuestionAnswered($currentQuestion['id']))
                            <div class="flex items-center gap-2 text-emerald-600 bg-emerald-50 px-4 py-3 rounded-2xl border border-emerald-200 mt-6">
                                <i class="fas fa-check-circle"></i>
                                <span class="font-medium">{{ $attempt->canEditAnswers() ? 'Answer saved' : 'Answer submitted' }}</span>
                            </div>
                        @endif
                        
                        {{-- Quiz Status Warning --}}
                        @if(!$attempt->canEditAnswers())
                            <div class="flex items-center gap-3 text-amber-700 bg-amber-50 px-4 py-3 rounded-2xl border border-amber-200 mt-6">
                                <i class="fas fa-lock"></i>
                                <span class="font-medium">This quiz has been submitted and cannot be edited. You can review your answers below.</span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Enhanced Navigation Controls --}}
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-8">
                    <button wire:click="goToPreviousQuestion" 
                            class="flex items-center gap-2 px-6 py-3 text-gray-600 hover:text-gray-800 hover:bg-white/50 rounded-2xl transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed backdrop-blur-sm"
                            {{ !$this->canGoPrevious() || !$attempt->canEditAnswers() ? 'disabled' : '' }}>
                        <i class="fas fa-chevron-left"></i>
                        <span class="font-medium">Previous</span>
                    </button>

                    {{-- Enhanced Question Navigator --}}
                    <div class="flex items-center gap-2 flex-wrap justify-center">
                        @foreach($questions as $index => $question)
                            <button wire:click="goToQuestion({{ $index }})"
                                    class="w-10 h-10 text-sm font-bold rounded-xl transition-all duration-300 {{ !$attempt->canEditAnswers() ? 'cursor-not-allowed' : '' }}
                                           {{ $index === $currentQuestionIndex 
                                              ? 'bg-gradient-to-br from-indigo-500 to-purple-600 text-white shadow-lg scale-110' 
                                              : ($this->isQuestionAnswered($question['id']) 
                                                 ? 'bg-gradient-to-br from-emerald-400 to-emerald-500 text-white hover:scale-105 shadow-md' 
                                                 : 'bg-white/70 text-gray-600 hover:bg-white hover:scale-105 shadow-sm border border-gray-200') }}"
                                {{ $index + 1 }}
                            </button>
                        @endforeach
                    </div>

                    <div class="flex gap-3">
                        @if(!$this->isLastQuestion())
                            <button wire:click="goToNextQuestion" 
                                    class="flex items-center gap-2 px-8 py-3 bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-2xl font-semibold hover:from-indigo-600 hover:to-purple-700 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg hover:shadow-xl transform hover:scale-105"
                                    {{ !$this->canGoNext() || !$attempt->canEditAnswers() ? 'disabled' : '' }}>
                                <span>Next</span>
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        @else
                            @php
                                $isFunGameOnly = collect($questions)->every(fn($q) => $q['type'] === 'fun_game');
                            @endphp
                            
                            @if($isFunGameOnly)
                                @php
                                    $allGamesCompleted = collect($questions)->every(fn($q) => $q['type'] !== 'fun_game' || $this->isGameCompleted($q['id']));
                                @endphp
                                
                                @if($allGamesCompleted)
                                    <div class="flex items-center gap-2 px-8 py-3 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-2xl font-semibold shadow-lg">
                                        <i class="fas fa-check-circle"></i>
                                        <span>All Games Completed</span>
                                    </div>
                                @else
                                    <div class="flex items-center gap-2 px-8 py-3 bg-gradient-to-r from-amber-500 to-amber-600 text-white rounded-2xl font-semibold shadow-lg">
                                        <i class="fas fa-gamepad"></i>
                                        <span>Complete All Games</span>
                                    </div>
                                @endif
                            @else
                                @if($attempt->canSubmit())
                                    <button wire:click="submitQuiz"
                                            wire:confirm="Are you sure you want to submit your quiz? This action cannot be undone."
                                            class="flex items-center gap-2 px-8 py-3 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-2xl font-semibold hover:from-emerald-600 hover:to-emerald-700 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105">
                                        <i class="fas fa-check"></i>
                                        <span>Submit Quiz</span>
                                    </button>
                                @else
                                    <div class="flex items-center gap-2 px-8 py-3 bg-gray-400 text-white rounded-2xl font-semibold cursor-not-allowed shadow-lg">
                                        <i class="fas fa-check-circle"></i>
                                        <span>Quiz Submitted</span>
                                    </div>
                                @endif
                            @endif
                        @endif
                    </div>
                </div>

                {{-- Legend --}}
                <div class="flex items-center justify-center gap-6 mt-6 text-xs text-gray-600">
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg"></div>
                        <span>Current</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 bg-gradient-to-br from-emerald-400 to-emerald-500 rounded-lg"></div>
                        <span>Answered</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 bg-white border-2 border-gray-200 rounded-lg"></div>
                        <span>Unanswered</span>
                    </div>
                </div>
            </div>
        @endif

    @else
        {{-- Enhanced Quiz Completion Message --}}
        <div class="min-h-screen flex items-center justify-center p-4">
            <div class="max-w-md w-full bg-white/80 backdrop-blur-xl rounded-3xl shadow-xl border border-white/20 p-8 text-center">
                <div class="w-20 h-20 bg-gradient-to-br from-blue-400 to-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg">
                    <i class="fas fa-check-circle text-white text-2xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-4">Quiz Complete!</h2>
                <p class="text-gray-600 mb-8">Redirecting to results page...</p>

                <div class="flex flex-col gap-3">
                    <a href="{{ route('quiz.results', ['attemptId' => $attempt->id]) }}" 
                       class="inline-flex items-center justify-center gap-2 bg-gradient-to-r from-blue-500 to-blue-600 text-white px-6 py-3 rounded-2xl font-semibold hover:from-blue-600 hover:to-blue-700 transition-all duration-300 shadow-lg">
                        <i class="fas fa-chart-bar"></i>
                        View Results
                    </a>
                    <button wire:click="goToQuizList" 
                            class="inline-flex items-center justify-center gap-2 bg-white text-gray-700 px-6 py-3 rounded-2xl font-semibold hover:bg-gray-50 transition-all duration-300 shadow-md border border-gray-200">
                        <i class="fas fa-list"></i>
                        Back to Quiz List
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Enhanced Success/Error Messages --}}
    @if(session()->has('success'))
        <div class="fixed top-4 right-4 bg-white/90 backdrop-blur-xl border border-emerald-200 rounded-2xl p-4 z-50 shadow-xl" 
             x-data="{ show: true }" 
             x-show="show" 
             x-init="setTimeout(() => show = false, 5000)"
             x-transition>
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-emerald-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-check-circle text-emerald-600"></i>
                </div>
                <p class="text-sm text-emerald-800 font-medium">{{ session('success') }}</p>
                <button @click="show = false" class="ml-2 text-emerald-600 hover:text-emerald-800">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    @endif

    @if(session()->has('error'))
        <div class="fixed top-4 right-4 bg-white/90 backdrop-blur-xl border border-red-200 rounded-2xl p-4 z-50 shadow-xl" 
             x-data="{ show: true }" 
             x-show="show" 
             x-init="setTimeout(() => show = false, 5000)"
             x-transition>
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-red-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-exclamation-circle text-red-600"></i>
                </div>
                <p class="text-sm text-red-800 font-medium">{{ session('error') }}</p>
                <button @click="show = false" class="ml-2 text-red-600 hover:text-red-800">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    @endif

    {{-- Enhanced Loading Overlay --}}
    <div wire:loading.flex class="fixed inset-0 bg-black/20 backdrop-blur-sm z-50 items-center justify-center">
        <div class="bg-white/90 backdrop-blur-xl rounded-3xl p-8 text-center shadow-2xl border border-white/20">
            <div class="w-12 h-12 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin mx-auto mb-4"></div>
            <p class="text-gray-700 font-medium">Processing...</p>
        </div>
    </div>

    {{-- Enhanced Image Modal for Fun Games --}}
    <div id="imageModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden" style="display: none;">
        <div class="flex items-center justify-center min-h-screen p-4" onclick="closeImageModal()">
            <div class="relative max-w-5xl max-h-full" onclick="event.stopPropagation()">
                <img id="modalImage" src="" alt="Game Image" class="max-w-full max-h-full object-contain rounded-3xl shadow-2xl">
                <button onclick="closeImageModal()" class="absolute -top-4 -right-4 bg-white text-gray-700 w-10 h-10 rounded-full flex items-center justify-center hover:bg-gray-100 transition-all duration-300 shadow-lg">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Enhanced timer and navigation scripts (same as original)
@php
    $timerStartTime = isset($attempt) && $attempt && $attempt->started_at ? $attempt->started_at->getTimestamp() : null;
    $timerLimitSeconds = isset($questionnaire) && $questionnaire->time_limit ? $questionnaire->time_limit * 60 : null;
    $timerCompleted = isset($isCompleted) ? $isCompleted : false;
@endphp

window.quizStartTime = {!! json_encode($timerStartTime) !!};
window.timeLimitSeconds = {!! json_encode($timerLimitSeconds) !!};
window.isQuizCompleted = {!! json_encode($timerCompleted) !!};
window.isQuizContinued = {!! json_encode(session()->has('quiz_continued')) !!};

// Enhanced notification for quiz continuation
if (window.isQuizContinued) {
    document.addEventListener('DOMContentLoaded', function() {
        const notification = document.createElement('div');
        notification.className = 'quiz-continued-notification-enhanced';
        notification.innerHTML = `
            <div class='flex items-center gap-4 bg-white/90 backdrop-blur-xl rounded-2xl p-4 shadow-xl border border-emerald-200'>
                <div class='w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center'>
                    <i class='fas fa-play-circle text-emerald-600'></i>
                </div>
                <div>
                    <div class='font-bold text-emerald-900'>Quiz Continued</div>
                    <div class='text-sm text-emerald-700'>Resuming from where you left off</div>
                </div>
            </div>
        `;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 4000);
    });
}

// Enhanced image modal functions
window.openImageModal = function(imageSrc) {
    const modal = document.getElementById('imageModal');
    const modalImage = document.getElementById('modalImage');
    if (modal && modalImage) {
        modalImage.src = imageSrc;
        modal.classList.remove('hidden');
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
}

window.closeImageModal = function() {
    const modal = document.getElementById('imageModal');
    if (modal) {
        modal.classList.add('hidden');
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

// Same navigation prevention and other scripts as original
// (Copy the rest of the JavaScript from the original file)
</script>
@endpush

@push('styles')
<style>
    /* Enhanced Quiz Styles */
    .timer-container-enhanced {
        min-width: 300px;
    }
    
    .timer-widget-enhanced {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        border-radius: 1rem;
        padding: 1rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        border: 2px solid transparent;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .timer-normal {
        border-color: rgba(59, 130, 246, 0.3);
        background: linear-gradient(135deg, rgba(219, 234, 254, 0.8) 0%, rgba(255, 255, 255, 0.95) 100%);
    }
    
    .timer-warning {
        border-color: rgba(245, 158, 11, 0.4);
        background: linear-gradient(135deg, rgba(254, 243, 199, 0.8) 0%, rgba(255, 255, 255, 0.95) 100%);
        animation: gentle-pulse 2s infinite;
    }
    
    .timer-critical {
        border-color: rgba(239, 68, 68, 0.4);
        background: linear-gradient(135deg, rgba(254, 202, 202, 0.8) 0%, rgba(255, 255, 255, 0.95) 100%);
        animation: urgent-pulse 1s infinite;
    }
    
    .timer-display-enhanced {
        font-size: 1.5rem;
        font-weight: 700;
        font-family: 'SF Mono', 'Monaco', monospace;
        color: #374151;
    }
    
    .timer-status-enhanced {
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #6b7280;
    }
    
    .timer-percentage-enhanced {
        font-size: 0.875rem;
        font-weight: 700;
        color: #4b5563;
    }
    
    .timer-progress-enhanced {
        width: 100%;
        height: 0.5rem;
        background: rgba(0, 0, 0, 0.1);
        border-radius: 0.25rem;
        overflow: hidden;
        margin-top: 0.75rem;
    }
    
    .timer-progress-bar-enhanced {
        height: 100%;
        border-radius: 0.25rem;
        transition: width 0.3s ease;
    }
    
    .timer-alert-notification {
        position: fixed;
        top: 1rem;
        right: 1rem;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        padding: 1rem;
        border-radius: 1rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        z-index: 1000;
        animation: slide-in-right 0.3s ease-out;
        border: 1px solid rgba(245, 158, 11, 0.2);
        color: #92400e;
        font-weight: 600;
    }
    
    .quiz-continued-notification-enhanced {
        position: fixed;
        top: 1rem;
        left: 50%;
        transform: translateX(-50%);
        z-index: 1000;
        animation: slide-down 0.5s ease-out;
    }
    
    @keyframes gentle-pulse {
        0%, 100% { 
            transform: scale(1);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        50% { 
            transform: scale(1.01);
            box-shadow: 0 15px 35px rgba(245, 158, 11, 0.15);
        }
    }
    
    @keyframes urgent-pulse {
        0%, 100% { 
            transform: scale(1);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        50% { 
            transform: scale(1.02);
            box-shadow: 0 20px 40px rgba(239, 68, 68, 0.2);
        }
    }
    
    @keyframes slide-in-right {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slide-down {
        from {
            transform: translateX(-50%) translateY(-100%);
            opacity: 0;
        }
        to {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
        }
    }
    
    /* Responsive design enhancements */
    @media (max-width: 768px) {
        .timer-container-enhanced {
            min-width: 250px;
        }
        
        .timer-display-enhanced {
            font-size: 1.25rem;
        }
        
        .timer-widget-enhanced {
            padding: 0.75rem;
        }
    }
    
    /* Enhanced scrollbar */
    ::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    
    ::-webkit-scrollbar-track {
        background: rgba(0, 0, 0, 0.05);
        border-radius: 3px;
    }
    
    ::-webkit-scrollbar-thumb {
        background: rgba(0, 0, 0, 0.2);
        border-radius: 3px;
    }
    
    ::-webkit-scrollbar-thumb:hover {
        background: rgba(0, 0, 0, 0.3);
    }
</style>
@endpush