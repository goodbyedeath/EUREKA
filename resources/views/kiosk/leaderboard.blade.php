<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ \App\Models\BrandSetting::title('Leaderboard') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        
        .trophy-gold { color: #FFD700; }
        .trophy-silver { color: #C0C0C0; }
        .trophy-bronze { color: #CD7F32; }
        
        .animate-pulse-slow { animation: pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
        
        .rank-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .team-card {
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        
        .team-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .team-card.rank-1 { border-left-color: #FFD700; }
        .team-card.rank-2 { border-left-color: #C0C0C0; }
        .team-card.rank-3 { border-left-color: #CD7F32; }
        
        .loading-shimmer {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: shimmer 2s infinite;
        }
        
        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 min-h-screen">
    <div class="container mx-auto px-6 py-8">
        <!-- Header -->
        <div class="text-center mb-12">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-r from-yellow-400 to-orange-500 rounded-full mb-6 shadow-lg">
                <i class="fas fa-trophy text-white text-3xl"></i>
            </div>
            <h1 class="text-5xl font-bold text-gray-800 mb-4">Team Leaderboard</h1>
            <div class="flex items-center justify-center space-x-4 text-gray-600">
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                    <span class="text-lg">Live Updates</span>
                </div>
                <div class="w-1 h-6 bg-gray-300"></div>
                <div class="flex items-center space-x-2">
                    <i class="fas fa-clock text-gray-500"></i>
                    <span class="text-lg" id="lastUpdated">Loading...</span>
                </div>
            </div>
        </div>

        <!-- Statistics Bar -->
        <div id="statsBar" class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-12">
            <!-- Loading placeholders -->
            <div class="bg-white rounded-xl p-6 shadow-md">
                <div class="loading-shimmer h-4 rounded mb-2"></div>
                <div class="loading-shimmer h-8 rounded"></div>
            </div>
            <div class="bg-white rounded-xl p-6 shadow-md">
                <div class="loading-shimmer h-4 rounded mb-2"></div>
                <div class="loading-shimmer h-8 rounded"></div>
            </div>
            <div class="bg-white rounded-xl p-6 shadow-md">
                <div class="loading-shimmer h-4 rounded mb-2"></div>
                <div class="loading-shimmer h-8 rounded"></div>
            </div>
            <div class="bg-white rounded-xl p-6 shadow-md">
                <div class="loading-shimmer h-4 rounded mb-2"></div>
                <div class="loading-shimmer h-8 rounded"></div>
            </div>
        </div>

        <!-- Leaderboard -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-8 py-6">
                <h2 class="text-3xl font-bold text-white flex items-center">
                    <i class="fas fa-medal mr-3"></i>
                    Team Rankings
                </h2>
            </div>
            
            <div id="leaderboard" class="p-8">
                <!-- Loading placeholders -->
                <div class="space-y-4">
                    <div class="flex items-center p-6 bg-gray-50 rounded-xl">
                        <div class="loading-shimmer w-12 h-12 rounded-full mr-6"></div>
                        <div class="flex-1">
                            <div class="loading-shimmer h-6 rounded mb-2"></div>
                            <div class="loading-shimmer h-4 rounded w-3/4"></div>
                        </div>
                        <div class="loading-shimmer w-20 h-8 rounded"></div>
                    </div>
                    <div class="flex items-center p-6 bg-gray-50 rounded-xl">
                        <div class="loading-shimmer w-12 h-12 rounded-full mr-6"></div>
                        <div class="flex-1">
                            <div class="loading-shimmer h-6 rounded mb-2"></div>
                            <div class="loading-shimmer h-4 rounded w-3/4"></div>
                        </div>
                        <div class="loading-shimmer w-20 h-8 rounded"></div>
                    </div>
                    <div class="flex items-center p-6 bg-gray-50 rounded-xl">
                        <div class="loading-shimmer w-12 h-12 rounded-full mr-6"></div>
                        <div class="flex-1">
                            <div class="loading-shimmer h-6 rounded mb-2"></div>
                            <div class="loading-shimmer h-4 rounded w-3/4"></div>
                        </div>
                        <div class="loading-shimmer w-20 h-8 rounded"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-12 text-gray-600">
            <p class="text-lg">Updates automatically every 5 seconds</p>
            <div class="flex items-center justify-center mt-4 space-x-6">
                <a href="/kiosk/map" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-map-marked-alt mr-2"></i>
                    View Team Locations
                </a>
                <a href="/kiosk/dashboard" class="inline-flex items-center px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                    <i class="fas fa-th-large mr-2"></i>
                    Dashboard View
                </a>
            </div>
        </div>
    </div>

    <script>
        let leaderboardData = [];
        
        // Fetch leaderboard data
        async function fetchLeaderboard() {
            try {
                const response = await fetch('/api/kiosk/leaderboard');
                const result = await response.json();
                
                if (result.success) {
                    leaderboardData = result.data;
                    updateLeaderboard();
                    updateStats();
                    updateLastUpdated();
                } else {
                    console.error('Failed to fetch leaderboard:', result.message);
                }
            } catch (error) {
                console.error('Error fetching leaderboard:', error);
            }
        }
        
        // Update leaderboard display
        function updateLeaderboard() {
            const leaderboard = document.getElementById('leaderboard');
            
            if (!leaderboardData.teams || leaderboardData.teams.length === 0) {
                leaderboard.innerHTML = `
                    <div class="text-center py-12">
                        <i class="fas fa-users text-6xl text-gray-300 mb-4"></i>
                        <p class="text-xl text-gray-500">No teams found</p>
                    </div>
                `;
                return;
            }
            
            const html = leaderboardData.teams.map((team, index) => {
                const rank = index + 1;
                let trophyIcon = '';
                let trophyClass = '';
                
                if (rank === 1) {
                    trophyIcon = '<i class="fas fa-trophy trophy-gold text-2xl"></i>';
                    trophyClass = 'rank-1';
                } else if (rank === 2) {
                    trophyIcon = '<i class="fas fa-trophy trophy-silver text-2xl"></i>';
                    trophyClass = 'rank-2';
                } else if (rank === 3) {
                    trophyIcon = '<i class="fas fa-trophy trophy-bronze text-2xl"></i>';
                    trophyClass = 'rank-3';
                } else {
                    trophyIcon = `<span class="rank-badge text-white text-lg font-bold w-12 h-12 flex items-center justify-center rounded-full">${rank}</span>`;
                }
                
                return `
                    <div class="team-card ${trophyClass} flex items-center p-6 bg-white rounded-xl shadow-md hover:shadow-lg mb-4 transition-all duration-300">
                        <div class="flex items-center justify-center w-16 h-16 mr-6">
                            ${trophyIcon}
                        </div>
                        
                        <div class="flex-1">
                            <h3 class="text-2xl font-bold text-gray-800 mb-1">${team.name}</h3>
                            <div class="flex items-center space-x-4 text-sm text-gray-600">
                                <span><i class="fas fa-users mr-1"></i>${team.member_count} members</span>
                                ${team.department ? `<span><i class="fas fa-building mr-1"></i>${team.department}</span>` : ''}
                                ${team.leader ? `<span><i class="fas fa-crown mr-1"></i>${team.leader}</span>` : ''}
                            </div>
                        </div>
                        
                        <div class="text-right">
                            <div class="text-3xl font-bold text-indigo-600">${team.points.toLocaleString()}</div>
                            <div class="text-sm text-gray-500">points</div>
                        </div>
                    </div>
                `;
            }).join('');
            
            leaderboard.innerHTML = html;
        }
        
        // Update statistics
        function updateStats() {
            const statsBar = document.getElementById('statsBar');
            
            if (!leaderboardData) return;
            
            const html = `
                <div class="bg-white rounded-xl p-6 shadow-md">
                    <div class="text-sm text-gray-600 mb-2">Total Teams</div>
                    <div class="text-3xl font-bold text-blue-600">${leaderboardData.total_teams}</div>
                </div>
                <div class="bg-white rounded-xl p-6 shadow-md">
                    <div class="text-sm text-gray-600 mb-2">Highest Score</div>
                    <div class="text-3xl font-bold text-green-600">${leaderboardData.highest_score.toLocaleString()}</div>
                </div>
                <div class="bg-white rounded-xl p-6 shadow-md">
                    <div class="text-sm text-gray-600 mb-2">Lowest Score</div>
                    <div class="text-3xl font-bold text-orange-600">${leaderboardData.lowest_score.toLocaleString()}</div>
                </div>
                <div class="bg-white rounded-xl p-6 shadow-md">
                    <div class="text-sm text-gray-600 mb-2">Average Score</div>
                    <div class="text-3xl font-bold text-purple-600">${Math.round(leaderboardData.teams.reduce((sum, team) => sum + team.points, 0) / leaderboardData.total_teams).toLocaleString()}</div>
                </div>
            `;
            
            statsBar.innerHTML = html;
        }
        
        // Update last updated time
        function updateLastUpdated() {
            const element = document.getElementById('lastUpdated');
            const now = new Date();
            element.textContent = `Last updated: ${now.toLocaleTimeString()}`;
        }
        
        // Initial load
        fetchLeaderboard();
        
        // Auto-refresh every 5 seconds
        setInterval(fetchLeaderboard, 10000);
        
        // Update time display more frequently
        setInterval(updateLastUpdated, 1000);
    </script>
</body>
</html>