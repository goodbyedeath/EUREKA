<div class="premium-stats-wrapper" @if(!\App\Models\FeatureSetting::isEnabled('user_dashboard_stats')) style="display: none;" @endif>
    <div class="premium-stats-grid">
        {{-- Total Attempts --}}
        @if(\App\Models\FeatureSetting::isEnabled('dashboard_stat_total_attempts'))
        <div class="premium-stat-card attempts">
            <div class="stat-card-glow"></div>
            <div class="stat-card-content">
                <div class="stat-header">
                    <div class="stat-icon-container">
                        <div class="stat-icon-bg attempts-bg"></div>
                        <i class="fas fa-clipboard-check stat-icon"></i>
                    </div>
                    <div class="stat-pulse-ring"></div>
                </div>
                <div class="stat-body">
                    <div class="stat-value-container">
                        <span class="stat-value">{{ $totalAttempts }}</span>
                        <div class="stat-value-effect"></div>
                    </div>
                    <div class="stat-label">{{ __('common.total_attempts') }}</div>
                    <div class="stat-description">{{ __('common.quiz_attempts_made') }}</div>
                </div>
            </div>
        </div>
        @endif

        {{-- Completed Attempts --}}
        @if(\App\Models\FeatureSetting::isEnabled('dashboard_stat_completed'))
        <div class="premium-stat-card completed">
            <div class="stat-card-glow"></div>
            <div class="stat-card-content">
                <div class="stat-header">
                    <div class="stat-icon-container">
                        <div class="stat-icon-bg completed-bg"></div>
                        <i class="fas fa-check-circle stat-icon"></i>
                    </div>
                    <div class="stat-pulse-ring"></div>
                </div>
                <div class="stat-body">
                    <div class="stat-value-container">
                        <span class="stat-value">{{ $completedAttempts }}</span>
                        <div class="stat-value-effect"></div>
                    </div>
                    <div class="stat-label">{{ __('common.completed') }}</div>
                    <div class="stat-description">{{ __('common.successfully_finished') }}</div>
                </div>
            </div>
        </div>
        @endif

        {{-- Average Points Per Question --}}
        @if(\App\Models\FeatureSetting::isEnabled('dashboard_stat_average_points'))
        <div class="premium-stat-card average clickable" wire:click="openScoreDetails">
            <div class="stat-card-glow"></div>
            <div class="stat-card-content">
                <div class="stat-header">
                    <div class="stat-icon-container">
                        <div class="stat-icon-bg average-bg"></div>
                        <i class="fas fa-calculator stat-icon"></i>
                    </div>
                    <div class="stat-pulse-ring"></div>
                </div>
                <div class="stat-body">
                    <div class="stat-value-container">
                        <span class="stat-value {{ $this->getScoreColor($averageScore) }}">{{ number_format($averageScore, 1) }}</span>
                        <div class="stat-value-effect"></div>
                    </div>
                    <div class="stat-label">{{ __('common.average_points') }}</div>
                    <div class="stat-description">{{ __('common.gained_per_question') }}</div>
                </div>
            </div>
        </div>
        @endif

        {{-- Completion Rate --}}
        @if(\App\Models\FeatureSetting::isEnabled('dashboard_stat_completion_rate'))
        <div class="premium-stat-card completion">
            <div class="stat-card-glow"></div>
            <div class="stat-card-content">
                <div class="stat-header">
                    <div class="stat-icon-container">
                        <div class="stat-icon-bg completion-bg"></div>
                        <div class="completion-circle">
                            <svg class="completion-ring" width="60" height="60" viewBox="0 0 60 60">
                                <circle cx="30" cy="30" r="25" fill="none" stroke="rgba(255,255,255,0.2)" stroke-width="3"/>
                                <circle cx="30" cy="30" r="25" fill="none" stroke="url(#completion-gradient)" stroke-width="4" 
                                        stroke-linecap="round" stroke-dasharray="{{ 2 * 3.14159 * 25 }}" 
                                        stroke-dashoffset="{{ 2 * 3.14159 * 25 * (1 - $completionRate / 100) }}"
                                        transform="rotate(-90 30 30)" class="completion-progress"/>
                            </svg>
                            <div class="completion-percentage">{{ $completionRate }}%</div>
                        </div>
                    </div>
                    <div class="stat-pulse-ring"></div>
                </div>
                <div class="stat-body">
                    <div class="stat-label">{{ __('common.completion_rate') }}</div>
                    <div class="stat-description">{{ __('common.success_percentage') }}</div>
                </div>
            </div>
        </div>
        @endif

        {{-- Total Score --}}
        @if($teamName && \App\Models\FeatureSetting::isEnabled('dashboard_stat_total_score'))
            <div class="premium-stat-card team">
                <div class="stat-card-glow"></div>
                <div class="stat-card-content">
                    <div class="stat-header">
                        <div class="stat-icon-container">
                            <div class="stat-icon-bg team-bg"></div>
                            <i class="fas fa-trophy stat-icon"></i>
                        </div>
                        <div class="stat-pulse-ring"></div>
                    </div>
                    <div class="stat-body">
                        <div class="stat-value-container">
                            <span class="stat-value {{ $teamPoints >= 1000 ? 'text-green-400' : ($teamPoints >= 500 ? 'text-yellow-400' : 'text-red-400') }}">{{ number_format($teamPoints, 0) }}</span>
                            <div class="stat-value-effect"></div>
                        </div>
                        <div class="stat-label">{{ __('common.total_score') }}</div>
                        <div class="stat-description">{{ __('common.base_gained_points') }}</div>
                    </div>
                </div>
            </div>
        @elseif(\App\Models\FeatureSetting::isEnabled('dashboard_stat_total_score'))
            <div class="premium-stat-card no-team">
                <div class="stat-card-glow"></div>
                <div class="stat-card-content">
                    <div class="stat-header">
                        <div class="stat-icon-container">
                            <div class="stat-icon-bg no-team-bg"></div>
                            <i class="fas fa-users stat-icon"></i>
                        </div>
                        <div class="stat-pulse-ring"></div>
                    </div>
                    <div class="stat-body">
                        <div class="stat-value-container">
                            <span class="stat-value text-gray-400">{{ __('common.no_team') }}</span>
                            <div class="stat-value-effect"></div>
                        </div>
                        <div class="stat-label">{{ __('common.team_status') }}</div>
                        <div class="stat-description">{{ __('common.join_team_earn_points') }}</div>
                    </div>
                </div>
            </div>
        @endif

        {{-- History Card --}}
        @if(\App\Models\FeatureSetting::isEnabled('dashboard_stat_history'))
        <div class="premium-stat-card history clickable" wire:click="openHistoryModal">
            <div class="stat-card-glow"></div>
            <div class="stat-card-content">
                <div class="stat-header">
                    <div class="stat-icon-container">
                        <div class="stat-icon-bg history-bg"></div>
                        <i class="fas fa-history stat-icon"></i>
                    </div>
                    <div class="stat-pulse-ring"></div>
                </div>
                <div class="stat-body">
                    <div class="stat-value-container">
                        <span class="stat-value">{{ $completedAttempts }}</span>
                        <div class="stat-value-effect"></div>
                    </div>
                    <div class="stat-label">{{ __('common.history') }}</div>
                    <div class="stat-description">{{ __('common.view_recent_attempts') }}</div>
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- Score Details Modal --}}
    @if($showDetailsModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeDetailsModal"></div>
            
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                            <i class="fas fa-chart-line text-blue-500 mr-2"></i>
                            {{ __('common.score_details') }}
                        </h3>
                        <button type="button" wire:click="closeDetailsModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    @if(count($scoreDetails) > 0)
                    <div class="mb-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-center">
                            <div>
                                <div class="text-2xl font-bold text-blue-600">{{ number_format($averageScore, 0) }}</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('common.average_total_score') }}</div>
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-green-600">{{ count($scoreDetails) }}</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('common.completed_quizzes') }}</div>
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-purple-600">{{ number_format(collect($scoreDetails)->avg('percentage'), 1) }}%</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('common.average_percentage') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('common.quiz') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('common.date') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('common.base_points') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('common.earned_points') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('common.total_score') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('common.percentage') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('common.duration') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($scoreDetails as $detail)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $detail['questionnaire_name'] }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ $detail['completed_at'] }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-mono text-gray-600 dark:text-gray-400">{{ number_format($detail['base_points']) }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-mono text-blue-600">+{{ number_format($detail['earned_points']) }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-bold font-mono {{ $this->getScoreColor($detail['total_score']) }}">{{ number_format($detail['total_score']) }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            @if($detail['percentage'] >= 90) bg-green-100 text-green-800
                                            @elseif($detail['percentage'] >= 80) bg-blue-100 text-blue-800
                                            @elseif($detail['percentage'] >= 70) bg-yellow-100 text-yellow-800
                                            @elseif($detail['percentage'] >= 60) bg-orange-100 text-orange-800
                                            @else bg-red-100 text-red-800
                                            @endif">
                                            {{ number_format($detail['percentage'], 1) }}%
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ $detail['duration'] }}</div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-8">
                        <div class="text-gray-400 text-lg mb-2">
                            <i class="fas fa-chart-line text-4xl"></i>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400">{{ __('common.no_completed_quizzes') }}</p>
                    </div>
                    @endif
                </div>

                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" wire:click="closeDetailsModal" class="w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:w-auto sm:text-sm">
                        {{ __('common.close') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- History Modal (Recent Attempts) --}}
    @if($showHistoryModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeHistoryModal"></div>
            
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-5xl sm:w-full">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                            <i class="fas fa-history text-purple-500 mr-2"></i>
                            {{ __('common.recent_quiz_attempts') }}
                        </h3>
                        <button type="button" wire:click="closeHistoryModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    @if(count($recentAttempts) > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('common.quiz') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('common.status') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('common.score') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('common.date') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($recentAttempts as $attempt)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $attempt['quiz_title'] }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            @if($attempt['status'] === 'completed') bg-green-100 text-green-800
                                            @elseif($attempt['status'] === 'in_progress') bg-yellow-100 text-yellow-800
                                            @else bg-gray-100 text-gray-800
                                            @endif">
                                            {{ ucfirst($attempt['status']) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($attempt['status'] === 'completed')
                                            <div class="text-sm font-mono font-bold text-green-600">{{ number_format($attempt['score']) }}</div>
                                        @else
                                            <div class="text-sm text-gray-400">-</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ $attempt['date'] }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        @if($attempt['status'] === 'completed')
                                            <a href="{{ route('quiz.results', $attempt['id']) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                                {{ __('common.view_details') }}
                                            </a>
                                        @elseif($attempt['status'] === 'in_progress')
                                            <a href="{{ route('quiz.take', $attempt['questionnaire_id']) }}" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300">
                                                {{ __('common.continue') }}
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-8">
                        <div class="text-gray-400 text-lg mb-2">
                            <i class="fas fa-clipboard-list text-4xl"></i>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400">{{ __('common.no_quiz_attempts') }}</p>
                    </div>
                    @endif
                </div>

                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" wire:click="closeHistoryModal" class="w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:w-auto sm:text-sm">
                        {{ __('common.close') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <style>
    /* Premium Stats Styles */
    .premium-stats-wrapper {
        margin-bottom: 32px;
    }
    
    .premium-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 24px;
    }
    
    .premium-stat-card {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(20px);
        border-radius: 20px;
        padding: 32px;
        border: 1px solid rgba(255, 255, 255, 0.3);
        position: relative;
        overflow: hidden;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
    }
    
    .dark .premium-stat-card {
        background: rgba(15, 23, 42, 0.9);
        border: 1px solid rgba(51, 65, 85, 0.3);
    }
    
    .premium-stat-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    }
    
    .premium-stat-card:hover .stat-card-glow {
        opacity: 1;
    }
    
    .premium-stat-card:hover .stat-pulse-ring {
        animation: pulseFast 1.5s infinite;
    }
    
    .premium-stat-card.clickable {
        cursor: pointer;
        user-select: none;
    }
    
    .premium-stat-card.clickable:hover {
        transform: translateY(-12px);
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
    }
    
    .premium-stat-card.clickable:active {
        transform: translateY(-8px);
    }
    
    .stat-card-glow {
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        opacity: 0;
        transition: opacity 0.4s ease;
        pointer-events: none;
    }
    
    .premium-stat-card.attempts .stat-card-glow {
        background: conic-gradient(from 0deg, rgba(59, 130, 246, 0.15), rgba(79, 172, 254, 0.15), rgba(59, 130, 246, 0.15));
        animation: rotateGlow 20s linear infinite;
    }
    
    .premium-stat-card.completed .stat-card-glow {
        background: conic-gradient(from 0deg, rgba(16, 185, 129, 0.15), rgba(5, 150, 105, 0.15), rgba(16, 185, 129, 0.15));
        animation: rotateGlow 25s linear infinite;
    }
    
    .premium-stat-card.average .stat-card-glow {
        background: conic-gradient(from 0deg, rgba(245, 158, 11, 0.15), rgba(217, 119, 6, 0.15), rgba(245, 158, 11, 0.15));
        animation: rotateGlow 22s linear infinite;
    }
    
    .premium-stat-card.completion .stat-card-glow {
        background: conic-gradient(from 0deg, rgba(139, 92, 246, 0.15), rgba(124, 58, 237, 0.15), rgba(139, 92, 246, 0.15));
        animation: rotateGlow 18s linear infinite;
    }
    
    .premium-stat-card.team .stat-card-glow {
        background: conic-gradient(from 0deg, rgba(236, 72, 153, 0.15), rgba(219, 39, 119, 0.15), rgba(236, 72, 153, 0.15));
        animation: rotateGlow 24s linear infinite;
    }
    
    .premium-stat-card.no-team .stat-card-glow {
        background: conic-gradient(from 0deg, rgba(107, 114, 128, 0.1), rgba(75, 85, 99, 0.1), rgba(107, 114, 128, 0.1));
        animation: rotateGlow 30s linear infinite;
    }
    
    .premium-stat-card.history .stat-card-glow {
        background: conic-gradient(from 0deg, rgba(139, 92, 246, 0.15), rgba(99, 102, 241, 0.15), rgba(139, 92, 246, 0.15));
        animation: rotateGlow 20s linear infinite;
    }
    
    .stat-card-content {
        position: relative;
        z-index: 2;
    }
    
    .stat-header {
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 24px;
        position: relative;
    }
    
    .stat-icon-container {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .stat-icon-bg {
        width: 80px;
        height: 80px;
        border-radius: 20px;
        position: absolute;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
    }
    
    .attempts-bg {
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    }
    
    .completed-bg {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    }
    
    .average-bg {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    }
    
    .completion-bg {
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
    }
    
    .team-bg {
        background: linear-gradient(135deg, #ec4899 0%, #db2777 100%);
    }
    
    .no-team-bg {
        background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
    }
    
    .history-bg {
        background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
    }
    
    .stat-icon {
        font-size: 32px;
        color: white;
        position: relative;
        z-index: 2;
    }
    
    .stat-pulse-ring {
        position: absolute;
        top: 50%;
        left: 50%;
        width: 100px;
        height: 100px;
        border-radius: 50%;
        transform: translate(-50%, -50%);
        background: radial-gradient(circle, rgba(255, 255, 255, 0.2) 0%, transparent 70%);
        animation: pulse 3s infinite;
    }
    
    .stat-body {
        text-align: center;
    }
    
    .stat-value-container {
        position: relative;
        margin-bottom: 12px;
    }
    
    .stat-value {
        font-size: 40px;
        font-weight: 900;
        color: #1f2937;
        line-height: 1;
        font-family: 'SF Mono', 'Monaco', monospace;
        position: relative;
        z-index: 2;
        transition: color 0.3s ease;
    }
    
    .dark .stat-value {
        color: #f1f5f9;
    }
    
    .stat-value-effect {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        animation: shimmerEffect 3s infinite;
        pointer-events: none;
    }
    
    .stat-label {
        font-size: 16px;
        font-weight: 700;
        color: #374151;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
        transition: color 0.3s ease;
    }
    
    .dark .stat-label {
        color: #d1d5db;
    }
    
    .stat-description {
        font-size: 12px;
        font-weight: 500;
        color: #6b7280;
        opacity: 0.8;
        transition: color 0.3s ease;
    }
    
    .dark .stat-description {
        color: #9ca3af;
    }
    
    /* Completion Circle Styles */
    .completion-circle {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .completion-ring {
        filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.1));
    }
    
    .completion-progress {
        transition: stroke-dashoffset 2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .completion-percentage {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-size: 14px;
        font-weight: 700;
        color: white;
        font-family: 'SF Mono', 'Monaco', monospace;
    }
    
    /* Score Color Classes */
    .text-green-400 { color: #4ade80; }
    .text-blue-400 { color: #60a5fa; }
    .text-yellow-400 { color: #facc15; }
    .text-orange-400 { color: #fb923c; }
    .text-red-400 { color: #f87171; }
    .text-gray-400 { color: #9ca3af; }
    
    /* Animations */
    @keyframes rotateGlow {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    @keyframes pulse {
        0%, 100% { 
            transform: translate(-50%, -50%) scale(1); 
            opacity: 0.3; 
        }
        50% { 
            transform: translate(-50%, -50%) scale(1.2); 
            opacity: 0.1; 
        }
    }
    
    @keyframes pulseFast {
        0%, 100% { 
            transform: translate(-50%, -50%) scale(1); 
            opacity: 0.5; 
        }
        50% { 
            transform: translate(-50%, -50%) scale(1.3); 
            opacity: 0.2; 
        }
    }
    
    @keyframes shimmerEffect {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }
    
    /* Responsive Design */
    @media (max-width: 768px) {
        .premium-stats-grid {
            grid-template-columns: 1fr;
        }
        
        .premium-stat-card {
            padding: 24px;
        }
        
        .stat-value {
            font-size: 32px;
        }
        
        .stat-icon-bg {
            width: 60px;
            height: 60px;
        }
        
        .stat-icon {
            font-size: 24px;
        }
        
        .stat-pulse-ring {
            width: 80px;
            height: 80px;
        }
    }
</style>

<!-- SVG Gradient Definitions -->
<svg style="position: absolute; width: 0; height: 0;">
    <defs>
        <linearGradient id="completion-gradient" x1="0%" y1="0%" x2="100%" y2="0%">
            <stop offset="0%" style="stop-color:#8b5cf6;stop-opacity:1" />
            <stop offset="50%" style="stop-color:#a855f7;stop-opacity:1" />
            <stop offset="100%" style="stop-color:#7c3aed;stop-opacity:1" />
        </linearGradient>
    </defs>
</svg>
</div>