// Game utilities - loaded on demand
import { panzoom } from 'panzoom';
import interact from 'interactjs';

// Make libraries available globally
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
                return true;
            },
            beforeMouseDown: function(e) {
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
                    
                    this.updateZoomIndicator(newScale);
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
                    
                    this.updateZoomIndicator(newScale);
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
                instance.moveTo(0, 0);
                instance.zoomAbs(0, 0, 1);
                this.updateZoomIndicator(1);
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
                this.updateFullscreenControls(false);
            });
        } else {
            container.requestFullscreen().then(() => {
                this.updateFullscreenControls(true);
            }).catch(err => {
                console.warn('Could not enter fullscreen mode:', err);
            });
        }
    },

    updateFullscreenControls(isFullscreen) {
        if (isFullscreen) {
            this.showFullscreenControls();
            const instance = this.getInstance('game-map');
            if (instance) {
                try {
                    const transform = instance.getTransform();
                    this.updateZoomIndicator(transform.scale);
                } catch (error) {
                    this.updateZoomIndicator(1);
                }
            } else {
                this.updateZoomIndicator(1);
            }
        } else {
            this.hideFullscreenControls();
        }
    },

    updateZoomIndicator(scale) {
        const indicator = document.getElementById('zoom-level');
        if (indicator) {
            const percentage = Math.round(scale * 100);
            indicator.textContent = `${percentage}%`;
            
            const zoomIndicator = document.getElementById('zoom-indicator');
            if (zoomIndicator) {
                zoomIndicator.classList.add('updating');
                setTimeout(() => {
                    zoomIndicator.classList.remove('updating');
                }, 300);
            }
        }
    },

    addButtonFeedback(buttonType) {
        const button = document.querySelector(`.${buttonType}-btn`);
        if (button) {
            button.style.transform = 'scale(0.9)';
            setTimeout(() => {
                button.style.transform = '';
            }, 100);
        }
    },

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

    manualZoom(elementId, factor) {
        const element = document.getElementById(elementId);
        if (element) {
            const currentTransform = element.style.transform || '';
            let currentScale = 1;
            
            const scaleMatch = currentTransform.match(/scale\(([^)]+)\)/);
            if (scaleMatch) {
                currentScale = parseFloat(scaleMatch[1]);
            }
            
            const newScale = Math.max(0.2, Math.min(5, currentScale * factor));
            
            element.style.transform = currentTransform.replace(/scale\([^)]+\)/, '') + ` scale(${newScale})`;
            element.style.transformOrigin = 'center center';
            element.style.transition = 'transform 0.3s ease';
            
            this.updateZoomIndicator(newScale);
        }
    }
};

// Admin Map Manager
window.AdminMapManager = {
    instances: new Map(),
    
    initialize(containerId, markerId, options = {}) {
        const container = document.getElementById(containerId);
        const marker = document.getElementById(markerId);
        
        if (!container || !marker) {
            console.warn(`Container or marker not found: ${containerId}, ${markerId}`);
            return null;
        }
        
        this.destroy(markerId);
        
        if (!marker.hasAttribute('data-x')) {
            const initialX = parseFloat(marker.style.left) || 0;
            const initialY = parseFloat(marker.style.top) || 0;
            marker.setAttribute('data-x', initialX);
            marker.setAttribute('data-y', initialY);
        }
        
        const instance = interact(marker)
            .draggable({
                modifiers: [
                    interact.modifiers.restrictRect({
                        restriction: container,
                        endOnly: false
                    })
                ],
                
                inertia: false,
                autoScroll: false,
                
                listeners: {
                    start(event) {
                        event.target.style.boxShadow = '0 8px 25px rgba(239, 68, 68, 0.6)';
                        event.target.style.zIndex = '1000';
                        event.target.classList.add('dragging');
                        document.body.style.userSelect = 'none';
                    },
                    
                    move(event) {
                        const x = (parseFloat(event.target.getAttribute('data-x')) || 0) + event.dx;
                        const y = (parseFloat(event.target.getAttribute('data-y')) || 0) + event.dy;
                        
                        event.target.style.left = x + 'px';
                        event.target.style.top = y + 'px';
                        event.target.style.transform = 'translate(-50%, -50%) scale(1.1)';
                        
                        event.target.setAttribute('data-x', x);
                        event.target.setAttribute('data-y', y);
                        
                        AdminMapManager.updateCoordinateDisplay(Math.round(x), Math.round(y));
                    },
                    
                    end(event) {
                        const x = parseFloat(event.target.getAttribute('data-x')) || 0;
                        const y = parseFloat(event.target.getAttribute('data-y')) || 0;
                        
                        event.target.style.transform = 'translate(-50%, -50%)';
                        event.target.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.15)';
                        event.target.style.zIndex = '10';
                        event.target.classList.remove('dragging');
                        document.body.style.userSelect = '';
                        
                        AdminMapManager.updateCoordinates(Math.round(x), Math.round(y));
                        
                        event.target.style.animation = 'successPulse 0.6s ease-out';
                        setTimeout(() => {
                            event.target.style.animation = '';
                        }, 600);
                        
                        if (options.onEnd) {
                            options.onEnd(event, event.target, container);
                        }
                    }
                }
            });
            
        const clickHandler = (event) => {
            if (event.target === marker || event.target.closest('#admin-location-marker')) return;
            
            const rect = container.getBoundingClientRect();
            const x = event.clientX - rect.left;
            const y = event.clientY - rect.top;
            
            this.positionMarker(marker, x, y, container);
            this.updateCoordinates(Math.round(x), Math.round(y));
        };
        
        container.addEventListener('click', clickHandler);
        
        this.instances.set(markerId, {
            interact: instance,
            clickHandler: clickHandler,
            container: container,
            marker: marker,
            options: options
        });
        
        return instance;
    },
    
    positionMarker(marker, x, y, container) {
        marker.setAttribute('data-x', x);
        marker.setAttribute('data-y', y);
        
        marker.style.left = x + 'px';
        marker.style.top = y + 'px';
        marker.style.transform = 'translate(-50%, -50%)';
        
        marker.style.transition = 'transform 0.3s ease-out';
        marker.style.transform = 'translate(-50%, -50%) scale(1.2)';
        
        setTimeout(() => {
            marker.style.transform = 'translate(-50%, -50%)';
            marker.style.transition = '';
        }, 300);
        
        this.createRippleEffect(x, y, container);
    },
    
    updateCoordinates(x, y) {
        if (typeof window.updateCoordinates === 'function') {
            window.updateCoordinates(x, y);
        } else {
            console.warn('Global updateCoordinates function not found');
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
            if (instance.interact) {
                instance.interact.unset();
            }
            
            if (instance.container && instance.clickHandler) {
                instance.container.removeEventListener('click', instance.clickHandler);
            }
            
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

export { panzoom, interact };