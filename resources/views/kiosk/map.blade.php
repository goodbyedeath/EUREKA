<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ \App\Models\BrandSetting::title('Team Locations') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="/vendor/maplibre/3.6.2/maplibre-gl.css" rel="stylesheet">
    @include('partials.map-config')
    <script src="/vendor/maplibre/3.6.2/maplibre-gl.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        
        .maplibregl-popup-content {
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .team-marker {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: 2px solid #fff;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 12px;
            color: white;
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        
        .team-marker:hover {
            transform: scale(1.1);
        }
        
        .loading-shimmer {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: shimmer 2s infinite;
        }
        
        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
        
        .team-list-item {
            transition: all 0.3s ease;
        }
        
        .team-list-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-96 bg-white shadow-lg overflow-y-auto">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 p-6 text-white">
                <div class="flex items-center mb-4">
                    <i class="fas fa-map-marked-alt text-2xl mr-3"></i>
                    <h1 class="text-2xl font-bold">Team Locations</h1>
                </div>
                <div class="flex items-center space-x-4 text-blue-100">
                    <div class="flex items-center space-x-2">
                        <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                        <span class="text-sm">Live Updates</span>
                    </div>
                    <div class="w-px h-4 bg-blue-300"></div>
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-clock text-xs"></i>
                        <span class="text-sm" id="lastUpdated">Loading...</span>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="p-4 bg-gray-50 border-b">
                <div id="mapStats" class="grid grid-cols-2 gap-4">
                    <div class="text-center">
                        <div class="loading-shimmer h-6 rounded mb-1"></div>
                        <div class="loading-shimmer h-4 rounded"></div>
                    </div>
                    <div class="text-center">
                        <div class="loading-shimmer h-6 rounded mb-1"></div>
                        <div class="loading-shimmer h-4 rounded"></div>
                    </div>
                </div>
            </div>

            <!-- Team List -->
            <div class="p-4">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Teams with Locations</h3>
                <div id="teamList" class="space-y-3">
                    <!-- Loading placeholders -->
                    <div class="team-list-item p-4 bg-gray-50 rounded-lg">
                        <div class="loading-shimmer h-5 rounded mb-2"></div>
                        <div class="loading-shimmer h-4 rounded w-3/4 mb-1"></div>
                        <div class="loading-shimmer h-3 rounded w-1/2"></div>
                    </div>
                    <div class="team-list-item p-4 bg-gray-50 rounded-lg">
                        <div class="loading-shimmer h-5 rounded mb-2"></div>
                        <div class="loading-shimmer h-4 rounded w-3/4 mb-1"></div>
                        <div class="loading-shimmer h-3 rounded w-1/2"></div>
                    </div>
                    <div class="team-list-item p-4 bg-gray-50 rounded-lg">
                        <div class="loading-shimmer h-5 rounded mb-2"></div>
                        <div class="loading-shimmer h-4 rounded w-3/4 mb-1"></div>
                        <div class="loading-shimmer h-3 rounded w-1/2"></div>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <div class="p-4 border-t bg-gray-50">
                <div class="space-y-2">
                    <a href="/kiosk/leaderboard" class="block w-full text-center px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-trophy mr-2"></i>
                        View Leaderboard
                    </a>
                    <a href="/kiosk/dashboard" class="block w-full text-center px-4 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                        <i class="fas fa-th-large mr-2"></i>
                        Dashboard View
                    </a>
                </div>
            </div>
        </div>

        <!-- Map Container -->
        <div class="flex-1 relative">
            <div id="map" class="w-full h-full"></div>
            
            <!-- Map Loading Overlay -->
            <div id="mapLoading" class="absolute inset-0 bg-gray-200 flex items-center justify-center">
                <div class="text-center">
                    <div class="w-16 h-16 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mb-4"></div>
                    <p class="text-gray-600 text-lg">Loading map...</p>
                </div>
            </div>

            <!-- Map Controls -->
            <div class="absolute top-4 right-4 space-y-2">
                <button id="centerMap" class="bg-white p-3 rounded-lg shadow-lg hover:bg-gray-50 transition-colors" title="Center Map">
                    <i class="fas fa-crosshairs text-gray-600"></i>
                </button>
                <button id="refreshData" class="bg-white p-3 rounded-lg shadow-lg hover:bg-gray-50 transition-colors" title="Refresh Data">
                    <i class="fas fa-sync-alt text-gray-600"></i>
                </button>
            </div>
        </div>
    </div>

    <script>
        let map;
        let markers = [];
        let teamsData = [];
        
        // Team colors for markers
        const teamColors = [
            '#3B82F6', '#EF4444', '#10B981', '#F59E0B', '#8B5CF6',
            '#EC4899', '#06B6D4', '#84CC16', '#F97316', '#6366F1'
        ];
        
        // Initialize map
        function initMap() {
            map = new maplibregl.Map({
                container: 'map',
                style: 'https://demotiles.maplibre.org/style.json',
                center: [106.8451, -6.2088], // Jakarta center
                zoom: 11,
                attributionControl: false
            });
            
            map.on('load', () => {
                document.getElementById('mapLoading').style.display = 'none';
                fetchTeamLocations();
            });
            
            // Add navigation controls
            map.addControl(new maplibregl.NavigationControl(), 'top-left');
        }
        
        // Fetch team locations data
        async function fetchTeamLocations() {
            try {
                const response = await fetch('/api/kiosk/locations');
                const result = await response.json();
                
                if (result.success) {
                    teamsData = result.data;
                    updateMap();
                    updateTeamList();
                    updateMapStats();
                    updateLastUpdated();
                } else {
                    console.error('Failed to fetch team locations:', result.message);
                }
            } catch (error) {
                console.error('Error fetching team locations:', error);
            }
        }
        
        // Update map with team markers
        function updateMap() {
            // Clear existing markers
            markers.forEach(marker => marker.remove());
            markers = [];
            
            if (!teamsData.teams || teamsData.teams.length === 0) {
                return;
            }
            
            teamsData.teams.forEach((team, index) => {
                if (!team.location) return;
                
                const color = teamColors[index % teamColors.length];
                
                // Create marker element
                const markerEl = document.createElement('div');
                markerEl.className = 'team-marker';
                markerEl.style.backgroundColor = color;
                markerEl.innerHTML = team.name.charAt(0).toUpperCase();
                
                // Create popup
                const popup = new maplibregl.Popup({ offset: 25 })
                    .setHTML(`
                        <div class="text-center">
                            <h3 class="font-bold text-lg text-gray-800 mb-2">${team.name}</h3>
                            <div class="space-y-1 text-sm text-gray-600">
                                <p><i class="fas fa-trophy mr-2 text-yellow-500"></i>${team.points.toLocaleString()} points</p>
                                <p><i class="fas fa-users mr-2 text-blue-500"></i>${team.member_count} members</p>
                                ${team.leader ? `<p><i class="fas fa-crown mr-2 text-purple-500"></i>${team.leader}</p>` : ''}
                                ${team.department ? `<p><i class="fas fa-building mr-2 text-gray-500"></i>${team.department}</p>` : ''}
                                <hr class="my-2">
                                <p><i class="fas fa-map-marker-alt mr-2 text-red-500"></i>${team.location.location_name}</p>
                                ${team.location.address ? `<p class="text-xs text-gray-500">${team.location.address}</p>` : ''}
                                <p class="text-xs text-gray-500 mt-2">
                                    <i class="fas fa-clock mr-1"></i>
                                    Last seen: ${team.location.last_checkin_human}
                                </p>
                            </div>
                        </div>
                    `);
                
                // Create and add marker
                const marker = new maplibregl.Marker(markerEl)
                    .setLngLat([team.location.longitude, team.location.latitude])
                    .setPopup(popup)
                    .addTo(map);
                
                markers.push(marker);
            });
            
            // Don't auto zoom - let user control the map view
            // if (markers.length > 0) {
            //     const bounds = new maplibregl.LngLatBounds();
            //     teamsData.teams.forEach(team => {
            //         if (team.location) {
            //             bounds.extend([team.location.longitude, team.location.latitude]);
            //         }
            //     });
            //     map.fitBounds(bounds, { padding: 50 });
            // }
        }
        
        // Update team list in sidebar
        function updateTeamList() {
            const teamList = document.getElementById('teamList');
            
            if (!teamsData.teams || teamsData.teams.length === 0) {
                teamList.innerHTML = `
                    <div class="text-center py-8">
                        <i class="fas fa-map-marker-alt text-4xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500">No team locations found</p>
                    </div>
                `;
                return;
            }
            
            const html = teamsData.teams.map((team, index) => {
                const color = teamColors[index % teamColors.length];
                
                return `
                    <div class="team-list-item p-4 bg-white rounded-lg shadow-sm border cursor-pointer hover:border-blue-300 transition-all" 
                         onclick="focusTeam(${team.location.latitude}, ${team.location.longitude})">
                        <div class="flex items-center mb-2">
                            <div class="w-5 h-5 rounded-full mr-3 flex items-center justify-center text-white text-xs font-bold"
                                 style="background-color: ${color}">
                                ${team.name.charAt(0).toUpperCase()}
                            </div>
                            <h4 class="font-semibold text-gray-800">${team.name}</h4>
                        </div>
                        
                        <div class="text-sm text-gray-600 space-y-1 ml-9">
                            <div class="flex justify-between">
                                <span><i class="fas fa-trophy mr-1 text-yellow-500"></i>${team.points.toLocaleString()} pts</span>
                                <span><i class="fas fa-users mr-1"></i>${team.member_count}</span>
                            </div>
                            <p><i class="fas fa-map-marker-alt mr-1 text-red-500"></i>${team.location.location_name}</p>
                            <p class="text-xs text-gray-500">
                                <i class="fas fa-clock mr-1"></i>${team.location.last_checkin_human}
                            </p>
                        </div>
                    </div>
                `;
            }).join('');
            
            teamList.innerHTML = html;
        }
        
        // Update map statistics
        function updateMapStats() {
            const mapStats = document.getElementById('mapStats');
            
            if (!teamsData) return;
            
            const html = `
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600">${teamsData.total_teams_with_location}</div>
                    <div class="text-xs text-gray-600">Teams Located</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600" id="liveTime">${new Date().toLocaleTimeString()}</div>
                    <div class="text-xs text-gray-600">Live Time</div>
                </div>
            `;
            
            mapStats.innerHTML = html;
        }
        
        // Focus on specific team location
        function focusTeam(lat, lng) {
            map.flyTo({
                center: [lng, lat],
                zoom: 15,
                essential: true
            });
        }
        
        // Center map to show all teams
        function centerMap() {
            if (markers.length > 0) {
                const bounds = new maplibregl.LngLatBounds();
                teamsData.teams.forEach(team => {
                    if (team.location) {
                        bounds.extend([team.location.longitude, team.location.latitude]);
                    }
                });
                map.fitBounds(bounds, { padding: 50 });
            }
        }
        
        // Update last updated time
        function updateLastUpdated() {
            const element = document.getElementById('lastUpdated');
            const now = new Date();
            element.textContent = now.toLocaleTimeString();
        }
        
        // Event listeners
        document.getElementById('centerMap').addEventListener('click', centerMap);
        document.getElementById('refreshData').addEventListener('click', fetchTeamLocations);
        
        // Initialize
        initMap();
        
        // Auto-refresh every 5 seconds
        setInterval(fetchTeamLocations, 10000);
        
        // Update time display more frequently
        setInterval(() => {
            updateLastUpdated();
            const liveTimeEl = document.getElementById('liveTime');
            if (liveTimeEl) {
                liveTimeEl.textContent = new Date().toLocaleTimeString();
            }
        }, 1000);
    </script>
</body>
</html>