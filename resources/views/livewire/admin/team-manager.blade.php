<div class="p-6">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Team Management</h1>
            <p class="text-gray-600 dark:text-gray-400">Manage teams and their deposit points</p>
        </div>
        <button wire:click="createTeam" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
            <i class="fas fa-plus mr-2"></i>
            Create Team
        </button>
    </div>

    {{-- Team Statistics Summary --}}
    @php
        $totalTeams = $this->teams->count();
        $totalCurrentPoints = $this->teams->sum('points');
        $totalInitialPoints = $this->teams->sum('initial_points');
        $totalMembers = $this->teams->sum(function($team) { return $team->members->count(); });
        $pointsDifference = $totalCurrentPoints - $totalInitialPoints;
    @endphp
    
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <i class="fas fa-users text-blue-600"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Teams</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $totalTeams }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <i class="fas fa-user-friends text-green-600"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Members</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $totalMembers }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-2 bg-yellow-100 rounded-lg">
                    <i class="fas fa-coins text-yellow-600"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Points</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ number_format($totalCurrentPoints, 0) }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-2 {{ $pointsDifference >= 0 ? 'bg-green-100' : 'bg-red-100' }} rounded-lg">
                    <i class="fas {{ $pointsDifference >= 0 ? 'fa-trend-up text-green-600' : 'fa-trend-down text-red-600' }}"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Points Change</p>
                    <p class="text-lg font-semibold {{ $pointsDifference >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $pointsDifference >= 0 ? '+' : '' }}{{ number_format($pointsDifference, 0) }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Search and Bulk Actions --}}
    <div class="flex items-center justify-between mb-6">
        <div class="relative max-w-md">
            <input type="text" 
                   wire:model.live.debounce.300ms="searchTerm"
                   placeholder="Search teams..."
                   class="w-full px-4 py-2 pl-10 pr-4 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
        </div>
        
        @if(count($selectedTeams) > 0)
            <div class="flex items-center space-x-3">
                <span class="text-sm text-gray-600 dark:text-gray-400">{{ count($selectedTeams) }} team(s) selected</span>
                <button wire:click="bulkPointsOperation" 
                        class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg transition-colors">
                    <i class="fas fa-coins mr-2"></i>
                    Bulk Points Operation
                </button>
            </div>
        @endif
    </div>

    {{-- Teams Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <input type="checkbox" 
                                   wire:click="$toggle('selectAll')"
                                   class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Team</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Department</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Members</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Points</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Initial Points</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($this->teams as $team)
                        @if($team)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input type="checkbox" 
                                       wire:model.live="selectedTeams" 
                                       value="{{ $team->id }}"
                                       class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $team->name }}</div>
                                    @if($team->description)
                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ Str::limit($team->description, 50) }}</div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-900 dark:text-gray-100">{{ $team->department ?? '-' }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-900 dark:text-gray-100">{{ $team->members->count() }}</span>
                                @if($team->members->count() > 0)
                                    <button wire:click="viewMembers({{ $team->id }})" 
                                            class="ml-2 text-blue-600 hover:text-blue-800 text-xs">
                                        View
                                    </button>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <span class="text-sm font-medium {{ $team->points >= $team->initial_points ? 'text-green-600' : 'text-red-600' }}">
                                            {{ number_format($team->points, 2) }}
                                        </span>
                                        @php
                                            $pointsDiff = $team->points - $team->initial_points;
                                        @endphp
                                        @if($pointsDiff != 0)
                                            <span class="ml-2 text-xs px-2 py-1 rounded-full {{ $pointsDiff > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $pointsDiff > 0 ? '+' : '' }}{{ number_format($pointsDiff, 0) }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="flex items-center space-x-1">
                                        {{-- Quick Action Buttons --}}
                                        <div class="relative group">
                                            <button class="text-green-600 hover:text-green-800 text-xs px-2 py-1 rounded"
                                                    title="Quick Actions">
                                                <i class="fas fa-plus-circle"></i>
                                            </button>
                                            <div class="absolute right-0 top-6 hidden group-hover:block bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg z-10 p-2 whitespace-nowrap">
                                                @foreach($quickActions as $qAction)
                                                    @if($qAction['action'] === 'add')
                                                        <button wire:click="quickPointsAction({{ $team->id }}, '{{ $qAction['action'] }}', {{ $qAction['amount'] }}, '{{ $qAction['reason'] }}')"
                                                                class="block w-full text-left px-3 py-1 text-sm text-green-600 hover:bg-green-50 rounded">
                                                            {{ $qAction['label'] }}
                                                        </button>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                        <div class="relative group">
                                            <button class="text-red-600 hover:text-red-800 text-xs px-2 py-1 rounded"
                                                    title="Quick Penalties">
                                                <i class="fas fa-minus-circle"></i>
                                            </button>
                                            <div class="absolute right-0 top-6 hidden group-hover:block bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg z-10 p-2 whitespace-nowrap">
                                                @foreach($quickActions as $qAction)
                                                    @if($qAction['action'] === 'deduct')
                                                        <button wire:click="quickPointsAction({{ $team->id }}, '{{ $qAction['action'] }}', {{ $qAction['amount'] }}, '{{ $qAction['reason'] }}')"
                                                                class="block w-full text-left px-3 py-1 text-sm text-red-600 hover:bg-red-50 rounded">
                                                            {{ $qAction['label'] }}
                                                        </button>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                        <button wire:click="managePoints({{ $team->id }})" 
                                                class="text-blue-600 hover:text-blue-800 text-xs px-2 py-1 rounded"
                                                title="Custom Points">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-900 dark:text-gray-100">{{ number_format($team->initial_points, 2) }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-gray-100">{{ $team->created_at->format('M d, Y') }}</div>
                                @if($team->creator)
                                    <div class="text-sm text-gray-500 dark:text-gray-400">by {{ $team->creator->name }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-2">
                                    <button wire:click="editTeam({{ $team->id }})" 
                                            class="text-blue-600 hover:text-blue-900">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button wire:click="resetTeamPoints({{ $team->id }})" 
                                            class="text-orange-600 hover:text-orange-900"
                                            title="Reset to initial points">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                    <button wire:click="deleteTeam({{ $team->id }})" 
                                            class="text-red-600 hover:text-red-900"
                                            onclick="return confirm('Are you sure you want to delete this team?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                <i class="fas fa-users text-4xl mb-4"></i>
                                <p class="text-lg">No teams found</p>
                                <p class="text-sm">Create your first team to get started</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($this->teams->hasPages())
            <div class="px-6 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $this->teams->links() }}
            </div>
        @endif
    </div>





    {{-- Success/Error Messages --}}
    @if(session()->has('success'))
        <div class="fixed top-4 right-4 bg-green-50 border border-green-200 rounded-md p-4 z-50">
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

    @if(session()->has('error'))
        <div class="fixed top-4 right-4 bg-red-50 border border-red-200 rounded-md p-4 z-50">
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
</div>

<script>
    // Auto-hide messages after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(() => {
            const messages = document.querySelectorAll('.fixed.top-4.right-4');
            messages.forEach(message => {
                message.style.display = 'none';
            });
        }, 5000);
    });
</script>