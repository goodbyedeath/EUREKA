{{-- resources/views/livewire/user/dashboard-header.blade.php --}}
<div>
    <!-- Enhanced Action Buttons Layout -->
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
        <!-- Primary QR Scanner Button - Most Important Action -->
        <button 
            wire:click="openScanner" 
            class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 dark:from-blue-500 dark:to-blue-600 dark:hover:from-blue-600 dark:hover:to-blue-700 text-white px-4 py-3 rounded-lg transition-all duration-200 transform hover:scale-105 shadow-lg hover:shadow-xl group flex items-center justify-center sm:justify-start"
            title="Scan QR code">
            <div class="flex items-center">
                <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center mr-3">
                    <i class="fas fa-qrcode text-white"></i>
                </div>
                <div class="text-left">
                    <div class="font-semibold">{{ __('common.scan_qr') }}</div>
                    <div class="text-xs opacity-90">{{ __('common.quick_access') }}</div>
                </div>
            </div>
        </button>
        
        <!-- Secondary Refresh Button -->
        <button 
            wire:click="refreshData" 
            wire:loading.attr="disabled"
            wire:target="refreshData"
            class="bg-white hover:bg-gray-50 dark:bg-gray-800 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500 disabled:opacity-50 disabled:cursor-not-allowed px-4 py-3 rounded-lg transition-all duration-200 shadow-sm hover:shadow-md group flex items-center justify-center sm:justify-start"
            title="Refresh data">
            
            <!-- Normal State -->
            <div wire:loading.remove wire:target="refreshData" class="flex items-center">
                <div class="w-8 h-8 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center mr-3 group-hover:bg-gray-200 dark:group-hover:bg-gray-600 transition-colors">
                    <i class="fas fa-sync-alt text-gray-600 dark:text-gray-300"></i>
                </div>
                <div class="text-left">
                    <div class="font-semibold text-gray-900 dark:text-gray-100">{{ __('common.refresh') }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('common.update_data') }}</div>
                </div>
            </div>
            
            <!-- Loading State -->
            <div wire:loading wire:target="refreshData" class="flex items-center">
                <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center mr-3">
                    <i class="fas fa-spinner fa-spin text-blue-600 dark:text-blue-400"></i>
                </div>
                <div class="text-left">
                    <div class="font-semibold text-gray-900 dark:text-gray-100">{{ __('common.refreshing') }}...</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('common.please_wait') }}</div>
                </div>
            </div>
        </button>
    </div>
    
    <!-- Quick Action Hint for Mobile -->
    <div class="mt-3 sm:hidden">
        <p class="text-xs text-gray-500 dark:text-gray-400 text-center">
            <i class="fas fa-info-circle mr-1"></i>
            {{ __('common.tap_qr_scanner_hint') }}
        </p>
    </div>
</div>
