import './bootstrap';

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
        const container = document.getElementById(containerId);
        const marker = document.getElementById(markerId);
        
        if (!container || !marker) {
            console.warn(`Container or marker not found: ${containerId}, ${markerId}`);
            return null;
        }
        
        // Clean up existing instance
        this.destroy(markerId);
        
        // Default options
        const defaultOptions = {
            onMove: null,
            onEnd: null,
            restrictToParent: true,
            ...options
        };
        
        console.log('Setting up InteractJS for marker:', marker, 'in container:', container);
        
        // Make marker draggable with InteractJS
        const instance = interact(marker)
            .draggable({
                // Restrict movement to parent container
                modifiers: defaultOptions.restrictToParent ? [
                    interact.modifiers.restrictRect({
                        restriction: container,
                        endOnly: false
                    })
                ] : [],
                
                // Enable inertia
                inertia: {
                    resistance: 30,
                    minSpeed: 200,
                    endSpeed: 100
                },
                
                // Auto scroll when dragging near edge
                autoScroll: false,
                
                // Event listeners
                listeners: {
                    start: (event) => {
                        this.onDragStart(event, marker);
                    },
                    move: (event) => {
                        this.onDragMove(event, marker, container);
                        if (defaultOptions.onMove) {
                            defaultOptions.onMove(event, marker, container);
                        }
                    },
                    end: (event) => {
                        this.onDragEnd(event, marker, container);
                        if (defaultOptions.onEnd) {
                            defaultOptions.onEnd(event, marker, container);
                        }
                    }
                }
            });
            
        // Make container clickable to position marker
        const clickHandler = (event) => {
            if (event.target === marker) return; // Don't trigger on marker clicks
            
            const rect = container.getBoundingClientRect();
            const x = event.clientX - rect.left;
            const y = event.clientY - rect.top;
            
            this.positionMarker(marker, x, y, container);
            
            // Trigger the end callback for click positioning
            if (defaultOptions.onEnd) {
                const mockEvent = {
                    clientX: event.clientX,
                    clientY: event.clientY,
                    target: marker
                };
                defaultOptions.onEnd(mockEvent, marker, container);
            }
        };
        
        container.addEventListener('click', clickHandler);
        
        // Store instance data
        this.instances.set(markerId, {
            interact: instance,
            clickHandler: clickHandler,
            container: container,
            marker: marker,
            options: defaultOptions
        });
        
        return instance;
    },
    
    onDragStart(event, marker) {
        console.log('Drag started on marker:', marker.id);
        
        // Add dragging visual feedback
        marker.style.transform = 'translate(-50%, -50%) scale(1.2)';
        marker.style.boxShadow = '0 8px 25px rgba(239, 68, 68, 0.5)';
        marker.style.zIndex = '1000';
        marker.classList.add('dragging');
        
        // Prevent text selection during drag
        document.body.style.userSelect = 'none';
        document.body.style.cursor = 'grabbing';
    },
    
    onDragMove(event, marker, container) {
        // Get current position using InteractJS delta
        const x = (parseFloat(marker.getAttribute('data-x')) || 0) + event.dx;
        const y = (parseFloat(marker.getAttribute('data-y')) || 0) + event.dy;
        
        // Apply position with InteractJS transform
        marker.style.transform = `translate(${x}px, ${y}px) translate(-50%, -50%) scale(1.2)`;
        
        // Store position in data attributes
        marker.setAttribute('data-x', x);
        marker.setAttribute('data-y', y);
        
        // Calculate the final absolute position relative to the container
        // We need to account for the original position plus the drag offset
        const originalLeft = parseFloat(marker.style.left) || 0;
        const originalTop = parseFloat(marker.style.top) || 0;
        const finalX = originalLeft + x;
        const finalY = originalTop + y;
        
        // Update coordinate display with the final position
        this.updateCoordinateDisplay(Math.round(finalX), Math.round(finalY));
    },
    
    onDragEnd(event, marker, container) {
        // Get the drag offset
        const dragX = parseFloat(marker.getAttribute('data-x')) || 0;
        const dragY = parseFloat(marker.getAttribute('data-y')) || 0;
        
        // Calculate the new absolute position
        const originalLeft = parseFloat(marker.style.left) || 0;
        const originalTop = parseFloat(marker.style.top) || 0;
        const newLeft = originalLeft + dragX;
        const newTop = originalTop + dragY;
        
        // Update the marker's actual position
        marker.style.left = newLeft + 'px';
        marker.style.top = newTop + 'px';
        
        // Reset the transform and data attributes
        marker.style.transform = 'translate(-50%, -50%)';
        marker.setAttribute('data-x', 0);
        marker.setAttribute('data-y', 0);
        
        // Reset visual feedback
        marker.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.15)';
        marker.style.zIndex = '10';
        marker.classList.remove('dragging');
        
        // Reset body styles
        document.body.style.userSelect = '';
        document.body.style.cursor = '';
        
        // Update coordinates with the final position
        this.updateCoordinates(Math.round(newLeft), Math.round(newTop));
        
        // Add success animation
        marker.style.animation = 'successPulse 0.6s ease-out';
        setTimeout(() => {
            marker.style.animation = '';
        }, 600);
    },
    
    positionMarker(marker, x, y, container) {
        // Reset any transform data
        marker.setAttribute('data-x', 0);
        marker.setAttribute('data-y', 0);
        
        // Position marker directly
        marker.style.left = x + 'px';
        marker.style.top = y + 'px';
        marker.style.transform = 'translate(-50%, -50%)';
        
        // Add click animation
        marker.style.transition = 'all 0.3s ease-out';
        marker.style.transform = 'translate(-50%, -50%) scale(1.2)';
        
        setTimeout(() => {
            marker.style.transform = 'translate(-50%, -50%)';
            marker.style.transition = '';
        }, 300);
        
        // Update coordinates
        this.updateCoordinates(Math.round(x), Math.round(y));
        
        // Add ripple effect
        this.createRippleEffect(x, y, container);
    },
    
    updateCoordinates(x, y) {
        // Update Livewire component properties
        if (typeof Livewire !== 'undefined') {
            try {
                // Find the Livewire component and update coordinates
                const component = Livewire.find(document.querySelector('[wire\\:id]'));
                if (component) {
                    component.set('coordinate_x', x);
                    component.set('coordinate_y', y);
                }
            } catch (error) {
                console.warn('Could not update Livewire coordinates:', error);
            }
        }
        
        // Also try direct property update
        if (typeof window.updateCoordinates === 'function') {
            window.updateCoordinates(x, y);
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
                instance.marker.style.transform = '';
                instance.marker.style.boxShadow = '';
                instance.marker.style.zIndex = '';
                instance.marker.classList.remove('dragging');
                instance.marker.removeAttribute('data-x');
                instance.marker.removeAttribute('data-y');
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

// Test function to check fullscreen state and CSS
window.debugFullscreen = function() {
    const mapContainer = document.getElementById('map-container');
    const floatingControls = document.getElementById('floating-controls');
    const fullscreenButton = document.querySelector('button[onclick*="handleFullscreen"]');
    
    console.log('=== FULLSCREEN DEBUG ===');
    console.log('Document fullscreen element:', document.fullscreenElement);
    console.log('Is map container in fullscreen:', mapContainer === document.fullscreenElement);
    console.log('Map container element:', mapContainer);
    console.log('Floating controls element:', floatingControls);
    console.log('Fullscreen button element:', fullscreenButton);
    
    if (floatingControls) {
        const styles = window.getComputedStyle(floatingControls);
        console.log('Floating controls computed styles:');
        console.log('- display:', styles.display);
        console.log('- opacity:', styles.opacity);
        console.log('- position:', styles.position);
        console.log('- z-index:', styles.zIndex);
        console.log('- pointer-events:', styles.pointerEvents);
    }
    
    if (fullscreenButton) {
        const styles = window.getComputedStyle(fullscreenButton);
        console.log('Fullscreen button computed styles:');
        console.log('- display:', styles.display);
        console.log('- opacity:', styles.opacity);
        console.log('- position:', styles.position);
        console.log('- z-index:', styles.zIndex);
    }
};

// Global fullscreen change listener
document.addEventListener('fullscreenchange', function() {
    if (typeof window.GameMapManager !== 'undefined') {
        const isFullscreen = !!document.fullscreenElement;
        console.log('Fullscreen change detected:', isFullscreen);
        window.GameMapManager.updateFullscreenControls(isFullscreen);
        
        // Run debug after a short delay to let CSS settle
        setTimeout(() => {
            if (typeof window.debugFullscreen === 'function') {
                window.debugFullscreen();
            }
        }, 100);
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


