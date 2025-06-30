import './bootstrap';

// Dark Mode functionality - improved version
window.DarkMode = {
    init() {
        console.log('Dark Mode initializing...');
        
        // Set theme immediately on init
        const savedTheme = localStorage.getItem('theme') || 'light';
        console.log('Saved theme:', savedTheme);
        this.setTheme(savedTheme);
        
        // Update buttons when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                console.log('DOM loaded, updating buttons');
                this.updateToggleButtons();
            });
        } else {
            // DOM is already loaded
            console.log('DOM already loaded, updating buttons immediately');
            this.updateToggleButtons();
        }
        
        // Also listen for Livewire page loads
        document.addEventListener('livewire:navigated', () => {
            console.log('Livewire navigated, updating buttons');
            this.updateToggleButtons();
        });
    },
    
    setTheme(theme) {
        console.log('Setting theme to:', theme);
        
        if (theme === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
        
        localStorage.setItem('theme', theme);
        
        // Use setTimeout to ensure DOM updates are processed
        setTimeout(() => {
            this.updateToggleButtons();
        }, 10);
    },
    
    toggle() {
        const isDark = document.documentElement.classList.contains('dark');
        const newTheme = isDark ? 'light' : 'dark';
        console.log('Toggling theme from', isDark ? 'dark' : 'light', 'to', newTheme);
        this.setTheme(newTheme);
    },
    
    updateToggleButtons() {
        const isDark = document.documentElement.classList.contains('dark');
        const toggleButtons = document.querySelectorAll('[data-theme-toggle]');
        
        console.log('Updating buttons. Is dark:', isDark, 'Found buttons:', toggleButtons.length);
        
        toggleButtons.forEach((button, index) => {
            const sunIcon = button.querySelector('.sun-icon');
            const moonIcon = button.querySelector('.moon-icon');
            
            console.log(`Button ${index}: sun icon found:`, !!sunIcon, 'moon icon found:', !!moonIcon);
            
            if (sunIcon && moonIcon) {
                if (isDark) {
                    sunIcon.style.display = 'block';
                    moonIcon.style.display = 'none';
                    console.log(`Button ${index}: Showing sun icon (dark mode)`);
                } else {
                    sunIcon.style.display = 'none';
                    moonIcon.style.display = 'block';
                    console.log(`Button ${index}: Showing moon icon (light mode)`);
                }
            }
        });
    },
    
    getTheme() {
        return document.documentElement.classList.contains('dark') ? 'dark' : 'light';
    }
};

// Initialize dark mode immediately
window.DarkMode.init();

// Global function for theme toggle with debugging
window.toggleTheme = function() {
    console.log('toggleTheme called');
    try {
        window.DarkMode.toggle();
    } catch (error) {
        console.error('Error in toggleTheme:', error);
    }
};

// Add click event listeners as backup
document.addEventListener('DOMContentLoaded', function() {
    console.log('Adding click listeners to theme toggle buttons');
    
    function addClickListeners() {
        const buttons = document.querySelectorAll('[data-theme-toggle]');
        console.log('Found toggle buttons for click listeners:', buttons.length);
        
        buttons.forEach((button, index) => {
            // Remove existing listeners to prevent duplicates
            button.removeEventListener('click', handleThemeToggle);
            // Add new listener
            button.addEventListener('click', handleThemeToggle);
            console.log(`Added click listener to button ${index}`);
        });
    }
    
    function handleThemeToggle(event) {
        console.log('Button clicked via event listener');
        event.preventDefault();
        window.toggleTheme();
    }
    
    // Add listeners initially
    addClickListeners();
    
    // Also add after Livewire navigation
    document.addEventListener('livewire:navigated', () => {
        console.log('Re-adding click listeners after Livewire navigation');
        addClickListeners();
    });
});

// Debug function to check current state
window.debugDarkMode = function() {
    console.log('=== Dark Mode Debug ===');
    console.log('Current theme:', window.DarkMode.getTheme());
    console.log('HTML has dark class:', document.documentElement.classList.contains('dark'));
    console.log('LocalStorage theme:', localStorage.getItem('theme'));
    console.log('Toggle buttons found:', document.querySelectorAll('[data-theme-toggle]').length);
    console.log('==================');
};

// Import and expose QR Scanner for global use
import QrScanner from 'qr-scanner';

// Import Panzoom for zoom functionality
import { panzoom } from 'panzoom';

// Import InteractJS for drag and drop functionality
import interact from 'interactjs';

// Set the worker path for QR Scanner (auto-detect from build)
// The worker file is automatically bundled by Vite

// Override HTMLCanvasElement.getContext to automatically set willReadFrequently for QR scanning
const originalGetContext = HTMLCanvasElement.prototype.getContext;
HTMLCanvasElement.prototype.getContext = function(contextType, contextAttributes = {}) {
    // For 2D contexts used in QR scanning, automatically set willReadFrequently
    if (contextType === '2d' && !contextAttributes.hasOwnProperty('willReadFrequently')) {
        // Check if this canvas is likely being used for QR scanning (frequent getImageData calls)
        // We'll set it to true by default for all 2D contexts to prevent the warning
        contextAttributes.willReadFrequently = true;
    }
    return originalGetContext.call(this, contextType, contextAttributes);
};

// Make QrScanner, Panzoom, and InteractJS available globally for Livewire components
window.QrScanner = QrScanner;
window.panzoom = panzoom;
window.interact = interact;

// Global Panzoom utility for game maps
window.GameMapManager = {
    instances: new Map(),
    
    initialize(elementId, options = {}) {
        const element = document.getElementById(elementId);
        if (!element) {
            console.warn(`Element with ID "${elementId}" not found`);
            return null;
        }

        // Destroy existing instance if exists
        this.destroy(elementId);

        // Default options for panzoom v9
        const defaultOptions = {
            maxZoom: 5,
            minZoom: 0.2,
            zoomSpeed: 0.2,
            smoothScroll: false,
            bounds: true,
            boundsPadding: 0.1,
            zoomDoubleClickSpeed: 1,
            beforeWheel: function(e) {
                // Allow wheel zoom
                return true;
            },
            beforeMouseDown: function(e) {
                // Allow mouse pan
                return true;
            },
            ...options
        };

        // Create new panzoom instance
        const instance = panzoom(element, defaultOptions);
        this.instances.set(elementId, instance);
        
        return instance;
    },

    getInstance(elementId) {
        return this.instances.get(elementId);
    },

    destroy(elementId) {
        const instance = this.instances.get(elementId);
        if (instance) {
            instance.dispose();
            this.instances.delete(elementId);
        }
    },

    zoomIn(elementId) {
        const instance = this.getInstance(elementId);
        if (instance) {
            const element = document.getElementById(elementId);
            if (element) {
                try {
                    // Get current scale and calculate new scale
                    const transform = instance.getTransform();
                    const currentScale = transform.scale;
                    const newScale = Math.min(currentScale * 1.3, 5);
                    
                    if (typeof instance.zoomAbs === 'function') {
                        const rect = element.getBoundingClientRect();
                        const centerX = rect.width / 2;
                        const centerY = rect.height / 2;
                        instance.zoomAbs(centerX, centerY, newScale);
                    } else if (typeof instance.zoom === 'function') {
                        instance.zoom(newScale / currentScale);
                    } else {
                        this.manualZoom(elementId, 1.3);
                    }
                    
                    // Update zoom indicator
                    this.updateZoomIndicator(newScale);
                    
                    // Add button feedback
                    this.addButtonFeedback('zoom-in');
                } catch (error) {
                    console.warn('Zoom in failed, trying fallback:', error);
                    this.manualZoom(elementId, 1.3);
                }
            }
        }
    },

    zoomOut(elementId) {
        const instance = this.getInstance(elementId);
        if (instance) {
            const element = document.getElementById(elementId);
            if (element) {
                try {
                    // Get current scale and calculate new scale
                    const transform = instance.getTransform();
                    const currentScale = transform.scale;
                    const newScale = Math.max(currentScale / 1.3, 0.2);
                    
                    if (typeof instance.zoomAbs === 'function') {
                        const rect = element.getBoundingClientRect();
                        const centerX = rect.width / 2;
                        const centerY = rect.height / 2;
                        instance.zoomAbs(centerX, centerY, newScale);
                    } else if (typeof instance.zoom === 'function') {
                        instance.zoom(newScale / currentScale);
                    } else {
                        this.manualZoom(elementId, 0.77);
                    }
                    
                    // Update zoom indicator
                    this.updateZoomIndicator(newScale);
                    
                    // Add button feedback
                    this.addButtonFeedback('zoom-out');
                } catch (error) {
                    console.warn('Zoom out failed, trying fallback:', error);
                    this.manualZoom(elementId, 0.77);
                }
            }
        }
    },

    reset(elementId) {
        const instance = this.getInstance(elementId);
        if (instance) {
            try {
                // Reset to initial state
                instance.moveTo(0, 0);
                instance.zoomAbs(0, 0, 1);
                
                // Update zoom indicator
                this.updateZoomIndicator(1);
                
                // Add button feedback
                this.addButtonFeedback('reset');
            } catch (error) {
                console.warn('Reset failed:', error);
            }
        }
    },

    toggleFullscreen(containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;

        if (document.fullscreenElement) {
            document.exitFullscreen().then(() => {
                // Re-show normal controls
                this.updateFullscreenControls(false);
            });
        } else {
            container.requestFullscreen().then(() => {
                // Show fullscreen controls
                this.updateFullscreenControls(true);
            }).catch(err => {
                console.warn('Could not enter fullscreen mode:', err);
            });
        }
    },

    updateFullscreenControls(isFullscreen) {
        console.log('Updating fullscreen controls:', isFullscreen);
        
        if (isFullscreen) {
            this.showFullscreenControls();
            
            // Initialize zoom indicator with current scale
            const instance = this.getInstance('game-map');
            if (instance) {
                try {
                    const transform = instance.getTransform();
                    this.updateZoomIndicator(transform.scale);
                } catch (error) {
                    this.updateZoomIndicator(1); // Default to 100%
                }
            } else {
                this.updateZoomIndicator(1);
            }
        } else {
            this.hideFullscreenControls();
        }
    },

    // Update zoom level indicator
    updateZoomIndicator(scale) {
        const indicator = document.getElementById('zoom-level');
        if (indicator) {
            const percentage = Math.round(scale * 100);
            indicator.textContent = `${percentage}%`;
            
            // Add pulse animation
            const zoomIndicator = document.getElementById('zoom-indicator');
            if (zoomIndicator) {
                zoomIndicator.classList.add('updating');
                setTimeout(() => {
                    zoomIndicator.classList.remove('updating');
                }, 300);
            }
        }
    },

    // Add visual feedback to buttons
    addButtonFeedback(buttonType) {
        const button = document.querySelector(`.${buttonType}-btn`);
        if (button) {
            button.style.transform = 'scale(0.9)';
            setTimeout(() => {
                button.style.transform = '';
            }, 100);
        }
    },

    // Enhanced fullscreen controls management
    showFullscreenControls() {
        const controls = document.querySelectorAll('.game-controls');
        controls.forEach(control => {
            control.classList.add('animate-in');
        });
    },

    hideFullscreenControls() {
        const controls = document.querySelectorAll('.game-controls');
        controls.forEach(control => {
            control.classList.remove('animate-in');
        });
    },

    // Fallback manual zoom using CSS transforms
    manualZoom(elementId, factor) {
        const element = document.getElementById(elementId);
        if (element) {
            const currentTransform = element.style.transform || '';
            let currentScale = 1;
            
            // Extract current scale from transform  
            const scaleMatch = currentTransform.match(/scale\(([^)]+)\)/);
            if (scaleMatch) {
                currentScale = parseFloat(scaleMatch[1]);
            }
            
            const newScale = Math.max(0.2, Math.min(5, currentScale * factor));
            
            // Apply the new scale
            element.style.transform = currentTransform.replace(/scale\([^)]+\)/, '') + ` scale(${newScale})`;
            element.style.transformOrigin = 'center center';
            element.style.transition = 'transform 0.3s ease';
            
            // Update zoom indicator for manual zoom too
            this.updateZoomIndicator(newScale);
        }
    }
};

// Admin Map Manager for drag and drop positioning using InteractJS
window.AdminMapManager = {
    instances: new Map(),
    
    initialize(containerId, markerId, options = {}) {
        console.log('=== AdminMapManager.initialize called ===');
        console.log('Params:', { containerId, markerId, options });
        
        const container = document.getElementById(containerId);
        const marker = document.getElementById(markerId);
        
        console.log('Found elements:', {
            container: !!container,
            marker: !!marker,
            containerElement: container,
            markerElement: marker
        });
        
        if (!container || !marker) {
            console.warn(`Container or marker not found: ${containerId}, ${markerId}`);
            return null;
        }
        
        // Clean up existing instance
        this.destroy(markerId);
        
        // Initialize position tracking - use consistent positioning approach
        if (!marker.hasAttribute('data-x')) {
            // Get initial position from current left/top styles or default to current position
            const initialX = parseFloat(marker.style.left) || 0;
            const initialY = parseFloat(marker.style.top) || 0;
            marker.setAttribute('data-x', initialX);
            marker.setAttribute('data-y', initialY);
        }
        
        console.log('Setting up InteractJS for marker:', marker.id);
        console.log('interact function available:', typeof interact);
        console.log('marker element:', marker);
        
        // Make marker draggable with InteractJS using proper patterns
        console.log('Calling interact(marker)...');
        const instance = interact(marker)
            .draggable({
                // Restrict movement to parent container
                modifiers: [
                    interact.modifiers.restrictRect({
                        restriction: container,
                        endOnly: false
                    })
                ],
                
                // Disable inertia for precise positioning
                inertia: false,
                
                // Auto scroll when dragging near edge
                autoScroll: false,
                
                // Event listeners following InteractJS patterns
                listeners: {
                    start(event) {
                        console.log('Drag started on marker:', event.target.id);
                        
                        // Add dragging visual feedback
                        event.target.style.boxShadow = '0 8px 25px rgba(239, 68, 68, 0.6)';
                        event.target.style.zIndex = '1000';
                        event.target.classList.add('dragging');
                        
                        // Prevent text selection during drag
                        document.body.style.userSelect = 'none';
                    },
                    
                    move(event) {
                        // Get the current position from data attributes (InteractJS best practice)
                        const x = (parseFloat(event.target.getAttribute('data-x')) || 0) + event.dx;
                        const y = (parseFloat(event.target.getAttribute('data-y')) || 0) + event.dy;
                        
                        // Use simple transform following InteractJS best practices
                        // Keep the centering separate from the dragging transform
                        event.target.style.left = x + 'px';
                        event.target.style.top = y + 'px';
                        event.target.style.transform = 'translate(-50%, -50%) scale(1.1)';
                        
                        // Update position in data attributes
                        event.target.setAttribute('data-x', x);
                        event.target.setAttribute('data-y', y);
                        
                        // Update coordinate display with current position
                        AdminMapManager.updateCoordinateDisplay(Math.round(x), Math.round(y));
                    },
                    
                    end(event) {
                        console.log('Drag ended on marker:', event.target.id);
                        
                        // Get final position from data attributes
                        const x = parseFloat(event.target.getAttribute('data-x')) || 0;
                        const y = parseFloat(event.target.getAttribute('data-y')) || 0;
                        
                        // Reset visual feedback - keep positioning simple
                        event.target.style.transform = 'translate(-50%, -50%)';
                        event.target.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.15)';
                        event.target.style.zIndex = '10';
                        event.target.classList.remove('dragging');
                        
                        // Reset body styles
                        document.body.style.userSelect = '';
                        
                        // Update coordinates with final position
                        AdminMapManager.updateCoordinates(Math.round(x), Math.round(y));
                        
                        // Add success animation
                        event.target.style.animation = 'successPulse 0.6s ease-out';
                        setTimeout(() => {
                            event.target.style.animation = '';
                        }, 600);
                        
                        // Call custom end callback if provided
                        if (options.onEnd) {
                            options.onEnd(event, event.target, container);
                        }
                    }
                }
            });
            
        // Make container clickable to position marker
        const clickHandler = (event) => {
            if (event.target === marker || event.target.closest('#admin-location-marker')) return;
            
            const rect = container.getBoundingClientRect();
            const x = event.clientX - rect.left;
            const y = event.clientY - rect.top;
            
            this.positionMarker(marker, x, y, container);
            
            // Update coordinates
            this.updateCoordinates(Math.round(x), Math.round(y));
        };
        
        container.addEventListener('click', clickHandler);
        
        // Store instance data
        this.instances.set(markerId, {
            interact: instance,
            clickHandler: clickHandler,
            container: container,
            marker: marker,
            options: options
        });
        
        console.log('✅ AdminMapManager.initialize completed successfully');
        console.log('Instance stored for markerId:', markerId);
        console.log('=== AdminMapManager.initialize finished ===');
        
        return instance;
    },
    
    positionMarker(marker, x, y, container) {
        // Update data attributes to maintain consistency with InteractJS
        marker.setAttribute('data-x', x);
        marker.setAttribute('data-y', y);
        
        // Position marker using left/top (consistent with drag implementation)
        marker.style.left = x + 'px';
        marker.style.top = y + 'px';
        marker.style.transform = 'translate(-50%, -50%)';
        
        // Add click animation
        marker.style.transition = 'transform 0.3s ease-out';
        marker.style.transform = 'translate(-50%, -50%) scale(1.2)';
        
        setTimeout(() => {
            marker.style.transform = 'translate(-50%, -50%)';
            marker.style.transition = '';
        }, 300);
        
        // Add ripple effect
        this.createRippleEffect(x, y, container);
    },
    
    updateCoordinates(x, y) {
        // Update Livewire component properties by calling the global updateCoordinates function
        // This function is defined in the Blade template and has access to @this
        if (typeof window.updateCoordinates === 'function') {
            window.updateCoordinates(x, y);
        } else {
            console.warn('Global updateCoordinates function not found. Make sure the modal/blade template is loaded.');
        }
    },
    
    updateCoordinateDisplay(x, y) {
        const display = document.getElementById('coordinate-display');
        if (display) {
            display.textContent = `${x}, ${y}`;
        }
    },
    
    createRippleEffect(x, y, container) {
        const ripple = document.createElement('div');
        ripple.className = 'ripple-effect';
        ripple.style.cssText = `
            position: absolute;
            left: ${x}px;
            top: ${y}px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: rgba(59, 130, 246, 0.5);
            transform: translate(-50%, -50%) scale(0);
            animation: ripple 0.6s ease-out;
            pointer-events: none;
            z-index: 100;
        `;
        
        container.appendChild(ripple);
        
        setTimeout(() => {
            ripple.remove();
        }, 600);
    },
    
    destroy(markerId) {
        const instance = this.instances.get(markerId);
        if (instance) {
            // Remove interact instance
            if (instance.interact) {
                instance.interact.unset();
            }
            
            // Remove click handler
            if (instance.container && instance.clickHandler) {
                instance.container.removeEventListener('click', instance.clickHandler);
            }
            
            // Clean up marker styles
            if (instance.marker) {
                instance.marker.style.transform = 'translate(-50%, -50%)';
                instance.marker.style.boxShadow = '';
                instance.marker.style.zIndex = '';
                instance.marker.classList.remove('dragging');
                instance.marker.setAttribute('data-x', 0);
                instance.marker.setAttribute('data-y', 0);
            }
            
            this.instances.delete(markerId);
        }
    },
    
    getInstance(markerId) {
        return this.instances.get(markerId);
    }
};

// Global test functions for debugging zoom
window.testZoomIn = function() {
    console.log('Testing zoom in...');
    console.log('GameMapManager available:', typeof window.GameMapManager);
    
    if (typeof window.GameMapManager !== 'undefined') {
        const instance = window.GameMapManager.getInstance('game-map');
        console.log('Panzoom instance:', instance);
        
        if (instance) {
            console.log('Current transform:', instance.getTransform());
            console.log('Available methods:', Object.getOwnPropertyNames(instance).filter(prop => typeof instance[prop] === 'function'));
            
            // Try direct panzoom methods
            if (typeof instance.zoom === 'function') {
                console.log('Using instance.zoom(1.3)');
                instance.zoom(1.3);
            } else if (typeof instance.zoomBy === 'function') {
                console.log('Using instance.zoomBy(1.3)');
                instance.zoomBy(1.3);
            } else {
                console.log('Using manual fallback');
                window.GameMapManager.manualZoom('game-map', 1.3);
            }
        } else {
            console.log('No panzoom instance found, trying manual zoom');
            window.GameMapManager.manualZoom('game-map', 1.3);
        }
        
        // Also try through GameMapManager
        window.GameMapManager.zoomIn('game-map');
    }
};

window.testZoomOut = function() {
    console.log('Testing zoom out...');
    console.log('GameMapManager available:', typeof window.GameMapManager);
    
    if (typeof window.GameMapManager !== 'undefined') {
        const instance = window.GameMapManager.getInstance('game-map');
        console.log('Panzoom instance:', instance);
        
        if (instance) {
            console.log('Current transform before zoom out:', instance.getTransform());
            console.log('Available methods:', Object.getOwnPropertyNames(instance).filter(prop => typeof instance[prop] === 'function'));
            
            // Try direct panzoom methods for zoom out
            const transform = instance.getTransform();
            const currentScale = transform.scale;
            const newScale = Math.max(currentScale / 1.3, 0.2);
            
            console.log('Current scale:', currentScale, 'New scale:', newScale);
            
            if (typeof instance.zoomAbs === 'function') {
                console.log('Using instance.zoomAbs()');
                const element = document.getElementById('game-map');
                const rect = element.getBoundingClientRect();
                instance.zoomAbs(rect.width / 2, rect.height / 2, newScale);
            } else if (typeof instance.zoom === 'function') {
                console.log('Using instance.zoom() with relative factor');
                instance.zoom(newScale / currentScale);
            } else {
                console.log('Using manual fallback');
                window.GameMapManager.manualZoom('game-map', 0.77);
            }
            
            setTimeout(() => {
                console.log('Transform after zoom out:', instance.getTransform());
            }, 100);
        }
        
        // Also try through GameMapManager
        window.GameMapManager.zoomOut('game-map');
    }
};

window.testReset = function() {
    console.log('Testing reset...');
    if (typeof window.GameMapManager !== 'undefined') {
        window.GameMapManager.reset('game-map');
    }
};

// Global handler functions for map controls
window.handleZoomIn = function() {
    try {
        if (window.GameMapManager && typeof window.GameMapManager.zoomIn === 'function') {
            window.GameMapManager.zoomIn('game-map');
        } else {
            console.warn('GameMapManager not available for zoom in');
        }
    } catch (error) {
        console.error('Error in handleZoomIn:', error);
    }
};

window.handleZoomOut = function() {
    try {
        if (window.GameMapManager && typeof window.GameMapManager.zoomOut === 'function') {
            window.GameMapManager.zoomOut('game-map');
        } else {
            console.warn('GameMapManager not available for zoom out');
        }
    } catch (error) {
        console.error('Error in handleZoomOut:', error);
    }
};

window.handleReset = function() {
    try {
        if (window.GameMapManager && typeof window.GameMapManager.reset === 'function') {
            window.GameMapManager.reset('game-map');
        } else {
            console.warn('GameMapManager not available for reset');
        }
    } catch (error) {
        console.error('Error in handleReset:', error);
    }
};

window.handleFullscreen = function() {
    try {
        if (window.GameMapManager && typeof window.GameMapManager.toggleFullscreen === 'function') {
            window.GameMapManager.toggleFullscreen('map-container');
        } else {
            console.warn('GameMapManager not available for fullscreen');
        }
    } catch (error) {
        console.error('Error in handleFullscreen:', error);
    }
};

// Direct fullscreen handler with comprehensive browser support
window.handleFullscreenClick = function() {
    const mapContainer = document.getElementById('map-container');
    if (mapContainer) {
        toggleFullscreenDirect(mapContainer);
    }
};

// Direct zoom handlers for immediate response
window.handleZoomInClick = function() {
    try {
        if (window.GameMapManager && typeof window.GameMapManager.zoomIn === 'function') {
            window.GameMapManager.zoomIn('map-wrapper');
        } else {
            manualZoomIn();
        }
    } catch (error) {
        manualZoomIn();
    }
};

window.handleZoomOutClick = function() {
    try {
        if (window.GameMapManager && typeof window.GameMapManager.zoomOut === 'function') {
            window.GameMapManager.zoomOut('map-wrapper');
        } else {
            manualZoomOut();
        }
    } catch (error) {
        manualZoomOut();
    }
};

window.handleResetClick = function() {
    try {
        if (window.GameMapManager && typeof window.GameMapManager.reset === 'function') {
            window.GameMapManager.reset('map-wrapper');
        } else {
            manualReset();
        }
    } catch (error) {
        manualReset();
    }
};

// Fallback manual zoom functions
function manualZoomIn() {
    const mapWrapper = document.getElementById('map-wrapper');
    if (mapWrapper) {
        let currentScale = getCurrentScale(mapWrapper);
        const newScale = Math.min(currentScale * 1.3, 5);
        applyScale(mapWrapper, newScale);
        updateZoomDisplay(newScale);
    }
}

function manualZoomOut() {
    const mapWrapper = document.getElementById('map-wrapper');
    if (mapWrapper) {
        let currentScale = getCurrentScale(mapWrapper);
        const newScale = Math.max(currentScale / 1.3, 0.2);
        applyScale(mapWrapper, newScale);
        updateZoomDisplay(newScale);
    }
}

function manualReset() {
    const mapWrapper = document.getElementById('map-wrapper');
    if (mapWrapper) {
        applyScale(mapWrapper, 1);
        updateZoomDisplay(1);
        // Also reset position
        mapWrapper.style.transform = 'scale(1)';
        mapWrapper.style.left = '0px';
        mapWrapper.style.top = '0px';
    }
}

function getCurrentScale(element) {
    const transform = element.style.transform || '';
    const scaleMatch = transform.match(/scale\(([^)]+)\)/);
    return scaleMatch ? parseFloat(scaleMatch[1]) : 1;
}

function applyScale(element, scale) {
    element.style.transform = `scale(${scale})`;
    element.style.transformOrigin = 'center center';
    element.style.transition = 'transform 0.2s ease-out';
    
    // Remove transition after animation
    setTimeout(() => {
        element.style.transition = '';
    }, 200);
}

function updateZoomDisplay(scale) {
    const zoomLevel = document.getElementById('zoom-level');
    if (zoomLevel) {
        const percentage = Math.round(scale * 100);
        zoomLevel.textContent = percentage + '%';
        
        // Show zoom indicator temporarily
        const zoomIndicator = document.getElementById('zoom-indicator');
        if (zoomIndicator) {
            zoomIndicator.style.opacity = '1';
            zoomIndicator.style.visibility = 'visible';
            setTimeout(() => {
                zoomIndicator.style.opacity = '0';
                zoomIndicator.style.visibility = 'hidden';
            }, 2000);
        }
    }
}

// Direct fullscreen toggle function
function toggleFullscreenDirect(element) {
    if (!element) return;

    const isCurrentlyFullscreen = !!(document.fullscreenElement || 
        document.webkitFullscreenElement || 
        document.mozFullScreenElement);

    if (!isCurrentlyFullscreen) {
        // Enter fullscreen
        if (element.requestFullscreen) {
            element.requestFullscreen().catch(() => {});
        } else if (element.webkitRequestFullscreen) {
            element.webkitRequestFullscreen();
        } else if (element.mozRequestFullScreen) {
            element.mozRequestFullScreen();
        }
    } else {
        // Exit fullscreen
        if (document.exitFullscreen) {
            document.exitFullscreen().catch(() => {});
        } else if (document.webkitExitFullscreen) {
            document.webkitExitFullscreen();
        } else if (document.mozCancelFullScreen) {
            document.mozCancelFullScreen();
        }
    }
}

// Test function to verify all handlers are loaded
window.testHandlers = function() {
    console.log('Testing all handler functions:');
    console.log('handleZoomIn:', typeof window.handleZoomIn);
    console.log('handleZoomOut:', typeof window.handleZoomOut);
    console.log('handleReset:', typeof window.handleReset);
    console.log('handleFullscreen:', typeof window.handleFullscreen);
    console.log('GameMapManager:', typeof window.GameMapManager);
};

// Test function to verify controls functionality
window.testMapControls = function() {
    console.log('=== MAP CONTROLS TEST ===');
    
    const mapContainer = document.getElementById('map-container');
    const floatingControls = document.getElementById('floating-controls');
    const fullscreenButton = document.getElementById('fullscreen-toggle');
    const zoomIndicator = document.getElementById('zoom-indicator');
    
    console.log('Map container:', !!mapContainer);
    console.log('Floating controls:', !!floatingControls);
    console.log('Fullscreen button:', !!fullscreenButton);
    console.log('Zoom indicator:', !!zoomIndicator);
    console.log('GameMapManager available:', !!window.GameMapManager);
    
    if (window.GameMapManager) {
        const instance = window.GameMapManager.getInstance('game-map');
        console.log('Panzoom instance:', !!instance);
        
        if (instance) {
            try {
                const transform = instance.getTransform();
                console.log('Current transform:', transform);
            } catch (e) {
                console.log('Could not get transform:', e.message);
            }
        }
    }
    
    console.log('=== END TEST ===');
};


// Global fullscreen change listener
document.addEventListener('fullscreenchange', function() {
    if (typeof window.GameMapManager !== 'undefined') {
        const isFullscreen = !!document.fullscreenElement;
        window.GameMapManager.updateFullscreenControls(isFullscreen);
    }
});

// Also listen for other fullscreen events (browser compatibility)
document.addEventListener('webkitfullscreenchange', function() {
    if (typeof window.GameMapManager !== 'undefined') {
        const isFullscreen = !!document.webkitFullscreenElement;
        window.GameMapManager.updateFullscreenControls(isFullscreen);
    }
});

document.addEventListener('mozfullscreenchange', function() {
    if (typeof window.GameMapManager !== 'undefined') {
        const isFullscreen = !!document.mozFullScreenElement;
        window.GameMapManager.updateFullscreenControls(isFullscreen);
    }
});


