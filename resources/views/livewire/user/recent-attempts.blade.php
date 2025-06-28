<div>
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Recent Quiz Attempts</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quiz</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Score</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($recentAttempts as $attempt)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                {{ $attempt->questionnaire->title ?? 'Unknown Quiz' }}
                            </div>
                            @if($attempt->questionnaire && $attempt->questionnaire->description)
                                <div class="text-sm text-gray-500">
                                    {{ Str::limit($attempt->questionnaire->description, 50) }}
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $this->getStatusBadge($attempt->status) }}">
                                {{ $this->getStatusText($attempt->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($attempt->status === \App\Models\QuizAttempt::STATUS_COMPLETED && $attempt->total_score !== null)
                                <span class="text-sm font-medium {{ $this->getScoreColor($attempt->total_score) }}">
                                    {{ $this->formatScore($attempt) }}
                                </span>
                            @elseif($attempt->status === \App\Models\QuizAttempt::STATUS_STARTED)
                                @php
                                    $progress = $this->getAttemptProgress($attempt);
                                @endphp
                                @if($progress)
                                    <div class="text-sm text-gray-600">
                                        {{ $progress['answered'] }}/{{ $progress['total'] }} answered
                                    </div>
                                    <div class="text-xs text-gray-500">{{ $progress['percentage'] }}% complete</div>
                                @else
                                    <span class="text-sm text-gray-500">In Progress</span>
                                @endif
                            @else
                                <span class="text-sm text-gray-500">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <div>{{ $attempt->created_at->format('M j, Y') }}</div>
                            <div class="text-xs text-gray-400">{{ $attempt->created_at->format('g:i A') }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                @if($attempt->status === \App\Models\QuizAttempt::STATUS_COMPLETED)
                                    <button wire:click="viewQuizDetails({{ $attempt->id }})" 
                                            class="text-blue-600 hover:text-blue-900 focus:outline-none">
                                        View Results
                                    </button>

                                    @if($attempt->questionnaire && $attempt->questionnaire->canUserAttempt(Auth::id()))
                                        <span class="text-gray-300">|</span>
                                        <button wire:click="retakeQuiz({{ $attempt->questionnaire->id }})" 
                                                class="text-green-600 hover:text-green-900 focus:outline-none">
                                            Retake
                                        </button>
                                    @endif

                                @elseif($attempt->status === \App\Models\QuizAttempt::STATUS_STARTED)
                                    @if($this->canContinueAttempt($attempt))
                                        <button wire:click="continueQuiz({{ $attempt->id }})" 
                                                class="text-blue-600 hover:text-blue-900 focus:outline-none">
                                            Continue Quiz
                                        </button>
                                        @if($this->getTimeRemaining($attempt))
                                            <div class="text-xs text-orange-600 mt-1">
                                                {{ $this->getTimeRemaining($attempt) }}
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-gray-400 text-xs">Cannot continue</span>
                                        @if($attempt->questionnaire && $attempt->questionnaire->time_limit)
                                            <div class="text-xs text-red-600 mt-1">Time expired</div>
                                        @endif
                                    @endif

                                @elseif(in_array($attempt->status, [\App\Models\QuizAttempt::STATUS_ABANDONED]))
                                    @if($attempt->questionnaire && $attempt->questionnaire->canUserAttempt(Auth::id()))
                                        <button wire:click="retakeQuiz({{ $attempt->questionnaire->id }})" 
                                                class="text-green-600 hover:text-green-900 focus:outline-none">
                                            Try Again
                                        </button>
                                    @else
                                        <span class="text-gray-400 text-xs">No retakes allowed</span>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center">
                            <div class="text-gray-400">
                                <i class="fas fa-clipboard-list text-3xl mb-3"></i>
                                <div class="text-sm font-medium">No recent quiz attempts found</div>
                                <div class="text-xs mt-1">Your quiz attempts will appear here once you start taking quizzes</div>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($this->hasMoreAttempts())
            <div class="px-6 py-3 border-t border-gray-200 bg-gray-50">
                <div class="text-center">
                    <button wire:click="loadMoreAttempts" 
                            class="text-sm text-blue-600 hover:text-blue-800 focus:outline-none focus:underline">
                        Load More Attempts ({{ $recentAttempts->count() }} of {{ $totalAttempts }})
                    </button>
                </div>
            </div>
        @endif
    </div>

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

    {{-- Info Messages --}}
    @if(session()->has('info'))
        <div class="mt-4 bg-blue-50 border border-blue-200 rounded-md p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-info-circle text-blue-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-blue-800">{{ session('info') }}</p>
                </div>
            </div>
        </div>
    @endif
</div>
