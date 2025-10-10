<div class="space-y-6">
    <!-- Enhanced Page Header -->
    <div class="flex flex-col lg:flex-row lg:justify-between lg:items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">User Progress Analytics</h2>
            <p class="text-gray-600 dark:text-gray-400">Track and analyze user learning progress and performance</p>
        </div>
        
        <!-- Enhanced Filters -->
        <div class="flex flex-col sm:flex-row gap-3 sm:gap-4">
            <!-- Search Bar -->
            <div class="relative">
                <input type="text" 
                       wire:model.live.debounce.300ms="searchTerm" 
                       placeholder="Search users..."
                       class="pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm transition-colors duration-200">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400 dark:text-gray-500 text-sm"></i>
                </div>
            </div>
            
            <!-- Filter Dropdowns -->
            <div class="flex gap-2 sm:gap-3">
                <select wire:model.live="selectedTimeframe" class="text-sm rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors duration-200">
                    <option value="7">Last 7 days</option>
                    <option value="30">Last 30 days</option>
                    <option value="90">Last 90 days</option>
                    <option value="365">Last year</option>
                </select>
                
                <select wire:model.live="selectedTeam" class="text-sm rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors duration-200">
                    <option value="all">All Teams</option>
                    @foreach($teams as $team)
                        <option value="{{ $team->id }}">{{ $team->name }}</option>
                    @endforeach
                </select>
                
                <select wire:model.live="selectedRole" class="text-sm rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors duration-200">
                    <option value="user">Regular Users</option>
                    <option value="admin">Admins</option>
                    <option value="all">All Roles</option>
                </select>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex gap-2">
                <button wire:click="resetFilters" class="px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors duration-200" title="Reset Filters">
                    <i class="fas fa-undo text-sm"></i>
                </button>
                <button wire:click="exportData" class="px-4 py-2 bg-indigo-600 dark:bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-700 transition-colors duration-200 flex items-center gap-2">
                    <i class="fas fa-file-pdf text-sm"></i>
                    <span class="hidden sm:inline">Preview PDF</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Enhanced Overview Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-4 lg:gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 lg:p-6 transition-colors duration-200">
            <div class="flex items-center">
                <div class="p-3 rounded-xl bg-blue-100 dark:bg-blue-900">
                    <i class="fas fa-users text-blue-600 dark:text-blue-400 text-lg"></i>
                </div>
                <div class="ml-4">
                    <p class="text-xs lg:text-sm font-medium text-gray-600 dark:text-gray-400">Total Users</p>
                    <p class="text-xl lg:text-2xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($totalUsers) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 lg:p-6 transition-colors duration-200">
            <div class="flex items-center">
                <div class="p-3 rounded-xl bg-green-100 dark:bg-green-900">
                    <i class="fas fa-user-check text-green-600 dark:text-green-400 text-lg"></i>
                </div>
                <div class="ml-4">
                    <p class="text-xs lg:text-sm font-medium text-gray-600 dark:text-gray-400">Active Users</p>
                    <p class="text-xl lg:text-2xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($activeUsers) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 lg:p-6 transition-colors duration-200">
            <div class="flex items-center">
                <div class="p-3 rounded-xl bg-yellow-100 dark:bg-yellow-900">
                    <i class="fas fa-play-circle text-yellow-600 dark:text-yellow-400 text-lg"></i>
                </div>
                <div class="ml-4">
                    <p class="text-xs lg:text-sm font-medium text-gray-600 dark:text-gray-400">In Progress</p>
                    <p class="text-xl lg:text-2xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($inProgressAttempts) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 lg:p-6 transition-colors duration-200">
            <div class="flex items-center">
                <div class="p-3 rounded-xl bg-purple-100 dark:bg-purple-900">
                    <i class="fas fa-chart-line text-purple-600 dark:text-purple-400 text-lg"></i>
                </div>
                <div class="ml-4">
                    <p class="text-xs lg:text-sm font-medium text-gray-600 dark:text-gray-400">Completion Rate</p>
                    <p class="text-xl lg:text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $completionRate }}%</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 lg:p-6 transition-colors duration-200">
            <div class="flex items-center">
                <div class="p-3 rounded-xl bg-red-100 dark:bg-red-900">
                    <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-lg"></i>
                </div>
                <div class="ml-4">
                    <p class="text-xs lg:text-sm font-medium text-gray-600 dark:text-gray-400">Struggling</p>
                    <p class="text-xl lg:text-2xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($strugglingUsers) }}</p>
                </div>
            </div>
        </div>
        
    </div>

    <!-- Game Assessment Stats Card -->
    @if($gameAssessmentStats['total'] > 0)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                <i class="fas fa-gamepad text-indigo-600 dark:text-indigo-400"></i>
                Game Assessment Status
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $gameAssessmentStats['total'] }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total Games</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $gameAssessmentStats['assessed'] }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Assessed</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ $gameAssessmentStats['pending'] }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Pending</div>
                </div>
            </div>
            <div class="mt-4">
                <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400 mb-1">
                    <span>Assessment Progress</span>
                    <span>{{ $gameAssessmentStats['assessment_rate'] }}%</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="bg-green-600 dark:bg-green-500 h-2 rounded-full transition-all duration-300" style="width: {{ $gameAssessmentStats['assessment_rate'] }}%"></div>
                </div>
            </div>
        </div>
    @endif

    <!-- Charts Section -->
    <div class="grid grid-cols-1 gap-6">
        <!-- Team Performance Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 transition-colors duration-200">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Team Points Leaderboard</h3>
            </div>
            <div class="h-64">
                <canvas id="teamPerformanceChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Top Performers -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow transition-colors duration-200">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 transition-colors duration-200">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Top Performers</h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($topPerformers->take(6) as $index => $performer)
                    <div class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg transition-colors duration-200">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
                                <span class="text-white font-semibold">{{ $index + 1 }}</span>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $performer['name'] }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $performer['team'] }}</p>
                            <div class="flex items-center mt-1">
                                <span class="text-sm font-semibold text-green-600">{{ $performer['total_points'] }} pts</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">{{ $performer['completed_quizzes'] }} quizzes</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Brief Feedback/Debrief Data Section -->
    @if($briefFeedbackData->isNotEmpty())
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow transition-colors duration-200">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 transition-colors duration-200">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Feedback & Debrief Responses</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">User feedback and debrief answers from questionnaires</p>
        </div>
        <div class="p-6">
            <div class="overflow-hidden">
                <div class="grid gap-4">
                    @foreach($briefFeedbackData as $feedback)
                        <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4 bg-gray-50 dark:bg-gray-700 transition-colors duration-200">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $feedback['user_name'] }}</span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">•</span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $feedback['team_name'] }}</span>
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                                        <span class="font-medium">{{ $feedback['questionnaire_title'] }}</span>
                                        <span class="ml-2">• {{ $feedback['submitted_at']->format('M d, Y H:i') }}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Question:</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 italic bg-white dark:bg-gray-800 p-3 rounded border-l-4 border-blue-500">
                                    {{ $feedback['question_text'] }}
                                </div>
                            </div>
                            
                            <div>
                                <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Response:</div>
                                <div class="text-sm text-gray-900 dark:text-gray-100 bg-white dark:bg-gray-800 p-3 rounded border-l-4 border-green-500">
                                    @if(!empty($feedback['feedback_answer']))
                                        {{ $feedback['feedback_answer'] }}
                                    @else
                                        <span class="text-gray-500 dark:text-gray-400 italic">No response provided</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                @if($briefFeedbackData->count() === 0)
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <i class="fas fa-comments text-3xl mb-4"></i>
                        <p>No feedback responses found for the selected timeframe and filters.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    <!-- Enhanced User Progress Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="px-4 lg:px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">User Progress Details</h3>
            <div class="flex items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
                <span>{{ $userProgressData->count() }} users</span>
                <div class="flex items-center gap-1">
                    <span>Sort by:</span>
                    <select wire:model.live="sortField" class="text-xs border-gray-300 dark:border-gray-600 rounded focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="completion_rate">Completion Rate</option>
                        <option value="total_points">Team Points</option>
                        <option value="average_score">Average Points</option>
                        <option value="total_attempts">Total Attempts</option>
                        <option value="last_activity">Last Activity</option>
                        <option value="name">Name</option>
                    </select>
                    <button wire:click="sortBy('{{ $sortField }}')
                            class="p-1 hover:bg-gray-100 rounded">
                        <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-xs"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Mobile Card View (Hidden on Desktop) -->
        <div class="block lg:hidden">
            @foreach($userProgressData as $user)
                <div class="border-b border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center space-x-3 mb-3">
                        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                            <span class="text-sm font-medium text-blue-600">{{ substr($user['name'], 0, 1) }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ $user['name'] }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $user['email'] }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $user['team'] }}</div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-3 text-sm mb-3">
                        <div>
                            <span class="font-medium text-gray-500 dark:text-gray-400">Attempts:</span>
                            <div class="text-gray-900 dark:text-gray-100">
                                <span class="font-medium">{{ $user['completed_attempts'] }}</span> / {{ $user['total_attempts'] }}
                            </div>
                        </div>
                        <div>
                            <span class="font-medium text-gray-500 dark:text-gray-400">Completion:</span>
                            <div class="text-gray-900 dark:text-gray-100">{{ $user['completion_rate'] }}%</div>
                        </div>
                        <div>
                            <span class="font-medium text-gray-500 dark:text-gray-400">Team Points:</span>
                            <div>
                                @if($user['total_points'] > 0)
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium 
                                        {{ $user['total_points'] >= 1000 ? 'bg-green-100 text-green-800' : ($user['total_points'] >= 500 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                        {{ $user['total_points'] }} pts
                                    </span>
                                    @if(isset($user['team_points_breakdown']) && isset($user['team_points_breakdown']['breakdown_text']) && $user['team_points_breakdown']['breakdown_text'])
                                        <div class="text-xs text-gray-500 mt-1">{{ $user['team_points_breakdown']['breakdown_text'] }}</div>
                                    @elseif($user['total_points'] > 0)
                                        <div class="text-xs text-gray-500 mt-1">{{ $user['total_points'] }} pts total</div>
                                    @endif
                                    @if(isset($user['assessment_notes']) && $user['assessment_notes']->count() > 0)
                                        <button wire:click="showAssessmentNotes({{ $user['id'] }})" 
                                                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800 hover:bg-orange-200 transition-colors mt-1 cursor-pointer"
                                                title="Click to view assessment notes">
                                            <i class="fas fa-sticky-note mr-1"></i>
                                            Notes ({{ $user['assessment_notes']->count() }})
                                        </button>
                                    @endif
                                @else
                                    <span class="text-gray-400">N/A</span>
                                @endif
                            </div>
                        </div>
                        <div>
                            <span class="font-medium text-gray-500 dark:text-gray-400">Last Activity:</span>
                            <div class="text-xs text-gray-900 dark:text-gray-100">
                                @if($user['last_activity'])
                                    {{ \Carbon\Carbon::parse($user['last_activity'])->diffForHumans() }}
                                @else
                                    <span class="text-gray-400">Never</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 block">Progress:</span>
                        <div class="flex items-center">
                            <div class="w-full bg-gray-200 rounded-full h-2 mr-2">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $user['completion_rate'] }}%"></div>
                            </div>
                            <span class="text-xs text-gray-900 dark:text-gray-100 whitespace-nowrap">{{ $user['completion_rate'] }}%</span>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-2 mt-3">
                        <button wire:click="viewVerificationPhotos({{ $user['id'] }})" 
                                class="inline-flex items-center px-3 py-1 border border-transparent text-xs leading-4 font-medium rounded text-green-700 bg-green-100 hover:bg-green-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                            <i class="fas fa-camera mr-1"></i>
                            Photos
                        </button>
                        <button wire:click="showUserDetail({{ $user['id'] }})" 
                                class="inline-flex items-center px-3 py-1 border border-transparent text-xs leading-4 font-medium rounded text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                            <i class="fas fa-eye mr-1"></i>
                            Details
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Desktop Table View (Hidden on Mobile) -->
        <div class="hidden lg:block overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 xl:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">User</th>
                        <th class="px-4 xl:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Team</th>
                        <th class="px-4 xl:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Attempts</th>
                        <th class="px-4 xl:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Completion Rate</th>
                        <th class="px-4 xl:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Team Points</th>
                        <th class="px-4 xl:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden xl:table-cell">Last Activity</th>
                        <th class="px-4 xl:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200">
                    @foreach($userProgressData as $user)
                        <tr class="hover:bg-gray-50 dark:bg-gray-700 transition-colors">
                            <td class="px-4 xl:px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                        <span class="text-sm font-medium text-blue-600">{{ substr($user['name'], 0, 1) }}</span>
                                    </div>
                                    <div class="ml-3 min-w-0">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ $user['name'] }}</div>
                                        <div class="text-xs xl:text-sm text-gray-500 dark:text-gray-400 truncate">{{ $user['email'] }}</div>
                                    </div>
                                    @if($user['team_id'])
                                        <button wire:click="showTeamDetail({{ $user['team_id'] }})" 
                                                class="ml-2 p-1 text-gray-400 hover:text-gray-600 dark:text-gray-400 transition-colors"
                                                title="View Team Questionnaire Details">
                                            <i class="fas fa-eye text-xs"></i>
                                        </button>
                                    @endif
                                    <button wire:click="showUserDetail({{ $user['id'] }})" 
                                            class="ml-1 p-1 text-blue-400 hover:text-blue-600 dark:text-blue-400 transition-colors"
                                            title="View User Progress Details">
                                        <i class="fas fa-chart-line text-xs"></i>
                                    </button>
                                </div>
                            </td>
                            <td class="px-4 xl:px-6 py-4 whitespace-nowrap text-xs xl:text-sm text-gray-900 dark:text-gray-100">{{ $user['team'] }}</td>
                            <td class="px-4 xl:px-6 py-4 whitespace-nowrap text-xs xl:text-sm text-gray-900 dark:text-gray-100">
                                <div class="flex flex-col">
                                    <span><span class="font-medium">{{ $user['completed_attempts'] }}</span> / {{ $user['total_attempts'] }}</span>
                                    @if($user['in_progress_attempts'] > 0)
                                        <span class="text-xs text-yellow-600">{{ $user['in_progress_attempts'] }} in progress</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 xl:px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-12 xl:w-16 bg-gray-200 rounded-full h-2 mr-2">
                                        <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: {{ $user['completion_rate'] }}%"></div>
                                    </div>
                                    <span class="text-xs xl:text-sm text-gray-900 dark:text-gray-100 whitespace-nowrap">{{ $user['completion_rate'] }}%</span>
                                </div>
                            </td>
                            <td class="px-4 xl:px-6 py-4 whitespace-nowrap text-xs xl:text-sm text-gray-900 dark:text-gray-100">
                                @if($user['total_points'] > 0)
                                    <div class="flex flex-col gap-1">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium 
                                            {{ $user['total_points'] >= 1000 ? 'bg-green-100 text-green-800' : ($user['total_points'] >= 500 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                            {{ $user['total_points'] }} pts
                                        </span>
                                        @if(isset($user['team_points_breakdown']) && isset($user['team_points_breakdown']['breakdown_text']) && $user['team_points_breakdown']['breakdown_text'])
                                            <span class="text-xs text-gray-500 dark:text-gray-400" title="Points Breakdown">
                                                {{ $user['team_points_breakdown']['breakdown_text'] }}
                                            </span>
                                        @elseif($user['total_points'] > 0)
                                            <span class="text-xs text-gray-500 dark:text-gray-400" title="Team Points">
                                                {{ $user['total_points'] }} pts total
                                            </span>
                                        @endif
                                        @if($user['average_score'] > 0)
                                            <span class="text-xs text-blue-600 dark:text-blue-400">Avg: {{ $user['average_score'] }} pts</span>
                                        @endif
                                        @if(isset($user['assessment_notes']) && $user['assessment_notes']->count() > 0)
                                            <button wire:click="showAssessmentNotes({{ $user['id'] }})" 
                                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800 hover:bg-orange-200 transition-colors mt-1 cursor-pointer"
                                                    title="Click to view assessment notes">
                                                <i class="fas fa-sticky-note mr-1"></i>
                                                Notes ({{ $user['assessment_notes']->count() }})
                                            </button>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-gray-400">N/A</span>
                                @endif
                            </td>
                            <td class="px-4 xl:px-6 py-4 whitespace-nowrap text-xs xl:text-sm text-gray-500 dark:text-gray-400 hidden xl:table-cell">
                                <div class="flex flex-col">
                                    @if($user['last_activity'])
                                        <span>{{ \Carbon\Carbon::parse($user['last_activity'])->diffForHumans() }}</span>
                                    @else
                                        <span class="text-gray-400">Never</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 xl:px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-2">
                                    <button wire:click="viewVerificationPhotos({{ $user['id'] }})" 
                                            class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300"
                                            title="View Verification Photos">
                                        <i class="fas fa-camera"></i>
                                    </button>
                                    <button wire:click="showUserDetail({{ $user['id'] }})" 
                                            class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                            title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Team Detail Modal -->
    @if($showTeamDetailView && $teamDetailData)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-7xl w-full max-h-[90vh] overflow-hidden">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-users text-indigo-600 text-lg"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $teamDetailData['team']->name }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $teamDetailData['team']->department ?? 'No Department' }} • {{ $teamDetailData['team_total_points'] ?? $teamDetailData['team']->initial_points ?? 1000 }} total points</p>
                        </div>
                    </div>
                    <button wire:click="hideTeamDetail" class="text-gray-400 hover:text-gray-600 dark:text-gray-400 p-2">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
                
                <!-- Modal Content -->
                <div class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
                    <!-- Team Summary -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Attempts</div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $teamDetailData['total_attempts'] }}</div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Completed</div>
                            <div class="text-2xl font-bold text-green-600">{{ $teamDetailData['completed_attempts'] }}</div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Team Points (Highest)</div>
                            <div class="text-2xl font-bold text-indigo-600">{{ $teamDetailData['team_total_points'] ?? 0 }}</div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Average Team Points</div>
                            <div class="text-2xl font-bold text-blue-600">{{ $teamDetailData['team_average_points'] ?? 0 }}</div>
                        </div>
                    </div>
                    
                    <!-- Team Questionnaire Completion Summary -->
                    @if(isset($teamDetailData['questionnaire_completion_summary']) && $teamDetailData['questionnaire_completion_summary']->count() > 0)
                        <div class="mb-6">
                            <h4 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                                <i class="fas fa-chart-pie text-blue-600"></i>
                                Team Questionnaire Completion Summary
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach($teamDetailData['questionnaire_completion_summary'] as $summary)
                                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                        <h5 class="font-medium text-gray-900 dark:text-gray-100 mb-2">{{ $summary['questionnaire_title'] }}</h5>
                                        <div class="space-y-2 text-sm">
                                            <div class="flex justify-between">
                                                <span class="text-gray-600 dark:text-gray-400">Team Members Completed:</span>
                                                <span class="font-medium">{{ $summary['users_completed'] }}/{{ $summary['total_team_members'] }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-gray-600 dark:text-gray-400">Completion Rate:</span>
                                                <span class="font-medium {{ $summary['team_completion_rate'] >= 80 ? 'text-green-600' : ($summary['team_completion_rate'] >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                                                    {{ $summary['team_completion_rate'] }}%
                                                </span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-gray-600 dark:text-gray-400">Questions Mastered:</span>
                                                <span class="font-medium">{{ $summary['questions_mastered'] }}/{{ $summary['total_questions'] }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-gray-600 dark:text-gray-400">Mastery Rate:</span>
                                                <span class="font-medium {{ $summary['questions_mastery_rate'] >= 80 ? 'text-green-600' : ($summary['questions_mastery_rate'] >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                                                    {{ $summary['questions_mastery_rate'] }}%
                                                </span>
                                            </div>
                                        </div>
                                        <!-- Progress Bar -->
                                        <div class="mt-3">
                                            <div class="w-full bg-gray-200 rounded-full h-2">
                                                <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: {{ $summary['team_completion_rate'] }}%"></div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    
                    <!-- Questionnaire Details -->
                    <div>
                        <h4 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Individual Questionnaire Performance</h4>
                        @if($teamDetailData['questionnaire_details']->count() > 0)
                            <div class="space-y-6">
                                @foreach($teamDetailData['questionnaire_details'] as $questionnaire)
                                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                                        <!-- Questionnaire Header -->
                                        <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                                            <div class="flex items-center justify-between">
                                                <h5 class="font-medium text-gray-900 dark:text-gray-100">{{ $questionnaire['questionnaire_title'] }}</h5>
                                                <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                                                    <span>{{ $questionnaire['completed_attempts'] }}/{{ $questionnaire['total_attempts'] }} completed</span>
                                                    <span>{{ $questionnaire['completion_rate'] }}% rate</span>
                                                    @if($questionnaire['average_completion_time'])
                                                        <span>{{ $questionnaire['average_completion_time'] }} avg</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Performance Stats -->
                                        <div class="px-4 py-3 bg-blue-50 border-b border-gray-200 dark:border-gray-700">
                                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                                <div>
                                                    <span class="font-medium text-gray-700 dark:text-gray-300">Avg Team Points:</span>
                                                    <span class="text-blue-600 font-semibold">{{ $questionnaire['average_score'] }} pts</span>
                                                </div>
                                                @if($questionnaire['fastest_completion'])
                                                    <div>
                                                        <span class="font-medium text-gray-700 dark:text-gray-300">Fastest:</span>
                                                        <span class="text-green-600 font-semibold">{{ $questionnaire['fastest_completion'] }}</span>
                                                    </div>
                                                @endif
                                                @if($questionnaire['slowest_completion'])
                                                    <div>
                                                        <span class="font-medium text-gray-700 dark:text-gray-300">Slowest:</span>
                                                        <span class="text-red-600 font-semibold">{{ $questionnaire['slowest_completion'] }}</span>
                                                    </div>
                                                @endif
                                                @if($questionnaire['average_completion_time'])
                                                    <div>
                                                        <span class="font-medium text-gray-700 dark:text-gray-300">Average:</span>
                                                        <span class="text-gray-600 dark:text-gray-400 font-semibold">{{ $questionnaire['average_completion_time'] }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                        
                                        <!-- Individual Attempts -->
                                        <div class="p-4">
                                            <h6 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Individual Attempts</h6>
                                            <div class="overflow-x-auto">
                                                <table class="min-w-full text-sm">
                                                    <thead>
                                                        <tr class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                            <th class="pb-2">User</th>
                                                            <th class="pb-2">Status</th>
                                                            <th class="pb-2">Started</th>
                                                            <th class="pb-2">Completed</th>
                                                            <th class="pb-2">Duration</th>
                                                            <th class="pb-2">Team Points</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="space-y-1">
                                                        @foreach($questionnaire['attempts_details'] as $attempt)
                                                            <tr class="border-t border-gray-100">
                                                                <td class="py-2 font-medium text-gray-900 dark:text-gray-100">{{ $attempt['user_name'] }}</td>
                                                                <td class="py-2">
                                                                    @if($attempt['status'] === 'completed')
                                                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                                            Completed
                                                                        </span>
                                                                    @elseif($attempt['status'] === 'started')
                                                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                                            In Progress
                                                                        </span>
                                                                    @else
                                                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                                            {{ ucfirst($attempt['status']) }}
                                                                        </span>
                                                                    @endif
                                                                </td>
                                                                <td class="py-2 text-gray-600 dark:text-gray-400">
                                                                    @if($attempt['started_at'])
                                                                        {{ $attempt['started_at']->format('M d, H:i') }}
                                                                    @else
                                                                        -
                                                                    @endif
                                                                </td>
                                                                <td class="py-2 text-gray-600 dark:text-gray-400">
                                                                    @if($attempt['completed_at'])
                                                                        {{ $attempt['completed_at']->format('M d, H:i') }}
                                                                    @else
                                                                        -
                                                                    @endif
                                                                </td>
                                                                <td class="py-2 text-gray-600 dark:text-gray-400">
                                                                    @if($attempt['completion_time'])
                                                                        {{ $attempt['completion_time'] }}
                                                                    @else
                                                                        -
                                                                    @endif
                                                                </td>
                                                                <td class="py-2">
                                                                    @if($attempt['total_score'] !== null)
                                                                        <span class="font-semibold {{ $attempt['total_score'] >= 1000 ? 'text-green-600' : ($attempt['total_score'] >= 800 ? 'text-yellow-600' : 'text-red-600') }}">
                                                                            {{ $attempt['total_score'] }} pts
                                                                        </span>
                                                                    @else
                                                                        -
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                                <i class="fas fa-inbox text-3xl mb-3"></i>
                                <p>No questionnaire attempts found in the selected timeframe</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
    
    <!-- User Detail Modal -->
    @if($showDetailedView && $userDetailData)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-6xl w-full max-h-[90vh] overflow-hidden">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-user text-blue-600 text-lg"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $userDetailData['user']->name }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $userDetailData['user']->email }} • {{ $userDetailData['user']->team->name ?? 'No Team' }}</p>
                        </div>
                    </div>
                    <button wire:click="hideUserDetail" class="text-gray-400 hover:text-gray-600 dark:text-gray-400 p-2">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
                
                <!-- Modal Content -->
                <div class="p-6 overflow-y-auto max-h-[calc(90vh-120px)] space-y-6">
                    
                    <!-- Enhanced Team Points Breakdown -->
                    @if($userDetailData['user']->team)
                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900 dark:to-indigo-900 rounded-lg p-4">
                            <h4 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-3 flex items-center gap-2">
                                <i class="fas fa-trophy text-yellow-500"></i>
                                Team Points Breakdown
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-blue-600">{{ $userDetailData['team_points_breakdown']['base_points'] ?? 0 }}</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">Base Points</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-green-600">{{ $userDetailData['team_points_breakdown']['earned_points'] ?? 0 }}</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">Earned Points</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-purple-600">{{ $userDetailData['team_points_breakdown']['assessment_bonus'] ?? 0 }}</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">Assessment Bonus</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-3xl font-bold text-indigo-600">{{ $userDetailData['team_points_breakdown']['total'] ?? 0 }}</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">Total Points</div>
                                </div>
                            </div>
                            <div class="mt-3 text-center">
                                <div class="text-lg font-medium text-gray-700 dark:text-gray-300">
                                    {{ $userDetailData['team_points_breakdown']['breakdown_text'] ?? 'No breakdown available' }}
                                </div>
                            </div>
                        </div>
                    @endif
                    
                    <!-- Questionnaire Completion Details -->
                    @if($userDetailData['questionnaire_details']->count() > 0)
                        <div>
                            <h4 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                                <i class="fas fa-clipboard-list text-blue-600"></i>
                                Questionnaire Progress
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @foreach($userDetailData['questionnaire_details'] as $quest)
                                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                        <h5 class="font-medium text-gray-900 dark:text-gray-100 mb-2">{{ $quest['questionnaire_title'] }}</h5>
                                        <div class="space-y-2 text-sm">
                                            <div class="flex justify-between">
                                                <span class="text-gray-600 dark:text-gray-400">Attempts:</span>
                                                <span class="font-medium">{{ $quest['completed_attempts'] }}/{{ $quest['total_attempts'] }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-gray-600 dark:text-gray-400">Questions Correct:</span>
                                                <span class="font-medium">{{ $quest['questions_answered_correctly'] }}/{{ $quest['total_questions'] }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-gray-600 dark:text-gray-400">Mastery Rate:</span>
                                                <span class="font-medium {{ $quest['questions_completion_rate'] >= 80 ? 'text-green-600' : ($quest['questions_completion_rate'] >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                                                    {{ $quest['questions_completion_rate'] }}%
                                                </span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-gray-600 dark:text-gray-400">Best Score:</span>
                                                <span class="font-medium text-blue-600">{{ $quest['best_score'] }} pts</span>
                                            </div>
                                            @if($quest['last_attempt_date'])
                                                <div class="flex justify-between">
                                                    <span class="text-gray-600 dark:text-gray-400">Last Attempt:</span>
                                                    <span class="text-xs">{{ \Carbon\Carbon::parse($quest['last_attempt_date'])->diffForHumans() }}</span>
                                                </div>
                                            @endif
                                        </div>
                                        <!-- Progress Bar -->
                                        <div class="mt-3">
                                            <div class="w-full bg-gray-200 rounded-full h-2">
                                                <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: {{ $quest['questions_completion_rate'] }}%"></div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    
                    <!-- Assessment Notes -->
                    @if($userDetailData['assessment_notes']->count() > 0)
                        <div>
                            <h4 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                                <i class="fas fa-sticky-note text-orange-600"></i>
                                Assessment Notes & Feedback
                            </h4>
                            <div class="space-y-4">
                                @foreach($userDetailData['assessment_notes'] as $note)
                                    <div class="bg-orange-50 dark:bg-orange-900 border border-orange-200 dark:border-orange-700 rounded-lg p-4">
                                        <div class="flex items-start justify-between mb-2">
                                            <h5 class="font-medium text-gray-900 dark:text-gray-100">{{ $note['questionnaire_title'] }}</h5>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ \Carbon\Carbon::parse($note['assessment_date'])->format('M d, Y H:i') }}
                                            </div>
                                        </div>
                                        @if($note['notes'])
                                            <div class="mb-2">
                                                <span class="font-medium text-gray-700 dark:text-gray-300">Notes:</span>
                                                <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $note['notes'] }}</p>
                                            </div>
                                        @endif
                                        @if($note['total_deposit'])
                                            <div class="text-sm">
                                                <span class="font-medium text-gray-700 dark:text-gray-300">Total Deposit:</span>
                                                <span class="text-green-600 dark:text-green-400 font-medium">{{ $note['total_deposit'] }} pts</span>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    
                    <!-- Recent Attempts Summary -->
                    <div>
                        <h4 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                            <i class="fas fa-history text-gray-600"></i>
                            Recent Attempts
                        </h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        <th class="pb-2">Questionnaire</th>
                                        <th class="pb-2">Status</th>
                                        <th class="pb-2">Points Earned</th>
                                        <th class="pb-2">Started</th>
                                        <th class="pb-2">Duration</th>
                                    </tr>
                                </thead>
                                <tbody class="space-y-1">
                                    @foreach($userDetailData['attempts'] as $attempt)
                                        <tr class="border-t border-gray-100">
                                            <td class="py-2 font-medium text-gray-900 dark:text-gray-100">{{ $attempt['questionnaire_title'] }}</td>
                                            <td class="py-2">
                                                @if($attempt['status'] === 'completed')
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        Completed
                                                    </span>
                                                @elseif($attempt['status'] === 'started')
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                        In Progress
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                        {{ ucfirst($attempt['status']) }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="py-2">
                                                @if($attempt['earned_points'] !== null)
                                                    <span class="font-semibold {{ $attempt['earned_points'] >= 200 ? 'text-green-600' : ($attempt['earned_points'] >= 100 ? 'text-yellow-600' : 'text-red-600') }}">
                                                        {{ number_format($attempt['earned_points']) }} pts
                                                    </span>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="py-2 text-gray-600 dark:text-gray-400">
                                                @if($attempt['started_at'])
                                                    {{ $attempt['started_at']->format('M d, H:i') }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="py-2 text-gray-600 dark:text-gray-400">
                                                @if($attempt['duration'])
                                                    {{ $attempt['duration'] }} min
                                                @else
                                                    -
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Brief Feedback Section -->
                    @php
                        // Get brief feedback data for this user safely
                        $userBriefFeedback = collect();
                        try {
                            if (isset($userDetailData['user']) && $userDetailData['user']->id) {
                                $userBriefFeedback = \App\Models\UserAnswer::whereHas('quizAttempt', function($query) use ($userDetailData) {
                                    $query->where('user_id', $userDetailData['user']->id)
                                          ->where('status', 'completed');
                                })
                                ->whereHas('question', function($query) {
                                    $query->where('type', 'brief');
                                })
                                ->with(['question:id,question', 'quizAttempt.questionnaire:id,title'])
                                ->orderBy('created_at', 'desc')
                                ->get();
                            }
                        } catch (\Exception $e) {
                            // Silently handle any database errors
                            $userBriefFeedback = collect();
                        }
                    @endphp
                    
                    <!-- Always show the section, even if empty -->
                    <div class="pt-6">
                        <h4 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                            <i class="fas fa-comments text-blue-500"></i>
                            Brief Feedback Responses 
                            @if($userBriefFeedback->count() > 0)
                                ({{ $userBriefFeedback->count() }})
                            @endif
                        </h4>
                        
                        @if($userBriefFeedback->count() > 0)
                            <div class="space-y-4 max-h-64 overflow-y-auto">
                                @foreach($userBriefFeedback as $feedback)
                                    <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-3 bg-gray-50 dark:bg-gray-700">
                                        <div class="flex items-center justify-between mb-2">
                                            <div class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                                {{ $feedback->quizAttempt->questionnaire->title ?? 'Unknown Quiz' }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $feedback->created_at->format('M d, Y H:i') }}
                                            </div>
                                        </div>
                                        
                                        <!-- Question -->
                                        <div class="mb-2">
                                            <div class="text-xs font-medium text-blue-700 dark:text-blue-300 mb-1">Question:</div>
                                            <div class="text-xs text-gray-600 dark:text-gray-400 italic bg-white dark:bg-gray-800 p-2 rounded border-l-2 border-blue-500">
                                                {{ $feedback->question->question ?? 'Question not available' }}
                                            </div>
                                        </div>
                                        
                                        <!-- Response -->
                                        <div>
                                            <div class="text-xs font-medium text-green-700 dark:text-green-300 mb-1">Response:</div>
                                            <div class="text-xs text-gray-900 dark:text-gray-100 bg-white dark:bg-gray-800 p-2 rounded border-l-2 border-green-500">
                                                @if(!empty($feedback->answer))
                                                    {{ $feedback->answer }}
                                                @else
                                                    <span class="text-gray-500 dark:text-gray-400 italic">No response provided</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-6 bg-gray-50 dark:bg-gray-700 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600">
                                <div class="text-gray-400 dark:text-gray-500 mb-2">
                                    <i class="fas fa-comment-slash text-2xl"></i>
                                </div>
                                <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">No Brief Feedback Yet</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                    This user hasn't submitted any brief feedback responses from questionnaires.
                                </p>
                            </div>
                        @endif
                    </div>
                    
                </div>
                
                <!-- Modal Footer -->
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end">
                    <button wire:click="hideUserDetail" 
                            class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm rounded-lg transition-colors duration-200">
                        <i class="fas fa-times mr-2"></i>Close
                    </button>
                </div>
            </div>
        </div>
    @endif
    
    <!-- Assessment Notes Modal (Simplified) -->
    @if($showAssessmentNotesView && $assessmentNotesData)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-4xl w-full max-h-[80vh] overflow-hidden">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-sticky-note text-orange-600 text-lg"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Assessment Notes</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $assessmentNotesData['user']->name }} • {{ $assessmentNotesData['user']->email }}</p>
                            @if(isset($assessmentNotesData['team_points_breakdown']))
                                <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                                    Team Points: {{ $assessmentNotesData['team_points_breakdown']['breakdown_text'] }}
                                </p>
                            @endif
                        </div>
                    </div>
                    <button wire:click="hideAssessmentNotes" class="text-gray-400 hover:text-gray-600 dark:text-gray-400 p-2">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
                
                <!-- Modal Content -->
                <div class="p-6 overflow-y-auto max-h-[calc(80vh-120px)]">
                    @if($assessmentNotesData['assessment_notes']->count() > 0)
                        <div class="space-y-4">
                            @foreach($assessmentNotesData['assessment_notes'] as $note)
                                <div class="bg-orange-50 dark:bg-orange-900 border border-orange-200 dark:border-orange-700 rounded-lg p-4">
                                    <div class="flex items-start justify-between mb-3">
                                        <div>
                                            <h4 class="font-medium text-gray-900 dark:text-gray-100">{{ $note['questionnaire_title'] }}</h4>
                                            <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                                {{ \Carbon\Carbon::parse($note['assessment_date'])->format('M d, Y H:i') }}
                                            </div>
                                        </div>
                                        @if($note['total_deposit'])
                                            <div class="text-sm">
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    {{ $note['total_deposit'] }} pts
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                    
                                    <div class="space-y-3">
                                        @if($note['notes'])
                                            <div>
                                                <span class="font-medium text-gray-700 dark:text-gray-300 text-sm">Notes:</span>
                                                <p class="text-gray-600 dark:text-gray-400 mt-1 text-sm leading-relaxed">{{ $note['notes'] }}</p>
                                            </div>
                                        @endif
                                        
                                        @if(!$note['notes'])
                                            <p class="text-gray-500 dark:text-gray-400 text-sm italic">No detailed notes available for this assessment.</p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-sticky-note text-gray-400 text-2xl"></i>
                            </div>
                            <h4 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">No Assessment Notes</h4>
                            <p class="text-gray-500 dark:text-gray-400">This user doesn't have any assessment notes in the selected timeframe.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Verification Photos Modal --}}
    <div x-data="{ 
        show: false,
        verificationData: {
            user_id: null,
            user_name: '',
            assessments: []
        },
        selectedPhoto: null,
        showPhotoModal: false
    }" 
         x-show="show" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @open-verification-photos-modal.window="
            show = true;
            verificationData = $event.detail[0] || $event.detail;
         "
         @close-verification-photos-modal.window="show = false">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" 
                 x-on:click="show = false"></div>

            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full sm:p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">
                        <i class="fas fa-camera mr-2"></i>
                        <span x-text="verificationData.user_name"></span> - Verification Photos
                    </h3>
                    <button x-on:click="show = false" class="text-gray-400 hover:text-gray-600 dark:text-gray-400">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <template x-if="verificationData.assessments && verificationData.assessments.length > 0">
                    <div class="space-y-4 max-h-96 overflow-y-auto">
                        <template x-for="assessment in verificationData.assessments" :key="assessment.id">
                            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="flex items-center flex-1">
                                    <div class="w-16 h-16 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                                        <i class="fas fa-user text-blue-600"></i>
                                    </div>
                                    <div class="flex-1">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100" x-text="assessment.questionnaire_title"></div>
                                        <div class="text-xs text-gray-400">
                                            <span>Completed: </span><span x-text="assessment.completed_at"></span>
                                        </div>
                                        <div class="text-xs text-gray-400">
                                            <span>Photo: </span><span x-text="assessment.photo_captured_at"></span>
                                        </div>
                                        <div class="text-sm font-semibold text-green-600">
                                            Points: <span x-text="assessment.total_deposit"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <template x-if="assessment.verification_photo">
                                        <button 
                                            @click="selectedPhoto = assessment.verification_photo; showPhotoModal = true"
                                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                            <i class="fas fa-eye mr-1"></i>
                                            View Photo
                                        </button>
                                    </template>
                                    <template x-if="!assessment.verification_photo">
                                        <span class="text-sm text-gray-400">No photo</span>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
                
                <template x-if="!verificationData.assessments || verificationData.assessments.length === 0">
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <i class="fas fa-camera text-4xl mb-4"></i>
                        <p>No verification photos found</p>
                        <p class="text-sm">Photos are captured when users submit fun game assessments</p>
                    </div>
                </template>
            </div>
        </div>

        {{-- Photo Viewer Modal --}}
        <div x-show="showPhotoModal" 
             x-cloak
             class="fixed inset-0 z-60 overflow-y-auto"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-black bg-opacity-90 transition-opacity" 
                     x-on:click="showPhotoModal = false"></div>

                <div class="inline-block align-middle bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">
                                Verification Photo
                            </h3>
                            <button x-on:click="showPhotoModal = false" class="text-gray-400 hover:text-gray-600">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="text-center">
                            <img :src="selectedPhoto" 
                                 alt="Verification Photo" 
                                 class="max-w-full max-h-96 mx-auto rounded-lg shadow-lg"
                                 x-show="selectedPhoto">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Team Performance Chart
    const teamPerformanceCtx = document.getElementById('teamPerformanceChart');
    if (teamPerformanceCtx) {
        new Chart(teamPerformanceCtx, {
            type: 'bar',
            data: {
                labels: @json($teamPerformanceData->pluck('name')),
                datasets: [{
                    label: 'Team Points',
                    data: @json($teamPerformanceData->pluck('total_points')),
                    backgroundColor: 'rgba(99, 102, 241, 0.8)',
                    borderColor: 'rgb(99, 102, 241)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
});

// Re-initialize charts when Livewire updates
document.addEventListener('livewire:morph-updated', function() {
    // Re-run chart initialization
    setTimeout(function() {
        const event = new Event('DOMContentLoaded');
        document.dispatchEvent(event);
    }, 100);
});
</script>
@endpush