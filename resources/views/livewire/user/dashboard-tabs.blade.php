<div>
    <div class="bg-white dark:bg-gray-800 rounded-t-xl border-b border-gray-200 dark:border-gray-700 shadow-sm">
        <nav class="flex space-x-1 p-2 overflow-x-auto" role="tablist">
            <button 
                wire:click="switchTab('dashboard')" 
                role="tab"
                aria-selected="{{ $activeTab === 'dashboard' ? 'true' : 'false' }}"
                class="flex-1 min-w-max px-6 py-4 rounded-lg font-semibold text-sm transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 
                       {{ $activeTab === 'dashboard' ? 'bg-gradient-to-r from-blue-500 to-blue-600 text-white shadow-lg transform scale-105' : 'text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                <div class="flex flex-col items-center space-y-1">
                    <i class="fas fa-tachometer-alt text-lg"></i>
                    <span class="text-xs font-medium">{{ __('common.dashboard') }}</span>
                </div>
            </button>
            
            <button 
                wire:click="switchTab('quizzes')" 
                role="tab"
                aria-selected="{{ $activeTab === 'quizzes' ? 'true' : 'false' }}"
                class="flex-1 min-w-max px-6 py-4 rounded-lg font-semibold text-sm transition-all duration-300 relative focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2
                       {{ $activeTab === 'quizzes' ? 'bg-gradient-to-r from-green-500 to-green-600 text-white shadow-lg transform scale-105' : 'text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                <div class="flex flex-col items-center space-y-1">
                    <i class="fas fa-clipboard-list text-lg"></i>
                    <span class="text-xs font-medium">Available Quizzes</span>
                </div>
                @if($scannedqr_code)
                    <span class="absolute -top-1 -right-1 h-4 w-4 bg-red-500 rounded-full animate-pulse border-2 border-white" title="QR Code Scanned"></span>
                @endif
            </button>

            <button 
                wire:click="switchTab('members')" 
                role="tab"
                aria-selected="{{ $activeTab === 'members' ? 'true' : 'false' }}"
                class="flex-1 min-w-max px-6 py-4 rounded-lg font-semibold text-sm transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2
                       {{ $activeTab === 'members' ? 'bg-gradient-to-r from-purple-500 to-purple-600 text-white shadow-lg transform scale-105' : 'text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                <div class="flex flex-col items-center space-y-1">
                    <i class="fas fa-users text-lg"></i>
                    <span class="text-xs font-medium">Team Members</span>
                </div>
            </button>

            <button 
                wire:click="switchTab('quests')" 
                role="tab"
                aria-selected="{{ $activeTab === 'quests' ? 'true' : 'false' }}"
                class="flex-1 min-w-max px-6 py-4 rounded-lg font-semibold text-sm transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2
                       {{ $activeTab === 'quests' ? 'bg-gradient-to-r from-orange-500 to-orange-600 text-white shadow-lg transform scale-105' : 'text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                <div class="flex flex-col items-center space-y-1">
                    <i class="fas fa-map-marker-alt text-lg"></i>
                    <span class="text-xs font-medium">Quest Locations</span>
                </div>
            </button>

            <button 
                wire:click="switchTab('games')" 
                role="tab"
                aria-selected="{{ $activeTab === 'games' ? 'true' : 'false' }}"
                class="flex-1 min-w-max px-6 py-4 rounded-lg font-semibold text-sm transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2
                       {{ $activeTab === 'games' ? 'bg-gradient-to-r from-indigo-500 to-indigo-600 text-white shadow-lg transform scale-105' : 'text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                <div class="flex flex-col items-center space-y-1">
                    <i class="fas fa-gamepad text-lg"></i>
                    <span class="text-xs font-medium">Outdoor Games</span>
                </div>
            </button>
        </nav>
    </div>
</div>
