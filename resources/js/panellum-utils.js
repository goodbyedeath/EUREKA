/**
 * Panellum 360° Panoramic Image Utilities
 * Lightweight and performant panoramic viewer
 * Documentation: https://pannellum.org/documentation/overview/
 */

// Import Panellum as a UMD module
import 'pannellum/build/pannellum.js';
import 'pannellum/src/css/pannellum.css';

class PanellumManager {
    constructor() {
        this.viewer = null;
        this.config = null;
        this.hotspots = new Map();
        this.isDestroyed = false;
        this.containerId = null;
        
        // Bind methods to maintain context
        this.handleResize = this.handleResize.bind(this);
    }

    /**
     * Initialize Panellum viewer for equirectangular panoramas
     */
    initialize(containerId, imageUrl, options = {}) {
        // Input validation
        if (!containerId || typeof containerId !== 'string') {
            throw new Error('Container ID must be a non-empty string');
        }
        if (!imageUrl || typeof imageUrl !== 'string') {
            throw new Error('Image URL must be a non-empty string');
        }
        
        const container = document.getElementById(containerId);
        if (!container) {
            throw new Error(`Container with ID "${containerId}" not found`);
        }

        // Clear any existing viewer
        this.destroy();
        this.isDestroyed = false;
        this.containerId = containerId;

        // Default Panellum configuration based on official documentation
        const defaultConfig = {
            type: 'equirectangular',
            panorama: imageUrl,
            autoLoad: true,
            autoRotate: 0,
            compass: false,
            showControls: true,
            showFullscreenCtrl: true,
            showZoomCtrl: true,
            keyboardZoom: true,
            mouseZoom: true,
            draggable: true,
            crossOrigin: 'anonymous',
            hfov: 90,
            pitch: 0,
            yaw: 0,
            minHfov: 50,
            maxHfov: 120,
            minPitch: -90,
            maxPitch: 90,
            backgroundColor: [0, 0, 0],
            hotSpots: []
        };

        // Merge with user options
        this.config = { ...defaultConfig, ...options };
        
        // Update panorama URL if provided in options
        if (options.panorama) {
            this.config.panorama = options.panorama;
        }

        try {
            console.log('Initializing Panellum with config:', this.config);
            
            // Initialize Panellum viewer using official API
            this.viewer = window.pannellum.viewer(containerId, this.config);
            
            console.log('✅ Panellum viewer initialized successfully');

            // Set up event listeners
            this.setupEventListeners();

            // Add resize listener
            window.addEventListener('resize', this.handleResize);

            return this.viewer;
        } catch (error) {
            console.error('Failed to initialize Panellum:', error);
            throw error;
        }
    }

    /**
     * Setup event listeners based on official API
     */
    setupEventListeners() {
        if (!this.viewer || this.isDestroyed) return;

        try {
            // Load event
            this.viewer.on('load', () => {
                console.log('✅ Panellum panorama loaded successfully');
            });

            // Error event
            this.viewer.on('error', (err) => {
                console.error('❌ Panellum error:', err);
            });

            // Zoom change event
            this.viewer.on('zoomchange', (hfov) => {
                console.log('🔍 Zoom changed to hfov:', hfov);
            });

            // Animation finished event
            this.viewer.on('animatefinished', () => {
                console.log('✨ Animation finished');
            });

        } catch (error) {
            console.warn('Failed to setup event listeners:', error);
        }
    }

    /**
     * Add hotspot to the panorama using official Panellum API with admin-precision coordinate detection
     */
    addHotspot(config) {
        if (!this.viewer || this.isDestroyed) {
            console.error('Viewer not initialized or destroyed');
            return null;
        }

        const {
            id = 'hotspot-' + Date.now(),
            pitch = 0,
            yaw = 0,
            type = 'info',
            text = 'Hotspot',
            URL = null,
            createTooltipFunc = null,
            createTooltipArgs = null,
            clickHandlerFunc = null,
            clickHandlerArgs = null,
            cssClass = 'custom-hotspot',
            hotspot_type = 'info',
            raw_pitch = null,
            raw_yaw = null
        } = config;

        // ADMIN-PRECISION coordinate conversion if raw values provided
        let finalPitch = pitch;
        let finalYaw = yaw;
        
        if (raw_pitch !== null && raw_yaw !== null) {
            const converted = this.convertCoordinatesFromDatabase(raw_pitch, raw_yaw);
            finalPitch = converted.pitch;
            finalYaw = converted.yaw;
            console.log(`📍 Coordinate conversion for hotspot ${id}:`, {
                raw: { pitch: raw_pitch, yaw: raw_yaw },
                converted: { pitch: finalPitch, yaw: finalYaw },
                system: converted.system
            });
        }

        try {
            // Official Panellum hotspot configuration using standard classes and admin-precision coordinates
            const hotspotConfig = {
                pitch: finalPitch,
                yaw: finalYaw,
                type: type,
                text: text
                // DON'T specify cssClass - let Pannellum use standard classes (pnlm-info, pnlm-scene)
            };

            // Add optional properties based on official API
            if (URL) hotspotConfig.URL = URL;
            if (createTooltipFunc) hotspotConfig.createTooltipFunc = createTooltipFunc;
            if (createTooltipArgs) hotspotConfig.createTooltipArgs = createTooltipArgs;
            if (clickHandlerFunc) hotspotConfig.clickHandlerFunc = clickHandlerFunc;
            if (clickHandlerArgs) hotspotConfig.clickHandlerArgs = clickHandlerArgs;

            // Add hotspot using official API method
            this.viewer.addHotSpot(hotspotConfig, id);

            // Store hotspot reference
            this.hotspots.set(id, {
                config: hotspotConfig,
                originalConfig: config
            });

            console.log(`✅ Hotspot "${id}" added successfully`);
            return id;
        } catch (error) {
            console.error('Failed to add hotspot:', error);
            return null;
        }
    }

    /**
     * Remove a specific hotspot
     */
    removeHotspot(id) {
        if (!this.viewer || this.isDestroyed) return;

        try {
            this.viewer.removeHotSpot(id);
            this.hotspots.delete(id);
            console.log(`✅ Hotspot "${id}" removed`);
        } catch (error) {
            console.error(`Failed to remove hotspot "${id}":`, error);
        }
    }

    /**
     * Clear all hotspots
     */
    clearHotspots() {
        if (!this.viewer || this.isDestroyed) return;

        try {
            this.hotspots.forEach((hotspotData, id) => {
                this.viewer.removeHotSpot(id);
            });
            this.hotspots.clear();
            console.log('✅ All hotspots cleared');
        } catch (error) {
            console.error('Failed to clear hotspots:', error);
        }
    }

    /**
     * Animate to a specific position using official API
     */
    lookAt(pitch, yaw, hfov = null, animationTime = 1000) {
        if (!this.viewer || this.isDestroyed) return;

        try {
            // Use official Panellum API methods
            if (hfov !== null) {
                this.viewer.lookAt(pitch, yaw, hfov, animationTime);
            } else {
                this.viewer.lookAt(pitch, yaw, undefined, animationTime);
            }
            console.log(`✅ Looking at pitch: ${pitch}, yaw: ${yaw}, hfov: ${hfov}`);
        } catch (error) {
            console.error('Failed to look at position:', error);
        }
    }

    /**
     * Get current view parameters using official API
     */
    getViewParams() {
        if (!this.viewer || this.isDestroyed) return null;

        try {
            return {
                pitch: this.viewer.getPitch(),
                yaw: this.viewer.getYaw(),
                hfov: this.viewer.getHfov()
            };
        } catch (error) {
            console.error('Failed to get view parameters:', error);
            return null;
        }
    }

    /**
     * Set configuration options using official API methods
     */
    setConfig(newConfig) {
        if (!this.viewer || this.isDestroyed) return;

        try {
            Object.keys(newConfig).forEach(key => {
                this.config[key] = newConfig[key];
            });
            
            // Apply config changes using official Panellum API methods
            if (newConfig.hfov !== undefined) this.viewer.setHfov(newConfig.hfov);
            if (newConfig.pitch !== undefined) this.viewer.setPitch(newConfig.pitch);
            if (newConfig.yaw !== undefined) this.viewer.setYaw(newConfig.yaw);
            
            console.log('✅ Configuration updated:', newConfig);
        } catch (error) {
            console.error('Failed to update configuration:', error);
        }
    }

    /**
     * Handle window resize
     */
    handleResize() {
        if (this.viewer && !this.isDestroyed) {
            try {
                this.viewer.resize();
                console.log('✅ Viewer resized');
            } catch (error) {
                console.error('Failed to resize viewer:', error);
            }
        }
    }

    /**
     * Manually trigger resize
     */
    resize() {
        this.handleResize();
    }

    /**
     * Check if viewer is loaded
     */
    isLoaded() {
        return this.viewer && this.viewer.isLoaded && this.viewer.isLoaded();
    }

    /**
     * Get the underlying Panellum viewer instance
     */
    getViewer() {
        return this.viewer;
    }

    /**
     * ADMIN-PRECISION coordinate conversion from database values (EXACT copy from admin logic)
     */
    convertCoordinatesFromDatabase(rawPitch, rawYaw) {
        let pitchDegrees, yawDegrees, coordinateSystem;
        
        // Parse raw values
        const pitch = parseFloat(rawPitch);
        const yaw = parseFloat(rawYaw);
        
        // EXACT admin detection logic - Check if values are in degree range (outside radian limits)
        if (Math.abs(pitch) > 1.571 || Math.abs(yaw) > 3.142) {
            // Values are likely in degrees already
            pitchDegrees = pitch;
            yawDegrees = yaw;
            coordinateSystem = 'degrees (legacy)';
        } else {
            // Values are likely in radians - convert to degrees normally
            pitchDegrees = pitch * (180 / Math.PI);
            yawDegrees = yaw * (180 / Math.PI);
            coordinateSystem = 'radians (converted)';
        }
        
        // ADMIN validation - Validate final values are reasonable for panorama display
        if (Math.abs(pitchDegrees) > 90 || Math.abs(yawDegrees) > 180) {
            console.warn('⚠️ Invalid coordinates after conversion - using defaults');
            pitchDegrees = 0;
            yawDegrees = 0;
            coordinateSystem = 'defaulted (invalid)';
        }
        
        return {
            pitch: pitchDegrees,
            yaw: yawDegrees,
            system: coordinateSystem
        };
    }

    /**
     * Get precise click coordinates using admin-precision mouseEventToCoords
     */
    getPreciseClickCoordinates(event) {
        if (!this.viewer || this.isDestroyed) return null;
        
        try {
            // ADMIN-PRECISION coordinate detection using mouseEventToCoords
            const coords = this.viewer.mouseEventToCoords(event);
            if (coords && coords.length >= 2 && !isNaN(coords[0]) && !isNaN(coords[1])) {
                const pitch = parseFloat(coords[0]);
                const yaw = parseFloat(coords[1]);
                
                // Validate coordinates are in reasonable ranges
                if (pitch >= -90 && pitch <= 90 && yaw >= -180 && yaw <= 360) {
                    return {
                        pitch: pitch,
                        yaw: yaw,
                        source: 'mouseEventToCoords (ADMIN-PRECISION)'
                    };
                }
            }
        } catch (error) {
            console.log('⚠️ mouseEventToCoords failed, using fallback:', error.message);
        }
        
        // Fallback to current view center
        try {
            const pitch = this.viewer.getPitch();
            const yaw = this.viewer.getYaw();
            return {
                pitch: pitch,
                yaw: yaw,
                source: 'View center (FALLBACK)'
            };
        } catch (error) {
            console.error('Failed to get fallback coordinates:', error);
            return null;
        }
    }

    /**
     * Add click listener for coordinate detection (like admin side)
     */
    enableClickCoordinateDetection(callback = null) {
        if (!this.containerId) return;
        
        const container = document.getElementById(this.containerId);
        if (!container) return;
        
        console.log('📍 Adding click listener for coordinate detection');
        container.addEventListener('click', (e) => {
            console.log('🎯 Panorama clicked - detecting coordinates...');
            
            const coords = this.getPreciseClickCoordinates(e);
            if (coords) {
                console.log('📍 PRECISE click coordinates:');
                console.log('  - Pitch (vertical):', coords.pitch.toFixed(5) + '°');
                console.log('  - Yaw (horizontal):', coords.yaw.toFixed(5) + '°');
                console.log('  - Coordinate source:', coords.source);
                
                // Call callback if provided
                if (callback && typeof callback === 'function') {
                    callback(coords);
                }
                
                // Show coordinates visually
                this.showCoordinates(coords.pitch, coords.yaw);
            }
        });
    }

    /**
     * Show coordinates on screen (like admin side)
     */
    showCoordinates(pitch, yaw) {
        // Create coordinate display element
        let coordDisplay = document.getElementById('panellum-coord-display');
        if (!coordDisplay) {
            coordDisplay = document.createElement('div');
            coordDisplay.id = 'panellum-coord-display';
            coordDisplay.style.cssText = 'position: fixed; top: 80px; left: 20px; z-index: 1000; pointer-events: none;';
            document.body.appendChild(coordDisplay);
        }
        
        coordDisplay.innerHTML = `
            <div style="background: rgba(0,0,0,0.8); color: white; padding: 12px; border-radius: 8px; font-family: monospace; font-size: 12px;">
                <div style="font-weight: bold; color: #60a5fa; margin-bottom: 4px;">📍 Click Coordinates</div>
                <div>Pitch: ${pitch.toFixed(2)}°</div>
                <div>Yaw: ${yaw.toFixed(2)}°</div>
                <div style="font-size: 10px; color: #9ca3af; margin-top: 4px;">Admin-precision detection</div>
            </div>
        `;
        
        // Auto-hide after 4 seconds
        setTimeout(() => {
            if (coordDisplay && coordDisplay.parentNode) {
                coordDisplay.innerHTML = '';
            }
        }, 4000);
    }

    /**
     * Load a new panorama
     */
    loadScene(imageUrl, config = {}) {
        if (!this.viewer || this.isDestroyed) {
            console.error('Viewer not initialized');
            return;
        }

        try {
            const sceneConfig = {
                type: 'equirectangular',
                panorama: imageUrl,
                ...config
            };

            this.viewer.loadScene(sceneConfig);
            console.log('✅ New scene loaded:', imageUrl);
        } catch (error) {
            console.error('Failed to load new scene:', error);
        }
    }

    /**
     * Toggle fullscreen mode
     */
    toggleFullscreen() {
        if (!this.viewer || this.isDestroyed) return;

        try {
            this.viewer.toggleFullscreen();
            console.log('✅ Fullscreen toggled');
        } catch (error) {
            console.error('Failed to toggle fullscreen:', error);
        }
    }

    /**
     * Proper cleanup using official API
     */
    destroy() {
        if (this.isDestroyed) return;

        this.isDestroyed = true;

        try {
            // Clear all hotspots
            this.clearHotspots();

            // Remove event listeners
            window.removeEventListener('resize', this.handleResize);

            // Destroy viewer using official API
            if (this.viewer) {
                // Panellum doesn't have a destroy method, just clear the container
                if (this.containerId) {
                    const container = document.getElementById(this.containerId);
                    if (container) {
                        container.innerHTML = '';
                    }
                }
                this.viewer = null;
            }

            // Clear references
            this.config = null;
            this.containerId = null;
            this.hotspots.clear();

            console.log('✅ PanellumManager destroyed');
        } catch (error) {
            console.error('Error during cleanup:', error);
        }
    }
}

// CSS enhancements for standard Pannellum hotspots with database coordinate support
const css = `
/* Standard Pannellum hotspot styling - using official classes with sprite icons */
.pnlm-hotspot-base {
    position: absolute !important;
    visibility: hidden !important;
    cursor: default !important;
    vertical-align: middle !important;
    top: 0 !important;
    z-index: 1 !important;
}

.pnlm-hotspot {
    height: 26px !important;
    width: 26px !important;
    border-radius: 13px !important;
    cursor: pointer !important;
}

.pnlm-hotspot:hover {
    background-color: rgba(255,255,255,0.2) !important;
}

/* Standard Pannellum info hotspots - uses sprite background */
.pnlm-hotspot.pnlm-info {
    background-position: 0 -104px !important;
}

/* Standard Pannellum scene hotspots - uses sprite background */  
.pnlm-hotspot.pnlm-scene {
    background-position: 0 -130px !important;
}

/* Ensure sprite background is applied */
.pnlm-hotspot.pnlm-info,
.pnlm-hotspot.pnlm-scene {
    background-image: url('data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2226%22%20height%3D%22208%22%3E%0A%3Ccircle%20fill-opacity%3D%22.78%22%20cy%3D%22117%22%20cx%3D%2213%22%20r%3D%2211%22%20fill%3D%22%23fff%22%2F%3E%0A%3Ccircle%20fill-opacity%3D%22.78%22%20cy%3D%22143%22%20cx%3D%2213%22%20r%3D%2211%22%20fill%3D%22%23fff%22%2F%3E%0A%3Ccircle%20cy%3D%22169%22%20cx%3D%2213%22%20r%3D%227%22%20fill%3D%22none%22%20stroke%3D%22%23000%22%20stroke-width%3D%222%22%2F%3E%0A%3Ccircle%20cy%3D%22195%22%20cx%3D%2213%22%20r%3D%227%22%20fill%3D%22none%22%20stroke%3D%22%23000%22%20stroke-width%3D%222%22%2F%3E%0A%3Ccircle%20cx%3D%2213%22%20cy%3D%22195%22%20r%3D%222.5%22%2F%3E%0A%3Cpath%20d%3D%22m5%2083v6h2v-4h4v-2zm10%200v2h4v4h2v-6zm-5%205v6h6v-6zm-5%205v6h6v-2h-4v-4zm14%200v4h-4v2h6v-6z%22%2F%3E%0A%3Cpath%20d%3D%22m13%20110a7%207%200%200%200%20-7%207%207%207%200%200%200%207%207%207%207%200%200%200%207%20-7%207%207%200%200%200%20-7%20-7zm-1%203h2v2h-2zm0%203h2v5h-2z%22%2F%3E%0A%3Cpath%20d%3D%22m5%2057v6h2v-4h4v-2zm10%200v2h4v4h2v-6zm-10%2010v6h6v-2h-4v-4zm14%200v4h-4v2h6v-6z%22%2F%3E%0A%3Cpath%20d%3D%22m17%2038v2h-8v-2z%22%2F%3E%0A%3Cpath%20d%3D%22m12%209v3h-3v2h3v3h2v-3h3v-2h-3v-3z%22%2F%3E%0A%3Cpath%20d%3D%22m13%20136-6.125%206.125h4.375v7.875h3.5v-7.875h4.375z%22%2F%3E%0A%3Cpath%20d%3D%22m10.428%20173.33v-5.77l5-2.89v5.77zm1-1.73%203-1.73-3.001-1.74z%22%2F%3E%0A%3C%2Fsvg%3E%0A') !important;
    background-repeat: no-repeat !important;
}

/* Panellum container enhancements */
.pannellum-container {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    width: 100%;
    height: 100%;
}

/* Loading overlay */
.pannellum-loading {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    border-radius: 8px;
}

.pannellum-loading-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #007bff;
    border-radius: 50%;
    animation: spinner-spin 1s linear infinite;
}

@keyframes spinner-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Dark mode support */
.dark .pannellum-loading {
    background: rgba(31, 41, 55, 0.9);
}

/* Enhanced controls integration */
.pannellum-controls {
    position: absolute;
    bottom: 20px;
    right: 20px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    z-index: 1000;
}

.pannellum-controls button {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.pannellum-controls button:hover {
    background: rgba(255, 255, 255, 1);
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.dark .pannellum-controls button {
    background: rgba(31, 41, 55, 0.9);
    border-color: rgba(255, 255, 255, 0.1);
    color: white;
}

.dark .pannellum-controls button:hover {
    background: rgba(31, 41, 55, 1);
}
`;

// Inject CSS
if (typeof document !== 'undefined') {
    const style = document.createElement('style');
    style.textContent = css;
    document.head.appendChild(style);
}

// Make PanellumManager available globally (pannellum is already global)
window.PanellumManager = PanellumManager;

export default PanellumManager;