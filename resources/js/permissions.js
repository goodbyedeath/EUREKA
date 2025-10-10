// Global permission handler for location and camera access
window.PermissionManager = {
    // Track permission status
    permissions: {
        location: null,
        camera: null
    },

    // Initialize permission requests
    async init() {
        // Only request permissions if we're in a secure context (HTTPS or localhost)
        if (!this.isSecureContext()) {
            console.warn('Permissions require HTTPS or localhost');
            return;
        }

        // Request permissions with a small delay to avoid blocking page load
        setTimeout(() => {
            this.requestAllPermissions();
        }, 1000);
    },

    // Check if we're in a secure context
    isSecureContext() {
        return window.isSecureContext || location.protocol === 'https:' || location.hostname === 'localhost';
    },

    // Request all permissions
    async requestAllPermissions() {
        await Promise.all([
            this.requestLocationPermission(),
            this.requestCameraPermission()
        ]);
    },

    // Request location permission
    async requestLocationPermission() {
        try {
            // Check if geolocation is supported
            if (!navigator.geolocation) {
                console.warn('Geolocation not supported');
                return false;
            }

            return new Promise((resolve) => {
                // Request current position to trigger permission prompt
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        this.permissions.location = 'granted';
                        console.log('Location permission granted');
                        resolve(true);
                    },
                    (error) => {
                        this.permissions.location = 'denied';
                        switch (error.code) {
                            case error.PERMISSION_DENIED:
                                console.warn('Location permission denied by user');
                                break;
                            case error.POSITION_UNAVAILABLE:
                                console.warn('Location information unavailable');
                                break;
                            case error.TIMEOUT:
                                console.warn('Location request timed out');
                                break;
                        }
                        resolve(false);
                    },
                    {
                        enableHighAccuracy: false,
                        timeout: 10000,
                        maximumAge: 300000 // 5 minutes
                    }
                );
            });
        } catch (error) {
            console.error('Error requesting location permission:', error);
            this.permissions.location = 'error';
            return false;
        }
    },

    // Request camera permission
    async requestCameraPermission() {
        try {
            // Check if mediaDevices is supported
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                console.warn('Camera access not supported');
                return false;
            }

            // Request camera access
            const stream = await navigator.mediaDevices.getUserMedia({ 
                video: { 
                    facingMode: 'environment' // Prefer back camera for QR scanning
                },
                audio: false 
            });

            this.permissions.camera = 'granted';
            console.log('Camera permission granted');

            // Stop the stream immediately after getting permission
            stream.getTracks().forEach(track => track.stop());
            
            return true;
        } catch (error) {
            this.permissions.camera = 'denied';
            if (error.name === 'NotAllowedError') {
                console.warn('Camera permission denied by user');
            } else if (error.name === 'NotFoundError') {
                console.warn('No camera found');
            } else if (error.name === 'NotSupportedError') {
                console.warn('Camera not supported');
            } else {
                console.warn('Camera permission error:', error.message);
            }
            return false;
        }
    },

    // Get current permission status
    getPermissionStatus(type) {
        return this.permissions[type] || 'unknown';
    },

    // Check if permission is granted
    isPermissionGranted(type) {
        return this.permissions[type] === 'granted';
    },

    // Re-request a specific permission
    async requestPermission(type) {
        switch (type) {
            case 'location':
                return await this.requestLocationPermission();
            case 'camera':
                return await this.requestCameraPermission();
            default:
                console.warn('Unknown permission type:', type);
                return false;
        }
    }
};

// Initialize permission manager when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.PermissionManager.init();
    });
} else {
    // DOM is already loaded
    window.PermissionManager.init();
}

// Also initialize after Livewire navigation
document.addEventListener('livewire:navigated', () => {
    // Small delay to avoid conflicts with page transitions
    setTimeout(() => {
        window.PermissionManager.init();
    }, 500);
});

// Export for global access
window.requestLocationPermission = () => window.PermissionManager.requestPermission('location');
window.requestCameraPermission = () => window.PermissionManager.requestPermission('camera');
window.getPermissionStatus = (type) => window.PermissionManager.getPermissionStatus(type);
window.isPermissionGranted = (type) => window.PermissionManager.isPermissionGranted(type);