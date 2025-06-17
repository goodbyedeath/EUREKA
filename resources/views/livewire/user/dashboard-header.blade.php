{{-- resources/views/livewire/user/dashboard-header.blade.php --}}
<div>
    <div class="flex items-center space-x-4">
        <button 
            wire:click="refreshData" 
            wire:loading.attr="disabled"
            wire:target="refreshData"
            class="bg-gray-100 hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed px-4 py-2 rounded-lg transition-colors"
            title="Refresh data">
            <div wire:loading.remove wire:target="refreshData">
                <i class="fas fa-sync-alt mr-2"></i>Refresh
            </div>
            <div wire:loading wire:target="refreshData">
                <i class="fas fa-spinner fa-spin mr-2"></i>Refreshing...
            </div>
        </button>
        
        <button 
            wire:click="openScanner" 
            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors"
            title="Scan QR code">
            <i class="fas fa-qr_code mr-2"></i>Scan QR
        </button>
        
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button 
                type="submit" 
                class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition-colors"
                title="Logout">
                <i class="fas fa-sign-out-alt mr-2"></i>Logout
            </button>
        </form>
    </div>
</div>
