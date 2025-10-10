// PWA Installer functions - works with Livewire's Alpine.js
document.addEventListener('alpine:init', () => {
    Alpine.data('pwaInstaller', () => ({
        deferredPrompt: null,
        showInstallPrompt: false,
        
        init() {
            // Check if PWA is already installed
            if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone) {
                this.showInstallPrompt = false;
                return;
            }
            
            // Check if user has dismissed the prompt in this session
            if (sessionStorage.getItem('pwa-prompt-dismissed') === 'true') {
                this.showInstallPrompt = false;
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
            }
            
            this.deferredPrompt = null;
        },
        
        dismissPrompt() {
            this.showInstallPrompt = false;
            // Store dismissal in session storage
            sessionStorage.setItem('pwa-prompt-dismissed', 'true');
        }
    }));
});