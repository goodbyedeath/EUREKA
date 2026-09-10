<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ \App\Models\BrandSetting::title('GPS Tracking Map') }}</title>
    <link href="/vendor/maplibre/4.7.1/maplibre-gl.css" rel="stylesheet" />
    @include('partials.map-config')
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

        #map {
            position: absolute;
            top: 70px;
            left: 0;
            right: 0;
            bottom: 0;
        }

        .header {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 70px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
            z-index: 1000;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .header h1 {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header h1 .icon {
            font-size: 28px;
        }

        .live-indicator {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            padding: 10px 18px;
            border-radius: 24px;
            font-weight: 600;
            font-size: 14px;
        }

        .live-dot {
            width: 10px;
            height: 10px;
            background: #10b981;
            border-radius: 50%;
            animation: pulse 2s infinite;
            box-shadow: 0 0 8px rgba(16, 185, 129, 0.6);
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

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.6; transform: scale(1.2); }
        }

        .stats-panel {
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

        .stats-panel::-webkit-scrollbar {
            width: 6px;
        }

        .stats-panel::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .stats-panel::-webkit-scrollbar-thumb {
            background: #667eea;
            border-radius: 10px;
        }

        .stats-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 2px solid #f0f0f0;
        }

        .stats-header h3 {
            margin: 0;
            color: #1f2937;
            font-size: 18px;
            font-weight: 700;
        }

        .stat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 16px;
            margin-bottom: 8px;
            background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
            border-radius: 10px;
            transition: all 0.2s;
        }

        .stat-item:hover {
            transform: translateX(3px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .stat-label {
            color: #6b7280;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .stat-icon {
            font-size: 16px;
        }

        .stat-value {
            font-weight: 700;
            color: #667eea;
            font-size: 16px;
        }

        .active-users-list {
            margin-top: 24px;
        }

        .section-title {
            font-size: 14px;
            font-weight: 700;
            color: #4b5563;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .user-item {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border-radius: 12px;
            padding: 14px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s;
            border-left: 4px solid #667eea;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .user-item:hover {
            background: linear-gradient(135deg, #e9ecef 0%, #f8f9fa 100%);
            transform: translateX(5px);
            box-shadow: 0 4px 16px rgba(102, 126, 234, 0.2);
        }

        .user-name {
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 6px;
            font-size: 15px;
        }

        .user-stats {
            font-size: 12px;
            color: #6b7280;
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
        }

        .user-stat-item {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .refresh-btn {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 700;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .refresh-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
        }

        .refresh-btn:active {
            transform: translateY(0);
        }

        .user-marker {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            width: 46px;
            height: 46px;
            border-radius: 50%;
            border: 4px solid white;
            box-shadow: 0 4px 16px rgba(102, 126, 234, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 18px;
            cursor: pointer;
            animation: userPulse 2s infinite;
        }

        @keyframes userPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.15); }
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #9ca3af;
        }

        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 12px;
            opacity: 0.5;
        }

        .empty-state-text {
            font-size: 14px;
            font-weight: 500;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .header {
                height: auto;
                min-height: 70px;
                padding: 12px 16px;
                flex-direction: column;
                gap: 12px;
            }

            .header-left {
                width: 100%;
                justify-content: space-between;
            }

            .header h1 {
                font-size: 18px;
            }

            .header h1 .icon {
                font-size: 22px;
            }

            .live-indicator {
                padding: 8px 14px;
                font-size: 12px;
            }

            .back-btn {
                padding: 8px 14px;
                font-size: 12px;
            }

            #map {
                top: 90px;
            }

            .stats-panel {
                top: 100px;
                left: 10px;
                right: 10px;
                width: auto;
                max-height: 40vh;
                padding: 16px;
            }

            .stats-header h3 {
                font-size: 16px;
            }

            .user-item {
                padding: 12px;
            }

            .user-name {
                font-size: 14px;
            }

            .user-stats {
                font-size: 11px;
            }
        }

        /* Tablet Responsive */
        @media (max-width: 1024px) and (min-width: 769px) {
            .stats-panel {
                width: 300px;
            }
        }

        /* Popup Styles */
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
</head>
<body>
    <div class="header">
        <div class="header-left">
            <a href="{{ route('admin.dashboard') }}" class="back-btn">
                <span>←</span> <span>Dashboard</span>
            </a>
            <h1>
                <span class="icon">🗺️</span>
                <span>{{ \App\Models\BrandSetting::appName() }} GPS Live Tracking</span>
            </h1>
        </div>
        <div class="live-indicator">
            <div class="live-dot"></div>
            <span id="activeCount">0 Active Teams</span>
        </div>
    </div>

    <div class="stats-panel">
        <div class="stats-header">
            <h3>Active Tracking</h3>
            <button class="refresh-btn" onclick="refreshData()">
                <span>🔄</span> <span>Refresh</span>
            </button>
        </div>

        <div class="stat-item">
            <span class="stat-label">
                <span class="stat-icon">👥</span>
                Active Users
            </span>
            <span class="stat-value" id="activeUsersCount">-</span>
        </div>
        <div class="stat-item">
            <span class="stat-label">
                <span class="stat-icon">📊</span>
                Total Sessions
            </span>
            <span class="stat-value" id="totalSessions">-</span>
        </div>
        <div class="stat-item">
            <span class="stat-label">
                <span class="stat-icon">🕐</span>
                Last Update
            </span>
            <span class="stat-value" id="lastUpdate">-</span>
        </div>

        <div class="active-users-list">
            <h4 class="section-title">Live Teams</h4>
            <div id="activeUsersList">
                <!-- Active users will be populated here -->
            </div>
        </div>
    </div>

    <div id="map"></div>

    <script>
        const API_BASE = 'https://tracker.questerra-series.com/api/export';
        let map;
        let markers = {};
        let routeLayers = {};

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
                    layers: [{
                        id: 'osm',
                        type: 'raster',
                        source: 'osm'
                    }]
                },
                center: [106.93, -6.11],
                zoom: 13
            });

            map.addControl(new maplibregl.NavigationControl(), 'top-right');
            map.addControl(new maplibregl.FullscreenControl(), 'top-right');

            map.on('load', () => {
                loadMapData();
                startAutoRefresh();
            });
        }

        // Load all map data
        async function loadMapData() {
            try {
                // Load GPS tracker routes and markers (admin-built maps)
                const mapDataResponse = await fetch(`${API_BASE}/map-data?include_active=false&include_completed=true&limit=10`);

                if (!mapDataResponse.ok) {
                    throw new Error(`Tracker API error: ${mapDataResponse.status}`);
                }

                const mapData = await mapDataResponse.json();

                if (mapData.success && mapData.data) {
                    displayRoutes(mapData.data);
                    document.getElementById('totalSessions').textContent = mapData.data.length;
                } else {
                    document.getElementById('totalSessions').textContent = '0';
                }

                // Load EUREKA internal live positions (actual users)
                const eurekaLiveResponse = await fetch('/api/live/positions');

                if (!eurekaLiveResponse.ok) {
                    throw new Error(`Live positions error: ${eurekaLiveResponse.status}`);
                }

                const eurekaLiveData = await eurekaLiveResponse.json();

                if (eurekaLiveData.success && eurekaLiveData.data) {
                    displayEurekaLiveUsers(eurekaLiveData.data.teams);
                    // Update stats with EUREKA users count
                    const totalUsers = eurekaLiveData.data.teams.reduce((sum, team) => sum + team.active_members.length, 0);
                    document.getElementById('activeUsersCount').textContent = totalUsers;
                    document.getElementById('activeCount').textContent = `${totalUsers} Active Users`;
                } else {
                    document.getElementById('activeUsersCount').textContent = '0';
                    document.getElementById('activeCount').textContent = '0 Active Users';
                }

                document.getElementById('lastUpdate').textContent = new Date().toLocaleTimeString();
            } catch (error) {
                console.error('Error loading map data:', error);
                showNotification('Unable to load tracking data. Please refresh the page.', 'error');
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
            `;
            notification.textContent = message;
            document.body.appendChild(notification);

            setTimeout(() => {
                notification.style.transition = 'opacity 0.3s';
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 300);
            }, 4000);
        }

        // Display routes
        function displayRoutes(sessions) {
            // Clear old route layers (but keep EUREKA user markers)
            Object.keys(routeLayers).forEach(routeId => {
                if (map.getLayer(routeId)) {
                    map.removeLayer(routeId);
                }
                if (map.getSource(routeId)) {
                    map.removeSource(routeId);
                }
            });
            routeLayers = {};

            // Clear old route markers (not EUREKA markers)
            Object.keys(markers).forEach(key => {
                if (!key.startsWith('eureka-')) {
                    markers[key].remove();
                    delete markers[key];
                }
            });

            sessions.forEach((session, index) => {
                if (session.route_points.length < 2) return;

                const routeId = `route-${session.session_id}`;
                const color = session.status === 'active' ? '#10b981' : '#667eea';

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
                            'line-width': 3,
                            'line-opacity': 0.7
                        }
                    });
                }

                session.markers.forEach(marker => {
                    const el = document.createElement('div');
                    el.innerHTML = marker.icon || '📍';
                    el.style.fontSize = '32px';
                    el.style.cursor = 'pointer';
                    el.style.textShadow = '2px 2px 4px rgba(0,0,0,0.5)';

                    const popup = new maplibregl.Popup({ offset: 25 })
                        .setHTML(`
                            <div style="padding: 8px;">
                                <h4 style="margin: 0 0 8px 0; color: #333;">${marker.title || 'Marker'}</h4>
                                ${marker.description ? `<p style="margin: 0; font-size: 13px; color: #666;">${marker.description}</p>` : ''}
                            </div>
                        `);

                    new maplibregl.Marker({ element: el, anchor: 'bottom' })
                        .setLngLat([parseFloat(marker.lng), parseFloat(marker.lat)])
                        .setPopup(popup)
                        .addTo(map);
                });
            });
        }

        // Display live users (DEPRECATED - only used for EUREKA now)
        function displayLiveUsers(users) {
            const usersList = document.getElementById('activeUsersList');
            if (users.length === 0) {
                usersList.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon">📍</div>
                        <div class="empty-state-text">No active teams tracking</div>
                    </div>
                `;
                return;
            }

            usersList.innerHTML = users.map(user => {
                const loc = user.current_location;
                return `
                    <div class="user-item" onclick="focusOnUser(${loc ? loc.lat : 0}, ${loc ? loc.lng : 0})">
                        <div class="user-name">${user.user.name}</div>
                        <div class="user-stats">
                            <span class="user-stat-item">📍 ${user.distance_formatted}</span>
                            <span class="user-stat-item">⏱️ ${formatDuration(user.duration)}</span>
                            ${loc ? `<span class="user-stat-item">🏃 ${loc.speed_kmh} km/h</span>` : ''}
                        </div>
                        ${loc ? `<div style="font-size: 11px; color: #9ca3af; margin-top: 6px;">Updated ${loc.time_ago}</div>` : ''}
                    </div>
                `;
            }).join('');

            users.forEach(user => {
                if (!user.current_location) return;

                const loc = user.current_location;
                const el = document.createElement('div');
                el.className = 'user-marker';
                el.textContent = user.user.name.charAt(0).toUpperCase();

                const popup = new maplibregl.Popup({ offset: 20 })
                    .setHTML(`
                        <div style="padding: 8px;">
                            <h4 style="margin: 0 0 8px 0;">👤 ${user.user.name}</h4>
                            <p style="margin: 4px 0; font-size: 13px;"><strong>Distance:</strong> ${user.distance_formatted}</p>
                            <p style="margin: 4px 0; font-size: 13px;"><strong>Speed:</strong> ${loc.speed_kmh} km/h</p>
                            <p style="margin: 4px 0; font-size: 13px;"><strong>Updated:</strong> ${loc.time_ago}</p>
                        </div>
                    `);

                const marker = new maplibregl.Marker({ element: el, anchor: 'center' })
                    .setLngLat([loc.lng, loc.lat])
                    .setPopup(popup)
                    .addTo(map);

                markers[user.session_id] = marker;

                if (Object.keys(markers).length === users.length) {
                    const bounds = new maplibregl.LngLatBounds();
                    users.forEach(u => {
                        if (u.current_location) {
                            bounds.extend([u.current_location.lng, u.current_location.lat]);
                        }
                    });
                    map.fitBounds(bounds, { padding: 100 });
                }
            });
        }

        // Display EUREKA internal live users
        function displayEurekaLiveUsers(teams) {
            // Clear old EUREKA markers
            Object.keys(markers).forEach(key => {
                if (key.startsWith('eureka-')) {
                    markers[key].remove();
                    delete markers[key];
                }
            });

            const bounds = new maplibregl.LngLatBounds();
            let hasEurekaUsers = false;

            teams.forEach(team => {
                team.active_members.forEach(member => {
                    hasEurekaUsers = true;

                    const el = document.createElement('div');
                    el.innerHTML = `
                        <div style="
                            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                            width: 46px;
                            height: 46px;
                            border-radius: 50%;
                            border: 4px solid white;
                            box-shadow: 0 4px 16px rgba(16, 185, 129, 0.6);
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            color: white;
                            font-weight: bold;
                            font-size: 18px;
                            cursor: pointer;
                        ">${member.name.charAt(0).toUpperCase()}</div>
                    `;

                    const popup = new maplibregl.Popup({ offset: 25 })
                        .setHTML(`
                            <div style="padding: 10px;">
                                <h4 style="margin: 0 0 8px 0; color: #10b981;">🌟 ${member.name}</h4>
                                <p style="margin: 4px 0; font-size: 13px;"><strong>Team:</strong> ${team.name}</p>
                                <p style="margin: 4px 0; font-size: 13px;"><strong>Status:</strong> Live (EUREKA)</p>
                                <p style="margin: 4px 0; font-size: 12px; color: #666;">Updated: ${member.last_update}</p>
                            </div>
                        `);

                    const marker = new maplibregl.Marker({ element: el })
                        .setLngLat([member.longitude, member.latitude])
                        .setPopup(popup)
                        .addTo(map);

                    markers[`eureka-${member.user_id}`] = marker;
                    bounds.extend([member.longitude, member.latitude]);
                });
            });

            // Fit map to show EUREKA users if any
            if (hasEurekaUsers) {
                // Collect all current marker positions
                const allBounds = new maplibregl.LngLatBounds();
                Object.values(markers).forEach(marker => {
                    allBounds.extend(marker.getLngLat());
                });

                map.fitBounds(allBounds, { padding: 100, maxZoom: 15 });
            }
        }

        function updateStats(liveData) {
            document.getElementById('activeUsersCount').textContent = liveData.active_users_count;
            document.getElementById('activeCount').textContent = `${liveData.active_users_count} Active Users`;
        }

        function focusOnUser(lat, lng) {
            map.flyTo({
                center: [lng, lat],
                zoom: 16,
                duration: 1500
            });
        }

        function formatDuration(seconds) {
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const secs = seconds % 60;

            if (hours > 0) {
                return `${hours}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
            }
            return `${minutes}:${String(secs).padStart(2, '0')}`;
        }

        function refreshData() {
            loadMapData();
        }

        function startAutoRefresh() {
            setInterval(() => {
                loadMapData();
            }, 10000); // Refresh every 10 seconds
        }

        initMap();
    </script>
</body>
</html>
