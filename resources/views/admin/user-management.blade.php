<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('User Management') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Tab Navigation -->
            <div class="mb-6">
                <nav class="flex space-x-8" aria-label="Tabs">
                    <a href="{{ route('admin.dashboard') }}" 
                       class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                        Dashboard
                    </a>
                    <a href="{{ route('admin.users') }}" 
                       class="border-indigo-500 text-indigo-600 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                        User Management
                    </a>
                    <a href="{{ route('admin.quest-locations') }}" 
                        class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                        Map Management
                    </a>
                </nav>
            </div>

            <!-- User Management Content -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h1 class="text-2xl font-bold mb-6">User Management</h1>
                    <livewire:admin.user-management />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>