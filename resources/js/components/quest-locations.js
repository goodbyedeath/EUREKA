/**
 * Quest Locations Alpine.js Component
 * Handles GPS tracking, check-in, route finding without Livewire
 */

export default function (Alpine) {
    Alpine.data('questLocations', () => ({
        // User GPS location
        userLatitude: null,
        userLongitude: null,
        locationAccuracy: null,
        locationPermissionGranted: false,

        // UI state
        loading: false,
        checkingIn: false,
        currentCheckInId: null,

        // Search and filters
        search: '',
        filterStatus: '',
        sortBy: 'name',

        // Locations data
        locations: [],
        pagination: {
            current_page: 1,
            last_page: 1,
            per_page: 12,
            total: 0
        },
        totalPoints: 0,

        // Modals
        showDetailsModal: false,
        selectedLocation: null,
        showFullscreenModal: false,
        showRouteModal: false,
        routeData: null,

        // GPS tracking
        watchId: null,
        locationRefreshInterval: null,

        // Messages
        successMessage: '',
        errorMessage: '',

        init() {
            console.log('Quest Locations Alpine component initialized');
            this.startLocationTracking();
            this.loadLocations();
        },

        /**
         * Start GPS location tracking
         */
        startLocationTracking() {
            if (!navigator.geolocation) {
                this.showError('Geolocation is not supported by this browser.');
                return;
            }

            // Get initial location
            this.requestLocation();

            // Set up periodic location updates (every 15 seconds)
            this.locationRefreshInterval = setInterval(() => {
                this.requestLocationSilently();
            }, 15000);

            console.log('GPS tracking started (updates every 15 seconds)');
        },

        /**
         * Request user's current location (with notification)
         */
        requestLocation() {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    this.updateLocation(position.coords, true);
                },
                (error) => {
                    this.handleLocationError(error);
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        },

        /**
         * Request location silently (no notification)
         */
        requestLocationSilently() {
            // Don't update location while checking in
            if (this.checkingIn) return;

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    this.updateLocation(position.coords, false);
                },
                (error) => {
                    // Silently fail for permission issues
                    if (error.code !== error.PERMISSION_DENIED) {
                        console.warn('Silent location update failed:', error);
                    }
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        },

        /**
         * Update location coordinates
         */
        updateLocation(coords, showNotification = false) {
            this.userLatitude = coords.latitude;
            this.userLongitude = coords.longitude;
            this.locationAccuracy = coords.accuracy;
            this.locationPermissionGranted = true;

            // Send to server for tracking
            fetch('/api/quest-locations/update-location', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    latitude: coords.latitude,
                    longitude: coords.longitude,
                    accuracy: coords.accuracy
                })
            }).catch(error => console.error('Failed to update location on server:', error));

            if (showNotification) {
                this.showSuccess('Location updated successfully!');
            }

            // Reload locations with distance calculation
            this.loadLocations();
        },

        /**
         * Handle location errors
         */
        handleLocationError(error) {
            let message = '';
            switch (error.code) {
                case error.PERMISSION_DENIED:
                    message = 'Location access denied. Please enable GPS and refresh the page.';
                    break;
                case error.POSITION_UNAVAILABLE:
                    message = 'Location information is unavailable.';
                    break;
                case error.TIMEOUT:
                    message = 'Location request timed out. Trying again...';
                    break;
                default:
                    message = 'An unknown error occurred while retrieving location.';
            }
            this.showError(message);
        },

        /**
         * Load quest locations from API
         */
        async loadLocations(page = 1) {
            this.loading = true;

            try {
                const params = new URLSearchParams({
                    page: page,
                    search: this.search,
                    filter_status: this.filterStatus,
                    sort_by: this.sortBy,
                    user_latitude: this.userLatitude || '',
                    user_longitude: this.userLongitude || ''
                });

                const response = await fetch(`/api/quest-locations?${params}`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.locations = data.locations;
                    this.pagination = data.pagination;
                    this.totalPoints = data.total_points;
                }
            } catch (error) {
                console.error('Failed to load locations:', error);
                this.showError('Failed to load quest locations');
            } finally {
                this.loading = false;
            }
        },

        /**
         * Check in to a quest location
         */
        async checkIn(locationId) {
            if (!this.locationPermissionGranted || !this.userLatitude || !this.userLongitude) {
                this.showError('Location access required. Please enable GPS and refresh the page.');
                return;
            }

            this.checkingIn = true;
            this.currentCheckInId = locationId;

            try {
                const response = await fetch('/quest-locations/checkin', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        location_id: locationId,
                        user_latitude: this.userLatitude,
                        user_longitude: this.userLongitude,
                        accuracy: this.locationAccuracy
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.showSuccess(data.message);
                    this.loadLocations(this.pagination.current_page); // Reload current page
                    this.closeDetailsModal(); // Close modal on success
                } else {
                    this.showError(data.message);
                }
            } catch (error) {
                console.error('Check-in failed:', error);
                this.showError('Check-in failed. Please try again.');
            } finally {
                this.checkingIn = false;
                this.currentCheckInId = null;
            }
        },

        /**
         * Show location details modal
         */
        showLocationDetails(location) {
            this.selectedLocation = location;
            this.showDetailsModal = true;
        },

        /**
         * Close details modal
         */
        closeDetailsModal() {
            this.showDetailsModal = false;
            this.selectedLocation = null;
        },

        /**
         * Show fullscreen map
         */
        openFullscreenMap(location) {
            this.selectedLocation = location;
            this.showFullscreenModal = true;

            // Initialize map in next tick
            this.$nextTick(() => {
                this.initializeFullscreenMap(location);
            });
        },

        /**
         * Close fullscreen map
         */
        closeFullscreenMap() {
            this.showFullscreenModal = false;
            this.selectedLocation = null;
        },

        /**
         * Initialize fullscreen map with MapLibre
         */
        initializeFullscreenMap(location) {
            const container = document.getElementById(`fullscreen-map-${location.id}`);
            if (!container || !window.maplibregl) return;

            const map = new maplibregl.Map({
                container: container,
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
                    'layers': [{ 'id': 'osm', 'type': 'raster', 'source': 'osm' }]
                },
                center: [location.longitude, location.latitude],
                zoom: 16
            });

            new maplibregl.Marker({ color: '#3B82F6' })
                .setLngLat([location.longitude, location.latitude])
                .addTo(map);
        },

        /**
         * Find route to location
         */
        async findRoute(locationId) {
            if (!this.locationPermissionGranted) {
                this.showError('Location access required for route finding.');
                return;
            }

            this.loading = true;

            try {
                const response = await fetch('/api/quest-locations/get-route', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        location_id: locationId,
                        user_latitude: this.userLatitude,
                        user_longitude: this.userLongitude
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.routeData = data.route;
                    this.selectedLocation = this.locations.find(l => l.id === locationId);
                    this.showRouteModal = true;

                    // Initialize route map in next tick
                    this.$nextTick(() => {
                        this.initializeRouteMap(data.route, data.destination);
                    });
                } else {
                    this.showError(data.message);
                }
            } catch (error) {
                console.error('Route finding failed:', error);
                this.showError('Unable to find route. Please try again.');
            } finally {
                this.loading = false;
            }
        },

        /**
         * Initialize route map
         */
        initializeRouteMap(routeData, destination) {
            // This will be implemented with MapLibre to show the route
            console.log('Initialize route map:', routeData, destination);
        },

        /**
         * Close route modal
         */
        closeRouteModal() {
            this.showRouteModal = false;
            this.routeData = null;
        },

        /**
         * Update search and reload
         */
        updateSearch() {
            this.loadLocations(1); // Reset to page 1
        },

        /**
         * Update filter and reload
         */
        updateFilter() {
            this.loadLocations(1); // Reset to page 1
        },

        /**
         * Update sort and reload
         */
        updateSort() {
            this.loadLocations(1); // Reset to page 1
        },

        /**
         * Go to specific page
         */
        goToPage(page) {
            if (page >= 1 && page <= this.pagination.last_page) {
                this.loadLocations(page);
            }
        },

        /**
         * Show success message
         */
        showSuccess(message) {
            this.successMessage = message;
            this.errorMessage = '';
            setTimeout(() => {
                this.successMessage = '';
            }, 5000);
        },

        /**
         * Show error message
         */
        showError(message) {
            this.errorMessage = message;
            this.successMessage = '';
            setTimeout(() => {
                this.errorMessage = '';
            }, 5000);
        },

        /**
         * Format distance for display
         */
        formatDistance(meters) {
            if (!meters) return 'N/A';
            if (meters < 1000) {
                return Math.round(meters) + 'm';
            }
            return (meters / 1000).toFixed(2) + 'km';
        },

        /**
         * Get check-in button text
         */
        getCheckInText(location) {
            if (!this.locationPermissionGranted) {
                return 'Enable Location';
            }
            if (location.checked_in) {
                return 'Checked In ✓';
            }
            if (!location.within_radius) {
                return 'Move Closer to Check In';
            }
            if (location.max_check_ins_per_user && location.check_ins_count >= location.max_check_ins_per_user) {
                return 'Max Check-ins Reached';
            }
            return 'Check In';
        },

        /**
         * Check if can check in
         */
        canCheckIn(location) {
            return this.locationPermissionGranted &&
                   location.within_radius &&
                   !location.checked_in &&
                   (!location.max_check_ins_per_user || location.check_ins_count < location.max_check_ins_per_user);
        },

        /**
         * Cleanup on destroy
         */
        destroy() {
            if (this.watchId) {
                navigator.geolocation.clearWatch(this.watchId);
            }
            if (this.locationRefreshInterval) {
                clearInterval(this.locationRefreshInterval);
            }
        }
    }));
}
