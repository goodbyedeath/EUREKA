@extends('layouts.admin')

@section('page-title', 'Dashboard Management')

@section('content')
<!-- Dashboard Management Tools -->
<div class="mb-6">
    <div class="bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 p-6 rounded-xl border border-indigo-200 dark:border-indigo-700">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Dashboard Management Tools</h2>
                <p class="text-gray-600 dark:text-gray-400">Manage questionnaires, assessments, and team data</p>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Dashboard
            </a>
        </div>

        <!-- Tab Navigation -->
        <div x-data="{ activeTab: 'questionnaires' }" class="w-full">
            <!-- Tab Headers -->
            <div class="border-b border-gray-200 dark:border-gray-600 mb-6">
                <nav class="-mb-px flex space-x-8">
                    <button @click="activeTab = 'questionnaires'"
                            :class="activeTab === 'questionnaires' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'"
                            class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                        <i class="fas fa-clipboard-list mr-2"></i>
                        Questionnaire Manager
                    </button>
                    <button @click="activeTab = 'assessments'"
                            :class="activeTab === 'assessments' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'"
                            class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                        <i class="fas fa-gamepad mr-2"></i>
                        Game Assessments
                    </button>
                    <button @click="activeTab = 'teams'"
                            :class="activeTab === 'teams' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'"
                            class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                        <i class="fas fa-users-cog mr-2"></i>
                        Team Manager
                    </button>
                </nav>
            </div>

            <!-- Tab Content -->
            <div class="min-h-96">
                <!-- Questionnaire Manager -->
                <div x-show="activeTab === 'questionnaires'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <div class="flex items-center space-x-3 mb-4">
                            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                                <i class="fas fa-clipboard-list text-blue-600 dark:text-blue-400"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Questionnaire Management</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Create, edit, and manage questionnaires</p>
                            </div>
                        </div>
                        <livewire:admin.questionnaire-manager />
                    </div>
                </div>

                <!-- Game Assessment -->
                <div x-show="activeTab === 'assessments'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <div class="flex items-center space-x-3 mb-4">
                            <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                                <i class="fas fa-gamepad text-purple-600 dark:text-purple-400"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Game Assessment Management</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Review and assess game performance</p>
                            </div>
                        </div>
                        <livewire:admin.game-assessment />
                    </div>
                </div>

                <!-- Team Manager -->
                <div x-show="activeTab === 'teams'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <div class="flex items-center space-x-3 mb-4">
                            <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center">
                                <i class="fas fa-users-cog text-indigo-600 dark:text-indigo-400"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Team Management</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Manage teams and team members</p>
                            </div>
                        </div>
                        <livewire:admin.team-manager />
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection