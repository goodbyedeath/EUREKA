/**
 * Quest Location Dashboard JavaScript Module
 * Handles maps, location services, and UI interactions
 */

class QuestLocationDashboard {
    constructor() {
        this.watchId = null;
        this.locationMaps = {};
        this.fullMap = null;
        this.modalMap = null;
        this.routeMap = null;
        this.currentRoute = null;
        this.initialized = false;
        
        // Only auto-initialize if DOM is ready or loading
        if (document.readyState === 'loading') {
            this.init();
        } else {
            // DOM is already ready, initialize immediately
            this.setupEventListeners();
            this.requestLocation();
            // Use requestIdleCallback for better performance
            this.scheduleMapInitialization();
            this.initialized = true;
        }
    }

    init() {
        if (this.initialized) return;
        
        document.addEventListener('DOMContentLoaded', () => {
            this.requestLocation();
            // Use efficient scheduling for map initialization
            this.scheduleMapInitialization();
        });

        this.setupEventListeners();
        this.initialized = true;
    }

    setupEventListeners() {
        // Reinitialize maps when Livewire updates the page
        document.addEventListener('livewire:morph.updated', () => {
            // Use requestAnimationFrame for smoother updates
            requestAnimationFrame(() => {
                this.initializeMaps();
                this.initializeModalMap();
            });
        });

        // Watch for modal map when details modal opens
        this.setupModalObserver();
        
        // Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            if (this.watchId) {
                navigator.geolocation.clearWatch(this.watchId);
            }
        });
    }

    /**
     * Efficiently schedule map initialization using modern APIs
     */
    scheduleMapInitialization() {
        // Use requestIdleCallback if available, fallback to requestAnimationFrame
        if (typeof requestIdleCallback !== 'undefined') {
            requestIdleCallback(() => {
                this.initializeMaps();
            }, { timeout: 500 });
        } else {
            // Fallback for browsers without requestIdleCallback
            requestAnimationFrame(() => {
                this.initializeMaps();
            });
        }
    }

    /**
     * Initialize all mini maps on the page with performance optimization
     */
    initializeMaps() {
        const mapElements = document.querySelectorAll('[id^="map-"]');
        
        // Batch process maps to avoid blocking UI
        const batchSize = 2; // Process 2 maps at a time
        let index = 0;
        
        const processBatch = () => {
            const batch = Array.from(mapElements).slice(index, index + batchSize);
            
            batch.forEach(mapElement => {
                const lat = parseFloat(mapElement.dataset.lat);
                const lng = parseFloat(mapElement.dataset.lng);
                const name = mapElement.dataset.name;
                const mapId = mapElement.id;
                
                if (lat && lng && !this.locationMaps[mapId]) {
                    try {
                        this.locationMaps[mapId] = this.createMap(mapId, lng, lat, {
                            zoom: 14,
                            interactive: false
                        });
                        
                        this.addMarker(this.locationMaps[mapId], lng, lat);
                    } catch (error) {
                        console.warn('Error initializing map for', mapId, error);
                    }
                }
            });
            
            index += batchSize;
            
            // Continue processing if there are more maps
            if (index < mapElements.length) {
                requestAnimationFrame(processBatch);
            }
        };
        
        if (mapElements.length > 0) {
            requestAnimationFrame(processBatch);
        }
    }

    /**
     * Initialize modal map when details modal opens
     */
    initializeModalMap() {
        const modalMapElement = document.getElementById('modalMap');
        if (modalMapElement && !this.modalMap) {
            const lat = parseFloat(modalMapElement.dataset.lat);
            const lng = parseFloat(modalMapElement.dataset.lng);
            
            if (lat && lng) {
                try {
                    this.modalMap = this.createMap('modalMap', lng, lat, {
                        zoom: 15,
                        interactive: true
                    });
                    
                    this.addMarker(this.modalMap, lng, lat);
                    this.modalMap.addControl(new maplibregl.NavigationControl());
                } catch (error) {
                    console.warn('Error initializing modal map:', error);
                }
            }
        }
    }

    /**
     * Create a new MapLibre GL map instance
     */
    createMap(containerId, lng, lat, options = {}) {
        const defaultOptions = {
            container: containerId,
            style: {
                'version': 8,
                'sources': {
                    'osm': {
                        'type': 'raster',
                        'tiles': [
                            'https://a.tile.openstreetmap.org/{z}/{x}/{y}.png'
                        ],
                        'tileSize': 256,
                        'attribution': '© OpenStreetMap contributors'
                    }
                },
                'layers': [
                    {
                        'id': 'osm',
                        'type': 'raster',
                        'source': 'osm'
                    }
                ]
            },
            center: [lng, lat],
            zoom: 14,
            interactive: true
        };

        return new maplibregl.Map({ ...defaultOptions, ...options });
    }

    /**
     * Add a marker to a map
     */
    addMarker(map, lng, lat) {
        return new maplibregl.Marker()
            .setLngLat([lng, lat])
            .addTo(map);
    }

    /**
     * Open full screen map modal
     */
    openFullMap(lat, lng, title) {
        const modal = document.getElementById('fullMapModal');
        const titleElement = document.getElementById('fullMapTitle');
        
        modal.classList.remove('hidden');
        titleElement.textContent = title || 'Location Map';
        
        // Focus management for accessibility
        modal.focus();
        
        // Use requestAnimationFrame for better performance
        requestAnimationFrame(() => {
            if (this.fullMap) {
                this.fullMap.remove();
            }
            
            try {
                this.fullMap = this.createMap('fullMapContainer', lng, lat, {
                    zoom: 15,
                    interactive: true
                });
                
                this.addMarker(this.fullMap, lng, lat);
                this.fullMap.addControl(new maplibregl.NavigationControl());
            } catch (error) {
                console.warn('Error opening full map:', error);
            }
        }, 100);
    }

    /**
     * Close full screen map modal
     */
    closeFullMap() {
        const modal = document.getElementById('fullMapModal');
        modal.classList.add('hidden');
        
        if (this.fullMap) {
            this.fullMap.remove();
            this.fullMap = null;
        }
    }

    /**
     * Request user's current location
     */
    requestLocation() {
        if (!navigator.geolocation) {
            this.showLocationError('Geolocation is not supported by this browser.');
            return;
        }

        // Get current position with improved options
        navigator.geolocation.getCurrentPosition(
            position => {
                if (window.Livewire && window.Livewire.first()) {
                    window.Livewire.first().call('locationUpdated', 
                        position.coords.latitude, 
                        position.coords.longitude, 
                        position.coords.accuracy
                    );
                }
            },
            error => {
                this.handleLocationError(error);
            },
            {
                enableHighAccuracy: true,
                timeout: 15000,
                maximumAge: 300000
            }
        );

        // Watch position for updates with improved error handling
        this.watchId = navigator.geolocation.watchPosition(
            position => {
                if (window.Livewire && window.Livewire.first()) {
                    window.Livewire.first().call('locationUpdated', 
                        position.coords.latitude, 
                        position.coords.longitude, 
                        position.coords.accuracy
                    );
                }
            },
            error => {
                // Silently handle watch errors to avoid spam
                console.warn('Location watch error:', error);
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 60000
            }
        );
    }

    /**
     * Handle location errors with user-friendly messages
     */
    handleLocationError(error) {
        let message;
        switch (error.code) {
            case error.PERMISSION_DENIED:
                message = 'Location access was denied. Please enable location services and reload the page.';
                break;
            case error.POSITION_UNAVAILABLE:
                message = 'Location information is unavailable. Please check your GPS settings.';
                break;
            case error.TIMEOUT:
                message = 'Location request timed out. Please try again.';
                break;
            default:
                message = 'An unknown error occurred while retrieving your location.';
                break;
        }
        this.showLocationError(message);
    }

    /**
     * Show location error to user
     */
    showLocationError(message) {
        if (window.Livewire && window.Livewire.first()) {
            window.Livewire.first().call('showAlert', 'error', message);
        } else {
            alert(message);
        }
    }

    /**
     * Setup mutation observer for modal map
     */
    setupModalObserver() {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'childList') {
                    const modalMapElement = document.getElementById('modalMap');
                    if (modalMapElement && !this.modalMap) {
                        // Use requestAnimationFrame for better performance
                        requestAnimationFrame(() => this.initializeModalMap());
                    }
                }
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    /**
     * Clean up resources
     */
    destroy() {
        if (this.watchId) {
            navigator.geolocation.clearWatch(this.watchId);
            this.watchId = null;
        }

        // Clean up maps
        Object.values(this.locationMaps).forEach(map => {
            if (map) map.remove();
        });
        this.locationMaps = {};

        if (this.fullMap) {
            this.fullMap.remove();
            this.fullMap = null;
        }

        if (this.modalMap) {
            this.modalMap.remove();
            this.modalMap = null;
        }

        if (this.routeMap) {
            this.routeMap.remove();
            this.routeMap = null;
        }

        this.initialized = false;
    }

    /**
     * Show route on map
     */
    showRoute(routeData, destination) {
        const modal = document.getElementById('routeModal');
        if (!modal) {
            console.warn('Route modal not found');
            return;
        }

        modal.classList.remove('hidden');
        
        // Initialize route map
        requestAnimationFrame(() => {
            this.initializeRouteMap(routeData, destination);
        });
    }

    /**
     * Initialize route map with navigation
     */
    initializeRouteMap(routeData, destination) {
        const routeMapContainer = document.getElementById('routeMap');
        if (!routeMapContainer) return;

        // Remove existing route map
        if (this.routeMap) {
            this.routeMap.remove();
        }

        try {
            // Calculate bounds for the route
            const coordinates = routeData.coordinates || [];
            const bounds = this.calculateBounds(coordinates);

            this.routeMap = new maplibregl.Map({
                container: 'routeMap',
                style: {
                    'version': 8,
                    'sources': {
                        'osm': {
                            'type': 'raster',
                            'tiles': ['https://a.tile.openstreetmap.org/{z}/{x}/{y}.png'],
                            'tileSize': 256,
                            'attribution': '© OpenStreetMap contributors'
                        }
                    },
                    'layers': [{
                        'id': 'osm',
                        'type': 'raster',
                        'source': 'osm'
                    }]
                },
                center: bounds.center,
                zoom: 14
            });

            this.routeMap.on('load', () => {
                this.addRouteToMap(routeData, destination);
                
                // Fit map to route bounds
                if (coordinates.length > 1) {
                    this.routeMap.fitBounds([bounds.sw, bounds.ne], {
                        padding: 50
                    });
                }
            });

            this.routeMap.addControl(new maplibregl.NavigationControl());

        } catch (error) {
            console.warn('Error initializing route map:', error);
        }
    }

    /**
     * Add route line and markers to map
     */
    addRouteToMap(routeData, destination) {
        const coordinates = routeData.coordinates || [];
        
        if (coordinates.length < 2) return;

        // Add route line
        this.routeMap.addSource('route', {
            'type': 'geojson',
            'data': {
                'type': 'Feature',
                'properties': {},
                'geometry': {
                    'type': 'LineString',
                    'coordinates': coordinates
                }
            }
        });

        this.routeMap.addLayer({
            'id': 'route',
            'type': 'line',
            'source': 'route',
            'layout': {
                'line-join': 'round',
                'line-cap': 'round'
            },
            'paint': {
                'line-color': '#3b82f6',
                'line-width': 4,
                'line-opacity': 0.8
            }
        });

        // Add start marker (current location)
        const startCoord = coordinates[0];
        new maplibregl.Marker({ color: '#10b981' })
            .setLngLat(startCoord)
            .setPopup(new maplibregl.Popup().setHTML('<strong>Your Location</strong>'))
            .addTo(this.routeMap);

        // Add destination marker
        const endCoord = coordinates[coordinates.length - 1];
        new maplibregl.Marker({ color: '#ef4444' })
            .setLngLat(endCoord)
            .setPopup(new maplibregl.Popup().setHTML(`<strong>${destination.name}</strong>`))
            .addTo(this.routeMap);
    }

    /**
     * Calculate bounds for route coordinates
     */
    calculateBounds(coordinates) {
        if (coordinates.length === 0) {
            return { center: [0, 0], sw: [0, 0], ne: [0, 0] };
        }

        let minLng = coordinates[0][0], maxLng = coordinates[0][0];
        let minLat = coordinates[0][1], maxLat = coordinates[0][1];

        coordinates.forEach(coord => {
            minLng = Math.min(minLng, coord[0]);
            maxLng = Math.max(maxLng, coord[0]);
            minLat = Math.min(minLat, coord[1]);
            maxLat = Math.max(maxLat, coord[1]);
        });

        return {
            center: [(minLng + maxLng) / 2, (minLat + maxLat) / 2],
            sw: [minLng, minLat],
            ne: [maxLng, maxLat]
        };
    }

    /**
     * Close route modal
     */
    closeRouteModal() {
        const modal = document.getElementById('routeModal');
        if (modal) {
            modal.classList.add('hidden');
        }
        
        if (this.routeMap) {
            this.routeMap.remove();
            this.routeMap = null;
        }
    }
}

// Simple global functions that work immediately
window.requestLocation = function() {
    if (!navigator.geolocation) {
        alert('Geolocation is not supported by this browser.');
        return;
    }

    navigator.geolocation.getCurrentPosition(
        position => {
            if (window.Livewire && window.Livewire.first()) {
                window.Livewire.first().call('locationUpdated', 
                    position.coords.latitude, 
                    position.coords.longitude, 
                    position.coords.accuracy
                );
            }
        },
        error => {
            let message;
            switch (error.code) {
                case error.PERMISSION_DENIED:
                    message = 'Location access was denied. Please enable location services and reload the page.';
                    break;
                case error.POSITION_UNAVAILABLE:
                    message = 'Location information is unavailable. Please check your GPS settings.';
                    break;
                case error.TIMEOUT:
                    message = 'Location request timed out. Please try again.';
                    break;
                default:
                    message = 'Unable to get your location. Please check your GPS settings.';
                    break;
            }
            alert(message);
        },
        {
            enableHighAccuracy: true,
            timeout: 15000,
            maximumAge: 300000
        }
    );
};

window.openFullMap = function(lat, lng, title) {
    const modal = document.getElementById('fullMapModal');
    const titleElement = document.getElementById('fullMapTitle');
    
    if (!modal || !titleElement) {
        console.warn('Full map modal elements not found');
        return;
    }
    
    modal.classList.remove('hidden');
    titleElement.textContent = title || 'Location Map';
    
    // Use requestAnimationFrame for smoother rendering
    requestAnimationFrame(() => {
        try {
            // Remove existing map
            if (window.fullMapInstance) {
                window.fullMapInstance.remove();
            }
            
            // Create new map
            window.fullMapInstance = new maplibregl.Map({
                container: 'fullMapContainer',
                style: {
                    'version': 8,
                    'sources': {
                        'osm': {
                            'type': 'raster',
                            'tiles': [
                                'https://a.tile.openstreetmap.org/{z}/{x}/{y}.png'
                            ],
                            'tileSize': 256,
                            'attribution': '© OpenStreetMap contributors'
                        }
                    },
                    'layers': [
                        {
                            'id': 'osm',
                            'type': 'raster',
                            'source': 'osm'
                        }
                    ]
                },
                center: [lng, lat],
                zoom: 15
            });
            
            new maplibregl.Marker()
                .setLngLat([lng, lat])
                .addTo(window.fullMapInstance);
                
            window.fullMapInstance.addControl(new maplibregl.NavigationControl());
        } catch (error) {
            console.warn('Error opening full map:', error);
        }
    }, 100);
};

window.closeFullMap = function() {
    const modal = document.getElementById('fullMapModal');
    if (modal) {
        modal.classList.add('hidden');
    }
    
    if (window.fullMapInstance) {
        window.fullMapInstance.remove();
        window.fullMapInstance = null;
    }
};

// Initialize the dashboard class for other functionality
let questDashboardInstance = null;

// Function to get or create dashboard instance
window.getQuestDashboard = function() {
    if (!questDashboardInstance) {
        questDashboardInstance = new QuestLocationDashboard();
    }
    return questDashboardInstance;
};

// Global route functions
window.showRoute = function(routeData, destination) {
    const dashboard = window.getQuestDashboard();
    dashboard.showRoute(routeData, destination);
};

window.closeRouteModal = function() {
    const dashboard = window.getQuestDashboard();
    dashboard.closeRouteModal();
};

// Create instance when DOM is ready for map initialization
document.addEventListener('DOMContentLoaded', () => {
    window.getQuestDashboard();
});

// Also available for direct access
window.questDashboard = questDashboardInstance;