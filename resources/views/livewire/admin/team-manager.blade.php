<div class="p-6">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Team Management</h1>
            <p class="text-gray-600">Manage teams and their deposit points</p>
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
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <i class="fas fa-users text-blue-600"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-600">Total Teams</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $totalTeams }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <i class="fas fa-user-friends text-green-600"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-600">Total Members</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $totalMembers }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-2 bg-yellow-100 rounded-lg">
                    <i class="fas fa-coins text-yellow-600"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-600">Total Points</p>
                    <p class="text-lg font-semibold text-gray-900">{{ number_format($totalCurrentPoints, 0) }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-2 {{ $pointsDifference >= 0 ? 'bg-green-100' : 'bg-red-100' }} rounded-lg">
                    <i class="fas {{ $pointsDifference >= 0 ? 'fa-trend-up text-green-600' : 'fa-trend-down text-red-600' }}"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-600">Points Change</p>
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
                   class="w-full px-4 py-2 pl-10 pr-4 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
        </div>
        
        @if(count($selectedTeams) > 0)
            <div class="flex items-center space-x-3">
                <span class="text-sm text-gray-600">{{ count($selectedTeams) }} team(s) selected</span>
                <button wire:click="bulkPointsOperation" 
                        class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg transition-colors">
                    <i class="fas fa-coins mr-2"></i>
                    Bulk Points Operation
                </button>
            </div>
        @endif
    </div>

    {{-- Teams Table --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <input type="checkbox" 
                                   wire:click="$toggle('selectAll')"
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Team</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Members</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Points</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Initial Points</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($this->teams as $team)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input type="checkbox" 
                                       wire:model.live="selectedTeams" 
                                       value="{{ $team->id }}"
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <div class="text-sm font-medium text-gray-900">{{ $team->name }}</div>
                                    @if($team->description)
                                        <div class="text-sm text-gray-500">{{ Str::limit($team->description, 50) }}</div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-900">{{ $team->department ?? '-' }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-900">{{ $team->members->count() }}</span>
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
                                            <div class="absolute right-0 top-6 hidden group-hover:block bg-white border border-gray-200 rounded-lg shadow-lg z-10 p-2 whitespace-nowrap">
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
                                            <div class="absolute right-0 top-6 hidden group-hover:block bg-white border border-gray-200 rounded-lg shadow-lg z-10 p-2 whitespace-nowrap">
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
                                <span class="text-sm text-gray-900">{{ number_format($team->initial_points, 2) }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $team->created_at->format('M d, Y') }}</div>
                                @if($team->creator)
                                    <div class="text-sm text-gray-500">by {{ $team->creator->name }}</div>
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
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500">
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
            <div class="px-6 py-3 border-t border-gray-200">
                {{ $this->teams->links() }}
            </div>
        @endif
    </div>

    {{-- Edit Team Modal --}}
    @if($showEditModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity"></div>

                <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-users text-blue-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">
                                {{ $teamId ? 'Edit Team' : 'Create New Team' }}
                            </h3>
                            
                            <form wire:submit="saveTeam" class="mt-4 space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Team Name</label>
                                    <input type="text" wire:model="name" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                           placeholder="Enter team name">
                                    @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                                    <input type="text" wire:model="department" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                           placeholder="Enter department">
                                    @error('department') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                    <textarea wire:model="description" rows="3"
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                              placeholder="Enter team description"></textarea>
                                    @error('description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Initial Points</label>
                                        <input type="number" wire:model="initial_points" step="0.01" min="0"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        @error('initial_points') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Current Points</label>
                                        <input type="number" wire:model="points" step="0.01" min="0"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        @error('points') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                        <button type="submit" wire:click="saveTeam"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                            {{ $teamId ? 'Update Team' : 'Create Team' }}
                        </button>
                        <button type="button" wire:click="closeModal"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Points Management Modal --}}
    @if($selectedTeam && !$showMembersModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity"></div>

                <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-coins text-green-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">
                                Manage Points - {{ $selectedTeam->name }}
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-600">Current Points: <strong>{{ number_format($selectedTeam->points, 2) }}</strong></p>
                            </div>
                            
                            <div class="mt-4 space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Action</label>
                                    <div class="flex space-x-4">
                                        <label class="flex items-center">
                                            <input type="radio" wire:model.live="pointsAction" value="set" class="mr-2">
                                            Set Points
                                        </label>
                                        <label class="flex items-center">
                                            <input type="radio" wire:model.live="pointsAction" value="add" class="mr-2">
                                            Add Points
                                        </label>
                                        <label class="flex items-center">
                                            <input type="radio" wire:model.live="pointsAction" value="deduct" class="mr-2">
                                            Deduct Points
                                        </label>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        @if($pointsAction === 'set') New Points Amount @endif
                                        @if($pointsAction === 'add') Points to Add @endif
                                        @if($pointsAction === 'deduct') Points to Deduct @endif
                                    </label>
                                    <input type="number" wire:model="pointsAmount" step="0.01" min="0"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Reason (Optional)</label>
                                    <input type="text" wire:model="pointsReason"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                           placeholder="Reason for points change">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                        <button wire:click="updatePoints"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Update Points
                        </button>
                        <button type="button" wire:click="closeModal"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Team Members Modal --}}
    @if($showMembersModal && $selectedTeam)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity"></div>

                <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            {{ $selectedTeam->name }} - Members
                        </h3>
                        <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    @if($selectedTeam->members->count() > 0)
                        <div class="space-y-3">
                            @foreach($selectedTeam->members as $member)
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-user text-blue-600"></i>
                                        </div>
                                        <div class="ml-3">
                                            <div class="text-sm font-medium text-gray-900">{{ $member->user->name }}</div>
                                            <div class="text-sm text-gray-500">{{ $member->user->email }}</div>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        @if($member->is_leader)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                Leader
                                            </span>
                                        @endif
                                        <span class="text-xs text-gray-500">
                                            Joined {{ $member->created_at->format('M d, Y') }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-users text-4xl mb-4"></i>
                            <p>No members in this team</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Bulk Points Operation Modal --}}
    @if($showBulkModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity"></div>

                <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-purple-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-coins text-purple-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">
                                Bulk Points Operation
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-600">Apply points operation to {{ count($selectedTeams) }} selected team(s)</p>
                            </div>
                            
                            <div class="mt-4 space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Action</label>
                                    <div class="flex space-x-4">
                                        <label class="flex items-center">
                                            <input type="radio" wire:model.live="bulkAction" value="add" class="mr-2">
                                            Add Points
                                        </label>
                                        <label class="flex items-center">
                                            <input type="radio" wire:model.live="bulkAction" value="deduct" class="mr-2">
                                            Deduct Points
                                        </label>
                                        <label class="flex items-center">
                                            <input type="radio" wire:model.live="bulkAction" value="set" class="mr-2">
                                            Set Points
                                        </label>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        @if($bulkAction === 'set') New Points Amount @endif
                                        @if($bulkAction === 'add') Points to Add @endif
                                        @if($bulkAction === 'deduct') Points to Deduct @endif
                                    </label>
                                    <input type="number" wire:model="bulkAmount" step="0.01" min="0"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                                    @error('bulkAmount') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
                                    <input type="text" wire:model="bulkReason"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500"
                                           placeholder="Reason for bulk points operation">
                                    @error('bulkReason') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                        <button wire:click="executeBulkPoints"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-purple-600 text-base font-medium text-white hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Execute Operation
                        </button>
                        <button type="button" wire:click="closeModal"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 sm:mt-0 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

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