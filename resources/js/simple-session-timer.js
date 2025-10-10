/**
 * Simple Session Timer - Native Laravel Implementation
 * Shows countdown timer for user sessions
 */
class SimpleSessionTimer {
    constructor() {
        this.sessionTimeout = null; // in seconds
        this.startTime = Date.now();
        this.timerInterval = null;
        this.warningShown = false;
        
        this.init();
    }
    
    async init() {
        
        // Get session timeout from user
        const timeoutSeconds = await this.getSessionTimeout();
        
        if (timeoutSeconds && timeoutSeconds > 0) {
            this.sessionTimeout = timeoutSeconds;
            
            this.showTimer();
            this.startCountdown();
            this.bindActivityEvents();
        } else {
        }
    }
    
    async getSessionTimeout() {
        try {
            // Get from user model - direct API call
            const response = await fetch('/api/session-config', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                return data.timeout;
            }
        } catch (error) {
        }
        
        // Fallback: get from meta tag or default
        const userRole = document.querySelector('meta[name="user-role"]')?.getAttribute('content');
        return userRole === 'user' ? 300 : null; // Default 5 minutes for users
    }
    
    showTimer() {
        const desktopTimer = document.getElementById('session-timer-container');
        const mobileTimer = document.getElementById('mobile-session-timer');
        
        if (desktopTimer) {
            desktopTimer.style.display = 'flex';
        }
        if (mobileTimer) {
            mobileTimer.style.display = 'block';
        }
    }
    
    startCountdown() {
        this.timerInterval = setInterval(() => {
            this.updateDisplay();
        }, 1000);
    }
    
    updateDisplay() {
        const elapsed = Math.floor((Date.now() - this.startTime) / 1000);
        const remaining = Math.max(0, this.sessionTimeout - elapsed);
        
        if (remaining <= 0) {
            this.handleTimeout();
            return;
        }
        
        // Format time as MM:SS
        const minutes = Math.floor(remaining / 60);
        const seconds = remaining % 60;
        const formattedTime = `${minutes}:${seconds.toString().padStart(2, '0')}`;
        
        // Update display elements
        const desktopValue = document.getElementById('session-timer-value');
        const mobileValue = document.getElementById('mobile-session-timer-value');
        
        if (desktopValue) desktopValue.textContent = formattedTime;
        if (mobileValue) mobileValue.textContent = formattedTime;
        
        // Show warning at 2 minutes
        if (remaining <= 120 && !this.warningShown) {
            this.showWarning();
        }
        
        // Update visual states
        this.updateVisualState(remaining);
    }
    
    updateVisualState(remaining) {
        const desktopTimer = document.getElementById('session-timer-container');
        const mobileTimer = document.getElementById('mobile-session-timer');
        
        [desktopTimer, mobileTimer].forEach(timer => {
            if (timer) {
                timer.classList.remove('warning', 'critical');
                
                if (remaining <= 60) { // Critical: 1 minute
                    timer.classList.add('critical');
                } else if (remaining <= 300) { // Warning: 5 minutes
                    timer.classList.add('warning');
                }
            }
        });
    }
    
    showWarning() {
        this.warningShown = true;
        
        // Simple browser alert
        if (confirm('Your session will expire in 2 minutes. Click OK to stay logged in.')) {
            this.resetTimer();
        }
    }
    
    resetTimer() {
        this.startTime = Date.now();
        this.warningShown = false;
        
        // Ping server to extend session
        this.extendServerSession();
    }
    
    async extendServerSession() {
        try {
            await fetch('/extend-session', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                }
            });
        } catch (error) {
        }
    }
    
    bindActivityEvents() {
        const events = ['click', 'keypress', 'scroll', 'mousemove'];
        let lastActivity = Date.now();
        
        const handleActivity = () => {
            const now = Date.now();
            // Only reset if more than 30 seconds since last reset (prevent spam)
            if (now - lastActivity > 30000) {
                this.resetTimer();
                lastActivity = now;
            }
        };
        
        events.forEach(event => {
            document.addEventListener(event, handleActivity, { passive: true });
        });
    }
    
    handleTimeout() {
        clearInterval(this.timerInterval);
        
        // Hide timer
        const desktopTimer = document.getElementById('session-timer-container');
        const mobileTimer = document.getElementById('mobile-session-timer');
        
        if (desktopTimer) desktopTimer.style.display = 'none';
        if (mobileTimer) mobileTimer.style.display = 'none';
        
        // Show logout message and redirect
        alert('Your session has expired. You will be redirected to the login page.');
        window.location.href = '/login';
    }
}

// Initialize when DOM is ready and user is a regular user
document.addEventListener('DOMContentLoaded', () => {
    const userRole = document.querySelector('meta[name="user-role"]')?.getAttribute('content');
    
    if (userRole === 'user') {
        new SimpleSessionTimer();
    } else {
    }
});