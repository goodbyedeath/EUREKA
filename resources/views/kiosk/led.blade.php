<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EUREKA LED Kiosk</title>
    <script src='https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.js'></script>
    <link href='https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.css' rel='stylesheet' />
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
        
        .map-overlay {
            position: absolute;
            top: 20px;
            left: 20px;
            right: 20px;
            z-index: 1000;
            text-align: center;
        }
        
        .map-title {
            font-size: 2rem;
            font-weight: 900;
            background: linear-gradient(45deg, #FFD700, #FFA500, #FF8C00);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: none;
            background: rgba(0, 0, 0, 0.4);
            padding: 15px 15px 10px 15px;
            border-radius: 20px;
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            animation: mapTitleGlow 4s ease-in-out infinite alternate;
            margin-bottom: 0;
        }
        
        .map-legend {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 15px;
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
                    <div class="stat-value" id="active-locations">0</div>
                    <div>Active Locations</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" id="total-checkpoints">0</div>
                    <div>Total Checkpoints</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let map;
        let markers = [];
        
        // Initialize map
        function initMap() {
            map = new maplibregl.Map({
                container: 'map',
                style: {
                    version: 8,
                    sources: {
                        'osm': {
                            type: 'raster',
                            tiles: ['https://tile.openstreetmap.org/{z}/{x}/{y}.png'],
                            tileSize: 256,
                            attribution: '© OpenStreetMap contributors'
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
                        <div class="team-members">${team.member_count} members</div>
                    </div>
                    <div class="team-points">${team.points}</div>
                `;
                
                container.appendChild(item);
            });
        }
        
        // Update map markers
        function updateMap(questTeams, liveTeams = []) {
            // Clear existing markers
            markers.forEach(marker => marker.remove());
            markers = [];
            
            if ((!questTeams || questTeams.length === 0) && (!liveTeams || liveTeams.length === 0)) {
                console.log('No location data available');
                return;
            }
            
            const teamColors = [
                '#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', 
                '#FFEAA7', '#DDA0DD', '#FFB347', '#87CEEB'
            ];
            
            // Calculate bounds for auto-centering
            const bounds = new maplibregl.LngLatBounds();
            let markerIndex = 0;
            
            // Add quest checkpoint markers
            questTeams.forEach((team) => {
                if (team.location && team.location.latitude && team.location.longitude) {
                    const color = teamColors[markerIndex % teamColors.length];
                    const lngLat = [team.location.longitude, team.location.latitude];
                    
                    // Extend bounds to include this location
                    bounds.extend(lngLat);
                    
                    // Create simple marker with team name directly visible
                    const el = document.createElement('div');
                    el.innerHTML = `
                        <div style="
                            display: flex;
                            flex-direction: column;
                            align-items: center;
                            font-family: 'Segoe UI', Arial, sans-serif;
                        ">
                            <div style="
                                width: 60px;
                                height: 60px;
                                background: linear-gradient(45deg, ${color}, ${color}dd);
                                border: 4px solid white;
                                border-radius: 50%;
                                box-shadow: 0 0 20px rgba(0,0,0,0.4), 0 0 15px ${color}55;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                font-size: 20px;
                                font-weight: 900;
                                color: white;
                                text-shadow: 2px 2px 4px rgba(0,0,0,0.8);
                                animation: markerPulse 3s ease-in-out infinite;
                                transition: all 0.3s ease;
                                cursor: pointer;
                            ">${team.name.charAt(0)}</div>
                            <div style="
                                margin-top: 8px;
                                background: rgba(0, 0, 0, 0.85);
                                color: white;
                                padding: 8px 16px;
                                border-radius: 20px;
                                font-size: 16px;
                                font-weight: 800;
                                text-shadow: 1px 1px 2px rgba(0,0,0,0.9);
                                white-space: nowrap;
                                border: 2px solid rgba(255,255,255,0.4);
                                min-width: 100px;
                                text-align: center;
                                box-shadow: 0 6px 20px rgba(0,0,0,0.6);
                                backdrop-filter: blur(10px);
                            ">${team.name}</div>
                        </div>
                    `;
                    
                    // Add hover effects
                    const circle = el.querySelector('div > div:first-child');
                    const label = el.querySelector('div > div:last-child');
                    
                    el.addEventListener('mouseenter', () => {
                        circle.style.transform = 'scale(1.15)';
                        label.style.backgroundColor = 'rgba(0, 0, 0, 0.95)';
                        label.style.transform = 'scale(1.05)';
                    });
                    el.addEventListener('mouseleave', () => {
                        circle.style.transform = 'scale(1)';
                        label.style.backgroundColor = 'rgba(0, 0, 0, 0.85)';
                        label.style.transform = 'scale(1)';
                    });
                    
                    const marker = new maplibregl.Marker(el)
                        .setLngLat(lngLat)
                        .setPopup(new maplibregl.Popup({ offset: 25 })
                            .setHTML(`
                                <div style="font-size: 16px; font-weight: bold;">
                                    <h3 style="margin: 0 0 10px 0; color: ${color};">${team.name}</h3>
                                    <p style="margin: 5px 0;"><strong>Location:</strong> ${team.location.location_name}</p>
                                    <p style="margin: 5px 0;"><strong>Members:</strong> ${team.member_count}</p>
                                    <p style="margin: 5px 0;"><strong>Points:</strong> ${team.points}</p>
                                    <p style="margin: 5px 0; font-size: 12px; opacity: 0.8;">
                                        Last update: ${new Date(team.location.last_checkin).toLocaleTimeString()}
                                    </p>
                                </div>
                            `))
                        .addTo(map);
                    
                    markers.push(marker);
                    markerIndex++;
                }
            });
            
            // Add live tracking markers
            liveTeams.forEach((team) => {
                if (team.active_members && team.active_members.length > 0) {
                    team.active_members.forEach((member) => {
                        const color = teamColors[markerIndex % teamColors.length];
                        const lngLat = [member.longitude, member.latitude];
                        
                        // Extend bounds to include this location
                        bounds.extend(lngLat);
                        
                        // Create live tracking marker (different style)
                        const el = document.createElement('div');
                        el.innerHTML = `
                            <div style="
                                display: flex;
                                flex-direction: column;
                                align-items: center;
                                font-family: 'Segoe UI', Arial, sans-serif;
                            ">
                                <div style="
                                    width: 50px;
                                    height: 50px;
                                    background: linear-gradient(45deg, ${color}, ${color}dd);
                                    border: 3px solid #00ff00;
                                    border-radius: 50%;
                                    box-shadow: 0 0 15px rgba(0,255,0,0.6), 0 0 10px ${color}55;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    font-size: 16px;
                                    font-weight: 900;
                                    color: white;
                                    text-shadow: 2px 2px 4px rgba(0,0,0,0.8);
                                    animation: livePulse 2s ease-in-out infinite;
                                    cursor: pointer;
                                    position: relative;
                                ">
                                    <div style="
                                        position: absolute;
                                        top: -2px;
                                        right: -2px;
                                        width: 16px;
                                        height: 16px;
                                        background: #00ff00;
                                        border-radius: 50%;
                                        border: 2px solid white;
                                        animation: liveBlink 1s ease-in-out infinite;
                                    "></div>
                                    ${member.name.charAt(0)}
                                </div>
                                <div style="
                                    margin-top: 6px;
                                    background: rgba(0, 255, 0, 0.85);
                                    color: white;
                                    padding: 6px 12px;
                                    border-radius: 15px;
                                    font-size: 14px;
                                    font-weight: 700;
                                    text-shadow: 1px 1px 2px rgba(0,0,0,0.9);
                                    white-space: nowrap;
                                    border: 2px solid rgba(255,255,255,0.6);
                                    min-width: 80px;
                                    text-align: center;
                                    box-shadow: 0 4px 15px rgba(0,255,0,0.4);
                                ">${member.name}</div>
                            </div>
                        `;
                        
                        const marker = new maplibregl.Marker(el)
                            .setLngLat(lngLat)
                            .setPopup(new maplibregl.Popup({ offset: 25 })
                                .setHTML(`
                                    <div style="font-size: 16px; font-weight: bold;">
                                        <h3 style="margin: 0 0 10px 0; color: #00ff00;">📡 ${member.name}</h3>
                                        <p style="margin: 5px 0;"><strong>Team:</strong> ${team.name}</p>
                                        <p style="margin: 5px 0;"><strong>Status:</strong> Live Tracking</p>
                                        <p style="margin: 5px 0; font-size: 12px; opacity: 0.8;">
                                            Last update: ${member.last_update}
                                        </p>
                                    </div>
                                `))
                            .addTo(map);
                        
                        markers.push(marker);
                        markerIndex++;
                    });
                }
            });
            
            // Auto-center and zoom to fit all markers
            if (markers.length > 0) {
                if (markers.length === 1) {
                    // Single marker - center on it with reasonable zoom
                    map.setCenter(bounds.getCenter());
                    map.setZoom(14);
                } else {
                    // Multiple markers - fit bounds with padding
                    map.fitBounds(bounds, {
                        padding: { top: 50, bottom: 100, left: 50, right: 50 },
                        maxZoom: 15
                    });
                }
            } else {
                // No markers - center on Jakarta (default)
                map.setCenter([106.8456, -6.2088]);
                map.setZoom(12);
            }
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
                // Fetch both quest checkpoints and live positions in parallel
                const [kioskResponse, liveResponse] = await Promise.all([
                    fetch('/api/kiosk/data'),
                    fetch('/api/live/positions')
                ]);
                
                if (!kioskResponse.ok) {
                    throw new Error(`Kiosk API error: ${kioskResponse.status}`);
                }
                
                const kioskResult = await kioskResponse.json();
                let liveResult = null;
                
                // Live positions are optional - don't fail if unavailable
                if (liveResponse.ok) {
                    liveResult = await liveResponse.json();
                }
                
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
                    
                    // Combine quest checkpoints and live positions for map
                    const questTeams = kioskResult.data.locations?.teams || [];
                    const liveTeams = liveResult?.success ? liveResult.data?.teams || [] : [];
                    
                    updateMap(questTeams, liveTeams);
                    
                    // Update stats
                    updateStats(kioskResult.data);
                    
                    // Update last updated time
                    const now = new Date().toLocaleTimeString();
                    console.log(`Data updated at ${now}`);
                    
                } else {
                    throw new Error(result.message || 'API returned unsuccessful response');
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
            
            // Update every 5 seconds
            setInterval(updateData, 5000);
        });
    </script>
</body>
</html>