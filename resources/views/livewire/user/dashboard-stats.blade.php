<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    {{-- Total Attempts --}}
    <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow p-6">
        <div class="flex items-center">
            <div class="p-3 bg-blue-100 rounded-full">
                <i class="fas fa-clipboard-check text-blue-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Total Attempts</p>
                <p class="text-3xl font-bold text-gray-900">{{ $totalAttempts }}</p>
            </div>
        </div>
    </div>

    {{-- Completed Attempts --}}
    <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow p-6">
        <div class="flex items-center">
            <div class="p-3 bg-green-100 rounded-full">
                <i class="fas fa-check-circle text-green-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Completed</p>
                <p class="text-3xl font-bold text-gray-900">{{ $completedAttempts }}</p>
            </div>
        </div>
    </div>

    {{-- Average Score --}}
    <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow p-6">
        <div class="flex items-center">
            <div class="p-3 bg-yellow-100 rounded-full">
                <i class="fas fa-star text-yellow-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Average Score</p>
                <p class="text-3xl font-bold text-gray-900 {{ $this->getScoreColor($averageScore) }}">{{ $averageScore }}</p>
                <p class="text-xs text-gray-500 mt-1">out of total points</p>
            </div>
        </div>
    </div>

    {{-- Completion Rate --}}
    <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow p-6">
        <div class="flex items-center">
            <div class="relative w-12 h-12">
                <svg class="w-12 h-12 transform -rotate-90" viewBox="0 0 36 36">
                    <path class="text-gray-300" stroke="currentColor" stroke-width="3" fill="none" 
                          d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"></path>
                    <path class="text-blue-600" stroke="currentColor" stroke-width="3" stroke-linecap="round" fill="none" 
                          stroke-dasharray="{{ $completionRate }}, 100" 
                          d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"></path>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Completion Rate</p>
                <p class="text-3xl font-bold text-gray-900">{{ $completionRate }}%</p>
            </div>
        </div>
    </div>
</div>