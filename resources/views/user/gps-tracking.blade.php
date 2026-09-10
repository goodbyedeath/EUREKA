@extends('layouts.appUser')

@section('title', 'My GPS Location')

@section('content')
<link href="/vendor/maplibre/4.7.1/maplibre-gl.css" rel="stylesheet" />
<script src="/vendor/maplibre/4.7.1/maplibre-gl.js"></script>

<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        background: #f5f5f5;
        overflow: hidden;
    }

    .gps-container {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        display: flex;
        flex-direction: column;
    }

    #map {
        flex: 1;
        width: 100%;
    }

    .header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 16px 24px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        z-index: 1000;
    }

    .header-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        max-width: 1400px;
        margin: 0 auto;
    }

    .header-left {
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .back-btn {
        background: rgba(255,255,255,0.2);
        backdrop-filter: blur(10px);
        padding: 10px 20px;
        border-radius: 10px;
        color: white;
        text-decoration: none;
        transition: all 0.3s;
        font-weight: 600;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .back-btn:hover {
        background: rgba(255,255,255,0.3);
        transform: translateX(-3px);
    }

    .header-title {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .header-title h1 {
        font-size: 22px;
        font-weight: 700;
        letter-spacing: -0.5px;
        margin: 0;
    }

    .header-title .icon {
        font-size: 28px;
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: 12px;
        background: rgba(255,255,255,0.2);
        backdrop-filter: blur(10px);
        padding: 8px 16px;
        border-radius: 10px;
    }

    .user-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 16px;
    }

    .user-name {
        font-weight: 600;
        font-size: 14px;
    }

    .location-panel {
        position: absolute;
        top: 90px;
        left: 20px;
        background: white;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.12);
        width: 340px;
        max-height: calc(100vh - 120px);
        overflow-y: auto;
        z-index: 999;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,0.8);
    }

    .location-panel::-webkit-scrollbar {
        width: 6px;
    }

    .location-panel::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }

    .location-panel::-webkit-scrollbar-thumb {
        background: #667eea;
        border-radius: 10px;
    }

    .panel-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 16px;
        border-bottom: 2px solid #f0f0f0;
    }

    .panel-header h3 {
        margin: 0;
        color: #1f2937;
        font-size: 18px;
        font-weight: 700;
    }

    .status-indicator {
        display: flex;
        align-items: center;
        gap: 8px;
        background: rgba(16, 185, 129, 0.1);
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        color: #10b981;
    }

    .status-dot {
        width: 8px;
        height: 8px;
        background: #10b981;
        border-radius: 50%;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.6; transform: scale(1.2); }
    }

    .location-item {
        background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
        padding: 14px 16px;
        margin-bottom: 12px;
        border-radius: 10px;
        transition: all 0.2s;
    }

    .location-item:hover {
        transform: translateX(3px);
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    .location-label {
        color: #6b7280;
        font-size: 13px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 6px;
    }

    .location-value {
        font-weight: 700;
        color: #1f2937;
        font-size: 16px;
        word-break: break-all;
    }

    .accuracy-indicator {
        margin-top: 6px;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
    }

    .accuracy-bar {
        flex: 1;
        height: 4px;
        background: #e5e7eb;
        border-radius: 2px;
        overflow: hidden;
    }

    .accuracy-fill {
        height: 100%;
        background: linear-gradient(90deg, #10b981, #3b82f6);
        transition: width 0.3s;
    }

    .center-btn {
        position: absolute;
        bottom: 24px;
        right: 24px;
        background: white;
        border: none;
        padding: 16px;
        border-radius: 50%;
        box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        cursor: pointer;
        transition: all 0.3s;
        z-index: 999;
    }

    .center-btn:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 20px rgba(0,0,0,0.2);
    }

    .center-btn i {
        color: #667eea;
        font-size: 24px;
    }

    .error-message {
        background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
        border-left: 4px solid #ef4444;
        padding: 16px;
        border-radius: 10px;
        color: #991b1b;
        font-size: 14px;
        line-height: 1.5;
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .header-content {
            flex-direction: column;
            gap: 12px;
        }

        .header-left {
            width: 100%;
            justify-content: space-between;
        }

        .header-title h1 {
            font-size: 18px;
        }

        .location-panel {
            top: 100px;
            left: 10px;
            right: 10px;
            width: auto;
            max-height: 40vh;
        }

        .center-btn {
            bottom: 16px;
            right: 16px;
            padding: 12px;
        }
    }

    .maplibregl-popup-content {
        border-radius: 12px;
        padding: 16px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    }

    .maplibregl-popup-content h4 {
        color: #1f2937;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .maplibregl-popup-content p {
        color: #6b7280;
        line-height: 1.5;
    }
</style>

<div class="gps-container">
    <div class="header">
        <div class="header-content">
            <div class="header-left">
                <a href="{{ route('user.dashboard') }}" class="back-btn">
                    <span>←</span> <span>Dashboard</span>
                </a>
                <div class="header-title">
                    <span class="icon">📍</span>
                    <h1>My GPS Location</h1>
                </div>
            </div>
            <div class="user-info">
                <div class="user-avatar">
                    {{ substr($user->name, 0, 1) }}
                </div>
                <span class="user-name">{{ $user->name }}</span>
            </div>
        </div>
    </div>

    <div id="map"></div>

    <button class="center-btn" onclick="centerOnMyLocation()" title="Center on my location">
        <i class="fas fa-crosshairs"></i>
    </button>
</div>

<script>
    const TRACKER_API_BASE = 'https://tracker.questerra-series.com/api/export';

    let map;
    let userMarker;
    let routeLayers = {};
    let routeMarkers = [];

    // Initialize map
    function initMap() {
        map = new maplibregl.Map({
            container: 'map',
            style: {
                version: 8,
                sources: {
                    'osm': {
                        type: 'raster',
                        tiles: window.EUREKA_MAP.tiles,
                        tileSize: 256,
                        attribution: window.EUREKA_MAP.attribution
                    }
                },
                layers: [
                    {
                        id: 'osm',
                        type: 'raster',
                        source: 'osm',
                        minzoom: 0,
                        maxzoom: 19
                    }
                ]
            },
            center: [106.8456, -6.2088], // Default: Jakarta
            zoom: 15
        });

        map.addControl(new maplibregl.NavigationControl(), 'top-right');

        map.on('load', function() {
            loadUserGpsData();
            startLiveTracking();
            startAutoRefresh();
        });
    }

    // Auto-refresh routes every 30 seconds
    function startAutoRefresh() {
        setInterval(() => {
            loadUserGpsData();
        }, 30000); // Refresh every 30 seconds
    }

    // Load GPS data from tracker API
    async function loadUserGpsData() {
        try {
            // Load map data (routes and markers)
            const mapDataResponse = await fetch(`${TRACKER_API_BASE}/map-data?include_active=true&include_completed=true&limit=10`);

            if (!mapDataResponse.ok) {
                throw new Error(`Server error: ${mapDataResponse.status}`);
            }

            const mapData = await mapDataResponse.json();

            if (mapData.success && mapData.data && mapData.data.length > 0) {
                displayUserRoutes(mapData.data);
            }
        } catch (error) {
            console.error('Error loading GPS data:', error);
            showNotification('Unable to load route data. Please try again later.', 'error');
        }
    }

    // Show notification
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 80px;
            right: 20px;
            background: ${type === 'error' ? '#ef4444' : '#3b82f6'};
            color: white;
            padding: 16px 24px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            z-index: 10000;
            font-size: 14px;
            max-width: 300px;
            animation: slideIn 0.3s ease-out;
        `;
        notification.textContent = message;
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.transition = 'opacity 0.3s';
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 300);
        }, 4000);
    }

    // Display user's routes on map
    function displayUserRoutes(sessions) {
        // Clear old markers
        routeMarkers.forEach(marker => marker.remove());
        routeMarkers = [];

        // Clear old route layers
        Object.keys(routeLayers).forEach(routeId => {
            if (map.getLayer(routeId)) {
                map.removeLayer(routeId);
            }
            if (map.getSource(routeId)) {
                map.removeSource(routeId);
            }
        });
        routeLayers = {};

        if (!sessions || sessions.length === 0) {
            return;
        }

        // Create bounds to fit all routes
        const allBounds = new maplibregl.LngLatBounds();
        let hasValidRoutes = false;

        sessions.forEach((session, index) => {
            if (!session.route_points || session.route_points.length < 2) {
                return;
            }

            hasValidRoutes = true;

            const routeId = `route-${session.session_id}`;
            const color = session.status === 'active' ? '#10b981' : '#667eea';

            // Add route line
            if (!map.getSource(routeId)) {
                map.addSource(routeId, {
                    type: 'geojson',
                    data: {
                        type: 'Feature',
                        properties: {},
                        geometry: {
                            type: 'LineString',
                            coordinates: session.route_points.map(p => [p.lng, p.lat])
                        }
                    }
                });

                map.addLayer({
                    id: routeId,
                    type: 'line',
                    source: routeId,
                    paint: {
                        'line-color': color,
                        'line-width': 4,
                        'line-opacity': 0.8
                    }
                });

                routeLayers[routeId] = true;
            }

            // Extend bounds to include this route
            session.route_points.forEach(point => {
                allBounds.extend([point.lng, point.lat]);
            });

            // Add markers along the route
            if (session.markers && session.markers.length > 0) {
                session.markers.forEach((marker, markerIndex) => {
                    const el = document.createElement('div');
                    el.innerHTML = marker.icon || '📍';
                    el.style.fontSize = '32px';
                    el.style.cursor = 'pointer';
                    el.style.textShadow = '2px 2px 4px rgba(0,0,0,0.5)';

                    const popup = new maplibregl.Popup({ offset: 25 })
                        .setHTML(`
                            <div style="padding: 10px;">
                                <h4 style="margin: 0 0 8px 0; color: #333; font-weight: bold;">${marker.title || 'Marker'}</h4>
                                ${marker.description ? `<p style="margin: 0; font-size: 13px; color: #666;">${marker.description}</p>` : ''}
                            </div>
                        `);

                    const markerLng = parseFloat(marker.lng);
                    const markerLat = parseFloat(marker.lat);

                    const markerObj = new maplibregl.Marker({ element: el, anchor: 'bottom' })
                        .setLngLat([markerLng, markerLat])
                        .setPopup(popup)
                        .addTo(map);

                    routeMarkers.push(markerObj);

                    // Extend bounds to include this marker
                    allBounds.extend([markerLng, markerLat]);
                });
            }
        });

        // Don't auto zoom - let user control the map view
        // if (hasValidRoutes) {
        //     map.fitBounds(allBounds, { padding: 80, maxZoom: 15 });
        // }
    }

    // Center map on my location
    function centerOnMyLocation() {
        if (userMarker) {
            const lngLat = userMarker.getLngLat();
            map.flyTo({
                center: [lngLat.lng, lngLat.lat],
                zoom: 16,
                duration: 1000
            });
        }
    }

    // Send live position to server for admin tracking
    async function sendLivePosition(latitude, longitude) {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (!csrfToken) return;

            await fetch('/api/live/position', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken.content
                },
                body: JSON.stringify({ latitude, longitude })
            });
        } catch (error) {
            console.error('Error sending position:', error);
        }
    }

    // Start continuous live tracking
    let lastPositionSentAt = 0;

    function startLiveTracking() {
        if (!navigator.geolocation) {
            console.error('Geolocation not supported');
            return;
        }

        navigator.geolocation.watchPosition(
            (position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;

                // Throttle the send, not the watch: keep the marker smooth on screen,
                // but tell the server at most every 10 seconds. watchPosition fires about
                // once a second while walking, and each one used to be a POST.
                const now = Date.now();
                if (now - lastPositionSentAt >= 10000) {
                    lastPositionSentAt = now;
                    sendLivePosition(lat, lng);
                }

                // Update or create live marker on the map
                if (userMarker) {
                    userMarker.setLngLat([lng, lat]);
                } else {
                    const el = document.createElement('div');
                    el.innerHTML = `
                        <div style="
                            width: 24px;
                            height: 24px;
                            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                            border: 3px solid white;
                            border-radius: 50%;
                            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.6);
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            color: white;
                            font-weight: bold;
                            font-size: 14px;
                        ">📍</div>
                    `;

                    userMarker = new maplibregl.Marker({ element: el })
                        .setLngLat([lng, lat])
                        .setPopup(new maplibregl.Popup({ offset: 25 }).setHTML(`
                            <div style="padding: 10px;">
                                <h4 style="margin: 0 0 8px 0; font-weight: bold; color: #10b981;">📍 Your Live Location</h4>
                                <p style="margin: 4px 0; font-size: 13px;"><strong>Lat:</strong> ${lat.toFixed(6)}</p>
                                <p style="margin: 4px 0; font-size: 13px;"><strong>Lng:</strong> ${lng.toFixed(6)}</p>
                                <p style="margin: 4px 0; font-size: 13px;"><strong>Accuracy:</strong> ±${position.coords.accuracy.toFixed(0)}m</p>
                            </div>
                        `))
                        .addTo(map);
                }
            },
            (error) => {
                console.error('Geolocation error:', error.code, error.message);
            },
            {
                enableHighAccuracy: true,
                maximumAge: 10000,
                timeout: 27000
            }
        );
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', () => {
        initMap();
    });
</script>

<style>
    @keyframes userPulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.15); }
    }
</style>

@endsection
