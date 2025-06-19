<div>

    {{-- QR Code Status Info --}}
    @if($scannedQr_code)
        <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fas fa-qrcode text-blue-600 mr-3"></i>
                    <div>
                        <h3 class="text-sm font-medium text-blue-800">QR Code Scanned</h3>
                        <p class="text-xs text-blue-600">QR Code: {{ $scannedQr_code }}</p>
                    </div>
                </div>
                <button wire:click="clearScannedQuiz" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                    <i class="fas fa-times mr-1"></i>Clear
                </button>
            </div>
        </div>
    @endif

    {{-- DEBUG SECTION - Always visible --}}
    <div class="mb-4 p-4 bg-yellow-100 border border-yellow-300 rounded">
        <h4 class="font-bold text-yellow-800 mb-2">DEBUG INFO</h4>
        <div class="text-xs text-yellow-800">
            Current User ID: {{ Auth::id() }}<br>
            Scanned QR Code: {{ $scannedQr_code ?? 'None' }}<br>
            Recent Attempts Count: {{ $this->recentAttempts ? $this->recentAttempts->count() : 'NULL' }}<br>
            @if($this->recentAttempts && $this->recentAttempts->count() > 0)
                Attempts: 
                @foreach($this->recentAttempts as $attempt)
                    [Q{{ $attempt->questionnaire_id }}:{{ $attempt->status }}:U{{ $attempt->user_id }}] 
                @endforeach
            @else
                NO ATTEMPTS FOUND
            @endif
            <br>
            <button wire:click="debugQuizData(1)" class="mt-2 bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-xs">
                Debug Quiz Data (ID: 1)
            </button>
        </div>
    </div>

    {{-- Available Quizzes Grid --}}
    @if($scannedQr_code)

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($availableQuestionnaires as $questionnaire)
            @php
                $quizStats = $this->getUserQuizStats($questionnaire->id);
                $hasCompleted = $quizStats['completed'];
                $hasInProgress = $quizStats['in_progress'];
                $hasAbandoned = $quizStats['abandoned'];
                $canTakeQuiz = $this->canUserTakeQuiz($questionnaire);
            @endphp
            
            <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow p-6 border-l-4 border-blue-500">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-blue-100 rounded-full">
                        <i class="fas fa-clipboard-list text-blue-600 text-xl"></i>
                    </div>
                    <div class="flex flex-col items-end space-y-1">
                        @if($questionnaire->isAvailable())
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Available
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                Unavailable
                            </span>
                        @endif
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            <i class="fas fa-qrcode mr-1"></i>QR Unlocked
                        </span>
                    </div>
                </div>
                
                <h3 class="text-lg font-medium text-gray-900 mb-2">{{ $questionnaire->title }}</h3>
                <p class="text-sm text-gray-600 mb-4">{{ $questionnaire->description }}</p>
                
                <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
                    @if($questionnaire->time_limit)
                        <span><i class="fas fa-clock mr-1"></i>{{ $questionnaire->time_limit }} min</span>
                    @else
                        <span><i class="fas fa-clock mr-1"></i>No time limit</span>
                    @endif
                    <span><i class="fas fa-question-circle mr-1"></i>{{ $questionnaire->questions_count ?? $questionnaire->questions->count() }} questions</span>
                </div>

                {{-- Quiz Statistics --}}
                @if($quizStats['attempts'] > 0)
                    <div class="mb-4 p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center justify-between text-xs text-gray-600 mb-1">
                            <span>Attempts: {{ $quizStats['attempts'] }}</span>
                            @if($questionnaire->max_attempts)
                                <span>Max: {{ $questionnaire->max_attempts }}</span>
                            @endif
                        </div>
                        @if($quizStats['best_score'] !== null)
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-gray-600">Best Score:</span>
                                <span class="font-medium {{ $quizStats['best_score'] >= ($questionnaire->pass_percentage ?? 70) ? 'text-green-600' : 'text-red-600' }}">
                                    {{ number_format($quizStats['best_score'], 1) }}%
                                </span>
                            </div>
                        @endif
                    </div>
                @endif
                

                {{-- DEBUG: Show actual values --}}
                <div class="mb-2 p-2 bg-yellow-100 text-xs text-yellow-800 rounded">
                    DEBUG: hasCompleted={{ $hasCompleted ? 'true' : 'false' }} | 
                    canTakeQuiz={{ $canTakeQuiz ? 'true' : 'false' }} | 
                    hasInProgress={{ $hasInProgress ? 'true' : 'false' }}
                    <br>recentAttempts count: {{ $this->recentAttempts->count() }}
                    <br>questionnaire_id: {{ $questionnaire->id }}
                    <br>current user_id: {{ Auth::id() }}
                    @if($this->recentAttempts->count() > 0)
                        <br>ALL attempt statuses: 
                        @foreach($this->recentAttempts as $attempt)
                            [Q{{ $attempt->questionnaire_id }}:{{ $attempt->status }}:U{{ $attempt->user_id }}]
                        @endforeach
                        <br>THIS questionnaire ({{ $questionnaire->id }}) attempts: 
                        @php
                            $thisQuestionnaireAttempts = $this->recentAttempts->where('questionnaire_id', $questionnaire->id);
                        @endphp
                        @if($thisQuestionnaireAttempts->count() > 0)
                            @foreach($thisQuestionnaireAttempts as $attempt)
                                [Q{{ $attempt->questionnaire_id }}:{{ $attempt->status }}:U{{ $attempt->user_id }}]
                            @endforeach
                        @else
                            NONE
                        @endif
                    @else
                        <br>NO RECENT ATTEMPTS FOUND
                    @endif
                    <br><button wire:click="debugQuizData({{ $questionnaire->id }})" class="bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded text-xs">
                        Debug Quiz Data
                    </button>
                </div>

                {{-- Action Buttons --}}
                @if($hasInProgress)
                    <button wire:click="continueQuiz({{ $hasInProgress->id }})" 
                            class="w-full bg-yellow-600 hover:bg-yellow-700 text-white py-2 px-4 rounded-md font-medium transition-colors duration-200 mb-2">
                        <i class="fas fa-play mr-2"></i>Continue Quiz
                    </button>
                    <p class="text-xs text-yellow-700 text-center">You have an unfinished attempt</p>
                @elseif(!$canTakeQuiz && !$hasCompleted)
                    <button disabled 
                            class="w-full bg-gray-400 text-white py-2 px-4 rounded-md font-medium cursor-not-allowed mb-2">
                        <i class="fas fa-lock mr-2"></i>Max Attempts Reached
                    </button>
                    <p class="text-xs text-gray-600 text-center">You've used all {{ $questionnaire->max_attempts }} attempts</p>
                @elseif($hasCompleted)
                    @if($canTakeQuiz)
                        <button wire:click="startQuiz({{ $questionnaire->id }})" 
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-md font-medium transition-colors duration-200 mb-2">
                            <i class="fas fa-redo mr-2"></i>Retake Quiz
                        </button>
                    @else
                        <button disabled 
                                class="w-full bg-gray-400 text-white py-2 px-4 rounded-md font-medium cursor-not-allowed mb-2">
                            <i class="fas fa-lock mr-2"></i>No More Attempts
                        </button>
                    @endif
                    <div class="flex items-center justify-center text-xs text-green-700">
                        <i class="fas fa-check-circle mr-1"></i>
                        Previously completed
                    </div>
                @else
                    <button wire:click="startQuiz({{ $questionnaire->id }})" 
                            class="w-full bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-md font-medium transition-colors duration-200">
                        <i class="fas fa-play mr-2"></i>Start Quiz
                    </button>
                @endif
                
                {{-- Last Completed Attempt Details --}}
                @if($hasCompleted)
                    @php
                        $lastCompletedAttempt = $this->recentAttempts
                            ->where('questionnaire_id', $questionnaire->id)
                            ->where('status', \App\Models\QuizAttempt::STATUS_COMPLETED)
                            ->sortByDesc('completed_at')
                            ->first();
                    @endphp
                    @if($lastCompletedAttempt)
                        <div class="mt-3 pt-3 border-t border-gray-200">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600">Last Score:</span>
                                <span class="font-medium {{ $lastCompletedAttempt->total_score >= ($questionnaire->pass_percentage ?? 70) ? 'text-green-600' : 'text-red-600' }}">
                                    {{ number_format($lastCompletedAttempt->total_score, 1) }}%
                                </span>
                            </div>
                            @if($lastCompletedAttempt->completed_at)
                                <div class="text-xs text-gray-500 mt-1">
                                    Completed: {{ $lastCompletedAttempt->completed_at->format('M d, Y') }}
                                </div>
                            @endif
                            @if($lastCompletedAttempt->total_time_seconds)
                                <div class="text-xs text-gray-500">
                                    Duration: {{ gmdate('H:i:s', $lastCompletedAttempt->total_time_seconds) }}
                                </div>
                            @endif
                        </div>
                    @endif
                @endif

                {{-- Abandoned Attempt Notice --}}
                @if($hasAbandoned && !$hasCompleted && !$hasInProgress)
                    <div class="mt-3 pt-3 border-t border-gray-200">
                        <div class="flex items-center text-xs text-orange-600">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            You have abandoned attempts for this quiz
                        </div>
                    </div>
                @endif
            </div>
            @empty
            {{-- No quiz found for scanned QR code --}}
            <div class="col-span-full text-center py-12">
                <div class="p-4 bg-red-100 rounded-full w-16 h-16 mx-auto mb-4 flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-red-500 text-2xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Quiz Not Found</h3>
                <p class="text-gray-600 mb-4">No active quiz found for the scanned QR code.</p>
                <p class="text-sm text-gray-500 mb-4">QR Code: <code class="bg-gray-100 px-2 py-1 rounded">{{ $scannedQr_code }}</code></p>
                <button wire:click="clearScannedQuiz" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium transition-colors">
                    <i class="fas fa-qrcode mr-2"></i>Scan Another QR Code
                </button>
            </div>
            @endforelse
        </div>
    @else
        {{-- No QR Code Scanned State --}}
        <div class="text-center py-12">
            <div class="p-4 bg-gray-100 rounded-full w-20 h-20 mx-auto mb-6 flex items-center justify-center">
                <i class="fas fa-qrcode text-gray-400 text-3xl"></i>
            </div>
            <h3 class="text-xl font-medium text-gray-900 mb-3">Scan QR Code to Access Quiz</h3>
            <p class="text-gray-600 mb-6 max-w-md mx-auto">
                To take a quiz, you need to scan the QR code provided with each questionnaire. 
                Each QR code contains a unique identifier that unlocks the specific quiz.
            </p>
            
            
            <div class="flex flex-col sm:flex-row gap-3 justify-center items-center">
                <div class="flex items-center text-sm text-gray-500">
                    <i class="fas fa-info-circle mr-2"></i>
                    Use the "Scan QR" button in the header to get started
                </div>
            </div>
        </div>
    @endif

    {{-- Error Messages --}}
    @if(session()->has('error'))
        <div class="mt-4 bg-red-50 border border-red-200 rounded-md p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle text-red-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-800">{{ session('error') }}</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Success Messages --}}
    @if(session()->has('success'))
        <div class="mt-4 bg-green-50 border border-green-200 rounded-md p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-green-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-green-800">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif
</div>