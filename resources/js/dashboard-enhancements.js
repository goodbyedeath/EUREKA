/**
 * Enhanced Dashboard JavaScript Functions
 * Provides better user experience with animations, interactions, and feedback
 */

// Enhanced Feature Card Initialization
function initializeFeatureCards() {
    const featureCards = document.querySelectorAll('.feature-card');
    
    // Add staggered entrance animation
    featureCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 150);
    });
    
    // Add click ripple effect
    featureCards.forEach(card => {
        const button = card.querySelector('button');
        if (button) {
            button.addEventListener('click', function(e) {
                createRippleEffect(this, e);
                
                // Track feature interaction
                const featureType = this.getAttribute('wire:click') || '';
                if (featureType.includes('Quiz')) trackFeatureInteraction('quiz');
                else if (featureType.includes('QR')) trackFeatureInteraction('qr');
                else if (featureType.includes('quest')) trackFeatureInteraction('quest');
                else if (featureType.includes('Game')) trackFeatureInteraction('game');
                else if (featureType.includes('Member')) trackFeatureInteraction('team');
            });
        }
    });
}

// Create ripple effect on button click
function createRippleEffect(element, event) {
    const ripple = document.createElement('span');
    const rect = element.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);
    const x = event.clientX - rect.left - size / 2;
    const y = event.clientY - rect.top - size / 2;
    
    ripple.style.cssText = `
        position: absolute;
        width: ${size}px;
        height: ${size}px;
        left: ${x}px;
        top: ${y}px;
        background: rgba(255,255,255,0.4);
        border-radius: 50%;
        transform: scale(0);
        animation: ripple 0.6s ease-out;
        pointer-events: none;
        z-index: 10;
    `;
    
    element.appendChild(ripple);
    setTimeout(() => ripple.remove(), 600);
}

// Load and animate user statistics
function loadUserStats() {
    // Get user stats from API or generate demo data
    const stats = generateUserStats();
    
    Object.entries(stats).forEach(([id, value], index) => {
        const element = document.getElementById(id);
        if (element) {
            setTimeout(() => {
                element.classList.add('stat-animate');
                
                // Animate number counting
                if (typeof value === 'number') {
                    animateValue(element, 0, value, 1200);
                } else {
                    animateTextValue(element, value, 800);
                }
            }, index * 200);
        }
    });
}

// Generate user statistics (replace with real API call)
function generateUserStats() {
    // In a real implementation, this would fetch from your Laravel backend
    return {
        'completed-quizzes': Math.floor(Math.random() * 50) + 10,
        'average-score': Math.floor(Math.random() * 30) + 70,
        'total-time': Math.floor(Math.random() * 10) + 5,
        'streak': Math.floor(Math.random() * 15) + 1
    };
}

// Animate numeric values with counting effect
function animateValue(element, start, end, duration) {
    const startTimestamp = performance.now();
    const step = (timestamp) => {
        const progress = Math.min((timestamp - startTimestamp) / duration, 1);
        const current = Math.floor(progress * (end - start) + start);
        
        // Add appropriate suffix based on element ID
        let suffix = '';
        if (element.id === 'average-score') suffix = '%';
        else if (element.id === 'total-time') suffix = 'h';
        
        element.textContent = current + suffix;
        
        if (progress < 1) {
            requestAnimationFrame(step);
        }
    };
    requestAnimationFrame(step);
}

// Animate text values with typewriter effect
function animateTextValue(element, finalText, duration) {
    let i = 0;
    const typeWriter = () => {
        if (i < finalText.length) {
            element.textContent = finalText.substring(0, i + 1);
            i++;
            setTimeout(typeWriter, duration / finalText.length);
        }
    };
    typeWriter();
}

// Add interactive effects to dashboard elements
function addInteractiveEffects() {
    // Add hover effects to buttons
    const buttons = document.querySelectorAll('button:not(.feature-card button)');
    buttons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-1px)';
            this.style.transition = 'transform 0.2s ease';
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // Add focus ring animations
    addFocusRingAnimations();
    
    // Initialize progressive loading for heavy content
    initializeProgressiveLoading();
}

// Enhanced focus ring animations for accessibility
function addFocusRingAnimations() {
    const focusableElements = document.querySelectorAll('button, a, input, select, textarea');
    
    focusableElements.forEach(element => {
        element.addEventListener('focus', function() {
            this.style.outline = '2px solid #3B82F6';
            this.style.outlineOffset = '2px';
            this.style.transition = 'outline 0.2s ease';
        });
        
        element.addEventListener('blur', function() {
            this.style.outline = 'none';
        });
    });
}

// Progressive loading for dashboard sections
function initializeProgressiveLoading() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-fade-in');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });
    
    const sections = document.querySelectorAll('.bg-white, .bg-gray-800');
    sections.forEach(section => {
        observer.observe(section);
    });
}

// Feature interaction tracking
function trackFeatureInteraction(featureType) {
    if (featureInteractions[featureType + '_clicks'] !== undefined) {
        featureInteractions[featureType + '_clicks']++;
    }
    
    // Store in localStorage
    localStorage.setItem('feature_interactions', JSON.stringify(featureInteractions));
    
    // Show usage feedback
    showFeatureUsageFeedback(featureType);
}

// Show feature usage feedback
function showFeatureUsageFeedback(featureType) {
    const notification = document.createElement('div');
    notification.className = 'fixed top-4 right-4 bg-blue-500 text-white px-4 py-2 rounded-lg shadow-lg transform translate-x-full transition-transform z-50';
    notification.innerHTML = `
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            <span>${featureType.charAt(0).toUpperCase() + featureType.slice(1)} feature activated!</span>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => notification.remove(), 300);
    }, 2500);
}

// Enhanced flash message handling
function handleEnhancedFlashMessages() {
    const messages = document.querySelectorAll('[class*="fixed top-4 right-4"]');
    messages.forEach((message, index) => {
        // Add entrance animation
        message.style.transform = 'translateX(100%)';
        message.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
        
        setTimeout(() => {
            message.style.transform = 'translateX(0)';
        }, index * 100);
        
        // Add progress bar
        addProgressBarToMessage(message);
        
        // Auto-hide with animation
        setTimeout(() => {
            message.style.transform = 'translateX(100%)';
            message.style.opacity = '0';
            setTimeout(() => message.remove(), 300);
        }, 5000);
    });
}

// Add progress bar to flash messages
function addProgressBarToMessage(message) {
    if (message.querySelector('.progress-bar')) return; // Already has progress bar
    
    const progressBar = document.createElement('div');
    progressBar.className = 'progress-bar';
    progressBar.style.cssText = `
        position: absolute;
        bottom: 0;
        left: 0;
        height: 2px;
        background: rgba(255,255,255,0.4);
        width: 0%;
        transition: width 5s linear;
        border-radius: 0 0 0.5rem 0.5rem;
    `;
    
    message.style.position = 'relative';
    message.style.overflow = 'hidden';
    message.appendChild(progressBar);
    
    setTimeout(() => progressBar.style.width = '100%', 100);
}

// Connection status indicator
function initializeConnectionStatus() {
    window.addEventListener('online', () => showConnectionStatus('online'));
    window.addEventListener('offline', () => showConnectionStatus('offline'));
}

function showConnectionStatus(status) {
    const indicator = document.createElement('div');
    const isOnline = status === 'online';
    
    indicator.className = `fixed bottom-4 left-4 ${isOnline ? 'bg-green-500' : 'bg-red-500'} text-white px-4 py-2 rounded-lg shadow-lg z-50 transform translate-y-full transition-transform`;
    indicator.innerHTML = `
        <div class="flex items-center">
            <i class="fas ${isOnline ? 'fa-wifi' : 'fa-wifi-slash'} mr-2"></i>
            <span>${isOnline ? 'Back online!' : 'You\'re offline'}</span>
        </div>
    `;
    
    document.body.appendChild(indicator);
    
    setTimeout(() => {
        indicator.style.transform = 'translateY(0)';
    }, 100);
    
    setTimeout(() => {
        indicator.style.transform = 'translateY(100%)';
        setTimeout(() => indicator.remove(), 300);
    }, isOnline ? 2000 : 0);
}

// Load stored feature interactions on page load
function loadStoredInteractions() {
    const stored = localStorage.getItem('feature_interactions');
    if (stored) {
        featureInteractions = { ...featureInteractions, ...JSON.parse(stored) };
    }
}

// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    loadStoredInteractions();
    initializeConnectionStatus();
});

// Export functions for global use
window.dashboardEnhancements = {
    initializeFeatureCards,
    loadUserStats,
    addInteractiveEffects,
    trackFeatureInteraction,
    handleEnhancedFlashMessages
};