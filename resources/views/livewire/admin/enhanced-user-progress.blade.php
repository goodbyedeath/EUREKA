<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100">
    {{-- Enhanced Header --}}
    <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="py-6">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center">
                            <i class="fas fa-chart-line text-white text-xl"></i>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Enhanced User Progress</h1>
                            <p class="text-gray-600">Advanced monitoring and analytics dashboard</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <button wire:click="refreshData" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-sync-alt" wire:loading.class="animate-spin"></i>
                            <span>Refresh</span>
                        </button>
                        <button wire:click="exportUserProgress" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors">
                            <i class="fas fa-download"></i>
                            <span>Export</span>
                        </button>
                    </div>
                </div>

                {{-- Enhanced Navigation Tabs --}}
                <div class="mt-6">
                    <nav class="flex space-x-1 bg-gray-100 rounded-xl p-1">
                        <button wire:click="$set('view', 'overview')" 
                                class="flex-1 flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium rounded-lg transition-all {{ $view === 'overview' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Overview</span>
                        </button>
                        <button wire:click="$set('view', 'users')" 
                                class="flex-1 flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium rounded-lg transition-all {{ $view === 'users' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">
                            <i class="fas fa-users"></i>
                            <span>Users</span>
                        </button>
                        <button wire:click="$set('view', 'teams')" 
                                class="flex-1 flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium rounded-lg transition-all {{ $view === 'teams' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">
                            <i class="fas fa-users-cog"></i>
                            <span>Teams</span>
                        </button>
                        <button wire:click="$set('view', 'analytics')" 
                                class="flex-1 flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium rounded-lg transition-all {{ $view === 'analytics' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">
                            <i class="fas fa-chart-bar"></i>
                            <span>Analytics</span>
                        </button>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        {{-- Overview Tab --}}
        @if($view === 'overview')
            {{-- Key Metrics Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                            <i class="fas fa-users text-blue-600"></i>
                        </div>
                        <span class="text-2xl font-bold text-gray-900">{{ number_format($analyticsData['total_users'] ?? 0) }}</span>
                    </div>
                    <h3 class="text-sm font-medium text-gray-600">Total Users</h3>
                    <div class="mt-2 flex items-center gap-2">
                        <span class="text-xs px-2 py-1 bg-green-100 text-green-700 rounded-full">
                            {{ $analyticsData['user_activity_rate'] ?? 0 }}% Active
                        </span>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center">
                            <i class="fas fa-clipboard-check text-emerald-600"></i>
                        </div>
                        <span class="text-2xl font-bold text-gray-900">{{ number_format($analyticsData['completed_attempts'] ?? 0) }}</span>
                    </div>
                    <h3 class="text-sm font-medium text-gray-600">Completed Attempts</h3>
                    <div class="mt-2 flex items-center gap-2">
                        <span class="text-xs px-2 py-1 bg-blue-100 text-blue-700 rounded-full">
                            {{ $analyticsData['completion_rate'] ?? 0 }}% Rate
                        </span>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center">
                            <i class="fas fa-star text-amber-600"></i>
                        </div>
                        <span class="text-2xl font-bold text-gray-900">{{ number_format($analyticsData['average_score'] ?? 0, 1) }}</span>
                    </div>
                    <h3 class="text-sm font-medium text-gray-600">Average Score</h3>
                    <div class="mt-2">
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-amber-500 h-2 rounded-full" style="width: {{ min(100, ($analyticsData['average_score'] ?? 0)) }}%"></div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                            <i class="fas fa-clock text-purple-600"></i>
                        </div>
                        <span class="text-2xl font-bold text-gray-900">{{ gmdate('i:s', $analyticsData['average_completion_time'] ?? 0) }}</span>
                    </div>
                    <h3 class="text-sm font-medium text-gray-600">Avg. Completion Time</h3>
                    <div class="mt-2 flex items-center gap-2">
                        <span class="text-xs px-2 py-1 bg-purple-100 text-purple-700 rounded-full">
                            Minutes
                        </span>
                    </div>
                </div>
            </div>

            {{-- Charts Section --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                {{-- Completion Trends Chart --}}
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-900">Completion Trends</h3>
                        <select wire:model.live="selectedTimeframe" class="text-sm border border-gray-300 rounded-lg px-3 py-1">
                            <option value="7">Last 7 days</option>
                            <option value="14">Last 14 days</option>
                            <option value="30">Last 30 days</option>
                        </select>
                    </div>
                    <canvas id="completionTrendsChart" width="400" height="200"></canvas>
                </div>

                {{-- Team Performance Chart --}}
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 mb-6">Top Performing Teams</h3>
                    <div class="space-y-4">
                        @forelse($teamPerformance as $index => $team)
                            <div class="flex items-center gap-4">
                                <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center text-white font-bold text-sm">
                                    {{ $index + 1 }}
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="font-medium text-gray-900">{{ $team->name }}</span>
                                        <span class="text-sm font-semibold text-gray-700">{{ $team->avg_score }}/100</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-gradient-to-r from-blue-500 to-purple-600 h-2 rounded-full" style="width: {{ $team->avg_score }}%"></div>
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">{{ $team->member_count }} members</div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-chart-bar text-3xl mb-2"></i>
                                <p>No team performance data available</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Recent Activity --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Recent Activity (Last 24 Hours)</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        @forelse($recentActivity as $activity)
                            <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl">
                                <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center text-white">
                                    <i class="fas fa-{{ $activity['status'] === 'completed' ? 'check' : ($activity['status'] === 'started' ? 'play' : 'pause') }}"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="font-medium text-gray-900">{{ $activity['user_name'] }}</span>
                                        <span class="text-gray-500">{{ $activity['status'] }}</span>
                                        <span class="font-medium text-gray-700">{{ $activity['questionnaire_title'] }}</span>
                                    </div>
                                    <div class="flex items-center gap-4 text-sm text-gray-600">
                                        @if($activity['score'])
                                            <span class="flex items-center gap-1">
                                                <i class="fas fa-star text-amber-500"></i>
                                                {{ $activity['score'] }}/100
                                            </span>
                                        @endif
                                        <span class="flex items-center gap-1">
                                            <i class="fas fa-clock"></i>
                                            {{ $activity['time_ago'] }}
                                        </span>
                                    </div>
                                </div>
                                <span class="px-3 py-1 text-xs font-medium rounded-full {{ $activity['status'] === 'completed' ? 'bg-green-100 text-green-700' : ($activity['status'] === 'started' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700') }}">
                                    {{ ucfirst($activity['status']) }}
                                </span>
                            </div>
                        @empty
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-clock text-3xl mb-2"></i>
                                <p>No recent activity</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif

        {{-- Users Tab --}}
        @if($view === 'users')
            {{-- Filters --}}
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search Users</label>
                        <input type="text" wire:model.live.debounce.300ms="searchTerm" placeholder="Name or email..." 
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Team Filter</label>
                        <select wire:model.live="selectedTeam" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Teams</option>
                            @foreach($availableTeams as $team)
                                <option value="{{ $team->id }}">{{ $team->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Activity Status</label>
                        <select wire:model.live="selectedStatus" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="all">All Users</option>
                            <option value="active">Active (7 days)</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Timeframe</label>
                        <select wire:model.live="selectedTimeframe" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="7">Last 7 days</option>
                            <option value="30">Last 30 days</option>
                            <option value="90">Last 90 days</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Users Table --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Users Progress ({{ $users->total() }} total)</h3>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" wire:click="sortBy('name')">
                                    <div class="flex items-center gap-2">
                                        User
                                        @if($sortBy === 'name')
                                            <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-blue-500"></i>
                                        @endif
                                    </div>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Team</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" wire:click="sortBy('progress')">
                                    <div class="flex items-center gap-2">
                                        Progress
                                        @if($sortBy === 'progress')
                                            <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-blue-500"></i>
                                        @endif
                                    </div>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" wire:click="sortBy('activity')">
                                    <div class="flex items-center gap-2">
                                        Last Activity
                                        @if($sortBy === 'activity')
                                            <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-blue-500"></i>
                                        @endif
                                    </div>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($users as $user)
                                @php
                                    $totalAttempts = $user->quizAttempts->count();
                                    $completedAttempts = $user->quizAttempts->where('status', 'completed')->count();
                                    $avgScore = $user->quizAttempts->where('status', 'completed')->avg('total_score');
                                    $completionRate = $totalAttempts > 0 ? round(($completedAttempts / $totalAttempts) * 100, 1) : 0;
                                @endphp
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center text-white font-semibold">
                                                {{ strtoupper(substr($user->name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                                                <div class="text-sm text-gray-500">{{ $user->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($user->team)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ $user->team->name }}
                                            </span>
                                        @else
                                            <span class="text-gray-400">No team</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="space-y-2">
                                            <div class="flex items-center justify-between text-sm">
                                                <span class="text-gray-600">{{ $completedAttempts }}/{{ $totalAttempts }} completed</span>
                                                <span class="font-medium">{{ $completionRate }}%</span>
                                            </div>
                                            <div class="w-full bg-gray-200 rounded-full h-2">
                                                <div class="bg-gradient-to-r from-blue-500 to-purple-600 h-2 rounded-full" style="width: {{ $completionRate }}%"></div>
                                            </div>
                                            @if($avgScore)
                                                <div class="text-xs text-gray-500">Avg. Score: {{ round($avgScore, 1) }}/100</div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($user->last_login_at)
                                            <div class="text-sm text-gray-900">{{ $user->last_login_at->diffForHumans() }}</div>
                                            <div class="text-xs text-gray-500">{{ $user->last_login_at->format('M d, Y H:i') }}</div>
                                        @else
                                            <span class="text-gray-400">Never logged in</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button class="text-blue-600 hover:text-blue-900 mr-3">View Details</button>
                                        <button class="text-indigo-600 hover:text-indigo-900">Send Message</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center">
                                        <div class="text-gray-500">
                                            <i class="fas fa-users text-4xl mb-4"></i>
                                            <p class="text-lg font-medium">No users found</p>
                                            <p class="text-sm">Try adjusting your filters</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($users->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200">
                        {{ $users->links() }}
                    </div>
                @endif
            </div>
        @endif

        {{-- Teams Tab --}}
        @if($view === 'teams')
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">Teams Performance</h3>
                        <div class="flex items-center gap-4">
                            <input type="text" wire:model.live.debounce.300ms="searchTerm" placeholder="Search teams..." 
                                   class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <select wire:model.live="selectedTimeframe" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="7">Last 7 days</option>
                                <option value="30">Last 30 days</option>
                                <option value="90">Last 90 days</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Team</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Members</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Attempts</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Completion Rate</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg. Score</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($teams as $team)
                                @php
                                    $completionRate = $team->total_attempts > 0 ? round(($team->completed_attempts / $team->total_attempts) * 100, 1) : 0;
                                @endphp
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl flex items-center justify-center text-white font-semibold">
                                                {{ strtoupper(substr($team->name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">{{ $team->name }}</div>
                                                @if($team->department)
                                                    <div class="text-sm text-gray-500">{{ $team->department }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <i class="fas fa-users text-gray-400"></i>
                                            <span class="text-sm font-medium text-gray-900">{{ $team->member_count }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ $team->total_attempts ?? 0 }}</div>
                                        <div class="text-xs text-gray-500">{{ $team->completed_attempts ?? 0 }} completed</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div class="flex-1">
                                                <div class="w-full bg-gray-200 rounded-full h-2">
                                                    <div class="bg-gradient-to-r from-emerald-500 to-teal-600 h-2 rounded-full" style="width: {{ $completionRate }}%"></div>
                                                </div>
                                            </div>
                                            <span class="text-sm font-medium text-gray-900">{{ $completionRate }}%</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($team->avg_score)
                                            <div class="flex items-center gap-2">
                                                <i class="fas fa-star text-amber-500"></i>
                                                <span class="text-sm font-medium text-gray-900">{{ round($team->avg_score, 1) }}/100</span>
                                            </div>
                                        @else
                                            <span class="text-gray-400">No data</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button class="text-blue-600 hover:text-blue-900 mr-3">View Members</button>
                                        <button class="text-indigo-600 hover:text-indigo-900">Manage</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center">
                                        <div class="text-gray-500">
                                            <i class="fas fa-users-cog text-4xl mb-4"></i>
                                            <p class="text-lg font-medium">No teams found</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($teams->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200">
                        {{ $teams->links() }}
                    </div>
                @endif
            </div>
        @endif

        {{-- Analytics Tab --}}
        @if($view === 'analytics')
            <div class="space-y-8">
                {{-- Advanced Analytics Cards --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200">
                        <h4 class="text-lg font-semibold text-gray-900 mb-4">Engagement Metrics</h4>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Daily Active Users</span>
                                <span class="font-semibold">{{ $analyticsData['active_users'] ?? 0 }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Avg. Session Time</span>
                                <span class="font-semibold">{{ gmdate('i:s', $analyticsData['average_completion_time'] ?? 0) }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Retention Rate</span>
                                <span class="font-semibold">{{ $analyticsData['user_activity_rate'] ?? 0 }}%</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200">
                        <h4 class="text-lg font-semibold text-gray-900 mb-4">Performance Metrics</h4>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Success Rate</span>
                                <span class="font-semibold">{{ $analyticsData['completion_rate'] ?? 0 }}%</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Quality Score</span>
                                <span class="font-semibold">{{ number_format($analyticsData['average_score'] ?? 0, 1) }}/100</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Pending Reviews</span>
                                <span class="font-semibold">{{ $analyticsData['pending_assessments'] ?? 0 }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200">
                        <h4 class="text-lg font-semibold text-gray-900 mb-4">System Health</h4>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Total Quizzes</span>
                                <span class="font-semibold">{{ $analyticsData['total_attempts'] ?? 0 }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Active Teams</span>
                                <span class="font-semibold">{{ $analyticsData['total_teams'] ?? 0 }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">System Status</span>
                                <span class="flex items-center gap-2">
                                    <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                    <span class="text-sm font-semibold text-green-600">Healthy</span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Detailed Charts --}}
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200">
                    <h4 class="text-lg font-semibold text-gray-900 mb-6">Detailed Analytics</h4>
                    <div class="text-center py-12 text-gray-500">
                        <i class="fas fa-chart-area text-4xl mb-4"></i>
                        <p class="text-lg font-medium">Advanced Analytics Coming Soon</p>
                        <p class="text-sm">Detailed charts and insights will be available in the next update</p>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Success/Error Messages --}}
    @if(session()->has('success'))
        <div class="fixed top-4 right-4 bg-green-50 border border-green-200 rounded-xl p-4 z-50 shadow-lg" 
             x-data="{ show: true }" 
             x-show="show" 
             x-init="setTimeout(() => show = false, 5000)"
             x-transition>
            <div class="flex items-center gap-3">
                <i class="fas fa-check-circle text-green-600"></i>
                <span class="text-green-800 font-medium">{{ session('success') }}</span>
                <button @click="show = false" class="ml-2 text-green-600 hover:text-green-800">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('livewire:initialized', () => {
    let completionChart = null;
    
    function initCompletionTrendsChart() {
        const ctx = document.getElementById('completionTrendsChart');
        if (!ctx) return;
        
        if (completionChart) {
            completionChart.destroy();
        }
        
        const data = @json($completionTrends);
        
        completionChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.map(item => item.date),
                datasets: [{
                    label: 'Completed Quizzes',
                    data: data.map(item => item.completed),
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
    
    // Initialize chart
    setTimeout(initCompletionTrendsChart, 100);
    
    // Reinitialize when data changes
    Livewire.on('dataRefreshed', () => {
        setTimeout(initCompletionTrendsChart, 100);
    });
});
</script>
@endpush

@push('styles')
<style>
    /* Enhanced styling for the analytics dashboard */
    .gradient-border {
        background: linear-gradient(white, white) padding-box,
                    linear-gradient(135deg, #667eea 0%, #764ba2 100%) border-box;
        border: 2px solid transparent;
    }
    
    /* Custom scrollbar */
    .overflow-x-auto::-webkit-scrollbar {
        height: 8px;
    }
    
    .overflow-x-auto::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }
    
    .overflow-x-auto::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 4px;
    }
    
    .overflow-x-auto::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }
</style>
@endpush