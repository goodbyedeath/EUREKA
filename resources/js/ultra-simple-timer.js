/**
 * Working Session Timer with Real Countdown
 */

// Only run for users
const userRole = document.querySelector('meta[name="user-role"]')?.getAttribute('content');
if (userRole !== 'user') {
} else {
    
    // Get timeout from server (in minutes, convert to seconds)
    const timeoutMinutes = window.userSessionTimeout || 5;
    const totalSeconds = timeoutMinutes * 60;
    
    // Get user ID for localStorage key
    const userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content') || 'unknown';
    const storageKey = `session_timer_${userId}`;
    
    // Try to restore remaining time from localStorage
    let remainingTime = localStorage.getItem(storageKey);
    if (remainingTime) {
        remainingTime = parseInt(remainingTime);
        if (remainingTime <= 0 || isNaN(remainingTime)) {
            remainingTime = totalSeconds;
        }
    } else {
        remainingTime = totalSeconds;
    }
    
    
    // Show timer elements
    const desktopTimer = document.getElementById('session-timer-container');
    const mobileTimer = document.getElementById('mobile-session-timer');
    
    if (desktopTimer) desktopTimer.style.display = 'flex';
    if (mobileTimer) mobileTimer.style.display = 'block';
    
    // Update display function - WORKING VERSION
    function updateDisplay() {
        const minutes = Math.floor(remainingTime / 60);
        const seconds = remainingTime % 60;
        const display = `${minutes}:${seconds.toString().padStart(2, '0')}`;
        
        // Update timer displays
        const desktopValue = document.getElementById('session-timer-value');
        const mobileValue = document.getElementById('mobile-session-timer-value');
        
        if (desktopValue) desktopValue.textContent = display;
        if (mobileValue) mobileValue.textContent = display;
        
        
        // Warning at 2 minutes remaining
        if (remainingTime === 120 && !window.sessionWarningShown) {
            window.sessionWarningShown = true;
            alert('Warning: Your session will expire in 2 minutes. Please save your work or interact with the page to extend your session.');
        }
        
        // Handle expiry
        if (remainingTime <= 0) {
            localStorage.removeItem(storageKey);
            alert('Session expired! You will be logged out automatically.');
            
            // Properly logout using POST request with CSRF token
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/logout';
            form.style.display = 'none';
            
            // Add CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (csrfToken) {
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = csrfToken;
                form.appendChild(csrfInput);
            }
            
            document.body.appendChild(form);
            form.submit();
            return;
        }
        
        // Countdown and save to localStorage
        remainingTime--;
        localStorage.setItem(storageKey, remainingTime);
    }
    
    // Reset timer on user activity
    function resetTimer() {
        remainingTime = totalSeconds;
        localStorage.setItem(storageKey, remainingTime);
        // Reset warning flag so user gets warned again after activity
        window.sessionWarningShown = false;
        
        // Show brief confirmation of session extension (optional)
        showSessionExtendedNotification();
    }
    
    // Show brief visual feedback that session was extended
    function showSessionExtendedNotification() {
        // Only show if user has been active for a while
        if (remainingTime < totalSeconds - 300) { // If more than 5 minutes had passed
            const notification = document.createElement('div');
            notification.textContent = 'Session extended due to activity';
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #10b981;
                color: white;
                padding: 12px 16px;
                border-radius: 8px;
                font-size: 14px;
                font-weight: 500;
                z-index: 10000;
                box-shadow: 0 4px 12px rgba(0,0,0,0.2);
                opacity: 0;
                transition: opacity 0.3s ease;
            `;
            
            document.body.appendChild(notification);
            
            // Fade in
            setTimeout(() => notification.style.opacity = '1', 100);
            
            // Fade out and remove
            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => document.body.removeChild(notification), 300);
            }, 2000);
        }
    }
    
    // Activity detection - more conservative approach
    let lastActivity = Date.now();
    let significantActivityCount = 0;
    
    // Only detect meaningful user interactions, not automatic events
    ['click', 'keydown', 'touchstart'].forEach(event => {
        document.addEventListener(event, (e) => {
            // Skip if event is from automated/programmatic actions
            if (e.isTrusted === false) return;
            
            const now = Date.now();
            
            // Only reset if there's been significant time since last reset (5 minutes minimum)
            if (now - lastActivity > 300000) { // 5 minutes = 300,000ms
                resetTimer();
                lastActivity = now;
                significantActivityCount = 0;
            } else {
                // Count significant activities within 5-minute window
                significantActivityCount++;
                
                // If multiple activities within window, consider it genuine user activity
                if (significantActivityCount >= 3 && now - lastActivity > 60000) { // 1 minute minimum
                    resetTimer();
                    lastActivity = now;
                    significantActivityCount = 0;
                }
            }
        });
    });
    
    // Start countdown immediately
    updateDisplay();
    setInterval(updateDisplay, 1000);
    
}