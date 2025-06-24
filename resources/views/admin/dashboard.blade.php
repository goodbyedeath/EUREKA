@extends('layouts.admin')

@section('page-title', 'Admin Dashboard')

@section('content')
<!-- Stats Cards Component -->
<livewire:admin.dashboard-stats :stats="$stats" />

<!-- Dashboard Tabs Container -->
<div x-data="{ activeTab: 'questionnaires' }">
    <!-- Tab Navigation for Dashboard Sections -->
    <div class="mb-6">
        <nav class="flex space-x-8" aria-label="Dashboard Tabs">
            <a href="#" 
               @click.prevent="activeTab = 'questionnaires'"
               :class="activeTab === 'questionnaires' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
               class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                Questionnaire Manager
            </a>
            <a href="#" 
               @click.prevent="activeTab = 'assessments'"
               :class="activeTab === 'assessments' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
               class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                Game Assessments
            </a>
        </nav>
    </div>

    <!-- Tab Content -->
    <div>
        <!-- Questionnaire Manager Component -->
        <div x-show="activeTab === 'questionnaires'" x-transition>
            <livewire:admin.questionnaire-manager />
        </div>

        <!-- Game Assessment Component -->
        <div x-show="activeTab === 'assessments'" x-transition>
            <livewire:admin.game-assessment />
        </div>
    </div>
</div>
@endsection