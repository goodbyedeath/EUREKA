// Service Worker Configuration
// This script manages which service worker to use and provides testing capabilities

class ServiceWorkerManager {
    constructor() {
        this.swConfig = {
            // Set to true to use enhanced service worker (for testing)
            useEnhanced: this.getEnhancedMode(),
            originalSW: '/sw.js',
            enhancedSW: '/sw-enhanced.js'
        };
        
        this.init();
    }

    getEnhancedMode() {
        // Check URL parameter for testing
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('enhanced-sw') === 'true') {
            localStorage.setItem('eureka-enhanced-sw', 'true');
            return true;
        }
        if (urlParams.get('enhanced-sw') === 'false') {
            localStorage.removeItem('eureka-enhanced-sw');
            return false;
        }
        
        // Check localStorage for persistent setting
        return localStorage.getItem('eureka-enhanced-sw') === 'true';
    }

    async init() {
        if ('serviceWorker' in navigator) {
            try {
                const swUrl = this.swConfig.useEnhanced ? 
                              this.swConfig.enhancedSW : 
                              this.swConfig.originalSW;
                
                console.log(`Registering Service Worker: ${swUrl}`);
                
                const registration = await navigator.serviceWorker.register(swUrl);
                console.log('ServiceWorker registration successful:', registration.scope);
                
                // Update service worker if needed
                registration.addEventListener('updatefound', () => {
                    console.log('Service Worker update found');
                    const newWorker = registration.installing;
                    
                    newWorker.addEventListener('statechange', () => {
                        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                            // New service worker installed, show update prompt
                            this.showUpdatePrompt(newWorker);
                        }
                    });
                });
                
                // Listen for messages from service worker
                navigator.serviceWorker.addEventListener('message', event => {
                    this.handleServiceWorkerMessage(event);
                });
                
                return registration;
                
            } catch (error) {
                console.error('ServiceWorker registration failed:', error);
                
                // Fallback to original service worker if enhanced fails
                if (this.swConfig.useEnhanced) {
                    console.log('Falling back to original service worker...');
                    try {
                        return await navigator.serviceWorker.register(this.swConfig.originalSW);
                    } catch (fallbackError) {
                        console.error('Fallback ServiceWorker registration also failed:', fallbackError);
                    }
                }
            }
        } else {
            console.log('Service Worker not supported');
        }
    }

    showUpdatePrompt(newWorker) {
        // Create update prompt
        const updatePrompt = document.createElement('div');
        updatePrompt.id = 'sw-update-prompt';
        updatePrompt.className = 'fixed bottom-4 right-4 bg-blue-600 text-white p-4 rounded-lg shadow-lg z-50';
        updatePrompt.innerHTML = `
            <div class="flex items-center space-x-3">
                <div>
                    <p class="font-medium">App Update Available</p>
                    <p class="text-sm text-blue-100">Refresh to get the latest version</p>
                </div>
                <button id="sw-update-btn" class="bg-white text-blue-600 px-3 py-1 rounded text-sm font-medium">
                    Update
                </button>
                <button id="sw-dismiss-btn" class="text-blue-100 hover:text-white">
                    ✕
                </button>
            </div>
        `;
        
        document.body.appendChild(updatePrompt);
        
        // Handle update button
        document.getElementById('sw-update-btn').addEventListener('click', () => {
            newWorker.postMessage({ type: 'SKIP_WAITING' });
            window.location.reload();
        });
        
        // Handle dismiss button
        document.getElementById('sw-dismiss-btn').addEventListener('click', () => {
            updatePrompt.remove();
        });
        
        // Auto-remove after 10 seconds
        setTimeout(() => {
            if (document.getElementById('sw-update-prompt')) {
                updatePrompt.remove();
            }
        }, 10000);
    }

    handleServiceWorkerMessage(event) {
        const { data } = event;
        
        switch (data.type) {
            case 'OFFLINE_ACTION_QUEUED':
                console.log('🔄 Action queued for sync:', data);
                this.showToast('Action saved for when you\'re back online', 'info');
                break;
                
            case 'SYNC_COMPLETE':
                console.log('✅ Background sync completed');
                this.showToast('Offline actions synced successfully', 'success');
                break;
                
            case 'CACHE_UPDATED':
                console.log('📦 Cache updated:', data.url);
                break;
        }
    }

    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `fixed top-4 right-4 p-3 rounded-md shadow-lg z-50 transition-all duration-300 transform translate-x-full`;
        
        const colors = {
            'info': 'bg-blue-500 text-white',
            'success': 'bg-green-500 text-white',
            'warning': 'bg-yellow-500 text-black',
            'error': 'bg-red-500 text-white'
        };
        
        toast.className += ` ${colors[type] || colors.info}`;
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        // Animate in
        setTimeout(() => {
            toast.style.transform = 'translateX(0)';
        }, 100);
        
        // Animate out and remove
        setTimeout(() => {
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    // Switch between service workers
    async switchServiceWorker(useEnhanced) {
        if (useEnhanced) {
            localStorage.setItem('eureka-enhanced-sw', 'true');
        } else {
            localStorage.removeItem('eureka-enhanced-sw');
        }
        
        // Unregister current service worker
        if ('serviceWorker' in navigator) {
            const registrations = await navigator.serviceWorker.getRegistrations();
            for (const registration of registrations) {
                await registration.unregister();
            }
        }
        
        // Reload to register new service worker
        window.location.reload();
    }

    // Get current status
    getStatus() {
        return {
            useEnhanced: this.swConfig.useEnhanced,
            currentSW: this.swConfig.useEnhanced ? this.swConfig.enhancedSW : this.swConfig.originalSW,
            supported: 'serviceWorker' in navigator
        };
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.swManager = new ServiceWorkerManager();
});

// Export for module use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ServiceWorkerManager;
}