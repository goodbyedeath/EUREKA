class SessionTimeout {
    constructor() {
        this.warningShown = false;
        this.timeoutId = null;
        this.warningTimeoutId = null;
        this.timerUpdateId = null;
        this.syncIntervalId = null;
        this.sessionTimeout = null;
        this.warningTime = 300; // Show warning 5 minutes before timeout
        this.sessionStartTime = Date.now();
        this.lastActivity = Date.now();
        this.workflowTimersEnabled = false;
        
        this.init();
    }
    
    init() {
        // Check if workflow timers are enabled
        this.checkWorkflowTimersEnabled().then(enabled => {
            this.workflowTimersEnabled = enabled;
            
            if (enabled) {
                // Use workflow timer system
                this.initWorkflowTimers();
            } else {
                // Use legacy client-side timer system
                this.initLegacyTimers();
            }
        });
    }
    
    async checkWorkflowTimersEnabled() {
        try {
            const response = await fetch('/api/user/feature-status?feature=workflow_timers');
            const data = await response.json();
            return data.enabled || false;
        } catch (error) {
            return false; // Default to legacy system
        }
    }
    
    initWorkflowTimers() {
        // For workflow timers, we primarily listen for server events
        // and update the display based on Livewire data
        this.showTimerDisplay();
        this.startWorkflowTimerUpdate();
        this.bindActivityEvents(); // Still track activity for middleware
        
        // Listen for workflow timer events via Echo/WebSocket
        if (window.Echo) {
            window.Echo.private(`user.${window.userId}`)
                .listen('SessionExpired', (e) => {
                    this.handleWorkflowSessionExpired(e);
                });
        }
    }
    
    initLegacyTimers() {
        // Original client-side timer logic
        this.getSessionTimeout().then((timeout) => {
            if (timeout && timeout > 0) {
                this.sessionTimeout = timeout * 1000; // Convert to milliseconds
                this.lastActivity = Date.now(); // Always use current time for client timer
                
                this.startTimer();
                this.bindActivityEvents();
                this.showTimerDisplay();
                this.startTimerUpdate();
            }
        }).catch(error => {
            // Silent error handling
        });
    }
    
    async initFallback() {
        // Fallback to the original method if session status fails
        this.getSessionTimeout().then((timeout) => {
            if (timeout && timeout > 0) {
                this.sessionTimeout = timeout * 1000;
                this.lastActivity = Date.now();
                this.startTimer();
                this.bindActivityEvents();
                this.showTimerDisplay();
                this.startTimerUpdate();
            }
        });
    }
    
    async getSessionTimeout() {
        try {
            const response = await fetch('/api/user/session-timeout');
            const data = await response.json();
            return data.timeout;
        } catch (error) {
            return null;
        }
    }
    
    async getSessionStatus() {
        try {
            const response = await fetch('/api/user/session-status');
            const data = await response.json();
            return data;
        } catch (error) {
            return null;
        }
    }
    
    startTimer() {
        this.clearTimers();
        
        // Reset the activity timer to now
        this.lastActivity = Date.now();
        
        // Set warning timer (5 minutes before timeout)
        const warningTime = Math.max(this.sessionTimeout - (this.warningTime * 1000), 0);
        if (warningTime > 0) {
            this.warningTimeoutId = setTimeout(() => {
                this.showWarning();
            }, warningTime);
        }
        
        // Set logout timer
        this.timeoutId = setTimeout(() => {
            this.logout();
        }, this.sessionTimeout);
    }
    
    resetTimer() {
        if (this.sessionTimeout) {
            this.lastActivity = Date.now();
            this.hideWarning();
            this.startTimer();
            
            // Also extend the server session
            this.extendSession();
        }
    }
    
    clearTimers() {
        if (this.timeoutId) {
            clearTimeout(this.timeoutId);
            this.timeoutId = null;
        }
        if (this.warningTimeoutId) {
            clearTimeout(this.warningTimeoutId);
            this.warningTimeoutId = null;
        }
        if (this.timerUpdateId) {
            clearInterval(this.timerUpdateId);
            this.timerUpdateId = null;
        }
        if (this.syncIntervalId) {
            clearInterval(this.syncIntervalId);
            this.syncIntervalId = null;
        }
    }
    
    bindActivityEvents() {
        const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
        
        events.forEach(event => {
            document.addEventListener(event, () => {
                this.resetTimer();
            }, { passive: true });
        });
    }
    
    showWarning() {
        if (this.warningShown) return;
        
        this.warningShown = true;
        
        // Create warning modal
        const modal = document.createElement('div');
        modal.id = 'session-timeout-warning';
        modal.className = 'fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50';
        modal.innerHTML = `
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
                <div class="mt-3 text-center">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 dark:bg-yellow-800">
                        <svg class="h-6 w-6 text-yellow-600 dark:text-yellow-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100 mt-4">
                        Session Timeout Warning
                    </h3>
                    <div class="mt-2 px-7 py-3">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Your session will expire in <span id="countdown" class="font-bold text-red-600">5:00</span> due to inactivity.
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                            Click "Stay Logged In" to continue your session.
                        </p>
                    </div>
                    <div class="items-center px-4 py-3">
                        <button id="stay-logged-in" class="px-4 py-2 bg-blue-500 text-white text-base font-medium rounded-md w-24 mr-2 hover:bg-blue-600 focus:outline-none transition-colors">
                            Stay Logged In
                        </button>
                        <button id="logout-now" class="px-4 py-2 bg-gray-300 text-gray-700 text-base font-medium rounded-md w-24 hover:bg-gray-400 focus:outline-none transition-colors">
                            Logout
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Bind events
        document.getElementById('stay-logged-in').addEventListener('click', () => {
            this.extendSession();
        });
        
        document.getElementById('logout-now').addEventListener('click', () => {
            this.logout();
        });
        
        // Start countdown
        this.startCountdown();
    }
    
    startCountdown() {
        const countdownElement = document.getElementById('countdown');
        let timeLeft = this.warningTime; // 5 minutes in seconds
        
        const updateCountdown = () => {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            countdownElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
            
            if (timeLeft <= 0) {
                this.logout();
                return;
            }
            
            timeLeft--;
            setTimeout(updateCountdown, 1000);
        };
        
        updateCountdown();
    }
    
    hideWarning() {
        const modal = document.getElementById('session-timeout-warning');
        if (modal) {
            modal.remove();
        }
        this.warningShown = false;
    }
    
    async extendSession() {
        try {
            const response = await fetch('/api/user/extend-session', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                }
            });
            
            if (response.ok) {
                this.hideWarning();
                this.resetTimer();
            } else {
                throw new Error('Failed to extend session');
            }
        } catch (error) {
            this.logout();
        }
    }
    
    logout() {
        this.clearTimers();
        this.hideWarning();
        this.hideTimerDisplay();
        
        // Show logout message
        const logoutMsg = document.createElement('div');
        logoutMsg.className = 'fixed top-4 right-4 bg-red-500 text-white px-4 py-2 rounded-lg z-50';
        logoutMsg.textContent = 'Session expired. Redirecting to login...';
        document.body.appendChild(logoutMsg);
        
        // Redirect to login after 2 seconds
        setTimeout(() => {
            window.location.href = '/login';
        }, 2000);
    }
    
    showTimerDisplay() {
        const desktopTimer = document.getElementById('session-timer-container');
        const mobileTimer = document.getElementById('mobile-session-timer');
        
        if (desktopTimer) {
            desktopTimer.style.display = 'flex';
        } else {
        }
        if (mobileTimer) {
            mobileTimer.style.display = 'block';
        } else {
        }
    }
    
    hideTimerDisplay() {
        const desktopTimer = document.getElementById('session-timer-container');
        const mobileTimer = document.getElementById('mobile-session-timer');
        
        if (desktopTimer) {
            desktopTimer.style.display = 'none';
        }
        if (mobileTimer) {
            mobileTimer.style.display = 'none';
        }
    }
    
    startTimerUpdate() {
        this.timerUpdateId = setInterval(() => {
            this.updateTimerDisplay();
        }, 1000);
        
        // Removed: a 30-second poll of /api/user/session-status.
        //
        // It asked "am I still logged in?" and did nothing with the answer except log out
        // when it was no. But an expired session already announces itself on the next real
        // request: `access.window` answers 403 with `access_window_expired`, and
        // network-monitor picks that up. The poll paid two requests a minute, on every
        // team page, to learn something the app is told for free the moment it matters.
        //
        // At a venue that was two requests a minute times every team, from one shared
        // address, forever — the shape of traffic that trips a host's rate limiter.
        //
        // The countdown display is unaffected: it runs locally off `sessionTimeout`.
    }
    
    updateTimerDisplay() {
        if (this.workflowTimersEnabled) {
            // For workflow timers, display is updated via Livewire
            // We just need to sync with the Livewire component
            this.updateWorkflowTimerDisplay();
            return;
        }

        // Legacy timer display logic
        if (!this.sessionTimeout) return;
        
        const now = Date.now();
        const elapsedSinceLastActivity = now - this.lastActivity;
        const remaining = Math.max(0, this.sessionTimeout - elapsedSinceLastActivity);
        
        if (remaining <= 0) {
            this.hideTimerDisplay();
            return;
        }
        
        const totalMinutes = Math.floor(remaining / (60 * 1000));
        const totalSeconds = Math.floor((remaining % (60 * 1000)) / 1000);
        const formattedTime = `${totalMinutes}:${totalSeconds.toString().padStart(2, '0')}`;
        
        // Update timer displays
        const desktopValue = document.getElementById('session-timer-value');
        const mobileValue = document.getElementById('mobile-session-timer-value');
        
        if (desktopValue) {
            desktopValue.textContent = formattedTime;
        }
        if (mobileValue) {
            mobileValue.textContent = formattedTime;
        }
        
        // Update visual states based on time remaining
        const desktopContainer = document.getElementById('session-timer-container');
        const mobileContainer = document.getElementById('mobile-session-timer');
        
        // Remove existing state classes
        if (desktopContainer) {
            desktopContainer.classList.remove('warning', 'critical');
        }
        if (mobileContainer) {
            mobileContainer.classList.remove('warning', 'critical');
        }
        
        // Add state classes based on remaining time
        const remainingMinutes = remaining / (60 * 1000);
        
        if (remainingMinutes <= 2) { // Critical: 2 minutes or less
            if (desktopContainer) desktopContainer.classList.add('critical');
            if (mobileContainer) mobileContainer.classList.add('critical');
        } else if (remainingMinutes <= 5) { // Warning: 5 minutes or less
            if (desktopContainer) desktopContainer.classList.add('warning');
            if (mobileContainer) mobileContainer.classList.add('warning');
        }
    }

    startWorkflowTimerUpdate() {
        // For workflow timers, refresh every 5 seconds (less frequent than legacy)
        this.timerUpdateId = setInterval(() => {
            this.updateTimerDisplay();
        }, 5000);
    }

    updateWorkflowTimerDisplay() {
        // Get timer data from Livewire component if available
        if (window.Livewire && window.Livewire.find) {
            const dashboardComponent = document.querySelector('[wire\\:id]');
            if (dashboardComponent) {
                const component = window.Livewire.find(dashboardComponent.getAttribute('wire:id'));
                if (component && component.get) {
                    const timeRemaining = component.get('sessionTimeRemaining');
                    const timeFormat = component.call ? component.call('getFormattedSessionTime') : null;
                    const status = component.call ? component.call('getSessionTimerStatus') : 'normal';
                    
                    // Update displays if we have data
                    if (timeFormat !== null) {
                        const desktopValue = document.getElementById('session-timer-value');
                        const mobileValue = document.getElementById('mobile-session-timer-value');
                        
                        if (desktopValue) desktopValue.textContent = timeFormat;
                        if (mobileValue) mobileValue.textContent = timeFormat;
                        
                        // Update container classes
                        const desktopContainer = document.getElementById('session-timer-container');
                        const mobileContainer = document.getElementById('mobile-session-timer');
                        
                        [desktopContainer, mobileContainer].forEach(container => {
                            if (container) {
                                container.classList.remove('warning', 'critical');
                                if (status === 'warning') container.classList.add('warning');
                                if (status === 'critical') container.classList.add('critical');
                            }
                        });
                    }
                }
            }
        }
    }

    handleWorkflowSessionExpired(event) {
        this.clearTimers();
        this.hideTimerDisplay();
        
        // Show logout message
        const logoutMsg = document.createElement('div');
        logoutMsg.className = 'fixed top-4 right-4 bg-red-500 text-white px-4 py-2 rounded-lg z-50';
        logoutMsg.textContent = 'Session expired due to inactivity. Redirecting to login...';
        document.body.appendChild(logoutMsg);
        
        // Redirect after 2 seconds
        setTimeout(() => {
            window.location.href = '/login';
        }, 2000);
    }
    
    async syncWithServer() {
        try {
            const data = await this.getSessionStatus();
            
            // Check if session is expired on server
            if (!data || !data.authenticated) {
                this.logout();
                return;
            }
            
            // Just verify server session is still active, don't sync timing
        } catch (error) {
        }
    }
}

// Initialize session timeout when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    const userRoleMeta = document.querySelector('meta[name="user-role"]');
    
    if (userRoleMeta) {
        const userRole = userRoleMeta.getAttribute('content');
        
        if (userRole === 'user') {
            new SessionTimeout();
        } else {
        }
    } else {
    }
});