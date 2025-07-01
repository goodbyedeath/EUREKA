@extends('layouts.admin')

@section('title', 'Team Management')

@section('content')
    @livewire('admin.team-manager')

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
            teamData = $event.detail;
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
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                       placeholder="Enter team name">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Department</label>
                                <input type="text" x-model="teamData.department"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                       placeholder="Enter department">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                                <textarea x-model="teamData.description" rows="3"
                                          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                          placeholder="Enter team description"></textarea>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Initial Points</label>
                                    <input type="number" x-model.number="teamData.initial_points" step="1" min="0"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Current Points</label>
                                    <input type="number" x-model.number="teamData.points" step="1" min="0"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
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

    {{-- Points Management Modal --}}
    <div x-data="{ 
        show: false,
        teamData: {
            id: null,
            name: '',
            points: 0,
            pointsAction: 'set',
            pointsAmount: 0,
            pointsReason: ''
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
         @open-points-modal.window="
            show = true;
            teamData = $event.detail;
         "
         @close-points-modal.window="show = false">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" 
                 x-on:click="show = false"></div>

            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <form @submit.prevent="
                    $wire.call('updatePointsFromModal', teamData).then(() => show = false).catch(() => {});
                ">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-coins text-green-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">
                                Manage Points - <span x-text="teamData.name"></span>
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-600 dark:text-gray-400">Current Points: <strong x-text="teamData.points"></strong></p>
                            </div>
                            
                            <div class="mt-4 space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Action</label>
                                    <div class="flex space-x-4">
                                        <label class="flex items-center">
                                            <input type="radio" x-model="teamData.pointsAction" value="set" class="mr-2">
                                            Set Points
                                        </label>
                                        <label class="flex items-center">
                                            <input type="radio" x-model="teamData.pointsAction" value="add" class="mr-2">
                                            Add Points
                                        </label>
                                        <label class="flex items-center">
                                            <input type="radio" x-model="teamData.pointsAction" value="deduct" class="mr-2">
                                            Deduct Points
                                        </label>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        <span x-show="teamData.pointsAction === 'set'">New Points Amount</span>
                                        <span x-show="teamData.pointsAction === 'add'">Points to Add</span>
                                        <span x-show="teamData.pointsAction === 'deduct'">Points to Deduct</span>
                                    </label>
                                    <input type="number" x-model.number="teamData.pointsAmount" step="1" min="0"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Reason (Optional)</label>
                                    <input type="text" x-model="teamData.pointsReason"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                           placeholder="Reason for points change">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                        <button type="submit"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Update Points
                        </button>
                        <button type="button" x-on:click="show = false"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
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
            teamData = $event.detail;
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

    {{-- Bulk Points Modal --}}
    <div x-data="{ 
        show: false,
        bulkData: {
            selectedCount: 0,
            bulkAction: 'add',
            bulkAmount: 0,
            bulkReason: ''
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
         @open-bulk-modal.window="
            show = true;
            bulkData = $event.detail;
         "
         @close-bulk-modal.window="show = false">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" 
                 x-on:click="show = false"></div>

            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <form @submit.prevent="
                    $wire.call('executeBulkPointsFromModal', bulkData).then(() => show = false).catch(() => {});
                ">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-purple-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-coins text-purple-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">
                                Bulk Points Operation
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-600 dark:text-gray-400">Apply points operation to <span x-text="bulkData.selectedCount"></span> selected team(s)</p>
                            </div>
                            
                            <div class="mt-4 space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Action</label>
                                    <div class="flex space-x-4">
                                        <label class="flex items-center">
                                            <input type="radio" x-model="bulkData.bulkAction" value="add" class="mr-2">
                                            Add Points
                                        </label>
                                        <label class="flex items-center">
                                            <input type="radio" x-model="bulkData.bulkAction" value="deduct" class="mr-2">
                                            Deduct Points
                                        </label>
                                        <label class="flex items-center">
                                            <input type="radio" x-model="bulkData.bulkAction" value="set" class="mr-2">
                                            Set Points
                                        </label>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        <span x-show="bulkData.bulkAction === 'set'">New Points Amount</span>
                                        <span x-show="bulkData.bulkAction === 'add'">Points to Add</span>
                                        <span x-show="bulkData.bulkAction === 'deduct'">Points to Deduct</span>
                                    </label>
                                    <input type="number" x-model.number="bulkData.bulkAmount" step="1" min="0"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Reason</label>
                                    <input type="text" x-model="bulkData.bulkReason"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500"
                                           placeholder="Reason for bulk points operation">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                        <button type="submit"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-purple-600 text-base font-medium text-white hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Execute Operation
                        </button>
                        <button type="button" x-on:click="show = false"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 sm:mt-0 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection