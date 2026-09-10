/**
 * Network Connectivity Monitor
 * Detects network connection loss and shows reconnection notifications
 */

class NetworkMonitor {
    constructor() {
        this.isOnline = navigator.onLine;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.reconnectInterval = null;
        this.pingInterval = null;
        this.notificationElement = null;
        this.lastSuccessfulPing = Date.now();
        // Last time the server was heard from by ANY means, not just a ping.
        this.lastSeenServer = Date.now();
        
        this.init();
    }

    init() {
        this.createNotificationElement();
        this.setupEventListeners();
        this.startPingMonitoring();
    }

    createNotificationElement() {
        // Create notification container
        this.notificationElement = document.createElement('div');
        this.notificationElement.id = 'network-notification';
        this.notificationElement.className = 'fixed top-4 right-4 z-50 transition-all duration-300 transform translate-x-full opacity-0';
        this.notificationElement.innerHTML = `
            <div class="bg-red-500 text-white px-6 py-4 rounded-lg shadow-lg max-w-sm">
                <div class="flex items-center gap-3">
                    <div class="network-status-icon">
                        <svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold network-message">Connection Lost</p>
                        <p class="text-sm opacity-90 network-details">Attempting to reconnect...</p>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(this.notificationElement);
    }

    setupEventListeners() {
        // Browser online/offline events
        window.addEventListener('online', () => this.handleOnline());
        window.addEventListener('offline', () => this.handleOffline());
        
        // Listen for Livewire connection events
        document.addEventListener('livewire:init', () => {
            if (window.Livewire) {
                // Listen for Livewire request failures
                window.Livewire.hook('request', ({ fail }) => {
                    fail((error) => {
                        // 429 is not an outage: the server is up and deliberately asking
                        // us to slow down. At a venue every team shares one public IP, so
                        // a rate limit is something a whole event can walk into at once —
                        // and without this branch Livewire renders the empty 429 body over
                        // the page, which looks exactly like the site dying.
                        if (error.status === 429) {
                            this.handleRateLimited(error);
                            // Swallow it. Livewire's default is to replace the document
                            // with the response body; for a bodiless 429 that is a blank
                            // white screen the team cannot get out of.
                            error.preventDefault?.();
                            return;
                        }

                        if (error.status === 0 || error.status >= 500) {
                            this.handleLivewireOffline();
                        }
                    });
                });
                
                // Listen for successful Livewire requests
                window.Livewire.hook('request', ({ succeed }) => {
                    succeed(() => {
                        // Every successful request is proof the server is reachable, so
                        // it counts as a ping. An app in active use then needs none of
                        // its own — see pingServer().
                        this.lastSeenServer = Date.now();
                        if (!this.isOnline) {
                            this.handleLivewireOnline();
                        }
                    });
                });
            }
        });
        
        // Legacy Livewire events (if available)
        if (window.Livewire) {
            try {
                window.Livewire.on('livewire:offline', () => this.handleLivewireOffline());
                window.Livewire.on('livewire:online', () => this.handleLivewireOnline());
            } catch (e) {
                // Livewire events might not be available in all versions
            }
        }
    }

    startPingMonitoring() {
        // Every 30 seconds, not 10.
        //
        // This runs on every page in every open tab, so at 10s it was six requests a
        // minute per tab doing nothing — an admin with the panel and the LED board open
        // was sending steady background traffic all day, which is the kind of thing a
        // host's rate limiter counts. The browser's own online/offline events already
        // catch a dropped connection immediately; this only exists to notice the subtler
        // case of a connection that is up but cannot reach us, and 30s is soon enough
        // for that.
        this.pingInterval = setInterval(() => {
            this.pingServer();
        }, 30000);
    }

    async pingServer() {
        // While backing off we do not add to the load we are being asked to reduce.
        if (this.backoffUntil && Date.now() < this.backoffUntil) return;

        // Adaptive: a request that already succeeded proves the connection, so an app in
        // use pings not at all. This tick only exists to notice a connection that is up
        // but cannot reach us, and that only matters while nothing else is talking.
        //
        // It runs on every page in every open tab, so at a venue it was multiplied by the
        // number of teams — a steady cost for information the app was already getting for
        // free from its own traffic.
        if (this.lastSeenServer && Date.now() - this.lastSeenServer < 30000) return;

        try {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 5000); // 5 second timeout
            
            const response = await fetch(window.location.origin + '/ping', {
                method: 'HEAD',
                cache: 'no-cache',
                signal: controller.signal
            });
            
            clearTimeout(timeoutId);
            
            if (response.ok) {
                this.lastSuccessfulPing = Date.now();
                this.lastSeenServer = Date.now();
                if (!this.isOnline) {
                    this.handleConnectionRestored();
                }
            } else {
                throw new Error('Server responded with error');
            }
        } catch (error) {
            // If we haven't had a successful ping in 15 seconds, consider offline
            if (Date.now() - this.lastSuccessfulPing > 15000 && this.isOnline) {
                this.handleConnectionLost();
            }
        }
    }

    handleOnline() {
        if (!this.isOnline) {
            this.handleConnectionRestored();
        }
    }

    handleOffline() {
        this.handleConnectionLost();
    }

    handleLivewireOffline() {
        this.handleConnectionLost();
    }

    /**
     * The server asked us to slow down (HTTP 429).
     *
     * Everything the app polls is put to sleep for a cooling-off period, then woken up.
     * Backing off is the only thing that actually helps: retrying immediately is what
     * keeps the limit tripped, and at a venue every team is behind one public IP, so one
     * device that keeps hammering holds the whole room down.
     *
     * The wait doubles each time, capped, and honours Retry-After when the server sends
     * one. Team-facing wording: nobody at an event needs to read "429".
     */
    handleRateLimited(error) {
        const header = Number(error?.response?.headers?.get?.('Retry-After'));
        this.rateLimitStrikes = (this.rateLimitStrikes || 0) + 1;

        const wait = Number.isFinite(header) && header > 0
            ? header * 1000
            : Math.min(60000, 5000 * Math.pow(2, this.rateLimitStrikes - 1));

        this.pauseBackgroundWork(wait);

        // Mark ourselves offline, not just paint the banner. The recovery paths —
        // `handleLivewireOnline` on the next successful request, and the timer below —
        // both do nothing unless this flag says we were down, so leaving it true meant
        // the banner would stay on screen after the server started answering again.
        this.isOnline = false;
        this.showNotification('offline');
        const msg = this.notificationElement?.querySelector('.network-message');
        const det = this.notificationElement?.querySelector('.network-details');
        if (msg) msg.textContent = 'Server sedang sibuk';
        if (det) det.textContent = 'Mencoba lagi dalam ' + Math.ceil(wait / 1000)
            + ' detik. Jangan tutup halaman ini — jawaban Anda tersimpan.';

        clearTimeout(this.rateLimitTimer);
        this.rateLimitTimer = setTimeout(() => {
            this.resumeBackgroundWork();
            this.handleConnectionRestored();
            // Forgive one strike per successful recovery, so a single bad minute does not
            // leave the app crawling for the rest of the event.
            this.rateLimitStrikes = Math.max(0, (this.rateLimitStrikes || 1) - 1);
        }, wait);
    }

    /**
     * Stop every timer this class owns while we are backing off.
     *
     * Only our own: a quiz countdown is the team's time and must keep running whatever
     * the network is doing.
     */
    pauseBackgroundWork(ms) {
        clearInterval(this.pingInterval);
        this.pingInterval = null;
        this.clearReconnectInterval?.();
        this.backoffUntil = Date.now() + ms;
    }

    resumeBackgroundWork() {
        this.backoffUntil = 0;
        if (!this.pingInterval) {
            this.startPingMonitoring();
        }
    }

    handleLivewireOnline() {
        this.handleConnectionRestored();
    }

    handleConnectionLost() {
        this.isOnline = false;
        this.reconnectAttempts = 0;
        this.showNotification('offline');
        this.startReconnectAttempts();
    }

    handleConnectionRestored() {
        this.isOnline = true;
        this.reconnectAttempts = 0;
        this.clearReconnectInterval();
        this.showNotification('online');
        
        // Hide notification after 3 seconds
        setTimeout(() => {
            this.hideNotification();
        }, 3000);
    }

    startReconnectAttempts() {
        this.clearReconnectInterval();
        
        this.reconnectInterval = setInterval(() => {
            this.reconnectAttempts++;
            this.updateNotification();
            
            if (this.reconnectAttempts >= this.maxReconnectAttempts) {
                this.clearReconnectInterval();
                this.showNotification('failed');
            } else {
                // Try to reconnect
                this.attemptReconnect();
            }
        }, 3000); // Try every 3 seconds
    }

    async attemptReconnect() {
        try {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 3000);
            
            const response = await fetch(window.location.origin + '/ping', {
                method: 'HEAD',
                cache: 'no-cache',
                signal: controller.signal
            });
            
            clearTimeout(timeoutId);
            
            if (response.ok) {
                this.handleConnectionRestored();
            }
        } catch (error) {
            // Continue trying
        }
    }

    showNotification(type) {
        const messageEl = this.notificationElement.querySelector('.network-message');
        const detailsEl = this.notificationElement.querySelector('.network-details');
        const iconEl = this.notificationElement.querySelector('.network-status-icon');
        const containerEl = this.notificationElement.querySelector('div');
        
        switch (type) {
            case 'offline':
                containerEl.className = 'bg-red-500 text-white px-6 py-4 rounded-lg shadow-lg max-w-sm';
                messageEl.textContent = 'Connection Lost';
                detailsEl.textContent = 'Attempting to reconnect...';
                iconEl.innerHTML = `
                    <svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                `;
                break;
                
            case 'online':
                containerEl.className = 'bg-green-500 text-white px-6 py-4 rounded-lg shadow-lg max-w-sm';
                messageEl.textContent = 'Connection Restored';
                detailsEl.textContent = 'Successfully reconnected to server';
                iconEl.innerHTML = `
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                `;
                break;
                
            case 'failed':
                containerEl.className = 'bg-gray-600 text-white px-6 py-4 rounded-lg shadow-lg max-w-sm';
                messageEl.textContent = 'Connection Failed';
                detailsEl.textContent = 'Unable to reconnect. Please check your network.';
                iconEl.innerHTML = `
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.664-.833-2.464 0L5.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                `;
                break;
        }
        
        // Show notification
        this.notificationElement.classList.remove('translate-x-full', 'opacity-0');
        this.notificationElement.classList.add('translate-x-0', 'opacity-100');
    }

    updateNotification() {
        const detailsEl = this.notificationElement.querySelector('.network-details');
        detailsEl.textContent = `Reconnecting attempt ${this.reconnectAttempts}/${this.maxReconnectAttempts}...`;
    }

    hideNotification() {
        this.notificationElement.classList.remove('translate-x-0', 'opacity-100');
        this.notificationElement.classList.add('translate-x-full', 'opacity-0');
    }

    clearReconnectInterval() {
        if (this.reconnectInterval) {
            clearInterval(this.reconnectInterval);
            this.reconnectInterval = null;
        }
    }

    destroy() {
        this.clearReconnectInterval();
        
        if (this.pingInterval) {
            clearInterval(this.pingInterval);
        }
        
        window.removeEventListener('online', this.handleOnline);
        window.removeEventListener('offline', this.handleOffline);
        
        if (this.notificationElement) {
            this.notificationElement.remove();
        }
    }

    // Testing methods (only available in debug mode)
    testDisconnection() {
        if (window.location.hostname === 'localhost' || window.location.hostname.includes('test')) {
            this.handleConnectionLost();
        }
    }

    testReconnection() {
        if (window.location.hostname === 'localhost' || window.location.hostname.includes('test')) {
            this.handleConnectionRestored();
        }
    }
}

// Initialize network monitor when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.networkMonitor = new NetworkMonitor();
});

// Clean up on page unload
window.addEventListener('beforeunload', () => {
    if (window.networkMonitor) {
        window.networkMonitor.destroy();
    }
});

export default NetworkMonitor;