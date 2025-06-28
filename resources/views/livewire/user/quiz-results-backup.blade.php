<div class="min-h-screen quiz-results-container">
    <!-- Floating Particles Background -->
    <div class="particles-container">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>
    
    <!-- Gradient Orbs -->
    <div class="gradient-orbs">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
    </div>
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- Spectacular Header -->
        <div class="results-header-premium">
            <div class="header-background-effect"></div>
            <div class="header-content-premium">
                <div class="header-left">
                    <div class="completion-status">
                        <div class="status-icon-wrapper">
                            <div class="status-icon {{ $this->getScorePercentage() >= 90 ? 'excellent' : ($this->getScorePercentage() >= 70 ? 'good' : 'needs-improvement') }}">
                                <i class="fas {{ $this->getScorePercentage() >= 90 ? 'fa-crown' : ($this->getScorePercentage() >= 70 ? 'fa-trophy' : 'fa-medal') }}"></i>
                            </div>
                            <div class="status-ripple"></div>
                        </div>
                        <div class="status-text">
                            <div class="completion-badge">
                                @if($this->getScorePercentage() >= 90)
                                    <i class="fas fa-star"></i>
                                    Excellent Performance
                                @elseif($this->getScorePercentage() >= 70)
                                    <i class="fas fa-thumbs-up"></i>
                                    Great Job
                                @else
                                    <i class="fas fa-chart-line"></i>
                                    Keep Improving
                                @endif
                            </div>
                            <h1 class="results-title-premium">{{ $questionnaire->title }}</h1>
                            <div class="completion-details">
                                <span class="completion-time">
                                    <i class="fas fa-calendar"></i>
                                    {{ $attempt->completed_at->format('F j, Y') }}
                                </span>
                                <span class="completion-duration">
                                    <i class="fas fa-stopwatch"></i>
                                    {{ $this->formatDuration($attempt->total_time_seconds) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="header-actions-premium">
                    <button wire:click="backToDashboard" 
                            class="action-btn-premium secondary">
                        <div class="btn-content">
                            <i class="fas fa-arrow-left"></i>
                            <span>Back to Dashboard</span>
                        </div>
                        <div class="btn-glow"></div>
                    </button>
                    
                    @if($questionnaire->canUserAttempt(Auth::id()) && $questionnaire->isAvailable())
                        <button wire:click="retakeQuiz" 
                                class="action-btn-premium primary">
                            <div class="btn-content">
                                <i class="fas fa-redo"></i>
                                <span>Retake Quiz</span>
                            </div>
                            <div class="btn-glow"></div>
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Spectacular Score Showcase -->
        <div class="score-showcase">
            <!-- Hero Score Display -->
            <div class="hero-score-card">
                <div class="card-background-effect"></div>
                <div class="score-content-premium">
                    <div class="score-badge {{ $this->getScorePercentage() >= 90 ? 'diamond' : ($this->getScorePercentage() >= 70 ? 'gold' : 'silver') }}">
                        <i class="fas {{ $this->getScorePercentage() >= 90 ? 'fa-gem' : ($this->getScorePercentage() >= 70 ? 'fa-star' : 'fa-award') }}"></i>
                        @if($this->getScorePercentage() >= 90)
                            Diamond Tier
                        @elseif($this->getScorePercentage() >= 70)
                            Gold Tier
                        @else
                            Silver Tier
                        @endif
                    </div>
                    
                    <div class="score-display-premium">
                        <div class="score-percentage">
                            <span class="percentage-number">{{ round($this->getScorePercentage()) }}</span>
                            <span class="percentage-symbol">%</span>
                        </div>
                        <div class="score-label-premium">Final Score</div>
                        <div class="score-breakdown-premium">
                            {{ number_format($this->getFinalScore(), 1) }} of {{ number_format($this->getTotalPossiblePoints(), 1) }} points
                        </div>
                    </div>
                    
                    <!-- Advanced Circular Progress -->
                    <div class="advanced-progress">
                        <svg class="progress-ring-premium" width="160" height="160" viewBox="0 0 160 160">
                            <!-- Background Circle -->
                            <circle cx="80" cy="80" r="70" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="3"/>
                            
                            <!-- Progress Circle -->
                            <circle cx="80" cy="80" r="70" fill="none" 
                                    stroke="url(#gradient-premium-{{ $this->getScorePercentage() >= 90 ? 'diamond' : ($this->getScorePercentage() >= 70 ? 'gold' : 'silver') }})" 
                                    stroke-width="6" 
                                    stroke-linecap="round"
                                    stroke-dasharray="{{ 2 * 3.14159 * 70 }}" 
                                    stroke-dashoffset="{{ 2 * 3.14159 * 70 * (1 - $this->getScorePercentage() / 100) }}"
                                    transform="rotate(-90 80 80)"
                                    class="progress-circle-animated"/>
                            
                            <!-- Glow Effect -->
                            <circle cx="80" cy="80" r="70" fill="none" 
                                    stroke="url(#gradient-glow-{{ $this->getScorePercentage() >= 90 ? 'diamond' : ($this->getScorePercentage() >= 70 ? 'gold' : 'silver') }})" 
                                    stroke-width="12" 
                                    stroke-linecap="round"
                                    stroke-dasharray="{{ 2 * 3.14159 * 70 }}" 
                                    stroke-dashoffset="{{ 2 * 3.14159 * 70 * (1 - $this->getScorePercentage() / 100) }}"
                                    transform="rotate(-90 80 80)"
                                    opacity="0.3"
                                    class="progress-glow"/>
                        </svg>
                        
                        <!-- Central Score -->
                        <div class="progress-center">
                            <div class="central-percentage">{{ round($this->getScorePercentage()) }}%</div>
                            <div class="pass-status {{ $this->isPassed() ? 'passed' : 'failed' }}">
                                @if($this->isPassed())
                                    <i class="fas fa-check"></i> PASSED
                                @else
                                    <i class="fas fa-times"></i> REVIEW
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    @php $breakdown = $this->getScoreBreakdown(); @endphp
                    @if($breakdown['assessment_score'] != 0 || $breakdown['regular_score'] != 0)
                        <div class="score-composition">
                            <div class="composition-item regular">
                                <div class="composition-icon">
                                    <i class="fas fa-clipboard-list"></i>
                                </div>
                                <div class="composition-data">
                                    <span class="composition-value">{{ number_format($breakdown['regular_score'], 1) }}</span>
                                    <span class="composition-label">Regular</span>
                                </div>
                            </div>
                            <div class="composition-plus">+</div>
                            <div class="composition-item games">
                                <div class="composition-icon">
                                    <i class="fas fa-gamepad"></i>
                                </div>
                                <div class="composition-data">
                                    <span class="composition-value">{{ number_format($breakdown['assessment_score'], 1) }}</span>
                                    <span class="composition-label">Games</span>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Premium Stats Grid -->
            <div class="premium-stats-grid">
                <!-- Status Card -->
                <div class="premium-stat-card status {{ $this->isPassed() ? 'passed' : 'failed' }}">
                    <div class="card-glow"></div>
                    <div class="stat-header">
                        <div class="stat-icon-premium">
                            <i class="fas {{ $this->isPassed() ? 'fa-shield-check' : 'fa-shield-exclamation' }}"></i>
                        </div>
                        <div class="stat-pulse"></div>
                    </div>
                    <div class="stat-body">
                        <h3 class="stat-title">Quiz Status</h3>
                        <div class="stat-main-value">{{ $this->isPassed() ? 'PASSED' : 'REVIEW' }}</div>
                        @if($questionnaire->pass_percentage)
                            <div class="stat-detail">Required: {{ $questionnaire->pass_percentage }}%</div>
                        @endif
                        <div class="stat-progress-mini">
                            <div class="progress-fill-mini" style="width: {{ min(100, $this->getScorePercentage()) }}%"></div>
                        </div>
                    </div>
                </div>

                <!-- Questions Card -->
                <div class="premium-stat-card questions">
                    <div class="card-glow"></div>
                    <div class="stat-header">
                        <div class="stat-icon-premium">
                            <i class="fas fa-list-check"></i>
                        </div>
                        <div class="stat-pulse"></div>
                    </div>
                    <div class="stat-body">
                        <h3 class="stat-title">Questions</h3>
                        <div class="stat-main-value">{{ $this->getTotalQuestionsCount() }}</div>
                        <div class="stat-breakdown-mini">
                            @if($this->getRegularQuestionsCount() > 0)
                                <span class="breakdown-item regular">
                                    <i class="fas fa-edit"></i>
                                    {{ $this->getRegularQuestionsCount() }} Regular
                                </span>
                            @endif
                            @if($this->getFunGameQuestionsCount() > 0)
                                <span class="breakdown-item games">
                                    <i class="fas fa-gamepad"></i>
                                    {{ $this->getFunGameQuestionsCount() }} Games
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Time Card -->
                <div class="premium-stat-card time">
                    <div class="card-glow"></div>
                    <div class="stat-header">
                        <div class="stat-icon-premium">
                            <i class="fas fa-hourglass-end"></i>
                        </div>
                        <div class="stat-pulse"></div>
                    </div>
                    <div class="stat-body">
                        <h3 class="stat-title">Duration</h3>
                        <div class="stat-main-value">{{ $this->formatDuration($attempt->total_time_seconds) }}</div>
                        @if($questionnaire->time_limit)
                            @php
                                $usedPercentage = min(100, ($attempt->total_time_seconds / ($questionnaire->time_limit * 60)) * 100);
                            @endphp
                            <div class="stat-detail">Limit: {{ $this->formatDuration($questionnaire->time_limit * 60) }}</div>
                            <div class="time-usage-bar">
                                <div class="time-usage-fill" style="width: {{ $usedPercentage }}%"></div>
                            </div>
                            <div class="time-usage-text">{{ round($usedPercentage) }}% of time used</div>
                        @endif
                    </div>
                </div>

                <!-- Assessment Card -->
                @if($assessments->count() > 0)
                    <div class="premium-stat-card assessments">
                        <div class="card-glow"></div>
                        <div class="stat-header">
                            <div class="stat-icon-premium">
                                @php
                                    $status = $this->getAssessmentStatus();
                                    $statusIcon = [
                                        'complete' => 'fa-check-double',
                                        'partial' => 'fa-clock-rotate-left',
                                        'pending' => 'fa-hourglass-half'
                                    ][$status] ?? 'fa-question';
                                @endphp
                                <i class="fas {{ $statusIcon }}"></i>
                            </div>
                            <div class="stat-pulse"></div>
                        </div>
                        <div class="stat-body">
                            <h3 class="stat-title">Assessments</h3>
                            <div class="stat-main-value">
                                @if($status === 'complete')
                                    COMPLETE
                                @elseif($status === 'partial')
                                    PARTIAL
                                @elseif($status === 'pending')
                                    PENDING
                                @else
                                    NONE
                                @endif
                            </div>
                            <div class="assessment-progress">
                                @php
                                    $assessedCount = $assessments->where('is_assessed', true)->count();
                                    $totalCount = $assessments->count();
                                    $assessmentPercentage = $totalCount > 0 ? ($assessedCount / $totalCount) * 100 : 0;
                                @endphp
                                <div class="assessment-bar">
                                    <div class="assessment-fill" style="width: {{ $assessmentPercentage }}%"></div>
                                </div>
                                <div class="assessment-text">{{ $assessedCount }}/{{ $totalCount }} assessed</div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Performance Insights Card -->
                <div class="premium-stat-card insights">
                    <div class="card-glow"></div>
                    <div class="stat-header">
                        <div class="stat-icon-premium">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-pulse"></div>
                    </div>
                    <div class="stat-body">
                        <h3 class="stat-title">Performance</h3>
                        <div class="performance-grade {{ $this->getScorePercentage() >= 90 ? 'grade-a' : ($this->getScorePercentage() >= 80 ? 'grade-b' : ($this->getScorePercentage() >= 70 ? 'grade-c' : 'grade-d')) }}">
                            @if($this->getScorePercentage() >= 90)
                                A+
                            @elseif($this->getScorePercentage() >= 80)
                                B+
                            @elseif($this->getScorePercentage() >= 70)
                                C+
                            @else
                                D
                            @endif
                        </div>
                        <div class="performance-text">
                            @if($this->getScorePercentage() >= 90)
                                Outstanding
                            @elseif($this->getScorePercentage() >= 80)
                                Excellent
                            @elseif($this->getScorePercentage() >= 70)
                                Good
                            @else
                                Needs Work
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Score Breakdown -->
        @php $breakdown = $this->getScoreBreakdown(); @endphp
        <div class="breakdown-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-chart-bar"></i>
                    Score Breakdown
                </h2>
            </div>
            
            <div class="breakdown-cards">
                <!-- Regular Questions Score -->
                @if($breakdown['regular_possible'] > 0)
                    <div class="breakdown-card regular">
                        <div class="breakdown-header">
                            <div class="breakdown-icon">
                                <i class="fas fa-clipboard-list"></i>
                            </div>
                            <h3 class="breakdown-title">Regular Questions</h3>
                        </div>
                        <div class="breakdown-score">
                            <span class="score-value">{{ number_format($breakdown['regular_score'], 1) }}</span>
                            <span class="score-divider">/</span>
                            <span class="score-total">{{ number_format($breakdown['regular_possible'], 1) }}</span>
                        </div>
                        <div class="progress-wrapper">
                            <div class="progress-bar">
                                <div class="progress-fill regular" 
                                     style="width: {{ $breakdown['regular_possible'] > 0 ? round(($breakdown['regular_score'] / $breakdown['regular_possible']) * 100) : 0 }}%"></div>
                            </div>
                            <div class="progress-text">
                                {{ $breakdown['regular_possible'] > 0 ? round(($breakdown['regular_score'] / $breakdown['regular_possible']) * 100, 1) : 0 }}% accuracy
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Game Assessment Score -->
                @if($breakdown['assessment_possible'] > 0)
                    <div class="breakdown-card games">
                        <div class="breakdown-header">
                            <div class="breakdown-icon">
                                <i class="fas fa-gamepad"></i>
                            </div>
                            <h3 class="breakdown-title">Fun Game Assessments</h3>
                        </div>
                        <div class="breakdown-score">
                            <span class="score-value">{{ number_format($breakdown['assessment_score'], 1) }}</span>
                            <span class="score-divider">/</span>
                            <span class="score-total">{{ number_format($breakdown['assessment_possible'], 1) }}</span>
                        </div>
                        <div class="progress-wrapper">
                            <div class="progress-bar">
                                <div class="progress-fill games" 
                                     style="width: {{ $breakdown['assessment_possible'] > 0 ? round(($breakdown['assessment_score'] / $breakdown['assessment_possible']) * 100) : 0 }}%"></div>
                            </div>
                            <div class="progress-text">
                                {{ $breakdown['assessment_possible'] > 0 ? round(($breakdown['assessment_score'] / $breakdown['assessment_possible']) * 100, 1) : 0 }}% of maximum
                            </div>
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

@push('styles')
<style>
    /* Premium Quiz Results - Ultra Modern Design */
    .quiz-results-container {
        background: linear-gradient(135deg, #0f0c29 0%, #302b63 25%, #24243e 50%, #302b63 75%, #0f0c29 100%) !important;
        min-height: 100vh !important;
        position: relative !important;
        overflow-x: hidden !important;
    }
    
    /* Floating Particles */
    .quiz-results-container .particles-container {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        width: 100% !important;
        height: 100% !important;
        pointer-events: none !important;
        z-index: 1 !important;
    }
    
    .quiz-results-container .particle {
        position: absolute !important;
        width: 4px !important;
        height: 4px !important;
        background: rgba(255, 255, 255, 0.3) !important;
        border-radius: 50% !important;
        animation: float 20s infinite linear !important;
    }
    
    .quiz-results-container .particle:nth-child(1) { left: 10% !important; animation-delay: 0s !important; animation-duration: 15s !important; }
    .quiz-results-container .particle:nth-child(2) { left: 20% !important; animation-delay: 2s !important; animation-duration: 18s !important; }
    .quiz-results-container .particle:nth-child(3) { left: 30% !important; animation-delay: 4s !important; animation-duration: 22s !important; }
    .quiz-results-container .particle:nth-child(4) { left: 40% !important; animation-delay: 6s !important; animation-duration: 16s !important; }
    .quiz-results-container .particle:nth-child(5) { left: 60% !important; animation-delay: 8s !important; animation-duration: 19s !important; }
    .quiz-results-container .particle:nth-child(6) { left: 70% !important; animation-delay: 10s !important; animation-duration: 17s !important; }
    .quiz-results-container .particle:nth-child(7) { left: 80% !important; animation-delay: 12s !important; animation-duration: 21s !important; }
    .quiz-results-container .particle:nth-child(8) { left: 90% !important; animation-delay: 14s !important; animation-duration: 20s !important; }
    
    /* Gradient Orbs */
    .quiz-results-container .gradient-orbs {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        width: 100% !important;
        height: 100% !important;
        pointer-events: none !important;
        z-index: 0 !important;
    }
    
    .quiz-results-container .orb {
        position: absolute !important;
        border-radius: 50% !important;
        filter: blur(40px) !important;
        animation: float-orb 30s infinite ease-in-out !important;
    }
    
    .quiz-results-container .orb-1 {
        width: 300px !important;
        height: 300px !important;
        background: radial-gradient(circle, rgba(99, 102, 241, 0.4) 0%, transparent 70%) !important;
        top: 10% !important;
        left: 10% !important;
        animation-delay: 0s !important;
    }
    
    .quiz-results-container .orb-2 {
        width: 400px !important;
        height: 400px !important;
        background: radial-gradient(circle, rgba(236, 72, 153, 0.3) 0%, transparent 70%) !important;
        top: 50% !important;
        right: 10% !important;
        animation-delay: 10s !important;
    }
    
    .quiz-results-container .orb-3 {
        width: 250px !important;
        height: 250px !important;
        background: radial-gradient(circle, rgba(34, 197, 94, 0.4) 0%, transparent 70%) !important;
        bottom: 10% !important;
        left: 30% !important;
        animation-delay: 20s !important;
    }
    
    .quiz-results-container > div:last-child {
        position: relative !important;
        z-index: 2 !important;
    }
    
    /* Header Styles */
    .results-header {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border-radius: 20px;
        padding: 32px;
        margin-bottom: 32px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
    }
    
    .header-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
    }
    
    .results-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        padding: 8px 16px;
        border-radius: 50px;
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 12px;
        animation: fadeInUp 0.6s ease-out;
    }
    
    .results-title {
        font-size: 32px;
        font-weight: 800;
        background: linear-gradient(135deg, #1e293b 0%, #475569 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        line-height: 1.2;
        margin-bottom: 8px;
        animation: fadeInUp 0.6s ease-out 0.2s both;
    }
    
    .results-subtitle {
        color: #64748b;
        font-size: 16px;
        font-weight: 500;
        animation: fadeInUp 0.6s ease-out 0.4s both;
    }
    
    .header-actions {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }
    
    .action-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 24px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 14px;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        animation: fadeInUp 0.6s ease-out 0.6s both;
    }
    
    .action-btn.primary {
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }
    
    .action-btn.primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4);
    }
    
    .action-btn.secondary {
        background: white;
        color: #64748b;
        border: 2px solid #e2e8f0;
    }
    
    .action-btn.secondary:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
        transform: translateY(-1px);
    }
    
    /* Score Overview Styles */
    .score-overview {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 32px;
        margin-bottom: 32px;
    }
    
    @media (max-width: 1024px) {
        .score-overview {
            grid-template-columns: 1fr;
            gap: 24px;
        }
    }
    
    /* Main Score Card */
    .main-score-card {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border-radius: 24px;
        padding: 32px;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        position: relative;
        overflow: hidden;
        animation: slideInLeft 0.8s ease-out;
    }
    
    .main-score-card::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(59, 130, 246, 0.1) 0%, transparent 70%);
        animation: rotate 20s linear infinite;
    }
    
    .score-icon-wrapper {
        margin-bottom: 20px;
        position: relative;
        z-index: 2;
    }
    
    .score-icon {
        width: 80px;
        height: 80px;
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
        color: white;
        margin-bottom: 20px;
        position: relative;
        overflow: hidden;
    }
    
    .score-icon.success {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        box-shadow: 0 8px 24px rgba(16, 185, 129, 0.3);
    }
    
    .score-icon.warning {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        box-shadow: 0 8px 24px rgba(245, 158, 11, 0.3);
    }
    
    .score-content {
        position: relative;
        z-index: 2;
    }
    
    .score-label {
        font-size: 14px;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
    }
    
    .score-main {
        font-size: 48px;
        font-weight: 900;
        line-height: 1;
        margin-bottom: 12px;
        font-family: 'SF Mono', 'Monaco', monospace;
    }
    
    .score-breakdown {
        font-size: 16px;
        color: #64748b;
        margin-bottom: 16px;
    }
    
    .score-details {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        font-size: 14px;
    }
    
    .score-part {
        color: #475569;
        font-weight: 600;
    }
    
    .score-separator {
        color: #94a3b8;
        font-weight: 400;
    }
    
    /* Circular Progress */
    .circular-progress {
        position: relative;
        margin-top: 24px;
    }
    
    .progress-ring {
        transform: rotate(-90deg);
    }
    
    .progress-ring-circle {
        transition: stroke-dashoffset 0.8s ease-out;
        stroke-linecap: round;
    }
    
    .progress-text {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-size: 16px;
        font-weight: 700;
        color: #1e293b;
    }
    
    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 20px;
        animation: slideInRight 0.8s ease-out;
    }
    
    .stat-card {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border-radius: 16px;
        padding: 24px;
        display: flex;
        align-items: center;
        gap: 16px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.2);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 32px rgba(0, 0, 0, 0.12);
    }
    
    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        border-radius: 16px 16px 0 0;
    }
    
    .stat-card.success::before { background: linear-gradient(90deg, #10b981, #059669); }
    .stat-card.danger::before { background: linear-gradient(90deg, #ef4444, #dc2626); }
    .stat-card.info::before { background: linear-gradient(90deg, #3b82f6, #1d4ed8); }
    .stat-card.primary::before { background: linear-gradient(90deg, #8b5cf6, #7c3aed); }
    .stat-card.warning::before { background: linear-gradient(90deg, #f59e0b, #d97706); }
    
    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        color: white;
        flex-shrink: 0;
    }
    
    .stat-card.success .stat-icon { background: linear-gradient(135deg, #10b981, #059669); }
    .stat-card.danger .stat-icon { background: linear-gradient(135deg, #ef4444, #dc2626); }
    .stat-card.info .stat-icon { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
    .stat-card.primary .stat-icon { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
    .stat-card.warning .stat-icon { background: linear-gradient(135deg, #f59e0b, #d97706); }
    
    .stat-content {
        flex: 1;
    }
    
    .stat-label {
        font-size: 12px;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }
    
    .stat-value {
        font-size: 24px;
        font-weight: 800;
        color: #1e293b;
        line-height: 1.2;
        margin-bottom: 4px;
    }
    
    .stat-meta {
        font-size: 12px;
        color: #64748b;
        font-weight: 500;
    }
    
    /* Section Styles */
    .breakdown-section {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border-radius: 20px;
        padding: 32px;
        margin-bottom: 32px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        animation: fadeInUp 0.8s ease-out;
    }
    
    .section-header {
        margin-bottom: 24px;
    }
    
    .section-title {
        font-size: 24px;
        font-weight: 700;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .breakdown-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 24px;
    }
    
    .breakdown-card {
        background: white;
        border-radius: 16px;
        padding: 24px;
        border: 2px solid transparent;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .breakdown-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        border-radius: 16px 16px 0 0;
    }
    
    .breakdown-card.regular::before { background: linear-gradient(90deg, #3b82f6, #1d4ed8); }
    .breakdown-card.games::before { background: linear-gradient(90deg, #10b981, #059669); }
    
    .breakdown-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 32px rgba(0, 0, 0, 0.12);
    }
    
    .breakdown-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
    }
    
    .breakdown-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 16px;
    }
    
    .breakdown-card.regular .breakdown-icon { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
    .breakdown-card.games .breakdown-icon { background: linear-gradient(135deg, #10b981, #059669); }
    
    .breakdown-title {
        font-size: 16px;
        font-weight: 700;
        color: #1e293b;
    }
    
    .breakdown-score {
        display: flex;
        align-items: baseline;
        gap: 4px;
        margin-bottom: 16px;
    }
    
    .score-value {
        font-size: 32px;
        font-weight: 900;
        color: #1e293b;
    }
    
    .score-divider {
        font-size: 24px;
        color: #94a3b8;
        font-weight: 400;
    }
    
    .score-total {
        font-size: 24px;
        color: #64748b;
        font-weight: 600;
    }
    
    .progress-wrapper {
        margin-top: 16px;
    }
    
    .progress-bar {
        width: 100%;
        height: 8px;
        background: #e2e8f0;
        border-radius: 4px;
        overflow: hidden;
        margin-bottom: 8px;
    }
    
    .progress-fill {
        height: 100%;
        border-radius: 4px;
        transition: width 0.8s ease-out;
        position: relative;
        overflow: hidden;
    }
    
    .progress-fill.regular { background: linear-gradient(90deg, #3b82f6, #1d4ed8); }
    .progress-fill.games { background: linear-gradient(90deg, #10b981, #059669); }
    
    .progress-fill::after {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        animation: shimmer 2s infinite;
    }
    
    .progress-text {
        font-size: 12px;
        color: #64748b;
        font-weight: 600;
    }
    
    /* SVG Gradients */
    svg defs {
        position: absolute;
        width: 0;
        height: 0;
    }
    
    /* Animations */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes slideInLeft {
        from {
            opacity: 0;
            transform: translateX(-50px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(50px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    @keyframes rotate {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    @keyframes shimmer {
        0% { left: -100%; }
        100% { left: 100%; }
    }
    
    @keyframes float {
        0%, 100% { transform: translateY(0px) translateX(0px); }
        25% { transform: translateY(-20px) translateX(10px); }
        50% { transform: translateY(-40px) translateX(-5px); }
        75% { transform: translateY(-20px) translateX(-10px); }
    }
    
    @keyframes float-orb {
        0%, 100% { transform: translateY(0px) translateX(0px) scale(1); }
        33% { transform: translateY(-30px) translateX(20px) scale(1.1); }
        66% { transform: translateY(15px) translateX(-15px) scale(0.9); }
    }
    
    /* Premium Header Styles */
    .quiz-results-container .results-header-premium {
        background: rgba(255, 255, 255, 0.95) !important;
        border-radius: 24px !important;
        padding: 40px !important;
        margin-bottom: 40px !important;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15) !important;
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
        backdrop-filter: blur(20px) !important;
        position: relative !important;
        overflow: hidden !important;
    }
    
    .header-background-effect {
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: conic-gradient(from 0deg, rgba(99, 102, 241, 0.1), rgba(236, 72, 153, 0.1), rgba(34, 197, 94, 0.1), rgba(99, 102, 241, 0.1));
        animation: rotate 30s linear infinite;
        pointer-events: none;
    }
    
    .header-content-premium {
        position: relative;
        z-index: 2;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 24px;
    }
    
    .completion-status {
        display: flex;
        align-items: center;
        gap: 20px;
    }
    
    .status-icon-wrapper {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .status-icon {
        width: 80px;
        height: 80px;
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
        color: white;
        position: relative;
        z-index: 2;
    }
    
    .status-icon.excellent {
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        box-shadow: 0 8px 32px rgba(139, 92, 246, 0.4);
    }
    
    .status-icon.good {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        box-shadow: 0 8px 32px rgba(16, 185, 129, 0.4);
    }
    
    .status-icon.needs-improvement {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        box-shadow: 0 8px 32px rgba(245, 158, 11, 0.4);
    }
    
    .status-ripple {
        position: absolute;
        top: 50%;
        left: 50%;
        width: 120px;
        height: 120px;
        border-radius: 50%;
        transform: translate(-50%, -50%);
        background: radial-gradient(circle, rgba(99, 102, 241, 0.2) 0%, transparent 70%);
        animation: ripple 3s infinite;
    }
    
    @keyframes ripple {
        0% { transform: translate(-50%, -50%) scale(0.8); opacity: 1; }
        100% { transform: translate(-50%, -50%) scale(1.5); opacity: 0; }
    }
    
    .completion-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(236, 72, 153, 0.1) 100%);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(99, 102, 241, 0.2);
        color: #4f46e5;
        padding: 8px 16px;
        border-radius: 50px;
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 12px;
    }
    
    .results-title-premium {
        font-size: 36px;
        font-weight: 900;
        background: linear-gradient(135deg, #1e293b 0%, #475569 50%, #1e293b 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        line-height: 1.2;
        margin-bottom: 12px;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .completion-details {
        display: flex;
        gap: 24px;
        align-items: center;
        font-size: 14px;
        color: #64748b;
        font-weight: 500;
    }
    
    .completion-time, .completion-duration {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .header-actions-premium {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }
    
    .action-btn-premium {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 14px 28px;
        border-radius: 16px;
        font-weight: 600;
        font-size: 14px;
        border: none;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        text-decoration: none;
        overflow: hidden;
        min-width: 140px;
    }
    
    .btn-content {
        position: relative;
        z-index: 2;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-glow {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        opacity: 0;
        transition: opacity 0.3s ease;
        border-radius: 16px;
    }
    
    .action-btn-premium.primary {
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        color: white;
        box-shadow: 0 8px 24px rgba(59, 130, 246, 0.3);
    }
    
    .action-btn-premium.primary .btn-glow {
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.2) 0%, rgba(255, 255, 255, 0.1) 100%);
    }
    
    .action-btn-premium.primary:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 36px rgba(59, 130, 246, 0.4);
    }
    
    .action-btn-premium.primary:hover .btn-glow {
        opacity: 1;
    }
    
    .action-btn-premium.secondary {
        background: rgba(255, 255, 255, 0.9);
        color: #64748b;
        border: 2px solid rgba(226, 232, 240, 0.8);
        backdrop-filter: blur(10px);
    }
    
    .action-btn-premium.secondary .btn-glow {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(99, 102, 241, 0.1) 100%);
    }
    
    .action-btn-premium.secondary:hover {
        background: rgba(248, 250, 252, 0.95);
        border-color: rgba(59, 130, 246, 0.3);
        transform: translateY(-2px);
        color: #475569;
    }
    
    .action-btn-premium.secondary:hover .btn-glow {
        opacity: 1;
    }
    
    /* Hero Score Card */
    .hero-score-card {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 32px;
        padding: 48px;
        box-shadow: 0 32px 64px rgba(0, 0, 0, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.3);
        backdrop-filter: blur(20px);
        position: relative;
        overflow: hidden;
        margin-bottom: 40px;
    }
    
    .card-background-effect {
        position: absolute;
        top: -100%;
        left: -100%;
        width: 300%;
        height: 300%;
        background: conic-gradient(from 0deg, rgba(99, 102, 241, 0.05), rgba(236, 72, 153, 0.05), rgba(34, 197, 94, 0.05), rgba(99, 102, 241, 0.05));
        animation: rotate 40s linear infinite;
        pointer-events: none;
    }
    
    .score-content-premium {
        position: relative;
        z-index: 2;
        display: grid;
        grid-template-columns: auto 1fr auto;
        gap: 48px;
        align-items: center;
    }
    
    .score-badge {
        display: inline-flex;
        align-items: center;
        gap: 12px;
        padding: 16px 24px;
        border-radius: 50px;
        font-size: 16px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        position: relative;
        overflow: hidden;
    }
    
    .score-badge.diamond {
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        color: white;
        box-shadow: 0 8px 32px rgba(139, 92, 246, 0.4);
    }
    
    .score-badge.gold {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: white;
        box-shadow: 0 8px 32px rgba(245, 158, 11, 0.4);
    }
    
    .score-badge.silver {
        background: linear-gradient(135deg, #64748b 0%, #475569 100%);
        color: white;
        box-shadow: 0 8px 32px rgba(100, 116, 139, 0.4);
    }
    
    .score-display-premium {
        text-align: center;
    }
    
    .score-percentage {
        display: flex;
        align-items: baseline;
        justify-content: center;
        gap: 4px;
        margin-bottom: 12px;
    }
    
    .percentage-number {
        font-size: 72px;
        font-weight: 900;
        background: linear-gradient(135deg, #1e293b 0%, #475569 50%, #1e293b 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        font-family: 'SF Mono', 'Monaco', monospace;
        line-height: 1;
    }
    
    .percentage-symbol {
        font-size: 48px;
        font-weight: 700;
        color: #64748b;
        font-family: 'SF Mono', 'Monaco', monospace;
    }
    
    .score-label-premium {
        font-size: 18px;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 8px;
    }
    
    .score-breakdown-premium {
        font-size: 16px;
        color: #475569;
        font-weight: 500;
    }
    
    /* Advanced Progress Ring */
    .advanced-progress {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .progress-ring-premium {
        filter: drop-shadow(0 8px 16px rgba(0, 0, 0, 0.1));
    }
    
    .progress-circle-animated {
        transition: stroke-dashoffset 2s cubic-bezier(0.4, 0, 0.2, 1);
        animation: pulse-glow 3s infinite;
    }
    
    .progress-glow {
        filter: blur(4px);
    }
    
    @keyframes pulse-glow {
        0%, 100% { opacity: 0.3; }
        50% { opacity: 0.6; }
    }
    
    .progress-center {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        text-align: center;
    }
    
    .central-percentage {
        font-size: 24px;
        font-weight: 900;
        color: #1e293b;
        font-family: 'SF Mono', 'Monaco', monospace;
        margin-bottom: 4px;
    }
    
    .pass-status {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
    }
    
    .pass-status.passed {
        color: #059669;
    }
    
    .pass-status.failed {
        color: #dc2626;
    }
    
    /* Score Composition */
    .score-composition {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 16px;
        margin-top: 24px;
        padding: 20px;
        background: rgba(248, 250, 252, 0.5);
        border-radius: 16px;
        backdrop-filter: blur(10px);
    }
    
    .composition-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        border-radius: 12px;
        background: white;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }
    
    .composition-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 14px;
    }
    
    .composition-item.regular .composition-icon {
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    }
    
    .composition-item.games .composition-icon {
        background: linear-gradient(135deg, #10b981, #059669);
    }
    
    .composition-data {
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    
    .composition-value {
        font-size: 18px;
        font-weight: 700;
        color: #1e293b;
        line-height: 1;
    }
    
    .composition-label {
        font-size: 12px;
        color: #64748b;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .composition-plus {
        font-size: 24px;
        font-weight: 700;
        color: #64748b;
    }
    
    /* Premium Stats Grid */
    .premium-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 24px;
        margin-top: 40px;
    }
    
    .premium-stat-card {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 20px;
        padding: 32px;
        position: relative;
        overflow: hidden;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid rgba(255, 255, 255, 0.3);
        backdrop-filter: blur(20px);
    }
    
    .premium-stat-card .card-glow {
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        opacity: 0;
        transition: opacity 0.3s ease;
        pointer-events: none;
    }
    
    .premium-stat-card.status .card-glow {
        background: conic-gradient(from 0deg, rgba(16, 185, 129, 0.1), rgba(34, 197, 94, 0.1), rgba(16, 185, 129, 0.1));
    }
    
    .premium-stat-card.questions .card-glow {
        background: conic-gradient(from 0deg, rgba(59, 130, 246, 0.1), rgba(99, 102, 241, 0.1), rgba(59, 130, 246, 0.1));
    }
    
    .premium-stat-card.time .card-glow {
        background: conic-gradient(from 0deg, rgba(245, 158, 11, 0.1), rgba(217, 119, 6, 0.1), rgba(245, 158, 11, 0.1));
    }
    
    .premium-stat-card.assessments .card-glow {
        background: conic-gradient(from 0deg, rgba(139, 92, 246, 0.1), rgba(124, 58, 237, 0.1), rgba(139, 92, 246, 0.1));
    }
    
    .premium-stat-card.insights .card-glow {
        background: conic-gradient(from 0deg, rgba(236, 72, 153, 0.1), rgba(219, 39, 119, 0.1), rgba(236, 72, 153, 0.1));
    }
    
    .premium-stat-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    }
    
    .premium-stat-card:hover .card-glow {
        opacity: 1;
        animation: rotate 20s linear infinite;
    }
    
    .stat-header {
        position: relative;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .stat-icon-premium {
        width: 64px;
        height: 64px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: white;
        position: relative;
        z-index: 2;
    }
    
    .premium-stat-card.status .stat-icon-premium {
        background: linear-gradient(135deg, #10b981, #059669);
        box-shadow: 0 8px 24px rgba(16, 185, 129, 0.3);
    }
    
    .premium-stat-card.questions .stat-icon-premium {
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        box-shadow: 0 8px 24px rgba(59, 130, 246, 0.3);
    }
    
    .premium-stat-card.time .stat-icon-premium {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        box-shadow: 0 8px 24px rgba(245, 158, 11, 0.3);
    }
    
    .premium-stat-card.assessments .stat-icon-premium {
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        box-shadow: 0 8px 24px rgba(139, 92, 246, 0.3);
    }
    
    .premium-stat-card.insights .stat-icon-premium {
        background: linear-gradient(135deg, #ec4899, #db2777);
        box-shadow: 0 8px 24px rgba(236, 72, 153, 0.3);
    }
    
    .stat-pulse {
        position: absolute;
        top: 50%;
        left: 50%;
        width: 80px;
        height: 80px;
        border-radius: 50%;
        transform: translate(-50%, -50%);
        background: radial-gradient(circle, rgba(99, 102, 241, 0.2) 0%, transparent 70%);
        animation: ripple 4s infinite;
    }
    
    .stat-body {
        text-align: center;
        position: relative;
        z-index: 2;
    }
    
    .stat-title {
        font-size: 14px;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 12px;
    }
    
    .stat-main-value {
        font-size: 32px;
        font-weight: 900;
        color: #1e293b;
        line-height: 1.2;
        margin-bottom: 8px;
        font-family: 'SF Mono', 'Monaco', monospace;
    }
    
    .stat-detail {
        font-size: 12px;
        color: #64748b;
        margin-bottom: 16px;
    }
    
    .stat-progress-mini {
        width: 100%;
        height: 4px;
        background: rgba(226, 232, 240, 0.5);
        border-radius: 2px;
        overflow: hidden;
        margin-bottom: 8px;
    }
    
    .progress-fill-mini {
        height: 100%;
        background: linear-gradient(90deg, #10b981, #059669);
        border-radius: 2px;
        transition: width 1s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .stat-breakdown-mini {
        display: flex;
        flex-direction: column;
        gap: 8px;
        font-size: 12px;
    }
    
    .breakdown-item {
        display: flex;
        align-items: center;
        gap: 6px;
        font-weight: 500;
    }
    
    .breakdown-item.regular {
        color: #3b82f6;
    }
    
    .breakdown-item.games {
        color: #10b981;
    }
    
    .time-usage-bar {
        width: 100%;
        height: 4px;
        background: rgba(226, 232, 240, 0.5);
        border-radius: 2px;
        overflow: hidden;
        margin-bottom: 8px;
    }
    
    .time-usage-fill {
        height: 100%;
        background: linear-gradient(90deg, #f59e0b, #d97706);
        border-radius: 2px;
        transition: width 1s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .time-usage-text {
        font-size: 11px;
        color: #64748b;
        font-weight: 500;
    }
    
    .assessment-progress {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    
    .assessment-bar {
        width: 100%;
        height: 4px;
        background: rgba(226, 232, 240, 0.5);
        border-radius: 2px;
        overflow: hidden;
    }
    
    .assessment-fill {
        height: 100%;
        background: linear-gradient(90deg, #8b5cf6, #7c3aed);
        border-radius: 2px;
        transition: width 1s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .assessment-text {
        font-size: 11px;
        color: #64748b;
        font-weight: 500;
    }
    
    .performance-grade {
        font-size: 40px;
        font-weight: 900;
        line-height: 1;
        margin-bottom: 8px;
        font-family: 'SF Mono', 'Monaco', monospace;
    }
    
    .performance-grade.grade-a {
        color: #059669;
        text-shadow: 0 0 20px rgba(5, 150, 105, 0.3);
    }
    
    .performance-grade.grade-b {
        color: #0284c7;
        text-shadow: 0 0 20px rgba(2, 132, 199, 0.3);
    }
    
    .performance-grade.grade-c {
        color: #d97706;
        text-shadow: 0 0 20px rgba(217, 119, 6, 0.3);
    }
    
    .performance-grade.grade-d {
        color: #dc2626;
        text-shadow: 0 0 20px rgba(220, 38, 38, 0.3);
    }
    
    .performance-text {
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        opacity: 0.8;
    }
    
    /* Responsive Design */
    @media (max-width: 768px) {
        .header-content {
            flex-direction: column;
            text-align: center;
        }
        
        .results-title {
            font-size: 24px;
        }
        
        .score-main {
            font-size: 36px;
        }
        
        .breakdown-cards {
            grid-template-columns: 1fr;
        }
        
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .score-content-premium {
            grid-template-columns: 1fr;
            gap: 32px;
            text-align: center;
        }
        
        .premium-stats-grid {
            grid-template-columns: 1fr;
        }
        
        .percentage-number {
            font-size: 56px;
        }
        
        .percentage-symbol {
            font-size: 36px;
        }
        
        .results-title-premium {
            font-size: 28px;
        }
        
        .completion-details {
            flex-direction: column;
            gap: 12px;
        }
    }
</style>

<!-- SVG Gradient Definitions -->
<svg style="position: absolute; width: 0; height: 0;">
    <defs>
        <!-- Progress Ring Gradients -->
        <linearGradient id="gradient-premium-diamond" x1="0%" y1="0%" x2="100%" y2="0%">
            <stop offset="0%" style="stop-color:#8b5cf6;stop-opacity:1" />
            <stop offset="50%" style="stop-color:#a855f7;stop-opacity:1" />
            <stop offset="100%" style="stop-color:#7c3aed;stop-opacity:1" />
        </linearGradient>
        
        <linearGradient id="gradient-premium-gold" x1="0%" y1="0%" x2="100%" y2="0%">
            <stop offset="0%" style="stop-color:#f59e0b;stop-opacity:1" />
            <stop offset="50%" style="stop-color:#fbbf24;stop-opacity:1" />
            <stop offset="100%" style="stop-color:#d97706;stop-opacity:1" />
        </linearGradient>
        
        <linearGradient id="gradient-premium-silver" x1="0%" y1="0%" x2="100%" y2="0%">
            <stop offset="0%" style="stop-color:#64748b;stop-opacity:1" />
            <stop offset="50%" style="stop-color:#94a3b8;stop-opacity:1" />
            <stop offset="100%" style="stop-color:#475569;stop-opacity:1" />
        </linearGradient>
        
        <!-- Glow Effect Gradients -->
        <linearGradient id="gradient-glow-diamond" x1="0%" y1="0%" x2="100%" y2="0%">
            <stop offset="0%" style="stop-color:#8b5cf6;stop-opacity:0.6" />
            <stop offset="50%" style="stop-color:#a855f7;stop-opacity:0.8" />
            <stop offset="100%" style="stop-color:#7c3aed;stop-opacity:0.6" />
        </linearGradient>
        
        <linearGradient id="gradient-glow-gold" x1="0%" y1="0%" x2="100%" y2="0%">
            <stop offset="0%" style="stop-color:#f59e0b;stop-opacity:0.6" />
            <stop offset="50%" style="stop-color:#fbbf24;stop-opacity:0.8" />
            <stop offset="100%" style="stop-color:#d97706;stop-opacity:0.6" />
        </linearGradient>
        
        <linearGradient id="gradient-glow-silver" x1="0%" y1="0%" x2="100%" y2="0%">
            <stop offset="0%" style="stop-color:#64748b;stop-opacity:0.6" />
            <stop offset="50%" style="stop-color:#94a3b8;stop-opacity:0.8" />
            <stop offset="100%" style="stop-color:#475569;stop-opacity:0.6" />
        </linearGradient>
        
        <!-- Legacy Gradients -->
        <linearGradient id="gradient-success" x1="0%" y1="0%" x2="100%" y2="0%">
            <stop offset="0%" style="stop-color:#10b981;stop-opacity:1" />
            <stop offset="100%" style="stop-color:#059669;stop-opacity:1" />
        </linearGradient>
        <linearGradient id="gradient-warning" x1="0%" y1="0%" x2="100%" y2="0%">
            <stop offset="0%" style="stop-color:#f59e0b;stop-opacity:1" />
            <stop offset="100%" style="stop-color:#d97706;stop-opacity:1" />
        </linearGradient>
    </defs>
</svg>
@endpush