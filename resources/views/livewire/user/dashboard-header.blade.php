{{-- resources/views/livewire/user/dashboard-header.blade.php --}}
<div>
    <div class="flex items-center space-x-2 sm:space-x-4">
        <button 
            wire:click="refreshData" 
            wire:loading.attr="disabled"
            wire:target="refreshData"
            class="bg-gray-100 hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed px-2 sm:px-4 py-2 rounded-lg transition-colors text-xs sm:text-sm"
            title="Refresh data">
            <div wire:loading.remove wire:target="refreshData">
                <i class="fas fa-sync-alt mr-1 sm:mr-2"></i>
                <span class="hidden sm:inline">{{ __('common.refresh') }}</span>
            </div>
            <div wire:loading wire:target="refreshData">
                <i class="fas fa-spinner fa-spin mr-1 sm:mr-2"></i>
                <span class="hidden sm:inline">{{ __('common.refreshing') }}...</span>
            </div>
        </button>
        
        <button 
            wire:click="openScanner" 
            class="bg-blue-600 hover:bg-blue-700 text-white px-2 sm:px-4 py-2 rounded-lg transition-colors text-xs sm:text-sm"
            title="Scan QR code">
            <i class="fas fa-qrcode mr-1 sm:mr-2"></i>
            <span class="hidden sm:inline">{{ __('common.scan_qr') }}</span>
        </button>
    </div>
</div>
