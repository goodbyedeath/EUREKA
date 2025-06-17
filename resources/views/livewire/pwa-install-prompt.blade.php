{{-- resources/views/livewire/pwa-install-prompt.blade.php --}}
<div x-data="pwaInstaller()" 
     x-show="showInstallPrompt && @entangle('showPrompt')" 
     x-cloak
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 transform translate-y-2"
     x-transition:enter-end="opacity-100 transform translate-y-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 transform translate-y-0"
     x-transition:leave-end="opacity-0 transform translate-y-2"
     class="fixed bottom-4 left-4 right-4 md:left-auto md:right-4 md:max-w-sm bg-white rounded-lg shadow-lg border border-gray-200 p-4 z-50">
    
    <div class="flex items-start space-x-3">
        <div class="flex-shrink-0">
            <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                <i class="fas fa-mobile-alt text-white"></i>
            </div>
        </div>
        
        <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900">
                Install Eureka App
            </p>
            <p class="text-sm text-gray-500">
                Add to your home screen for quick access
            </p>
        </div>
        
        <div class="flex-shrink-0 flex space-x-2">
            <button @click="installPwa()" 
                    class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Install
            </button>
            <button wire:click="hidePrompt" @click="dismissPrompt()"
                    class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Later
            </button>
        </div>
    </div>
</div>

<script>
function pwaInstaller() {
    return {
        deferredPrompt: null,
        showInstallPrompt: false,
        
        init() {
            // Check if PWA is already installed
            if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone) {
                this.showInstallPrompt = false;
                @this.call('hidePrompt');
                return;
            }
            
            // Check if user has dismissed the prompt in this session
            if (sessionStorage.getItem('pwa-prompt-dismissed') === 'true') {
                this.showInstallPrompt = false;
                @this.call('hidePrompt');
                return;
            }
            
            // Listen for the beforeinstallprompt event
            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault();
                this.deferredPrompt = e;
                this.showInstallPrompt = true;
            });
            
            // Hide prompt if app gets installed
            window.addEventListener('appinstalled', () => {
                this.showInstallPrompt = false;
                this.deferredPrompt = null;
                @this.call('hidePrompt');
            });
        },
        
        async installPwa() {
            if (!this.deferredPrompt) {
                return;
            }
            
            this.deferredPrompt.prompt();
            const { outcome } = await this.deferredPrompt.userChoice;
            
            if (outcome === 'accepted') {
                this.showInstallPrompt = false;
                @this.call('hidePrompt');
            }
            
            this.deferredPrompt = null;
        },
        
        dismissPrompt() {
            this.showInstallPrompt = false;
            // Store dismissal in session storage
            sessionStorage.setItem('pwa-prompt-dismissed', 'true');
        }
    }
}
</script>