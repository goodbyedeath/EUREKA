<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ \App\Models\BrandSetting::title('LED Kiosk') }}</title>
    @include('partials.map-config')
    <script src='/vendor/maplibre/3.6.2/maplibre-gl.js'></script>
    <link href='/vendor/maplibre/3.6.2/maplibre-gl.css' rel='stylesheet' />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(45deg, #0f0f23 0%, #1a1a3a 25%, #2d1b69 50%, #8b5cf6 75%, #a855f7 100%);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            color: white;
            height: 100vh;
            overflow: hidden;
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .brand-mark {
            position: fixed;
            top: 18px;
            right: 26px;
            height: 42px;
            width: auto;
            max-width: 26vw;
            object-fit: contain;
            opacity: .82;
            z-index: 20;
            pointer-events: none;
            /* The board is always dark, so a logo drawn for white paper still
               reads: the shadow gives a dark mark an edge to sit against. */
            filter: drop-shadow(0 2px 6px rgba(0,0,0,.55));
        }

        @media (max-width: 1024px) {
            .brand-mark { height: 30px; top: 12px; right: 14px; }
        }

        .kiosk-container {
            display: flex;
            height: 100vh;
            width: 100vw;
        }
        
        .leaderboard-section {
            flex: 1;
            padding: 40px;
            background: rgba(0, 0, 0, 0.15);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
            overflow-y: auto;
        }
        
        .leaderboard-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.1) 0%, rgba(168, 85, 247, 0.05) 100%);
            pointer-events: none;
        }
        
        .map-section {
            flex: 1;
            position: relative;
        }
        
        /* Mobile & Tablet Responsive Styles */
        @media (max-width: 1024px) {
            .kiosk-container {
                flex-direction: column;
            }
            
            .leaderboard-section {
                flex: none;
                height: 40vh;
                padding: 20px;
                border-right: none;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            }
            
            .map-section {
                flex: 1;
                height: 60vh;
            }
        }
        
        @media (max-width: 768px) {
            .leaderboard-section {
                height: 45vh;
                padding: 15px;
            }
            
            .map-section {
                height: 55vh;
            }
        }
        
        @media (max-width: 480px) {
            body {
                overflow: auto;
            }
            
            .kiosk-container {
                height: auto;
                min-height: 100vh;
            }
            
            .leaderboard-section {
                height: auto;
                min-height: 40vh;
                padding: 10px;
            }
            
            .map-section {
                height: 60vh;
                min-height: 400px;
            }
        }
        
        .section-title {
            font-size: 2.5rem;
            font-weight: 900;
            text-align: center;
            margin-bottom: 30px;
            background: linear-gradient(45deg, #FFD700, #FFA500, #FF8C00);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 0 30px rgba(255, 215, 0, 0.5);
            position: relative;
            z-index: 1;
            animation: titleGlow 3s ease-in-out infinite alternate;
        }
        
        @media (max-width: 1024px) {
            .section-title {
                font-size: 2rem;
                margin-bottom: 20px;
            }
        }
        
        @media (max-width: 768px) {
            .section-title {
                font-size: 1.8rem;
                margin-bottom: 15px;
            }
        }
        
        @media (max-width: 480px) {
            .section-title {
                font-size: 1.5rem;
                margin-bottom: 10px;
            }
        }
        
        @keyframes titleGlow {
            0% { text-shadow: 0 0 30px rgba(255, 215, 0, 0.5), 0 0 60px rgba(255, 215, 0, 0.3); }
            100% { text-shadow: 0 0 40px rgba(255, 215, 0, 0.8), 0 0 80px rgba(255, 215, 0, 0.4); }
        }
        
        .leaderboard-item {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.08) 0%, rgba(255, 255, 255, 0.02) 100%);
            border: 2px solid rgba(255, 255, 255, 0.15);
            padding: 25px;
            margin-bottom: 20px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            backdrop-filter: blur(15px);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        @media (max-width: 1024px) {
            .leaderboard-item {
                padding: 20px;
                margin-bottom: 15px;
                border-radius: 15px;
            }
        }
        
        @media (max-width: 768px) {
            .leaderboard-item {
                padding: 15px;
                margin-bottom: 12px;
                border-radius: 12px;
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
        }
        
        @media (max-width: 480px) {
            .leaderboard-item {
                padding: 12px;
                margin-bottom: 10px;
                border-radius: 10px;
            }
        }
        
        .leaderboard-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.6s;
        }
        
        .leaderboard-item:hover::before {
            left: 100%;
        }
        
        .leaderboard-item:hover {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.15) 0%, rgba(255, 255, 255, 0.05) 100%);
            border-color: rgba(255, 255, 255, 0.3);
            transform: translateY(-3px) scale(1.01);
            box-shadow: 0 12px 48px rgba(0, 0, 0, 0.2);
        }
        
        .rank-section {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .rank-number {
            font-size: 2rem;
            font-weight: 900;
            min-width: 60px;
            text-align: center;
            position: relative;
            z-index: 1;
        }
        
        @media (max-width: 768px) {
            .rank-section {
                gap: 15px;
            }
            
            .rank-number {
                font-size: 1.8rem;
                min-width: 50px;
            }
        }
        
        @media (max-width: 480px) {
            .rank-section {
                gap: 10px;
            }
            
            .rank-number {
                font-size: 1.5rem;
                min-width: 40px;
            }
        }
        
        .rank-1 { 
            background: linear-gradient(45deg, #FFD700, #FFA500);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: goldShimmer 2s ease-in-out infinite alternate;
        }
        .rank-2 { 
            background: linear-gradient(45deg, #C0C0C0, #E5E5E5);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .rank-3 { 
            background: linear-gradient(45deg, #CD7F32, #B8860B);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        @keyframes goldShimmer {
            0% { filter: drop-shadow(0 0 10px rgba(255, 215, 0, 0.5)); }
            100% { filter: drop-shadow(0 0 20px rgba(255, 215, 0, 0.8)); }
        }
        
        .trophy {
            font-size: 2rem;
            animation: bounce 2s ease-in-out infinite;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-5px); }
            60% { transform: translateY(-3px); }
        }
        
        .team-info {
            flex: 1;
        }
        
        .team-name {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 8px;
        }
        
        .team-members {
            font-size: 1.1rem;
            opacity: 0.8;
        }
        
        /* The race clock sits between the team and its score. Tabular figures so the
           digits do not jitter every second on a screen nobody is standing close to,
           and deliberately quieter than the points — the score is what a spectator
           reads first, the clock is what they check second. */
        .team-clock {
            font-family: 'Courier New', ui-monospace, monospace;
            font-variant-numeric: tabular-nums;
            font-size: 1.6rem;
            font-weight: 700;
            color: #9FB3C8;
            letter-spacing: 0.04em;
            min-width: 7ch;
            text-align: right;
            padding-right: 24px;
        }

        /* Indoors the map is blank, so this is the only "where is that team" cue. */
        .at-outpost {
            color: #00FF88;
            font-weight: 700;
        }

        @media (max-width: 1200px) {
            .team-clock { font-size: 1.3rem; padding-right: 14px; }
        }

        .team-points {
            font-size: 2rem;
            font-weight: 900;
            background: linear-gradient(45deg, #00FF88, #00CC66, #00AA44);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            filter: drop-shadow(0 0 15px rgba(0, 255, 136, 0.6));
            animation: pointsPulse 3s ease-in-out infinite;
            position: relative;
            z-index: 1;
        }
        
        @media (max-width: 1024px) {
            .team-name {
                font-size: 1.6rem;
                margin-bottom: 6px;
            }
            
            .team-members {
                font-size: 1rem;
            }
            
            .team-points {
                font-size: 1.8rem;
            }
        }
        
        @media (max-width: 768px) {
            .team-name {
                font-size: 1.4rem;
                margin-bottom: 5px;
            }
            
            .team-members {
                font-size: 0.9rem;
            }
            
            .team-points {
                font-size: 1.6rem;
            }
        }
        
        @media (max-width: 480px) {
            .team-name {
                font-size: 1.2rem;
                margin-bottom: 4px;
            }
            
            .team-members {
                font-size: 0.8rem;
            }
            
            .team-points {
                font-size: 1.4rem;
            }
        }
        
        @keyframes pointsPulse {
            0%, 100% { 
                filter: drop-shadow(0 0 15px rgba(0, 255, 136, 0.6));
                transform: scale(1);
            }
            50% { 
                filter: drop-shadow(0 0 25px rgba(0, 255, 136, 0.9));
                transform: scale(1.05);
            }
        }
        
        #map {
            width: 100%;
            height: 100%;
        }
        
        /* The glass panel belongs to the wrapper, not to the title.
           It used to sit on .map-title, where a second `background` declaration overrode
           the gold gradient while `-webkit-text-fill-color: transparent` stayed in force.
           The text was then clipped to a flat panel and painted with nothing — which is
           exactly why the screen showed an empty pane of glass. Panel here, gradient there. */
        .map-overlay {
            position: absolute;
            top: 20px;
            left: 20px;
            right: 20px;
            z-index: 1000;
            text-align: center;
            background: rgba(0, 0, 0, 0.4);
            padding: 15px 15px 12px;
            border-radius: 20px;
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .map-title {
            font-size: 2rem;
            font-weight: 900;
            /* A visible colour first, so anything that cannot clip a background to text
               still shows the title instead of nothing. */
            color: #FFD700;
            background: linear-gradient(45deg, #FFD700, #FFA500, #FF8C00);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: none;
            animation: mapTitleGlow 4s ease-in-out infinite alternate;
            margin: 0 0 10px;
        }
        
        .map-legend {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 0;
        }
        
        .legend-item {
            color: #fff;
            font-size: 0.85rem;
            font-weight: 600;
            padding: 5px 12px;
            background: rgba(0,0,0,0.3);
            border-radius: 15px;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        @media (max-width: 1024px) {
            .map-overlay {
                top: 15px;
                left: 15px;
                right: 15px;
            }
            
            .map-title {
                font-size: 1.8rem;
                padding: 12px;
                border-radius: 15px;
            }
        }
        
        @media (max-width: 768px) {
            .map-overlay {
                top: 10px;
                left: 10px;
                right: 10px;
            }
            
            .map-title {
                font-size: 1.5rem;
                padding: 10px;
                border-radius: 12px;
            }
        }
        
        @media (max-width: 480px) {
            .map-overlay {
                top: 8px;
                left: 8px;
                right: 8px;
            }
            
            .map-title {
                font-size: 1.2rem;
                padding: 8px;
                border-radius: 10px;
            }
        }
        
        @keyframes mapTitleGlow {
            0% { 
                filter: drop-shadow(0 0 20px rgba(255, 215, 0, 0.5));
                transform: scale(1);
            }
            100% { 
                filter: drop-shadow(0 0 30px rgba(255, 215, 0, 0.8));
                transform: scale(1.02);
            }
        }
        
        .maplibre-popup-content {
            font-size: 1.2rem;
            font-weight: bold;
        }
        
        .loading {
            display: none !important;
            visibility: hidden;
            opacity: 0;
        }
        
        .stats-bar {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.7) 0%, rgba(0, 0, 0, 0.9) 100%);
            padding: 20px;
            display: flex;
            justify-content: space-around;
            font-size: 1.2rem;
            backdrop-filter: blur(15px);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: 900;
            background: linear-gradient(45deg, #00FF88, #00CC66);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            filter: drop-shadow(0 0 10px rgba(0, 255, 136, 0.4));
            animation: statCounter 2s ease-in-out infinite alternate;
        }
        
        @media (max-width: 1024px) {
            .stats-bar {
                padding: 15px;
                font-size: 1.1rem;
            }
            
            .stat-value {
                font-size: 1.3rem;
            }
        }
        
        @media (max-width: 768px) {
            .stats-bar {
                padding: 12px;
                font-size: 1rem;
                flex-direction: column;
                gap: 8px;
            }
            
            .stat-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .stat-value {
                font-size: 1.2rem;
            }
        }
        
        @media (max-width: 480px) {
            .stats-bar {
                padding: 10px;
                font-size: 0.9rem;
            }
            
            .stat-value {
                font-size: 1.1rem;
            }
        }
        
        @keyframes statCounter {
            0% { transform: scale(1); }
            100% { transform: scale(1.05); }
        }
        
        @keyframes markerPulse {
            0%, 100% { 
                transform: scale(1);
                filter: brightness(1);
            }
            50% { 
                transform: scale(1.1);
                filter: brightness(1.2);
            }
        }
        
        @keyframes livePulse {
            0%, 100% { 
                transform: scale(1);
                box-shadow: 0 0 15px rgba(0,255,0,0.6), 0 0 10px rgba(0,0,0,0.3);
            }
            50% { 
                transform: scale(1.15);
                box-shadow: 0 0 25px rgba(0,255,0,0.9), 0 0 15px rgba(0,0,0,0.5);
            }
        }
        
        @keyframes liveBlink {
            0%, 100% { 
                opacity: 1;
                background: #00ff00;
            }
            50% { 
                opacity: 0.4;
                background: #00aa00;
            }
        }
    </style>
</head>
<body>
    <div class="loading" id="loading">Loading...</div>

    {{-- The event badge. Fixed to a corner rather than placed in the flow: the
         board is a full-height flex layout and adding a header row would squeeze
         the leaderboard. Dimmed so it never competes with the scores. --}}
    <img class="brand-mark" src="{{ \App\Models\BrandSetting::horizontalUrl() }}" alt="">
    
    <div class="kiosk-container">
        <div class="leaderboard-section">
            <h1 class="section-title">🏆 LEADERBOARD</h1>
            <div id="leaderboard-content"></div>
        </div>
        
        <div class="map-section">
            <div class="map-overlay">
                <h1 class="map-title">📍 TEAM POSITIONS</h1>
                <div class="map-legend">
                    <span class="legend-item">🏁 Quest Checkpoints</span>
                    <span class="legend-item">📡 Live Tracking</span>
                </div>
            </div>
            <div id="map"></div>
            <div class="stats-bar">
                <div class="stat-item">
                    <div class="stat-value" id="total-teams">0</div>
                    <div>Teams</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" id="gps-routes">0</div>
                    <div>GPS Routes</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" id="gps-markers">0</div>
                    <div>Route Markers</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const TRACKER_API_BASE = 'https://tracker.questerra-series.com/api/export';

        let map;
        let markers = [];
        let routeLayers = {};
        let routeMarkers = [];
        let gpsBoundsFitted = false;   // frame the routes once, then hold still

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
                            source: 'osm'
                        }
                    ]
                },
                center: [106.8456, -6.2088], // Jakarta coordinates
                zoom: 12,
                attributionControl: false
            });

            map.addControl(new maplibregl.NavigationControl(), 'top-right');

            // Load GPS tracking data when map is ready
            map.on('load', function() {
                loadGPSTrackingData();
            });
        }
        
        // Load GPS Tracking Data from Tracker API
        async function loadGPSTrackingData() {
            try {
                // Clear all existing markers before refresh
                routeMarkers.forEach(marker => marker.remove());
                routeMarkers = [];

                // Load GPS Tracker routes and markers (admin-built maps)
                const mapDataResponse = await fetch(`${TRACKER_API_BASE}/map-data?include_active=true&include_completed=true&limit=10`);

                if (!mapDataResponse.ok) {
                    console.error('Tracker API error:', mapDataResponse.status);
                } else {
                    const mapData = await mapDataResponse.json();

                    if (mapData.success && mapData.data && mapData.data.length > 0) {
                        displayGPSRoutes(mapData.data);
                    }
                }

                // EUREKA live positions no longer fetched here: they arrive with
                // /api/kiosk/data, which updateData() already polls. One screen asking
                // the same database twice on two timers was four wasted requests a
                // minute. This function keeps only the tracker call, which is a
                // different host and does not count against our own limit.
            } catch (error) {
                console.error('Error loading GPS tracking data:', error);
            }
        }

        // Display GPS routes on map
        function displayGPSRoutes(sessions) {
            let totalMarkers = 0;
            const bounds = new maplibregl.LngLatBounds();
            let hasPoints = false;

            sessions.forEach((session, index) => {
                if (!session.route_points || session.route_points.length < 2) return;

                const routeId = `gps-route-${session.session_id}`;
                const color = session.status === 'active' ? '#10b981' : '#ef4444';

                // Add the route line, or update the one already on the map.
                //
                // This used to `return` early whenever the source existed, so a route drawn
                // on the first load never grew again — the board refreshed every 15s and
                // showed the same frozen trail for the whole event.
                const routeData = {
                    type: 'Feature',
                    properties: {},
                    geometry: {
                        type: 'LineString',
                        coordinates: session.route_points.map(p => [p.lng, p.lat])
                    }
                };

                const existing = map.getSource(routeId);
                if (existing) {
                    existing.setData(routeData);
                } else {
                    map.addSource(routeId, { type: 'geojson', data: routeData });

                    map.addLayer({
                        id: routeId,
                        type: 'line',
                        source: routeId,
                        paint: {
                            'line-color': color,
                            'line-width': 5,
                            'line-opacity': 0.8
                        }
                    });

                    routeLayers[routeId] = true;
                }

                // Extend bounds to include route
                session.route_points.forEach(point => {
                    bounds.extend([point.lng, point.lat]);
                    hasPoints = true;
                });

                // Add markers along the route
                if (session.markers && session.markers.length > 0) {
                    totalMarkers += session.markers.length;

                    session.markers.forEach((marker) => {
                        const el = document.createElement('div');
                        // textContent, not innerHTML: this comes from the tracker app's
                        // database over HTTP and lands on a public, unauthenticated screen.
                        el.textContent = marker.icon || '📍';
                        el.style.fontSize = '32px';
                        el.style.cursor = 'pointer';
                        el.style.textShadow = '2px 2px 4px rgba(0,0,0,0.5)';

                        // Built as nodes rather than an HTML string, for the same reason:
                        // title, description and user name are all third-party values.
                        const popupEl = document.createElement('div');
                        popupEl.style.cssText = 'padding: 15px; font-size: 16px;';

                        const h3 = document.createElement('h3');
                        h3.style.cssText = 'margin: 0 0 10px 0; color: #333; font-weight: bold; font-size: 18px;';
                        h3.textContent = marker.title || 'Marker';
                        popupEl.appendChild(h3);

                        if (marker.description) {
                            const p = document.createElement('p');
                            p.style.cssText = 'margin: 0; font-size: 14px; color: #666;';
                            p.textContent = marker.description;
                            popupEl.appendChild(p);
                        }

                        const from = document.createElement('p');
                        from.style.cssText = 'margin: 8px 0 0 0; font-size: 12px; color: #999;';
                        from.textContent = 'From: ' + (session.user?.name ?? 'Unknown');
                        popupEl.appendChild(from);

                        const popup = new maplibregl.Popup({ offset: 30 }).setDOMContent(popupEl);

                        const markerObj = new maplibregl.Marker({ element: el, anchor: 'bottom' })
                            .setLngLat([parseFloat(marker.lng), parseFloat(marker.lat)])
                            .setPopup(popup)
                            .addTo(map);

                        routeMarkers.push(markerObj);

                        // Extend bounds to include marker
                        bounds.extend([parseFloat(marker.lng), parseFloat(marker.lat)]);
                    });
                }
            });

            // Update stats
            document.getElementById('gps-routes').textContent = sessions.length;
            document.getElementById('gps-markers').textContent = totalMarkers;

            // Frame the routes once, not on every refresh.
            //
            // This runs every 15 seconds. Re-fitting each time made the big screen lurch and
            // re-zoom continuously as teams moved, which is unwatchable in a room. Fit on the
            // first load that has points; after that let the view sit still.
            if (hasPoints && !gpsBoundsFitted) {
                gpsBoundsFitted = true;
                map.fitBounds(bounds, {
                    padding: { top: 80, bottom: 150, left: 80, right: 80 },
                    maxZoom: 15
                });
            }
        }

        // Display GPS live locations
        function displayGPSLiveLocations(userData) {
            userData.forEach((user) => {
                if (!user.current_location) return;

                const loc = user.current_location;
                const el = document.createElement('div');
                el.innerHTML = `
                    <div style="
                        width: 40px;
                        height: 40px;
                        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
                        border: 5px solid white;
                        border-radius: 50%;
                        box-shadow: 0 0 20px rgba(239, 68, 68, 0.8), 0 4px 16px rgba(0,0,0,0.4);
                        animation: livePulse 2s ease-in-out infinite;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-size: 18px;
                        color: white;
                        font-weight: bold;
                    ">${user.user.name.charAt(0)}</div>
                `;

                const markerObj = new maplibregl.Marker({ element: el, anchor: 'center' })
                    .setLngLat([loc.lng, loc.lat])
                    .setPopup(new maplibregl.Popup({ offset: 25 }).setHTML(`
                        <div style="padding: 15px; font-size: 16px;">
                            <h3 style="margin: 0 0 10px 0; font-weight: bold; color: #ef4444; font-size: 18px;">📍 ${user.user.name}</h3>
                            <p style="margin: 5px 0; font-size: 14px;"><strong>Distance:</strong> ${user.distance_formatted}</p>
                            <p style="margin: 5px 0; font-size: 14px;"><strong>Speed:</strong> ${loc.speed_kmh} km/h</p>
                            <p style="margin: 5px 0; font-size: 14px;"><strong>Updated:</strong> ${loc.time_ago}</p>
                        </div>
                    `))
                    .addTo(map);

                routeMarkers.push(markerObj);
            });
        }

        // Display EUREKA internal live users
        function displayEurekaLiveUsers(teams) {
            teams.forEach(team => {
                team.active_members.forEach(member => {
                    const el = document.createElement('div');
                    el.innerHTML = `
                        <div style="
                            width: 45px;
                            height: 45px;
                            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                            border: 5px solid white;
                            border-radius: 50%;
                            box-shadow: 0 0 20px rgba(16, 185, 129, 0.8), 0 4px 16px rgba(0,0,0,0.4);
                            animation: livePulse 2s ease-in-out infinite;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            font-size: 20px;
                            color: white;
                            font-weight: bold;
                        ">${member.name.charAt(0)}</div>
                    `;

                    const markerObj = new maplibregl.Marker({ element: el, anchor: 'center' })
                        .setLngLat([member.longitude, member.latitude])
                        .setPopup(new maplibregl.Popup({ offset: 25 }).setHTML(`
                            <div style="padding: 15px; font-size: 16px;">
                                <h3 style="margin: 0 0 10px 0; font-weight: bold; color: #10b981; font-size: 18px;">🌟 ${member.name}</h3>
                                <p style="margin: 5px 0; font-size: 14px;"><strong>Team:</strong> ${team.name}</p>
                                <p style="margin: 5px 0; font-size: 14px;"><strong>Status:</strong> Live (EUREKA)</p>
                                <p style="margin: 5px 0; font-size: 12px; color: #666;">Updated: ${member.last_update}</p>
                            </div>
                        `))
                        .addTo(map);

                    routeMarkers.push(markerObj);
                });
            });
        }

        // Update leaderboard
        function updateLeaderboard(teams) {
            const container = document.getElementById('leaderboard-content');
            container.innerHTML = '';
            
            teams.forEach((team, index) => {
                const rank = index + 1;
                const item = document.createElement('div');
                item.className = 'leaderboard-item';
                
                let trophy = '';
                let rankClass = '';
                if (rank === 1) { trophy = '🥇'; rankClass = 'rank-1'; }
                else if (rank === 2) { trophy = '🥈'; rankClass = 'rank-2'; }
                else if (rank === 3) { trophy = '🥉'; rankClass = 'rank-3'; }
                
                item.innerHTML = `
                    <div class="rank-section">
                        <div class="rank-number ${rankClass}">#${rank}</div>
                        <div class="trophy">${trophy}</div>
                    </div>
                    <div class="team-info">
                        <div class="team-name">${team.name}</div>
                        <div class="team-members">${team.member_count} members${outpostLabel(team)}</div>
                    </div>
                    ${raceClock(team)}
                    <div class="team-points">${team.points}</div>
                `;
                
                container.appendChild(item);
            });
        }
        
        // ---- indoor additions ------------------------------------------------
        // Indoors GPS sees nothing through a roof, so the outpost the crew has opened
        // for a team is the only signal of where that team is. Shown beside the member
        // count rather than on the map, which stays blank at an indoor venue.
        function outpostLabel(team) {
            const at = team.at_outposts || [];
            if (!at.length) return '';
            const names = at.map(o => o.name).join(', ');
            return ` &middot; <span class="at-outpost">&#9679; ${names}</span>`;
        }

        // The clock is derived from the server's start instant, held in a data attribute
        // and advanced locally, so a screen that reloads mid-race shows the same number
        // as one that has been up for an hour.
        function raceClock(team) {
            if (!team.race) return '<div class="team-clock">&mdash;</div>';
            return `<div class="team-clock" data-elapsed="${team.race.elapsed_seconds}"
                         data-running="${team.race.finished ? 0 : 1}">${team.race.elapsed}</div>`;
        }

        function tickClocks() {
            document.querySelectorAll('.team-clock[data-running="1"]').forEach(el => {
                const s = (parseInt(el.dataset.elapsed, 10) || 0) + 1;
                el.dataset.elapsed = s;
                el.textContent =
                    String(Math.floor(s / 3600)).padStart(2, '0') + ':' +
                    String(Math.floor((s % 3600) / 60)).padStart(2, '0') + ':' +
                    String(s % 60).padStart(2, '0');
            });
        }
        setInterval(tickClocks, 1000);

        // Update map with GPS tracking data (replaces quest teams)
        function updateMap() {
            // No longer used - GPS tracking is handled by loadGPSTrackingData
        }
        
        // Update statistics
        function updateStats(data) {
            document.getElementById('total-teams').textContent = data.leaderboard?.teams?.length || 0;
            document.getElementById('active-locations').textContent = data.locations?.teams?.length || 0;
            
            // Count total quest checkpoints from teams with locations
            let totalCheckpoints = 0;
            if (data.locations?.teams) {
                data.locations.teams.forEach(team => {
                    if (team.location && !team.location.is_live_tracking) {
                        totalCheckpoints += 1; // Each team with a quest location counts as 1 checkpoint
                    }
                });
            }
            document.getElementById('total-checkpoints').textContent = totalCheckpoints;
        }
        
        // Fetch and update data
        async function updateData() {
            try {
                // Fetch kiosk data for leaderboard only
                const kioskResponse = await fetch('/api/kiosk/data');

                if (!kioskResponse.ok) {
                    throw new Error(`Kiosk API error: ${kioskResponse.status}`);
                }

                const kioskResult = await kioskResponse.json();

                if (kioskResult.success && kioskResult.data) {
                    // Hide loading indicator
                    document.getElementById('loading').style.display = 'none';

                    // Update leaderboard
                    if (kioskResult.data.leaderboard?.teams) {
                        updateLeaderboard(kioskResult.data.leaderboard.teams);
                    } else {
                        document.getElementById('leaderboard-content').innerHTML =
                            '<div style="text-align: center; color: #888; padding: 20px;">No team data available</div>';
                    }

                    // Update stats
                    updateStats(kioskResult.data);

                    // Live positions now ride along with this same response, so the
                    // map refreshes without a second request.
                    const live = kioskResult.data.live_positions;
                    if (live && live.teams && live.teams.length > 0) {
                        displayEurekaLiveUsers(live.teams);
                    }

                    // Update last updated time
                    const now = new Date().toLocaleTimeString();
                    console.log(`Data updated at ${now}`);

                } else {
                    throw new Error(kioskResult.message || 'API returned unsuccessful response');
                }

            } catch (error) {
                console.error('Error fetching kiosk data:', error);

                // Show error message in leaderboard if it's empty
                const leaderboardContent = document.getElementById('leaderboard-content');
                if (!leaderboardContent.children.length) {
                    leaderboardContent.innerHTML = `
                        <div style="text-align: center; color: #ff6b6b; padding: 20px;">
                            <div style="font-size: 24px; margin-bottom: 10px;">⚠️</div>
                            <div>Unable to load data</div>
                            <div style="font-size: 12px; opacity: 0.7;">Retrying in 5 seconds...</div>
                        </div>
                    `;
                }
            }
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            initMap();
            updateData();

            // Update leaderboard every 5 seconds
            setInterval(updateData, 10000);        // scores move every few minutes, not every 5s

            // Update GPS tracking every 10 seconds
            setInterval(loadGPSTrackingData, 15000);
        });
    </script>
</body>
</html>