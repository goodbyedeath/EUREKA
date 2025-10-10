/**
 * Quest Location Dashboard - MapLibre GeolocateControl Version
 * Uses MapLibre's built-in geolocation control for better UX
 */

// Global variables for map management
let locationMaps = {};
let fullMapInstance = null;
let modalMapInstance = null;
let geolocateControl = null;

// Auto-initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    initializeGeolocateControl();
    initializeMaps();
});

// Reinitialize maps when Livewire updates
document.addEventListener('livewire:morph.updated', function() {
    setTimeout(() => {
        initializeMaps();
        initializeModalMap();
    }, 100);
});

/**
 * Initialize MapLibre GeolocateControl for location handling
 */
function initializeGeolocateControl() {
    // Create a hidden map just for the geolocate control
    const hiddenMapContainer = document.createElement('div');
    hiddenMapContainer.id = 'hidden-geolocation-map';
    hiddenMapContainer.style.position = 'absolute';
    hiddenMapContainer.style.top = '-9999px';
    hiddenMapContainer.style.width = '100px';
    hiddenMapContainer.style.height = '100px';
    document.body.appendChild(hiddenMapContainer);

    try {
        // Create a minimal map for geolocation
        const hiddenMap = createMap('hidden-geolocation-map', 0, 0, {
            zoom: 1,
            interactive: false
        });

        // Create GeolocateControl with options
        geolocateControl = new maplibregl.GeolocateControl({
            positionOptions: {
                enableHighAccuracy: true,
                timeout: 15000,
                maximumAge: 300000
            },
            trackUserLocation: true,
            showAccuracyCircle: false,
            showUserHeading: false
        });

        // Add control to hidden map
        hiddenMap.addControl(geolocateControl);

        // Set up event listeners for geolocation
        geolocateControl.on('geolocate', function(e) {
            console.log('Location obtained:', e.coords);
            
            // Update Livewire component
            if (window.Livewire && window.Livewire.first()) {
                window.Livewire.first().call('locationUpdated', 
                    e.coords.latitude, 
                    e.coords.longitude, 
                    e.coords.accuracy
                );
            }
        });

        geolocateControl.on('error', function(e) {
            console.warn('Geolocation error:', e);
            
            // Determine error type and update Livewire
            if (window.Livewire && window.Livewire.first()) {
                let errorType = 'denied';
                if (e.code === e.POSITION_UNAVAILABLE) {
                    errorType = 'unavailable';
                } else if (e.code === e.TIMEOUT) {
                    errorType = 'timeout';
                }
                window.Livewire.first().call('locationError', errorType);
            }
        });

        geolocateControl.on('trackuserlocationstart', function() {
            console.log('Started tracking user location');
            if (window.Livewire && window.Livewire.first()) {
                window.Livewire.first().set('locationRequestStatus', 'requesting');
            }
        });

        geolocateControl.on('trackuserlocationend', function() {
            console.log('Stopped tracking user location');
        });

        // Automatically trigger location request
        setTimeout(() => {
            geolocateControl.trigger();
        }, 1000);

    } catch (error) {
        console.warn('Error setting up geolocation control:', error);
        // Fallback to basic geolocation
        fallbackGeolocation();
    }
}

/**
 * Fallback geolocation if MapLibre control fails
 */
function fallbackGeolocation() {
    if (!navigator.geolocation) {
        console.warn('Geolocation not supported');
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
            if (window.Livewire && window.Livewire.first()) {
                let errorType = error.code === error.POSITION_UNAVAILABLE ? 'unavailable' : 'denied';
                window.Livewire.first().call('locationError', errorType);
            }
        },
        { enableHighAccuracy: true, timeout: 15000 }
    );
}

/**
 * Initialize all mini maps on the page
 */
function initializeMaps() {
    document.querySelectorAll('[id^="map-"]').forEach(mapElement => {
        const lat = parseFloat(mapElement.dataset.lat);
        const lng = parseFloat(mapElement.dataset.lng);
        const mapId = mapElement.id;
        
        if (lat && lng && !locationMaps[mapId]) {
            try {
                locationMaps[mapId] = createMap(mapId, lng, lat, {
                    zoom: 14,
                    interactive: false
                });
                
                new maplibregl.Marker()
                    .setLngLat([lng, lat])
                    .addTo(locationMaps[mapId]);
            } catch (error) {
                console.warn('Error initializing map:', mapId, error);
            }
        }
    });
}

/**
 * Initialize modal map when details modal opens
 */
function initializeModalMap() {
    const modalMapElement = document.getElementById('modalMap');
    if (modalMapElement) {
        // Clean up existing modal map
        if (modalMapInstance) {
            try {
                modalMapInstance.remove();
                modalMapInstance = null;
            } catch (error) {
                console.warn('Error cleaning up modal map:', error);
            }
        }
        
        const lat = parseFloat(modalMapElement.dataset.lat);
        const lng = parseFloat(modalMapElement.dataset.lng);
        
        if (lat && lng && !isNaN(lat) && !isNaN(lng)) {
            try {
                modalMapInstance = createMap('modalMap', lng, lat, {
                    zoom: 15,
                    interactive: true
                });
                
                new maplibregl.Marker()
                    .setLngLat([lng, lat])
                    .addTo(modalMapInstance);
                    
                modalMapInstance.addControl(new maplibregl.NavigationControl());
                
                // Add geolocate control to modal map for user convenience
                const modalGeolocate = new maplibregl.GeolocateControl({
                    positionOptions: {
                        enableHighAccuracy: true,
                        timeout: 15000
                    },
                    trackUserLocation: true,
                    showUserHeading: true
                });
                modalMapInstance.addControl(modalGeolocate);
            } catch (error) {
                console.warn('Error initializing modal map:', error);
                // Show fallback message in modal map container
                modalMapElement.innerHTML = '<div class="flex items-center justify-center h-full text-gray-500"><span>Map could not be loaded</span></div>';
            }
        }
    }
}

/**
 * Create a MapLibre GL map
 */
function createMap(containerId, lng, lat, options = {}) {
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
 * Open full screen map (called via onclick)
 */
window.openFullMap = function(lat, lng, title) {
    const modal = document.getElementById('fullMapModal');
    const titleElement = document.getElementById('fullMapTitle');
    
    if (!modal || !titleElement) {
        console.warn('Full map modal elements not found');
        return;
    }
    
    modal.classList.remove('hidden');
    titleElement.textContent = title || 'Location Map';
    
    setTimeout(() => {
        try {
            if (fullMapInstance) {
                fullMapInstance.remove();
            }
            
            fullMapInstance = createMap('fullMapContainer', lng, lat, {
                zoom: 15,
                interactive: true
            });
            
            new maplibregl.Marker()
                .setLngLat([lng, lat])
                .addTo(fullMapInstance);
                
            fullMapInstance.addControl(new maplibregl.NavigationControl());
        } catch (error) {
            console.warn('Error opening full map:', error);
        }
    }, 100);
};

/**
 * Close full screen map (called via onclick)
 */
window.closeFullMap = function() {
    const modal = document.getElementById('fullMapModal');
    if (modal) {
        modal.classList.add('hidden');
    }
    
    if (fullMapInstance) {
        fullMapInstance.remove();
        fullMapInstance = null;
    }
};

/**
 * Manual location request (called from retry buttons)
 */
window.requestLocation = function() {
    if (geolocateControl) {
        // Use MapLibre's GeolocateControl
        geolocateControl.trigger();
    } else {
        // Fallback to basic geolocation
        fallbackGeolocation();
    }
};

// Watch for modal map when details modal opens
let observerInitialized = false;

function setupModalObserver() {
    if (observerInitialized) return;
    
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.type === 'childList') {
                const modalMapElement = document.getElementById('modalMap');
                if (modalMapElement) {
                    // Delay to ensure modal is fully rendered
                    setTimeout(initializeModalMap, 200);
                }
            }
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    observerInitialized = true;
}

// Initialize observer
setupModalObserver();

// Cleanup on page unload
let watchId = null; // Declare watchId variable
window.addEventListener('beforeunload', () => {
    if (watchId) {
        navigator.geolocation.clearWatch(watchId);
    }
});