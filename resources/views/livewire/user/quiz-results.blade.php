<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Quiz Results</h1>
                    <p class="text-gray-600 mt-1">{{ $questionnaire->title }}</p>
                </div>
                <div class="flex space-x-3">
                    <button wire:click="backToDashboard" 
                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Back to Quiz List
                    </button>
                    
                    @if($questionnaire->canUserAttempt(Auth::id()) && $questionnaire->isAvailable())
                        <button wire:click="retakeQuiz" 
                                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Retake Quiz
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Score Overview -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-{{ $assessments->count() > 0 ? '5' : '4' }} gap-6 mb-6">
            <!-- Overall Score -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <div class="text-sm font-medium text-gray-500">Overall Score</div>
                        <div class="text-2xl font-bold {{ $this->getScoreColor() }}">
                            {{ number_format($this->getFinalScore(), 1) }}/{{ number_format($this->getTotalPossiblePoints(), 1) }}
                        </div>
                        <div class="text-sm text-gray-600">{{ $this->getScorePercentage() }}%</div>
                        @php $breakdown = $this->getScoreBreakdown(); @endphp
                        @if($breakdown['assessment_score'] != 0 || $breakdown['regular_score'] != 0)
                            <div class="text-xs text-blue-600">
                                Regular: {{ number_format($breakdown['regular_score'], 1) }} + Games: {{ number_format($breakdown['assessment_score'], 1) }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Pass/Fail Status -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 {{ $this->isPassed() ? 'bg-green-100' : 'bg-red-100' }} rounded-full flex items-center justify-center">
                            @if($this->isPassed())
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            @else
                                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            @endif
                        </div>
                    </div>
                    <div class="ml-4">
                        <div class="text-sm font-medium text-gray-500">Status</div>
                        <div class="text-2xl font-bold {{ $this->isPassed() ? 'text-green-600' : 'text-red-600' }}">
                            {{ $this->isPassed() ? 'Passed' : 'Failed' }}
                        </div>
                        @if($questionnaire->pass_percentage)
                            <div class="text-sm text-gray-600">Required: {{ $questionnaire->pass_percentage }}%</div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Question Breakdown -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <div class="text-sm font-medium text-gray-500">Questions</div>
                        <div class="text-2xl font-bold text-green-600">
                            {{ $this->getTotalQuestionsCount() }} Total
                        </div>
                        <div class="text-sm text-gray-600">
                            @if($this->getRegularQuestionsCount() > 0)
                                {{ $this->getRegularQuestionsCount() }} Regular
                            @endif
                            @if($this->getFunGameQuestionsCount() > 0)
                                @if($this->getRegularQuestionsCount() > 0) • @endif
                                {{ $this->getFunGameQuestionsCount() }} Games
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Time Taken -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <div class="text-sm font-medium text-gray-500">Time Taken</div>
                        <div class="text-2xl font-bold text-purple-600">
                            {{ $this->formatDuration($attempt->total_time_seconds) }}
                        </div>
                        @if($questionnaire->time_limit)
                            <div class="text-sm text-gray-600">
                                Limit: {{ $this->formatDuration($questionnaire->time_limit * 60) }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Assessment Status -->
            @if($assessments->count() > 0)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            @php
                                $status = $this->getAssessmentStatus();
                                $statusConfig = [
                                    'complete' => ['bg' => 'bg-green-100', 'text' => 'text-green-600', 'icon' => 'M5 13l4 4L19 7'],
                                    'partial' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-600', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                                    'pending' => ['bg' => 'bg-orange-100', 'text' => 'text-orange-600', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z']
                                ][$status] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-600', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'];
                            @endphp
                            <div class="w-8 h-8 {{ $statusConfig['bg'] }} rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 {{ $statusConfig['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $statusConfig['icon'] }}"/>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-gray-500">Assessments</div>
                            <div class="text-2xl font-bold {{ $statusConfig['text'] }}">
                                @if($status === 'complete')
                                    Completed
                                @elseif($status === 'partial')
                                    Partial
                                @elseif($status === 'pending')
                                    Pending
                                @else
                                    None
                                @endif
                            </div>
                            <div class="text-sm text-gray-600">
                                {{ $assessments->where('is_assessed', true)->count() }}/{{ $assessments->count() }} assessed
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Score Breakdown -->
        @php $breakdown = $this->getScoreBreakdown(); @endphp
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Score Breakdown</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Regular Questions Score -->
                @if($breakdown['regular_possible'] > 0)
                    <div class="p-4 bg-blue-50 rounded-lg">
                        <h3 class="text-sm font-semibold text-blue-900 mb-3">Regular Questions</h3>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-blue-700">Score:</span>
                            <span class="font-medium text-blue-900">{{ number_format($breakdown['regular_score'], 1) }}/{{ number_format($breakdown['regular_possible'], 1) }}</span>
                        </div>
                        <div class="w-full bg-blue-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $breakdown['regular_possible'] > 0 ? round(($breakdown['regular_score'] / $breakdown['regular_possible']) * 100) : 0 }}%"></div>
                        </div>
                        <div class="text-xs text-blue-600 mt-1">
                            {{ $breakdown['regular_possible'] > 0 ? round(($breakdown['regular_score'] / $breakdown['regular_possible']) * 100, 1) : 0 }}% accuracy
                        </div>
                    </div>
                @endif

                <!-- Game Assessment Score -->
                @if($breakdown['assessment_possible'] > 0)
                    <div class="p-4 bg-green-50 rounded-lg">
                        <h3 class="text-sm font-semibold text-green-900 mb-3">Fun Game Assessments</h3>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-green-700">Score:</span>
                            <span class="font-medium text-green-900">{{ number_format($breakdown['assessment_score'], 1) }}/{{ number_format($breakdown['assessment_possible'], 1) }}</span>
                        </div>
                        <div class="w-full bg-green-200 rounded-full h-2">
                            <div class="bg-green-600 h-2 rounded-full" style="width: {{ $breakdown['assessment_possible'] > 0 ? round(($breakdown['assessment_score'] / $breakdown['assessment_possible']) * 100) : 0 }}%"></div>
                        </div>
                        <div class="text-xs text-green-600 mt-1">
                            {{ $breakdown['assessment_possible'] > 0 ? round(($breakdown['assessment_score'] / $breakdown['assessment_possible']) * 100, 1) : 0 }}% of maximum possible
                        </div>
                    </div>
                @endif
            </div>

            <!-- Assessment Details -->
            @if($assessments->count() > 0)
                <div class="mt-6">
                    <h4 class="text-sm font-medium text-gray-900 mb-3">Game Assessment Details</h4>
                    <div class="space-y-3">
                        @foreach($this->getAssessmentDetails() as $detail)
                            <div class="border border-gray-200 rounded-lg p-3">
                                <div class="flex items-center justify-between mb-2">
                                    <h5 class="font-medium text-gray-900">{{ $detail['question_name'] }}</h5>
                                    @if($detail['is_assessed'])
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Assessed
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            Pending
                                        </span>
                                    @endif
                                </div>
                                @if($detail['is_assessed'])
                                    <div class="grid grid-cols-4 gap-2 text-xs">
                                        <div>
                                            <span class="text-gray-500">Base:</span>
                                            <span class="font-medium">{{ number_format($detail['base_points'], 1) }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Additional:</span>
                                            <span class="font-medium text-green-600">+{{ number_format($detail['additional_points'], 1) }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Penalty:</span>
                                            <span class="font-medium text-red-600">-{{ number_format($detail['penalty_points'], 1) }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Total:</span>
                                            <span class="font-bold {{ $detail['total_score'] >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ number_format($detail['total_score'], 1) }}</span>
                                        </div>
                                    </div>
                                    @if($detail['notes'])
                                        <div class="mt-2 text-xs text-gray-600">
                                            <strong>Notes:</strong> {{ $detail['notes'] }}
                                        </div>
                                    @endif
                                @else
                                    <p class="text-xs text-gray-500">Assessment pending - game completed but not yet evaluated.</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Quiz Summary -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Quiz Summary</h2>
                <button wire:click="toggleDetails" 
                        class="inline-flex items-center px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    {{ $showDetails ? 'Hide' : 'Show' }} Details
                    <svg class="w-4 h-4 ml-1 transform {{ $showDetails ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <span class="font-medium text-gray-500">Completed:</span>
                    <span class="ml-2 text-gray-900">{{ $attempt->completed_at->format('M j, Y g:i A') }}</span>
                </div>
                <div>
                    <span class="font-medium text-gray-500">Total Questions:</span>
                    <span class="ml-2 text-gray-900">{{ $this->getTotalQuestionsCount() }}</span>
                </div>
                <div>
                    <span class="font-medium text-gray-500">Attempt #:</span>
                    <span class="ml-2 text-gray-900">{{ $questionnaire->getUserAttemptCount(Auth::id()) }}</span>
                </div>
            </div>
        </div>

        <!-- Assessment Results -->
        @if($assessments->count() > 0)
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Fun Game Assessment Results</h2>
                    @php
                        $assessmentStatus = $this->getAssessmentStatus();
                    @endphp
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                        {{ $assessmentStatus === 'complete' ? 'bg-green-100 text-green-800' : 
                           ($assessmentStatus === 'partial' ? 'bg-yellow-100 text-yellow-800' : 'bg-orange-100 text-orange-800') }}">
                        @if($assessmentStatus === 'complete')
                            <i class="fas fa-check-circle mr-1"></i>All Assessed
                        @elseif($assessmentStatus === 'partial')
                            <i class="fas fa-clock mr-1"></i>Partially Assessed
                        @else
                            <i class="fas fa-hourglass-half mr-1"></i>Pending Assessment
                        @endif
                    </span>
                </div>
                
                <div class="space-y-4">
                    @foreach($assessments as $assessment)
                        @php
                            $question = $questions->firstWhere('id', $assessment->question_id);
                        @endphp
                        <div class="border rounded-lg p-4 {{ $assessment->is_assessed ? 'bg-green-50 border-green-200' : 'bg-yellow-50 border-yellow-200' }}">
                            <div class="flex items-center justify-between mb-2">
                                <div>
                                    <h4 class="font-medium text-gray-900">{{ $question->game_name ?? 'Fun Game' }}</h4>
                                    <p class="text-sm text-gray-600">{{ $question->question }}</p>
                                </div>
                                <div class="text-right">
                                    @if($assessment->is_assessed)
                                        <div class="text-lg font-bold {{ $assessment->total_deposit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $assessment->total_deposit >= 0 ? '+' : '' }}{{ number_format($assessment->total_deposit, 1) }} pts
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            Deposit: {{ $assessment->deposit }} | Penalty: {{ $assessment->penalty }}
                                        </div>
                                    @else
                                        <div class="text-sm text-yellow-600 font-medium">
                                            <i class="fas fa-clock mr-1"></i>Pending Assessment
                                        </div>
                                    @endif
                                </div>
                            </div>
                            
                            @if($assessment->is_assessed && $assessment->notes)
                                <div class="mt-3 p-3 bg-white rounded border">
                                    <p class="text-sm text-gray-600"><strong>Assessor Notes:</strong></p>
                                    <p class="text-sm text-gray-800 mt-1">{{ $assessment->notes }}</p>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
                
                <!-- Assessment Summary -->
                <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500 font-medium">Assessment Progress:</span>
                            <div class="text-lg font-semibold text-gray-900">
                                {{ $assessments->where('is_assessed', true)->count() }}/{{ $assessments->count() }} Completed
                            </div>
                        </div>
                        <div>
                            <span class="text-gray-500 font-medium">Total Assessment Score:</span>
                            <div class="text-lg font-semibold {{ $this->getAssessmentScore() >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $this->getAssessmentScore() >= 0 ? '+' : '' }}{{ number_format($this->getAssessmentScore(), 1) }} points
                            </div>
                        </div>
                        <div>
                            <span class="text-gray-500 font-medium">Final Quiz Score:</span>
                            <div class="text-lg font-semibold {{ $this->getScoreColor() }}">
                                {{ number_format($this->getFinalScore(), 1) }}/{{ $questionnaire->total_points }} ({{ $this->getScorePercentage() }}%)
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Detailed Results -->
        @if($showDetails)
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-6">Question by Question Review</h2>
                
                <!-- Question Navigation -->
                <div class="flex flex-wrap gap-2 mb-6 p-4 bg-gray-50 rounded-lg">
                    @foreach($questions as $index => $question)
                        @php
                            $userAnswer = $userAnswers->get($question->id);
                            $isCorrect = $userAnswer ? $userAnswer->is_correct : false;
                        @endphp
                        <button wire:click="setCurrentQuestion({{ $index }})" 
                                class="w-10 h-10 rounded-full text-sm font-medium border-2 
                                {{ $currentQuestionIndex === $index ? 'border-blue-500 bg-blue-500 text-white' : 
                                   ($isCorrect ? 'border-green-500 bg-green-100 text-green-700' : 'border-red-500 bg-red-100 text-red-700') }}"
                            {{ $index + 1 }}
                        </button>
                    @endforeach
                </div>

                <!-- Current Question Review -->
                @if(isset($questions[$currentQuestionIndex]))
                    @php
                        $currentQuestion = $questions[$currentQuestionIndex];
                        $userAnswer = $userAnswers->get($currentQuestion->id);
                    @endphp
                    
                    <div class="border rounded-lg p-6 mb-4">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1">
                                <h3 class="text-lg font-medium text-gray-900 mb-2">
                                    Question {{ $currentQuestionIndex + 1 }}
                                </h3>
                                <p class="text-gray-700 mb-4">{{ $currentQuestion->question }}</p>
                            </div>
                            <div class="flex-shrink-0 ml-4">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                    {{ $userAnswer && $userAnswer->is_correct ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $userAnswer && $userAnswer->is_correct ? 'Correct' : 'Incorrect' }}
                                </span>
                            </div>
                        </div>

                        <div class="space-y-3">
                            @if($currentQuestion->type === 'multiple_choice')
                                @php
                                    $options = $currentQuestion->options; // Already cast to array
                                    $correctAnswer = $currentQuestion->correct_answer;
                                    $userSelectedAnswer = $userAnswer ? $userAnswer->answer : null;
                                @endphp
                                
                                @foreach($options as $key => $option)
                                    <div class="flex items-center p-3 rounded-lg border
                                        {{ $key === $correctAnswer ? 'bg-green-50 border-green-200' : 
                                           ($key === $userSelectedAnswer && $key !== $correctAnswer ? 'bg-red-50 border-red-200' : 'bg-gray-50 border-gray-200') }}">
                                        <div class="flex-shrink-0 mr-3">
                                            @if($key === $correctAnswer)
                                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                            @elseif($key === $userSelectedAnswer)
                                                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            @else
                                                <div class="w-5 h-5 rounded-full border-2 border-gray-300"></div>
                                            @endif
                                        </div>
                                        <div class="flex-1">
                                            <span class="text-gray-900">{{ $key }}. {{ $option }}</span>
                                            @if($key === $userSelectedAnswer)
                                                <span class="ml-2 text-sm text-gray-500">(Your answer)</span>
                                            @endif
                                            @if($key === $correctAnswer)
                                                <span class="ml-2 text-sm text-green-600">(Correct answer)</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>

                        <div class="mt-4 flex items-center justify-between text-sm text-gray-500">
                            <div>
                                Points: {{ $userAnswer ? $userAnswer->points_earned : 0 }} / {{ $currentQuestion->points }}
                            </div>
                            @if($userAnswer && $userAnswer->time_taken_seconds)
                                <div>
                                    Time taken: {{ $this->formatDuration($userAnswer->time_taken_seconds) }}
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Navigation -->
                    <div class="flex justify-between mt-6">
                        <button wire:click="previousQuestion" 
                                @if($currentQuestionIndex === 0) disabled @endif
                                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Previous
                        </button>
                        
                        <span class="text-sm text-gray-500 flex items-center">
                            Question {{ $currentQuestionIndex + 1 }} of {{ $questions->count() }}
                        </span>
                        
                        <button wire:click="nextQuestion" 
                                @if($currentQuestionIndex === $questions->count() - 1) disabled @endif
                                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                            Next
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>