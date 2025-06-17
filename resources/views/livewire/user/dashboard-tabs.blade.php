<div>
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-8" role="tablist">
            <button 
                wire:click="switchTab('dashboard')" 
                role="tab"
                aria-selected="{{ $activeTab === 'dashboard' ? 'true' : 'false' }}"
                class="py-2 px-1 border-b-2 font-medium text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2
                       {{ $activeTab === 'dashboard' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
            </button>
            
            <button 
                wire:click="switchTab('quizzes')" 
                role="tab"
                aria-selected="{{ $activeTab === 'quizzes' ? 'true' : 'false' }}"
                class="py-2 px-1 border-b-2 font-medium text-sm transition-colors relative focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2
                       {{ $activeTab === 'quizzes' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                <i class="fas fa-clipboard-list mr-2"></i>Tugas Tersedia
                @if($scannedqr_code)
                    <span class="absolute -top-1 -right-1 h-3 w-3 bg-green-500 rounded-full animate-pulse" title="New quiz available"></span>
                @endif
            </button>

            <button 
                wire:click="switchTab('members')" 
                role="tab"
                aria-selected="{{ $activeTab === 'members' ? 'true' : 'false' }}"
                class="py-2 px-1 border-b-2 font-medium text-sm transition-colors relative focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2
                       {{ $activeTab === 'members' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                <i class="fas fa-users mr-2"></i>Daftar Anggota
            </button>

            
        </nav>
    </div>
</div>
