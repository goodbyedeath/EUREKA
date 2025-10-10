<div class="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-purple-50">
    {{-- Enhanced Floating Background Elements --}}
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -right-40 w-80 h-80 bg-gradient-to-br from-blue-200/30 to-purple-200/30 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-gradient-to-br from-purple-200/30 to-pink-200/30 rounded-full blur-3xl animate-pulse" style="animation-delay: 2s;"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-gradient-to-br from-indigo-200/20 to-blue-200/20 rounded-full blur-3xl animate-pulse" style="animation-delay: 4s;"></div>
    </div>

    @if(!$isCompleted)
        {{-- Enhanced Quiz Header --}}
        <div class="sticky top-0 z-40 backdrop-blur-xl bg-white/90 border-b border-white/30 shadow-2xl">
            <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8">
                <div class="py-3 sm:py-4">
                    {{-- Header Content --}}
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
                        {{-- Quiz Info --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start gap-3 sm:gap-4">
                                <div class="w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg flex-shrink-0">
                                    <i class="fas fa-brain text-white text-lg sm:text-xl"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h1 class="text-lg sm:text-xl lg:text-2xl font-bold text-gray-900 truncate">{{ $questionnaire->title }}</h1>
                                    <div class="flex flex-wrap items-center gap-2 sm:gap-3 mt-1 text-xs sm:text-sm text-gray-600">
                                        <span class="flex items-center gap-1 bg-indigo-50 px-2 py-1 rounded-full">
                                            <i class="fas fa-question-circle text-indigo-500"></i>
                                            <span class="font-medium">{{ $currentQuestionIndex + 1 }}/{{ count($questions) }}</span>
                                        </span>
                                        @if($questionnaire->time_limit)
                                            <span class="flex items-center gap-1 bg-purple-50 px-2 py-1 rounded-full">
                                                <i class="fas fa-clock text-purple-500"></i>
                                                <span class="font-medium">{{ $questionnaire->time_limit }}min</span>
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
                            <div id="quiz-timer-container" class="timer-container-enhanced flex-shrink-0 w-full sm:w-auto mt-3 sm:mt-0 timer-normal" data-quiz-timer style="display: none;">
                                <div class="timer-widget-enhanced">
                                    {{-- Timer Display --}}
                                    <div class="flex items-center gap-3 mb-3">
                                        <div class="timer-icon-container">
                                            <i id="quiz-timer-icon" class="fas fa-stopwatch text-blue-500 text-lg"></i>
                                        </div>
                                        <div class="flex-1">
                                            <div id="quiz-timer-display" class="timer-display-enhanced">{{ sprintf("%d:%02d", floor($safeTimeRemaining / 60), $safeTimeRemaining % 60) }}</div>
                                            <div id="quiz-timer-status" class="timer-status-enhanced">Active</div>
                                        </div>
                                        <div id="quiz-timer-percentage" class="timer-percentage-enhanced">100%</div>
                                    </div>
                                    
                                    {{-- Progress Bar --}}
                                    <div class="timer-progress-enhanced">
                                        <div id="quiz-timer-progress" class="timer-progress-bar-enhanced bg-gradient-to-r from-blue-500 to-blue-600" style="width: 100%;"></div>
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
                    <div class="mt-3 sm:mt-4">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 text-xs sm:text-sm text-gray-600 mb-2">
                            <span class="flex items-center gap-2">
                                <i class="fas fa-chart-line text-indigo-500"></i>
                                <span class="font-medium">Progress: {{ $progressPercentage }}%</span>
                            </span>
                            <span class="flex items-center gap-2">
                                <i class="fas fa-check-circle text-emerald-500"></i>
                                <span class="font-medium">Answered: {{ $answeredPercentage }}%</span>
                            </span>
                        </div>
                        <div class="relative">
                            <div class="w-full bg-gray-200 rounded-full h-2 sm:h-3 overflow-hidden">
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
            <div class="max-w-6xl mx-auto px-3 sm:px-6 lg:px-8 py-4 sm:py-6 lg:py-8">
                <div class="bg-white/90 backdrop-blur-xl rounded-2xl sm:rounded-3xl shadow-2xl border border-white/30 overflow-hidden">
                    {{-- Question Header --}}
                    <div class="bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 p-4 sm:p-6 text-white">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-2 sm:gap-3 mb-3">
                                    <span class="bg-white/20 backdrop-blur-sm px-3 py-1 rounded-full text-sm font-medium">
                                        Q{{ $currentQuestionIndex + 1 }}
                                    </span>
                                    @if($currentQuestion['points'] > 1 && $currentQuestion['type'] !== 'brief')
                                        <span class="bg-yellow-400/20 backdrop-blur-sm px-3 py-1 rounded-full text-sm font-medium flex items-center gap-1">
                                            <i class="fas fa-star text-yellow-300"></i>
                                            {{ $currentQuestion['points'] }}pts
                                        </span>
                                    @endif
                                    @if($currentQuestion['type'] === 'brief')
                                        <span class="bg-cyan-400/20 backdrop-blur-sm px-3 py-1 rounded-full text-sm font-medium flex items-center gap-1">
                                            <i class="fas fa-comment text-cyan-300"></i>
                                            Feedback
                                        </span>
                                    @endif
                                    <span class="bg-white/10 backdrop-blur-sm px-3 py-1 rounded-full text-xs font-medium">
                                        {{ ucfirst(str_replace('_', ' ', $currentQuestion['type'])) }}
                                    </span>
                                </div>
                                <h2 class="text-lg sm:text-xl lg:text-2xl font-bold leading-relaxed break-words">
                                    {{ $currentQuestion['question'] }}
                                </h2>
                            </div>
                            <div class="flex-shrink-0 w-10 h-10 sm:w-12 sm:h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                                <i class="fas fa-{{ $currentQuestion['type'] === 'multiple_choice' ? 'list-ul' : ($currentQuestion['type'] === 'true_false' ? 'toggle-on' : ($currentQuestion['type'] === 'fun_game' ? 'gamepad' : ($currentQuestion['type'] === 'brief' ? 'comment-dots' : 'edit'))) }} text-lg sm:text-xl"></i>
                            </div>
                        </div>
                    </div>

                    {{-- Question Content --}}
                    <div class="p-4 sm:p-6 lg:p-8">
                        @if($currentQuestion['type'] === 'multiple_choice')
                            <div class="space-y-3">
                                @foreach($currentQuestion['options'] as $index => $option)
                                    @if(!empty(trim($option)))
                                        <label class="group block {{ $attempt->canEditAnswers() ? 'cursor-pointer' : 'cursor-not-allowed' }} transition-all duration-300">
                                            <div class="flex items-start gap-3 sm:gap-4 p-3 sm:p-4 rounded-xl sm:rounded-2xl border-2 transition-all duration-300 {{ $attempt->canEditAnswers() ? 'border-gray-200 hover:border-indigo-300 hover:bg-indigo-50/50 active:scale-[0.98]' : 'border-gray-100 bg-gray-50' }}">
                                                <div class="relative flex-shrink-0 mt-0.5 sm:mt-1">
                                                    <input type="radio" 
                                                           wire:model.live="answers.{{ $currentQuestion['id'] }}" 
                                                           value="{{ $option }}"
                                                           {{ $attempt->canEditAnswers() ? '' : 'disabled' }}
                                                           class="w-4 h-4 sm:w-5 sm:h-5 text-indigo-600 border-2 border-gray-300 focus:ring-indigo-500 focus:ring-2 {{ $attempt->canEditAnswers() ? '' : 'opacity-50' }}">
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <span class="text-sm sm:text-base text-gray-800 font-medium leading-relaxed {{ $attempt->canEditAnswers() ? '' : 'opacity-50' }} break-words">{{ $option }}</span>
                                                </div>
                                                <div class="flex-shrink-0">
                                                    <div class="w-5 h-5 sm:w-6 sm:h-6 rounded-full border-2 border-gray-300 flex items-center justify-center group-hover:border-indigo-400 transition-colors">
                                                        <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full bg-indigo-500 opacity-0 group-hover:opacity-50 transition-opacity"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </label>
                                    @endif
                                @endforeach
                            </div>
                        
                        @elseif($currentQuestion['type'] === 'true_false')
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                                <label class="group block {{ $attempt->canEditAnswers() ? 'cursor-pointer' : 'cursor-not-allowed' }} transition-all duration-300">
                                    <div class="flex items-center gap-3 sm:gap-4 p-4 sm:p-6 rounded-xl sm:rounded-2xl border-2 transition-all duration-300 {{ $attempt->canEditAnswers() ? 'border-gray-200 hover:border-emerald-300 hover:bg-emerald-50/50 active:scale-[0.98]' : 'border-gray-100 bg-gray-50' }}">
                                        <input type="radio" 
                                               wire:model.live="answers.{{ $currentQuestion['id'] }}" 
                                               value="true"
                                               {{ $attempt->canEditAnswers() ? '' : 'disabled' }}
                                               class="w-4 h-4 sm:w-5 sm:h-5 text-emerald-600 border-2 border-gray-300 focus:ring-emerald-500 focus:ring-2 {{ $attempt->canEditAnswers() ? '' : 'opacity-50' }}">
                                        <div class="flex items-center gap-2 sm:gap-3">
                                            <i class="fas fa-check-circle text-emerald-500 text-lg sm:text-xl"></i>
                                            <span class="text-base sm:text-lg font-semibold text-gray-800 {{ $attempt->canEditAnswers() ? '' : 'opacity-50' }}">True</span>
                                        </div>
                                    </div>
                                </label>
                                <label class="group block {{ $attempt->canEditAnswers() ? 'cursor-pointer' : 'cursor-not-allowed' }} transition-all duration-300">
                                    <div class="flex items-center gap-3 sm:gap-4 p-4 sm:p-6 rounded-xl sm:rounded-2xl border-2 transition-all duration-300 {{ $attempt->canEditAnswers() ? 'border-gray-200 hover:border-red-300 hover:bg-red-50/50 active:scale-[0.98]' : 'border-gray-100 bg-gray-50' }}">
                                        <input type="radio" 
                                               wire:model.live="answers.{{ $currentQuestion['id'] }}" 
                                               value="false"
                                               {{ $attempt->canEditAnswers() ? '' : 'disabled' }}
                                               class="w-4 h-4 sm:w-5 sm:h-5 text-red-600 border-2 border-gray-300 focus:ring-red-500 focus:ring-2 {{ $attempt->canEditAnswers() ? '' : 'opacity-50' }}">
                                        <div class="flex items-center gap-2 sm:gap-3">
                                            <i class="fas fa-times-circle text-red-500 text-lg sm:text-xl"></i>
                                            <span class="text-base sm:text-lg font-semibold text-gray-800 {{ $attempt->canEditAnswers() ? '' : 'opacity-50' }}">False</span>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        
                        @elseif($currentQuestion['type'] === 'text')
                            <div class="space-y-4">
                                <div class="relative">
                                    <textarea wire:model.live.debounce.500ms="answers.{{ $currentQuestion['id'] }}" 
                                              wire:blur="saveTextAnswer({{ $currentQuestion['id'] }}, $event.target.value)"
                                              class="w-full px-3 sm:px-4 py-3 sm:py-4 border-2 border-gray-200 rounded-xl sm:rounded-2xl focus:outline-none focus:ring-4 focus:ring-indigo-100 focus:border-indigo-400 transition-all duration-300 resize-none text-sm sm:text-base {{ $attempt->canEditAnswers() ? 'bg-white' : 'bg-gray-50 opacity-50' }}"
                                              rows="4"
                                              {{ $attempt->canEditAnswers() ? '' : 'readonly' }}
                                              placeholder="{{ $attempt->canEditAnswers() ? 'Type your answer here...' : 'Quiz has been submitted - answers cannot be edited' }}"></textarea>
                                    <div class="absolute bottom-2 sm:bottom-3 right-2 sm:right-3 text-xs text-gray-400">
                                        <i class="fas fa-edit"></i>
                                    </div>
                                </div>
                            </div>
                        
                        @elseif($currentQuestion['type'] === 'brief')
                            <div class="space-y-4">
                                {{-- Brief Question Description --}}
                                @if(!empty($currentQuestion['description']))
                                    <div class="bg-cyan-50 border border-cyan-200 rounded-2xl p-6">
                                        <h4 class="text-lg font-semibold text-cyan-900 mb-3 flex items-center gap-2">
                                            <i class="fas fa-info-circle"></i>
                                            Feedback Context
                                        </h4>
                                        <div class="text-cyan-800 leading-relaxed whitespace-pre-line">{{ $currentQuestion['description'] }}</div>
                                    </div>
                                @endif

                                {{-- Brief Question Notice --}}
                                <div class="bg-blue-50 border border-blue-200 rounded-2xl p-4 mb-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center flex-shrink-0">
                                            <i class="fas fa-comment-dots text-blue-600 text-lg"></i>
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-semibold text-blue-900 mb-1">Feedback Question</h4>
                                            <p class="text-xs text-blue-700">This is a feedback question and will not affect your score. Please share your honest thoughts and experiences.</p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Brief Question Answer Area --}}
                                <div class="relative">
                                    <textarea wire:model.live.debounce.500ms="answers.{{ $currentQuestion['id'] }}" 
                                              wire:blur="saveTextAnswer({{ $currentQuestion['id'] }}, $event.target.value)"
                                              class="w-full px-3 sm:px-4 py-3 sm:py-4 border-2 border-gray-200 rounded-xl sm:rounded-2xl focus:outline-none focus:ring-4 focus:ring-cyan-100 focus:border-cyan-400 transition-all duration-300 resize-none text-sm sm:text-base {{ $attempt->canEditAnswers() ? 'bg-white' : 'bg-gray-50 opacity-50' }}"
                                              rows="6"
                                              {{ $attempt->canEditAnswers() ? '' : 'readonly' }}
                                              placeholder="{{ $attempt->canEditAnswers() ? 'Share your thoughts, experiences, or feedback here... (Optional)' : 'Quiz has been submitted - answers cannot be edited' }}"></textarea>
                                    <div class="absolute bottom-2 sm:bottom-3 right-2 sm:right-3 flex items-center gap-2 text-xs text-gray-400">
                                        <span>Optional</span>
                                        <i class="fas fa-comment"></i>
                                    </div>
                                </div>

                                {{-- Character count helper --}}
                                @if($attempt->canEditAnswers())
                                    <div class="flex justify-between items-center text-xs text-gray-500">
                                        <span>This feedback helps improve the experience for everyone</span>
                                        <span>Max 2000 characters</span>
                                    </div>
                                @endif
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
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                                        @foreach($currentQuestion['images'] as $image)
                                            @php $imageUrl = asset('storage/' . $image); @endphp
                                            <div class="group relative bg-white rounded-xl sm:rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 cursor-pointer" onclick="openImageModal('{{ $imageUrl }}')">
                                                <div class="aspect-w-16 aspect-h-12">
                                                    <img src="{{ $imageUrl }}" 
                                                         alt="Game Image" 
                                                         class="w-full h-40 sm:h-48 object-cover group-hover:scale-105 transition-transform duration-300"
                                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                    <div style="display:none;" class="w-full h-40 sm:h-48 bg-gray-100 flex items-center justify-center text-gray-500">
                                                        <div class="text-center">
                                                            <i class="fas fa-image text-3xl sm:text-4xl mb-2"></i>
                                                            <p class="text-xs sm:text-sm font-medium">Image not found</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-all duration-300 flex items-center justify-center">
                                                    <div class="opacity-0 group-hover:opacity-100 transition-opacity">
                                                        <i class="fas fa-search-plus text-white text-xl sm:text-2xl"></i>
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
                                                    @php
                                                        // Check if there are unanswered standard questions
                                                        $hasUnansweredStandardQuestions = false;
                                                        foreach ($questions as $q) {
                                                            if ($q['type'] !== 'fun_game' && !$this->isQuestionAnswered($q['id'])) {
                                                                $hasUnansweredStandardQuestions = true;
                                                                break;
                                                            }
                                                        }
                                                    @endphp
                                                    
                                                    @if($hasUnansweredStandardQuestions)
                                                        <div class="flex items-center justify-center gap-3 text-amber-800 mb-3">
                                                            <i class="fas fa-tasks"></i>
                                                            <span class="font-semibold">Complete remaining questions first</span>
                                                        </div>
                                                        <p class="text-amber-700 text-sm leading-relaxed">Assessment will be available after you finish all quiz questions. Continue with the remaining questions to unlock the assessment.</p>
                                                    @else
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
                <div class="flex flex-col lg:flex-row items-center justify-between gap-4 mt-6 sm:mt-8">
                    <button wire:click="goToPreviousQuestion" 
                            class="flex items-center gap-2 px-4 sm:px-6 py-2 sm:py-3 text-gray-600 hover:text-gray-800 hover:bg-white/50 rounded-xl sm:rounded-2xl transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed backdrop-blur-sm text-sm sm:text-base order-2 lg:order-1"
                            {{ !$this->canGoPrevious() || !$attempt->canEditAnswers() ? 'disabled' : '' }}>
                        <i class="fas fa-chevron-left"></i>
                        <span class="font-medium">Previous</span>
                    </button>

                    {{-- Enhanced Question Navigator --}}
                    <div class="flex items-center gap-1 sm:gap-2 flex-wrap justify-center max-w-full overflow-x-auto pb-2 order-1 lg:order-2">
                        @foreach($questions as $index => $question)
                            <button wire:click="goToQuestion({{ $index }})"
                                    class="w-8 h-8 sm:w-10 sm:h-10 text-xs sm:text-sm font-bold rounded-lg sm:rounded-xl transition-all duration-300 flex-shrink-0 {{ !$attempt->canEditAnswers() ? 'cursor-not-allowed' : '' }}
                                           {{ $index === $currentQuestionIndex 
                                              ? 'bg-gradient-to-br from-indigo-500 to-purple-600 text-white shadow-lg scale-110' 
                                              : ($this->isQuestionAnswered($question['id']) 
                                                 ? 'bg-gradient-to-br from-emerald-400 to-emerald-500 text-white hover:scale-105 shadow-md' 
                                                 : 'bg-white/70 text-gray-600 hover:bg-white hover:scale-105 shadow-sm border border-gray-200') }}"
                                {{ $index + 1 }}
                            </button>
                        @endforeach
                    </div>

                    <div class="flex gap-2 sm:gap-3 order-3 lg:order-3">
                        @if(!$this->isLastQuestion())
                            <button wire:click="goToNextQuestion" 
                                    class="flex items-center gap-2 px-4 sm:px-8 py-2 sm:py-3 bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-xl sm:rounded-2xl font-semibold hover:from-indigo-600 hover:to-purple-700 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg hover:shadow-xl transform hover:scale-105 text-sm sm:text-base"
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
                                    <div class="flex items-center gap-2 px-4 sm:px-8 py-2 sm:py-3 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-xl sm:rounded-2xl font-semibold shadow-lg text-sm sm:text-base">
                                        <i class="fas fa-check-circle"></i>
                                        <span class="hidden sm:inline">All Games Completed</span>
                                        <span class="sm:hidden">Complete</span>
                                    </div>
                                @else
                                    <div class="flex items-center gap-2 px-4 sm:px-8 py-2 sm:py-3 bg-gradient-to-r from-amber-500 to-amber-600 text-white rounded-xl sm:rounded-2xl font-semibold shadow-lg text-sm sm:text-base">
                                        <i class="fas fa-gamepad"></i>
                                        <span class="hidden sm:inline">Complete All Games</span>
                                        <span class="sm:hidden">Finish</span>
                                    </div>
                                @endif
                            @else
                                @if($attempt->canSubmit())
                                    <button onclick="handleQuizSubmission()"
                                            class="flex items-center gap-2 px-4 sm:px-8 py-2 sm:py-3 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-xl sm:rounded-2xl font-semibold hover:from-emerald-600 hover:to-emerald-700 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105 text-sm sm:text-base">
                                        <i class="fas fa-camera mr-1"></i>
                                        <i class="fas fa-check"></i>
                                        <span>Take Photo & Submit</span>
                                    </button>
                                @else
                                    <div class="flex items-center gap-2 px-4 sm:px-8 py-2 sm:py-3 bg-gray-400 text-white rounded-xl sm:rounded-2xl font-semibold cursor-not-allowed shadow-lg text-sm sm:text-base">
                                        <i class="fas fa-check-circle"></i>
                                        <span class="hidden sm:inline">Quiz Submitted</span>
                                        <span class="sm:hidden">Submitted</span>
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
window.quizTimeLimitMinutes = {!! json_encode($questionnaire->time_limit ?? 0) !!};
window.quizAttemptId = {!! json_encode($attempt->id ?? 'unknown') !!};

// Temporary manual quiz timer implementation
if (window.location.pathname.includes('/quiz/take/') || document.querySelector('[data-quiz-timer]')) {
    
    const quizTimeLimit = window.quizTimeLimitMinutes || 0;
    const quizStartTime = window.quizStartTime;
    const quizAttemptId = window.quizAttemptId || 'unknown';
    const totalSeconds = quizTimeLimit * 60;
    const isCompleted = window.isQuizCompleted || false;
    const workflowTimersEnabled = {{ \App\Models\FeatureSetting::isEnabled('workflow_timers') ? 'true' : 'false' }};
    
    if (totalSeconds <= 0 || !quizStartTime || isCompleted) {
    } else {
        
        function calculateRemainingTime() {
            // Prefer server-side time if workflow timers are enabled
            if (workflowTimersEnabled && window.Livewire) {
                const component = window.Livewire.find(document.querySelector('[wire\\\\:id]').getAttribute('wire:id'));
                if (component && component.get('timeRemaining') !== null) {
                    return Math.max(0, component.get('timeRemaining'));
                }
            }
            
            // Fallback to client-side calculation
            const currentTime = Math.floor(Date.now() / 1000);
            const elapsedSeconds = currentTime - quizStartTime;
            const remaining = Math.max(0, totalSeconds - elapsedSeconds);
            return remaining;
        }
        
        function updateDisplay() {
            const remainingTime = calculateRemainingTime();
            const minutes = Math.floor(remainingTime / 60);
            const seconds = remainingTime % 60;
            const display = minutes + ':' + seconds.toString().padStart(2, '0');
            
            const timerDisplay = document.getElementById('quiz-timer-display');
            if (timerDisplay) {
                timerDisplay.textContent = display;
            }
            
            const timerContainer = document.getElementById('quiz-timer-container');
            const timerIcon = document.getElementById('quiz-timer-icon');
            const timerStatus = document.getElementById('quiz-timer-status');
            const progressBar = document.getElementById('quiz-timer-progress');
            const progressPercentage = document.getElementById('quiz-timer-percentage');
            
            if (timerContainer && timerIcon && timerStatus) {
                const percentage = totalSeconds > 0 ? Math.round((remainingTime / totalSeconds) * 100) : 0;
                
                if (progressPercentage) {
                    progressPercentage.textContent = percentage + '%';
                }
                
                if (progressBar) {
                    progressBar.style.width = percentage + '%';
                }
                
                timerContainer.className = timerContainer.className.replace(/timer-(normal|warning|critical)/g, '');
                progressBar.className = progressBar.className.replace(/bg-gradient-to-r from-\\w+-\\d+ to-\\w+-\\d+/g, '');
                
                if (remainingTime <= 60 && remainingTime > 0) {
                    timerContainer.classList.add('timer-critical');
                    timerIcon.className = 'fas fa-exclamation-triangle text-red-500 text-lg';
                    timerStatus.textContent = 'CRITICAL!';
                    progressBar.classList.add('bg-gradient-to-r', 'from-red-500', 'to-red-600');
                } else if (remainingTime <= 300 && remainingTime > 0) {
                    timerContainer.classList.add('timer-warning');
                    timerIcon.className = 'fas fa-clock text-amber-500 text-lg';
                    timerStatus.textContent = 'Warning';
                    progressBar.classList.add('bg-gradient-to-r', 'from-amber-500', 'to-amber-600');
                } else {
                    timerContainer.classList.add('timer-normal');
                    timerIcon.className = 'fas fa-stopwatch text-blue-500 text-lg';
                    timerStatus.textContent = 'Active';
                    progressBar.classList.add('bg-gradient-to-r', 'from-blue-500', 'to-blue-600');
                }
            }
            
            
            if (remainingTime <= 0) {
                if (window.Livewire && window.Livewire.find) {
                    const component = window.Livewire.find(document.querySelector('[wire\\\\:id]').getAttribute('wire:id'));
                    if (component) {
                        component.call('handleTimeExpiry');
                    }
                }
                
                if (window.quizTimerInterval) {
                    clearInterval(window.quizTimerInterval);
                }
                return;
            }
        }
        
        const timerContainer = document.getElementById('quiz-timer-container');
        if (timerContainer) {
            timerContainer.style.display = 'block';
        }
        
        updateDisplay();
        window.quizTimerInterval = setInterval(updateDisplay, 1000);
        
        
        // Listen for workflow timer events if enabled
        if (workflowTimersEnabled) {
            window.addEventListener('showTimeWarning', (event) => {
                const data = event.detail;
                if (data && data.message) {
                    // Show browser notification
                    if ('Notification' in window && Notification.permission === 'granted') {
                        new Notification('Quiz Time Warning', {
                            body: data.message,
                            icon: '/favicon.ico'
                        });
                    }
                    
                    // Update display immediately
                    updateDisplay();
                }
            });
            
            // Request notification permission on page load
            if ('Notification' in window && Notification.permission === 'default') {
                Notification.requestPermission();
            }
        }
        
        window.addEventListener('beforeunload', () => {
            if (window.quizTimerInterval) {
                clearInterval(window.quizTimerInterval);
            }
        });
    }
}
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

// Enhanced navigation prevention with proper synchronization
window.quizLocked = {!! json_encode($quizLocked ?? false) !!};
window.isQuizCompleted = {!! json_encode($isCompleted ?? false) !!};
window.canEditAnswers = {!! json_encode($attempt->canEditAnswers() ?? false) !!};

// Create unified navigation blocker function
window.isNavigationBlocked = function() {
    return window.quizLocked && !window.isQuizCompleted && window.canEditAnswers;
};

// Prevent accidental page refresh during quiz
if (!window.beforeUnloadHandler) {
    window.beforeUnloadHandler = function(e) {
        if (window.isNavigationBlocked()) {
            e.preventDefault();
            e.returnValue = 'Quiz in progress! You cannot leave until all questions are completed and submitted.';
            return 'Quiz in progress! You cannot leave until all questions are completed and submitted.';
        }
    };
}

// Add the warning if quiz is active
if (window.isNavigationBlocked()) {
    window.removeEventListener('beforeunload', window.beforeUnloadHandler);
    window.addEventListener('beforeunload', window.beforeUnloadHandler);
}

if (window.isNavigationBlocked()) {
    history.pushState(null, '', location.href);
    
    window.addEventListener('popstate', function(event) {
        if (window.isNavigationBlocked()) {
            history.pushState(null, '', location.href);
            
            if (confirm('Quiz in progress! Are you sure you want to abandon this quiz? Your progress will be lost.')) {
                if (window.Livewire && typeof $wire !== 'undefined') {
                    $wire.call('forceExit');
                }
            }
        }
    });
}

// Block keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (window.isNavigationBlocked()) {
        if (e.altKey && (e.key === 'ArrowLeft' || e.key === 'ArrowRight')) {
            e.preventDefault();
            return false;
        }
        
        if (e.ctrlKey && e.key === 'w' && !e.shiftKey) {
            e.preventDefault();
            return false;
        }
        
        if (e.key.startsWith('F') && e.key.length <= 3) {
            e.preventDefault();
            return false;
        }
    }
});

// Disable right-click context menu during quiz
if (window.isNavigationBlocked()) {
    document.addEventListener('contextmenu', function(e) {
        e.preventDefault();
        return false;
    });
}

// Auto-save functionality removed - text inputs now use dedicated wire:blur handler
// This prevents double-save conflicts between JavaScript and Livewire

// Keyboard shortcuts and prevention
window.quizCompleted = window.isQuizCompleted;

document.addEventListener('keydown', function(e) {
    if (!window.quizCompleted && (e.key === 'F5' || (e.ctrlKey && e.key === 'r'))) {
        e.preventDefault();
        return false;
    }
    
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
    
    if (!window.isQuizCompleted && e.ctrlKey && e.key === 'Enter') {
        e.preventDefault();
        handleQuizSubmission();
    }
});

// Listen for quiz completion
document.addEventListener('livewire:initialized', () => {
    Livewire.on('quizCompleted', () => {
        // Update global state
        window.removeEventListener('beforeunload', window.beforeUnloadHandler);
        window.isQuizCompleted = true;
        window.quizLocked = false;
        window.canEditAnswers = false;
        
        document.removeEventListener('contextmenu', function(e) {
            e.preventDefault();
            return false;
        });
        
        const notification = document.createElement('div');
        notification.className = 'fixed top-4 right-4 bg-emerald-50 border border-emerald-200 rounded-2xl p-4 z-50 shadow-xl';
        notification.innerHTML = `
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-emerald-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-check-circle text-emerald-600"></i>
                </div>
                <span class="text-emerald-800 font-medium">Quiz completed! Navigation unlocked.</span>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 3000);
    });
});

// Enhanced keyboard support for closing modal
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('imageModal');
        if (modal && !modal.classList.contains('hidden')) {
            closeImageModal();
        }
    }
});

// Fun Game Auto-Completion Triggers
window.FunGameTriggers = {
    // Auto-complete game after time limit
    autoCompleteAfterTime: function(questionId, timeSeconds) {
        setTimeout(() => {
            if (window.Livewire && typeof $wire !== 'undefined') {
                $wire.call('autoCompleteGame', questionId, 'time_limit', {
                    time_limit_seconds: timeSeconds,
                    completed_at: new Date().toISOString()
                });
            }
        }, timeSeconds * 1000);
    },

    // Auto-complete game when external condition met
    autoCompleteWhen: function(questionId, conditionFn, triggerType = 'condition', triggerData = {}) {
        const checkCondition = () => {
            if (conditionFn()) {
                if (window.Livewire && typeof $wire !== 'undefined') {
                    $wire.call('autoCompleteGame', questionId, triggerType, triggerData);
                }
                return true;
            }
            return false;
        };
        
        // Check immediately and then every second
        if (!checkCondition()) {
            const interval = setInterval(() => {
                if (checkCondition()) {
                    clearInterval(interval);
                }
            }, 1000);
        }
    },

    // Auto-complete game via external API/event
    autoCompleteViaEvent: function(questionId, eventName) {
        document.addEventListener(eventName, function(event) {
            if (window.Livewire && typeof $wire !== 'undefined') {
                $wire.call('autoCompleteGame', questionId, 'event', {
                    event_name: eventName,
                    event_data: event.detail || {},
                    completed_at: new Date().toISOString()
                });
            }
        }, { once: true }); // Only trigger once
    },

    // Manual trigger for external systems
    triggerCompletion: function(questionId, triggerType = 'external', triggerData = {}) {
        if (window.Livewire && typeof $wire !== 'undefined') {
            $wire.call('autoCompleteGame', questionId, triggerType, triggerData);
        }
    }
};

// Handle quiz submission with photo capture
async function handleQuizSubmission() {
    try {
        // Get the Livewire component instance
        const component = window.Livewire.find('{{ $this->getId() }}');
        if (!component) {
            throw new Error('Livewire component not found');
        }
        
        // Use the camera capture utility to take photo and submit
        await capturePhotoAndSubmit(component, 'submitQuiz');
    } catch (error) {
        console.error('Failed to capture photo and submit quiz:', error);
        
        // Show specific error message if available
        const errorMessage = error && error.message ? error.message : 'Camera capture failed';
        
        // Fallback: submit without photo if camera fails
        if (confirm(`${errorMessage}. Would you like to submit the quiz without a photo?`)) {
            // Try to get component again for fallback
            const component = window.Livewire.find('{{ $this->getId() }}');
            if (component) {
                component.call('submitQuiz');
            } else {
                // Last resort: use global Livewire dispatch
                window.Livewire.dispatch('submitQuiz');
            }
        }
    }
}
</script>
@endpush

@push('styles')
<style>
    /* Enhanced Quiz Styles */
    .timer-container-enhanced {
        min-width: 280px;
        max-width: 100%;
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
            min-width: 240px;
            max-width: 100%;
        }
        
        .timer-display-enhanced {
            font-size: 1.1rem;
        }
        
        .timer-widget-enhanced {
            padding: 0.75rem;
        }
        
        .timer-percentage-enhanced {
            font-size: 0.75rem;
        }
        
        .timer-status-enhanced {
            font-size: 0.625rem;
        }
    }
    
    @media (max-width: 640px) {
        .timer-container-enhanced {
            min-width: 200px;
        }
        
        .timer-display-enhanced {
            font-size: 1rem;
        }
        
        .timer-widget-enhanced {
            padding: 0.5rem;
        }
    }
    
    /* Touch-friendly hover states for mobile */
    @media (hover: none) and (pointer: coarse) {
        .group:hover .group-hover\:opacity-100 {
            opacity: 1;
        }
        
        .group:hover .group-hover\:scale-105 {
            transform: scale(1.05);
        }
        
        .hover\:scale-105:hover {
            transform: scale(1.05);
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