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
        $totalTeams = $teams->count();
        $totalCalculatedPoints = $teams->sum('calculated_total_score'); // Real calculated scores
        $totalInitialPoints = $teams->sum('initial_points');
        $totalMembers = $teams->sum(function($team) { return $team->members->count(); });
        $pointsDifference = $totalCalculatedPoints - $totalInitialPoints;
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
                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ number_format($totalCalculatedPoints, 0) }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-2 {{ $pointsDifference >= 0 ? 'bg-green-100' : 'bg-red-100' }} rounded-lg">
                    <i class="fas {{ $pointsDifference >= 0 ? 'fa-trend-up text-green-600' : 'fa-trend-down text-red-600' }}"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Points Gained</p>
                    <p class="text-lg font-semibold {{ $pointsDifference >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $pointsDifference >= 0 ? '+' : '' }}{{ number_format($pointsDifference, 0) }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Search --}}
    <div class="flex items-center justify-between mb-6">
        <div class="relative max-w-md">
            <input type="text" 
                   wire:model.live.debounce.300ms="searchTerm"
                   placeholder="Search teams..."
                   class="w-full px-4 py-2 pl-10 pr-4 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
        </div>
    </div>

    {{-- Teams Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
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
                    @forelse($teams as $team)
                        @if($team)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-700">
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
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm text-gray-900 dark:text-gray-100">{{ $team->members->count() }}</span>
                                    @if($team->members->count() > 0)
                                        <button wire:click="viewMembers({{ $team->id }})" 
                                                class="text-blue-600 hover:text-blue-800 text-xs px-2 py-1 border border-blue-300 rounded hover:bg-blue-50"
                                                title="View team members">
                                            Members
                                        </button>
                                    @endif
                                    @if($team->users_count > 0)
                                        <button wire:click="viewTeamUsers({{ $team->id }})" 
                                                class="text-green-600 hover:text-green-800 text-xs px-2 py-1 border border-green-300 rounded hover:bg-green-50"
                                                title="View team users ({{ $team->users_count }})">
                                            Users ({{ $team->users_count }})
                                        </button>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <span class="text-sm font-bold {{ $team->calculated_total_score >= $team->initial_points ? 'text-green-600' : 'text-red-600' }}">
                                            {{ number_format($team->calculated_total_score, 0) }}
                                        </span>
                                        @php
                                            $calculatedDiff = $team->calculated_total_score - $team->initial_points;
                                        @endphp
                                        @if($calculatedDiff != 0)
                                            <span class="ml-2 text-xs px-2 py-1 rounded-full {{ $calculatedDiff > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $calculatedDiff > 0 ? '+' : '' }}{{ number_format($calculatedDiff, 0) }}
                                            </span>
                                        @endif
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
                                    <button wire:click="viewGameAssessments({{ $team->id }})" 
                                            class="text-green-600 hover:text-green-900"
                                            title="View Game Assessment Photos">
                                        <i class="fas fa-camera"></i>
                                    </button>
                                    <button wire:click="editTeam({{ $team->id }})" 
                                            class="text-blue-600 hover:text-blue-900">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button wire:click="deleteTeam({{ $team->id }})" 
                                            class="text-red-600 hover:text-red-900"
                                            onclick="return confirm('Are you sure you want to delete this team?')"
                                            title="Delete team (requires empty team)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    @if($team->members->count() > 0 || $team->users_count > 0)
                                        <button wire:click="forceDeleteTeam({{ $team->id }})" 
                                                class="text-red-800 hover:text-red-900"
                                                onclick="return confirm('This will PERMANENTLY delete the team and remove ALL users and members. Are you absolutely sure?')"
                                                title="Force delete with all members">
                                            <i class="fas fa-exclamation-triangle"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
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
        @if($teams->hasPages())
            <div class="px-6 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $teams->links() }}
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

    {{-- Edit Team Modal --}}
    <div x-data="{ 
        show: false,
        teamData: {
            teamId: null,
            name: '',
            department: '',
            description: '',
            initial_points: 1000,
            points: 1000
        }
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
         @open-team-modal.window="
            show = true;
            teamData = $event.detail[0] || $event.detail;
         "
         @close-team-modal.window="show = false">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" 
                 x-on:click="show = false"></div>

            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="fas fa-users text-blue-600"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">
                            <span x-text="teamData.teamId ? 'Edit Team' : 'Create New Team'"></span>
                        </h3>
                        
                        <form @submit.prevent="$wire.call('saveTeam', teamData)" class="mt-4 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Team Name</label>
                                <input type="text" x-model="teamData.name"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                       placeholder="Enter team name">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Department</label>
                                <input type="text" x-model="teamData.department"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                       placeholder="Enter department">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                                <textarea x-model="teamData.description" rows="3"
                                          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                          placeholder="Enter team description"></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Initial Points</label>
                                <input type="number" x-model.number="teamData.initial_points" step="1" min="0"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            </div>
                        </form>
                    </div>
                </div>
                <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                    <button type="submit" @click="$wire.call('saveTeam', teamData).then(() => show = false)"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                        <span x-text="teamData.teamId ? 'Update Team' : 'Create Team'"></span>
                    </button>
                    <button type="button" x-on:click="show = false"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>


    {{-- Team Members Modal --}}
    <div x-data="{ 
        show: false,
        teamData: {
            id: null,
            name: '',
            members: []
        }
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
         @open-members-modal.window="
            show = true;
            teamData = $event.detail[0] || $event.detail;
            console.log('Members modal data:', teamData);
         "
         @close-members-modal.window="show = false">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" 
                 x-on:click="show = false"></div>

            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">
                        <span x-text="teamData.name"></span> - Members
                    </h3>
                    <button x-on:click="show = false" class="text-gray-400 hover:text-gray-600 dark:text-gray-400">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <template x-if="teamData.members && teamData.members.length > 0">
                    <div class="space-y-3">
                        <template x-for="member in teamData.members" :key="member.id">
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-user text-blue-600"></i>
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100" x-text="member.name"></div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400" x-text="member.email"></div>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <template x-if="member.is_leader">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            Leader
                                        </span>
                                    </template>
                                    <span class="text-xs text-gray-500 dark:text-gray-400" x-text="'Joined ' + member.joined_date"></span>
                                    <button @click="if(confirm('Are you sure you want to remove this team member?')) { $wire.call('removeMemberFromTeam', teamData.id, member.id) }"
                                            class="text-red-600 hover:text-red-900 text-xs px-2 py-1 border border-red-300 rounded hover:bg-red-50"
                                            title="Remove member">
                                        <i class="fas fa-user-minus"></i>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
                
                <template x-if="!teamData.members || teamData.members.length === 0">
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <i class="fas fa-users text-4xl mb-4"></i>
                        <p>No members in this team</p>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Game Assessments Modal --}}
    <div x-data="{ 
        show: false,
        assessmentData: {
            team_id: null,
            team_name: '',
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
         @open-assessments-modal.window="
            show = true;
            assessmentData = $event.detail[0] || $event.detail;
         "
         @close-assessments-modal.window="show = false">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" 
                 x-on:click="show = false"></div>

            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full sm:p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">
                        <i class="fas fa-camera mr-2"></i>
                        <span x-text="assessmentData.team_name"></span> - Game Assessment Photos
                    </h3>
                    <button x-on:click="show = false" class="text-gray-400 hover:text-gray-600 dark:text-gray-400">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <template x-if="assessmentData.assessments && assessmentData.assessments.length > 0">
                    <div class="space-y-4 max-h-96 overflow-y-auto">
                        <template x-for="assessment in assessmentData.assessments" :key="assessment.id">
                            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="flex items-center flex-1">
                                    <div class="w-16 h-16 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                                        <i class="fas fa-user text-blue-600"></i>
                                    </div>
                                    <div class="flex-1">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100" x-text="assessment.user_name"></div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400" x-text="assessment.questionnaire_title"></div>
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
                
                <template x-if="!assessmentData.assessments || assessmentData.assessments.length === 0">
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <i class="fas fa-camera text-4xl mb-4"></i>
                        <p>No game assessment photos found</p>
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

    {{-- Team Users Modal --}}
    <div x-data="{ 
        show: false,
        teamData: {
            id: null,
            name: '',
            members: []
        }
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
         @open-users-modal.window="
            show = true;
            teamData = $event.detail[0] || $event.detail;
         "
         @close-users-modal.window="show = false">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" 
                 x-on:click="show = false"></div>

            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">
                        <i class="fas fa-users mr-2 text-green-600"></i>
                        <span x-text="teamData.name"></span> - Team Users
                    </h3>
                    <button x-on:click="show = false" class="text-gray-400 hover:text-gray-600 dark:text-gray-400">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <template x-if="teamData.members && teamData.members.length > 0">
                    <div class="space-y-3">
                        <template x-for="member in teamData.members" :key="member.id">
                            <div class="flex items-center justify-between p-3 bg-green-50 dark:bg-gray-700 rounded-lg border border-green-200">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-user text-green-600"></i>
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100" x-text="member.name"></div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400" x-text="member.email"></div>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-xs text-gray-500 dark:text-gray-400" x-text="'Joined ' + member.joined_date"></span>
                                    <button @click="if(confirm('Are you sure you want to remove this user from the team?')) { $wire.call('removeUserFromTeam', teamData.id, member.id) }"
                                            class="text-red-600 hover:text-red-900 text-xs px-2 py-1 border border-red-300 rounded hover:bg-red-50"
                                            title="Remove user from team">
                                        <i class="fas fa-user-times"></i>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
                
                <template x-if="!teamData.members || teamData.members.length === 0">
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <i class="fas fa-users text-4xl mb-4"></i>
                        <p>No users assigned to this team</p>
                    </div>
                </template>
            </div>
        </div>
    </div>

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