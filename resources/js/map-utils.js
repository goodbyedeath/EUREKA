// Map utilities for admin map management functionality
// This provides the basic MapLibre integration needed for quest location management

// Global function to load MapLibre GL JS dynamically
window.loadMapUtils = async function() {
    if (window.maplibregl) {
        return Promise.resolve();
    }

    return new Promise((resolve, reject) => {
        // Load MapLibre GL JS CSS
        const cssLink = document.createElement('link');
        cssLink.rel = 'stylesheet';
        cssLink.href = 'https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.css';
        cssLink.onload = () => {
            // Load MapLibre GL JS
            const script = document.createElement('script');
            script.src = 'https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.js';
            script.onload = () => {
                if (window.maplibregl) {
                    console.log('MapLibre GL JS loaded successfully');
                    resolve();
                } else {
                    reject(new Error('MapLibre GL JS failed to load'));
                }
            };
            script.onerror = () => reject(new Error('Failed to load MapLibre GL JS script'));
            document.head.appendChild(script);
        };
        cssLink.onerror = () => reject(new Error('Failed to load MapLibre GL CSS'));
        document.head.appendChild(cssLink);
    });
};

// Initialize map utilities when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Pre-load MapLibre for admin pages that need maps
    if (window.location.pathname.includes('/admin/')) {
        window.loadMapUtils().catch(error => {
            console.warn('MapLibre pre-load failed:', error);
        });
    }
});