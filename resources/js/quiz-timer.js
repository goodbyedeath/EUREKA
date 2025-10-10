/**
 * Quiz Timer using Server-Based Time Calculation
 * Persistent timer that survives page refreshes by calculating elapsed time from server start time
 */

// Only run on quiz pages
if (window.location.pathname.includes('/quiz/take/') || document.querySelector('[data-quiz-timer]')) {
    
    // Get quiz data from server
    const quizTimeLimit = window.quizTimeLimitMinutes || 0; // in minutes
    const quizStartTime = window.quizStartTime; // Unix timestamp from server
    const quizAttemptId = window.quizAttemptId || 'unknown';
    const totalSeconds = quizTimeLimit * 60;
    const isCompleted = window.isQuizCompleted || false;
    
    if (totalSeconds <= 0 || !quizStartTime || isCompleted) {
    } else {
        
        // Calculate elapsed time function (similar to session timer logic)
        function calculateRemainingTime() {
            const currentTime = Math.floor(Date.now() / 1000); // Current Unix timestamp
            const elapsedSeconds = currentTime - quizStartTime;
            const remaining = Math.max(0, totalSeconds - elapsedSeconds);
            
            return remaining;
        }
        
        // Update display function
        function updateDisplay() {
            const remainingTime = calculateRemainingTime();
            
            const minutes = Math.floor(remainingTime / 60);
            const seconds = remainingTime % 60;
            const display = `${minutes}:${seconds.toString().padStart(2, '0')}`;
            
            // Update timer display
            const timerDisplay = document.getElementById('quiz-timer-display');
            if (timerDisplay) {
                timerDisplay.textContent = display;
            }
            
            // Update status and styling
            const timerContainer = document.getElementById('quiz-timer-container');
            const timerIcon = document.getElementById('quiz-timer-icon');
            const timerStatus = document.getElementById('quiz-timer-status');
            const progressBar = document.getElementById('quiz-timer-progress');
            const progressPercentage = document.getElementById('quiz-timer-percentage');
            
            if (timerContainer && timerIcon && timerStatus) {
                // Calculate percentages
                const percentage = totalSeconds > 0 ? Math.round((remainingTime / totalSeconds) * 100) : 0;
                
                // Update percentage display
                if (progressPercentage) {
                    progressPercentage.textContent = percentage + '%';
                }
                
                // Update progress bar
                if (progressBar) {
                    progressBar.style.width = percentage + '%';
                }
                
                // Update styling based on time remaining
                timerContainer.className = timerContainer.className.replace(/timer-(normal|warning|critical)/g, '');
                progressBar.className = progressBar.className.replace(/bg-gradient-to-r from-\w+-\d+ to-\w+-\d+/g, '');
                
                if (remainingTime <= 60 && remainingTime > 0) {
                    // Critical: 1 minute or less
                    timerContainer.classList.add('timer-critical');
                    timerIcon.className = 'fas fa-exclamation-triangle text-red-500 text-lg';
                    timerStatus.textContent = 'CRITICAL!';
                    progressBar.classList.add('bg-gradient-to-r', 'from-red-500', 'to-red-600');
                } else if (remainingTime <= 300 && remainingTime > 0) {
                    // Warning: 5 minutes or less
                    timerContainer.classList.add('timer-warning');
                    timerIcon.className = 'fas fa-clock text-amber-500 text-lg';
                    timerStatus.textContent = 'Warning';
                    progressBar.classList.add('bg-gradient-to-r', 'from-amber-500', 'to-amber-600');
                } else {
                    // Normal
                    timerContainer.classList.add('timer-normal');
                    timerIcon.className = 'fas fa-stopwatch text-blue-500 text-lg';
                    timerStatus.textContent = 'Active';
                    progressBar.classList.add('bg-gradient-to-r', 'from-blue-500', 'to-blue-600');
                }
            }
            
            
            // Handle expiry
            if (remainingTime <= 0) {
                // Call Livewire method to handle time expiry
                if (window.Livewire && window.Livewire.find) {
                    const component = window.Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id'));
                    if (component) {
                        component.call('handleTimeExpiry');
                    }
                }
                
                // Stop the timer
                if (window.quizTimerInterval) {
                    clearInterval(window.quizTimerInterval);
                }
                return;
            }
        }
        
        // Show timer container
        const timerContainer = document.getElementById('quiz-timer-container');
        if (timerContainer) {
            timerContainer.style.display = 'block';
        }
        
        // Start countdown immediately
        updateDisplay();
        window.quizTimerInterval = setInterval(updateDisplay, 1000);
        
        
        // Clean up on page unload
        window.addEventListener('beforeunload', () => {
            if (window.quizTimerInterval) {
                clearInterval(window.quizTimerInterval);
            }
        });
    }
}