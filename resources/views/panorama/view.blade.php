<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>360° View: {{ $gameLocation->name }}</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class'
        }
    </script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Panellum CSS & JS -->
    <link href="{{ asset('js/pannellum/pannellum.css') }}" rel="stylesheet">
    <script src="{{ asset('js/pannellum/pannellum.js') }}"></script>
    
    <style>
        /* Custom Panellum Hotspot Styles */
        .admin-hotspot-marker {
            background: linear-gradient(135deg, #3b82f6, #1e40af) !important;
            border: 3px solid white !important;
            border-radius: 50% !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3) !important;
            animation: pulse-hotspot 2s infinite !important;
        }
        
        .custom-admin-hotspot {
            background: linear-gradient(135deg, #f59e0b, #d97706) !important;
            border: 3px solid white !important;
            border-radius: 50% !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3) !important;
            animation: bounce-hotspot 3s infinite !important;
            width: 20px !important;
            height: 20px !important;
            display: block !important;
            position: absolute !important;
            z-index: 100 !important;
            cursor: pointer !important;
        }
        
        /* Ensure all Panellum hotspots are visible */
        .pnlm-hotspot {
            width: 20px !important;
            height: 20px !important;
            display: block !important;
            position: absolute !important;
            z-index: 100 !important;
            cursor: pointer !important;
        }
        
        @keyframes pulse-hotspot {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.2); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }
        
        @keyframes bounce-hotspot {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }
        
        @keyframes ping {
            75%, 100% {
                transform: scale(2);
                opacity: 0;
            }
        }
        
        /* Fullscreen styles */
        #panorama-viewer:-webkit-full-screen {
            width: 100vw !important;
            height: 100vh !important;
        }
        
        #panorama-viewer:-moz-full-screen {
            width: 100vw !important;
            height: 100vh !important;
        }
        
        #panorama-viewer:fullscreen {
            width: 100vw !important;
            height: 100vh !important;
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <!-- Header -->
    <header class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <a href="/admin/games" class="text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Game Manager
                    </a>
                </div>
                <div class="text-center">
                    <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                        360° View: {{ $gameLocation->name }}
                    </h1>
                </div>
                <div class="flex items-center space-x-4">
                    <button onclick="toggleDarkMode()" class="p-2 text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">
                        <i class="fas fa-moon dark:hidden"></i>
                        <i class="fas fa-sun hidden dark:inline"></i>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1">
        <!-- 360° Viewer Container -->
        <div id="panorama-viewer" class="w-full" style="height: calc(100vh - 64px);"></div>
        
        <!-- Floating Controls -->
        <div id="floating-controls" class="fixed top-20 right-4 flex flex-col space-y-2 z-10">
            <button onclick="toggleFullscreen()" 
                    class="bg-green-600 hover:bg-green-700 text-white p-3 rounded-lg shadow-lg transition-colors duration-200"
                    title="Toggle Fullscreen">
                <i class="fas fa-expand"></i>
            </button>
            <button onclick="toggleHotspots()" 
                    class="bg-purple-600 hover:bg-purple-700 text-white p-3 rounded-lg shadow-lg transition-colors duration-200"
                    title="Toggle Hotspots">
                <i class="fas fa-map-marker-alt"></i>
            </button>
            <button onclick="toggleHotspotMode()" 
                    class="bg-orange-600 hover:bg-orange-700 text-white p-3 rounded-lg shadow-lg transition-colors duration-200"
                    title="Add Hotspot Mode">
                <i class="fas fa-plus"></i>
            </button>
            <button onclick="resetView()" 
                    class="bg-gray-600 hover:bg-gray-700 text-white p-3 rounded-lg shadow-lg transition-colors duration-200"
                    title="Reset View">
                <i class="fas fa-undo"></i>
            </button>
            <button onclick="toggleDebugPanel()" 
                    class="bg-yellow-600 hover:bg-yellow-700 text-white p-3 rounded-lg shadow-lg transition-colors duration-200"
                    title="Debug Panel">
                <i class="fas fa-bug"></i>
            </button>
            <button onclick="refreshHotspots()" 
                    class="bg-indigo-600 hover:bg-indigo-700 text-white p-3 rounded-lg shadow-lg transition-colors duration-200"
                    title="Refresh Hotspots from Database">
                <i class="fas fa-sync-alt"></i>
            </button>
        </div>

        <!-- Enhanced Debug Panel -->
        <div id="debug-panel" class="fixed top-20 left-4 bg-black bg-opacity-90 text-white p-4 rounded-lg shadow-lg z-10 hidden max-w-sm">
            <h3 class="text-sm font-bold mb-3 text-yellow-400">🔧 Panorama Debug Panel</h3>
            <div class="text-xs space-y-2">
                <div class="bg-gray-800 p-2 rounded">
                    <div class="font-semibold text-blue-300">Current View:</div>
                    <div>Pitch: <span id="debug-pitch" class="text-green-400">0°</span> (Vertical: -90° to +90°)</div>
                    <div>Yaw: <span id="debug-yaw" class="text-green-400">0°</span> (Horizontal: -180° to +180°)</div>
                    <div>HFOV: <span id="debug-hfov" class="text-green-400">90°</span> (Zoom level)</div>
                </div>
                <div class="bg-gray-800 p-2 rounded">
                    <div class="font-semibold text-purple-300">Last Click:</div>
                    <div>Pitch: <span id="debug-click-pitch" class="text-yellow-400">None</span></div>
                    <div>Yaw: <span id="debug-click-yaw" class="text-yellow-400">None</span></div>
                    <div class="text-xs text-gray-400">Method: <span id="debug-click-method">-</span></div>
                </div>
                <div class="bg-gray-800 p-2 rounded">
                    <div class="font-semibold text-blue-300">Image Analysis:</div>
                    <div>Ratio: <span id="debug-image-ratio" class="text-orange-400">Loading...</span></div>
                </div>
                <div class="bg-gray-800 p-2 rounded">
                    <div class="font-semibold text-yellow-300">💡 Instructions:</div>
                    <div class="text-xs text-gray-300">
                        • Move around and watch coordinates
                        • Click coordinates show in console  
                        • Use these values for hotspots
                        • Ensure image is exactly 2:1 ratio
                        • For 360° photos only (not maps!)
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Hotspot Management Panel -->
    <div class="mt-6 bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 mx-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Hotspot Information & Management
        </h3>
        
        <!-- Hotspot Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400" id="total-hotspots">{{ $gameLocation->activeHotspots->count() }}</div>
                <div class="text-sm text-blue-600 dark:text-blue-400">Total Hotspots</div>
            </div>
            <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                <div class="text-2xl font-bold text-green-600 dark:text-green-400" id="visible-hotspots">{{ $gameLocation->activeHotspots->count() }}</div>
                <div class="text-sm text-green-600 dark:text-green-400">Visible Hotspots</div>
            </div>
            <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg">
                <div class="text-2xl font-bold text-purple-600 dark:text-purple-400" id="hotspot-mode-status">Disabled</div>
                <div class="text-sm text-purple-600 dark:text-purple-400">Hotspot Mode</div>
            </div>
        </div>
        
        <!-- Hotspot List -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Title</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">DB Coordinates</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Display Coordinates</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">System</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700" id="hotspots-table">
                    @foreach($gameLocation->activeHotspots as $hotspot)
                    <tr id="hotspot-row-{{ $hotspot->id }}" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white font-mono">{{ $hotspot->id }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $hotspot->title }}</td>
                        <td class="px-4 py-3 text-sm font-mono text-gray-600 dark:text-gray-400">
                            <div>P: {{ number_format($hotspot->pitch, 5) }}</div>
                            <div>Y: {{ number_format($hotspot->yaw, 5) }}</div>
                        </td>
                        <td class="px-4 py-3 text-sm font-mono text-blue-600 dark:text-blue-400" id="display-coords-{{ $hotspot->id }}">
                            <div class="loading">Calculating...</div>
                        </td>
                        <td class="px-4 py-3 text-xs" id="coord-system-{{ $hotspot->id }}">
                            <span class="loading px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">...</span>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <button onclick="locateHotspot({{ $hotspot->id }})" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 mr-2">
                                📍 Locate
                            </button>
                            <button onclick="deleteHotspot({{ $hotspot->id }})" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                🗑️ Delete  
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Hotspot Modal -->
    <div id="hotspot-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center" style="z-index: 10000;">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-2xl mx-4 max-h-screen overflow-y-auto">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Add New Hotspot</h3>
            <form id="hotspot-form" enctype="multipart/form-data">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Title</label>
                        <input type="text" id="hotspot-title" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                        <textarea id="hotspot-description" rows="3"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                    </div>
                    
                    <!-- Tour Features -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Hotspot Type</label>
                        <select id="hotspot-type" onchange="toggleTourFeatures()"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="info">📍 Information Point</option>
                            <option value="navigation">🚪 Navigation to Another Scene</option>
                            <option value="quiz">❓ Quiz/Assessment</option>
                        </select>
                    </div>
                    
                    <!-- Navigation Options -->
                    <div id="navigation-options" class="hidden">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Target Location</label>
                        <select id="target-location"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="">Select destination...</option>
                            @foreach($gameLocations as $location)
                                <option value="{{ $location->id }}">{{ $location->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <!-- Interactive Content Options -->
                    <div id="content-options" class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Rich Content</label>
                            <textarea id="rich-content" rows="2" placeholder="Additional content for popup (optional)"
                                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">📷 Information Image</label>
                            <input type="file" id="info-image" accept="image/*"
                                   class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400
                                          file:mr-4 file:py-2 file:px-4
                                          file:rounded-full file:border-0
                                          file:text-sm file:font-semibold
                                          file:bg-blue-50 file:text-blue-700
                                          hover:file:bg-blue-100
                                          dark:file:bg-blue-900 dark:file:text-blue-300">
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Upload an image to display with this information point (optional)</p>
                        </div>
                        <div id="image-preview" class="hidden">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Image Preview</label>
                            <div class="mt-1 relative">
                                <img id="preview-img" src="" alt="Preview" class="max-w-full h-32 object-cover rounded-lg border border-gray-300 dark:border-gray-600">
                                <button type="button" onclick="clearImagePreview()" 
                                        class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-red-600">×</button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quiz Options -->
                    <div id="quiz-options" class="hidden space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Question</label>
                            <textarea id="quiz-question" rows="2" placeholder="Enter your question..."
                                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Option A</label>
                                <input type="text" id="quiz-option-a" placeholder="First option"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Option B</label>
                                <input type="text" id="quiz-option-b" placeholder="Second option"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Option C</label>
                                <input type="text" id="quiz-option-c" placeholder="Third option"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Option D</label>
                                <input type="text" id="quiz-option-d" placeholder="Fourth option"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Correct Answer</label>
                                <select id="correct-answer"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <option value="A">Option A</option>
                                    <option value="B">Option B</option>
                                    <option value="C">Option C</option>
                                    <option value="D">Option D</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Points Value</label>
                                <input type="number" id="points-value" value="10" min="0"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Pitch</label>
                            <input type="number" id="hotspot-pitch" step="0.1" readonly
                                   class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm dark:bg-gray-600 dark:border-gray-600 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Yaw</label>
                            <input type="number" id="hotspot-yaw" step="0.1" readonly
                                   class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm dark:bg-gray-600 dark:border-gray-600 dark:text-white">
                        </div>
                    </div>
                </div>
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="closeHotspotModal()"
                            class="px-4 py-2 text-gray-600 hover:text-gray-800 dark:text-gray-300 dark:hover:text-white">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                        Add Hotspot
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let viewer = null;
        let hotspotsVisible = true;
        let hotspotMode = false;
        let pendingHotspotPosition = null;
        let config = {}; // Make config globally accessible
        
        // Make variables globally accessible for debugging
        window.hotspotMode = hotspotMode;
        
        console.log('🔧 Global variables initialized:');
        console.log('  - hotspotMode:', hotspotMode);
        console.log('  - window.hotspotMode:', window.hotspotMode);

        // Handle hotspot click events
        function handleHotspotClick(e) {
            console.log('🎯 Processing hotspot click...');
            
            // Show visual click indicator
            showClickIndicator(e.clientX, e.clientY);
            
            // Method 1: Use Pannellum's mouseEventToCoords for precise click location
            try {
                console.log('🔍 Attempting precise click coordinate conversion...');
                const coords = viewer.mouseEventToCoords(e);
                if (coords && coords.length >= 2 && !isNaN(coords[0]) && !isNaN(coords[1])) {
                    const pitch = parseFloat(coords[0]);  // mouseEventToCoords returns [pitch, yaw]
                    const yaw = parseFloat(coords[1]);     // mouseEventToCoords returns [pitch, yaw]
                    
                    // Validate coordinates are in reasonable ranges
                    if (pitch >= -90 && pitch <= 90 && yaw >= -180 && yaw <= 360) {
                        console.log('✅ PRECISE click coordinates:');
                        console.log('  - Yaw (horizontal):', yaw);
                        console.log('  - Pitch (vertical):', pitch);
                        console.log('  - Coordinate source: mouseEventToCoords (PRECISE)');
                        
                        // Update debug panel
                        updateClickDebugInfo(pitch, yaw, 'mouseEventToCoords (PRECISE)');
                        
                        addHotspotAtPosition(pitch, yaw);
                        return;
                    } else {
                        console.log('⚠️ mouseEventToCoords returned invalid coordinates:', pitch, yaw);
                    }
                }
            } catch (error) {
                console.log('⚠️ mouseEventToCoords failed:', error.message);
            }
            
            // Method 2: Calculate coordinates from mouse position manually
            try {
                console.log('🔄 Fallback: Manual coordinate calculation...');
                const panoramaContainer = document.getElementById('panorama-viewer');
                const rect = panoramaContainer.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                console.log('📐 Container rect:', rect.width, 'x', rect.height);
                console.log('📐 Click offset:', x, 'x', y);
                
                // Convert screen coordinates to panorama coordinates
                const hfov = viewer.getHfov();
                const currentPitch = viewer.getPitch();
                const currentYaw = viewer.getYaw();
                
                // Calculate normalized coordinates (-1 to 1)
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                const normalizedX = (x - centerX) / (rect.width / 2);
                const normalizedY = (centerY - y) / (rect.height / 2); // Inverted Y axis
                
                // Calculate field of view offsets
                const yawOffset = normalizedX * (hfov / 2);
                const vfov = hfov * (rect.height / rect.width); // Vertical FOV
                const pitchOffset = normalizedY * (vfov / 2);
                
                // Apply offsets to current view
                let targetYaw = currentYaw + yawOffset;
                let targetPitch = Math.max(-90, Math.min(90, currentPitch + pitchOffset)); // Clamp pitch
                
                // Normalize yaw to -180 to 180 range
                while (targetYaw > 180) targetYaw -= 360;
                while (targetYaw < -180) targetYaw += 360;
                
                console.log('🧮 Manual calculation:');
                console.log('  - Container:', rect.width, 'x', rect.height);
                console.log('  - Click position:', x, 'x', y);
                console.log('  - Normalized click:', normalizedX.toFixed(3), 'x', normalizedY.toFixed(3));
                console.log('  - Current view - Pitch:', currentPitch, 'Yaw:', currentYaw);
                console.log('  - HFOV:', hfov, 'VFOV:', vfov.toFixed(2));
                console.log('  - Offsets - Pitch:', pitchOffset.toFixed(2), 'Yaw:', yawOffset.toFixed(2));
                console.log('  - Target - Pitch:', targetPitch.toFixed(2), 'Yaw:', targetYaw.toFixed(2));
                console.log('  - Coordinate source: Manual calculation (IMPROVED)');
                
                // Validate final coordinates
                if (!isNaN(targetPitch) && !isNaN(targetYaw) && isFinite(targetPitch) && isFinite(targetYaw)) {
                    // Update debug panel
                    updateClickDebugInfo(targetPitch, targetYaw, 'Manual calculation (IMPROVED)');
                    
                    addHotspotAtPosition(targetPitch, targetYaw);
                } else {
                    console.error('❌ Manual calculation produced invalid coordinates:', targetPitch, targetYaw);
                    throw new Error('Invalid coordinates from manual calculation');
                }
                return;
            } catch (error) {
                console.log('❌ Manual calculation failed:', error.message);
            }
            
            // Method 3: Fallback to current view center (last resort)
            console.log('🔄 Final fallback: Using current view center...');
            try {
                const pitch = viewer.getPitch();
                const yaw = viewer.getYaw();
                
                console.log('📍 Fallback coordinates:');
                console.log('  - Pitch:', pitch);
                console.log('  - Yaw:', yaw);
                console.log('  - Coordinate source: View center (FALLBACK)');
                
                if (!isNaN(pitch) && !isNaN(yaw)) {
                    // Update debug panel
                    updateClickDebugInfo(pitch, yaw, 'View center (FALLBACK)');
                    
                    addHotspotAtPosition(pitch, yaw);
                    console.log('✅ Fallback method succeeded');
                } else {
                    console.error('❌ Even fallback method failed - viewer not ready');
                    alert('Error: Unable to get coordinates. Please try again after panorama loads completely.');
                }
            } catch (error) {
                console.error('❌ Critical error in fallback method:', error);
                alert('Error: Unable to capture coordinates. Please refresh the page and try again.');
            }
        }

        // Initialize panorama viewer
        document.addEventListener('DOMContentLoaded', function() {
            initializePanoramaViewer();
        });

        function initializePanoramaViewer() {
            const imageUrl = '{{ Storage::url($gameLocation->map_image_path) }}';
            
            config = {
                type: 'equirectangular',
                panorama: imageUrl,
                autoLoad: true,
                showControls: true,
                showFullscreenCtrl: false,
                showZoomCtrl: true,
                mouseZoom: true,
                keyboardZoom: true,
                draggable: true,
                // CRITICAL: Proper starting orientation for 360° photos
                default: {
                    pitch: {{ $gameLocation->default_pitch ?? 0 }},    // Default vertical angle (or 0 for horizon level)
                    yaw: {{ $gameLocation->default_yaw ?? 0 }},      // Default horizontal angle (or 0 for center)
                    hfov: 90     // Standard field of view
                },
                // HFOV settings optimized for 360° photos
                hfov: 90,
                minHfov: 50,
                maxHfov: 120,
                // Pitch constraints for 360° photos
                minPitch: -90,
                maxPitch: 90,
                // Auto-rotation for 360° experience
                autoRotate: -2,
                autoRotateInactivityDelay: 3000,
                autoRotateStopDelay: 2000,
                // ENHANCED DEBUG: Interactive coordinate finding
                hotSpotDebug: true,
                // Add compass for orientation reference
                compass: true,
                northOffset: 0,
                hotSpots: []
            };

            // Add existing hotspots from database
            console.log('📍 Loading existing hotspots from database...');
            console.log('🎮 GameLocation ID: {{ $gameLocation->id }}');
            console.log('📊 ActiveHotspots count from PHP: {{ $gameLocation->activeHotspots->count() }}');
            
            @if($gameLocation->activeHotspots->count() > 0)
                @foreach($gameLocation->activeHotspots as $hotspot)
                    // Smart coordinate detection and conversion
                    let pitchDegrees{{ $hotspot->id }}, yawDegrees{{ $hotspot->id }};
                    let coordinateSystem{{ $hotspot->id }};
                    
                    // Detect coordinate system based on value ranges
                    const rawPitch{{ $hotspot->id }} = {{ $hotspot->pitch }};
                    const rawYaw{{ $hotspot->id }} = {{ $hotspot->yaw }};
                    
                    // Check if values are in degree range (outside radian limits)
                    if (Math.abs(rawPitch{{ $hotspot->id }}) > 1.571 || Math.abs(rawYaw{{ $hotspot->id }}) > 3.142) {
                        // Values are likely in degrees already
                        pitchDegrees{{ $hotspot->id }} = rawPitch{{ $hotspot->id }};
                        yawDegrees{{ $hotspot->id }} = rawYaw{{ $hotspot->id }};
                        coordinateSystem{{ $hotspot->id }} = 'degrees (legacy)';
                    } else {
                        // Values are likely in radians - convert to degrees normally
                        pitchDegrees{{ $hotspot->id }} = rawPitch{{ $hotspot->id }} * (180 / Math.PI);
                        yawDegrees{{ $hotspot->id }} = rawYaw{{ $hotspot->id }} * (180 / Math.PI);
                        coordinateSystem{{ $hotspot->id }} = 'radians (converted)';
                    }
                    
                    // Validate final values are reasonable for panorama display
                    if (Math.abs(pitchDegrees{{ $hotspot->id }}) > 90 || Math.abs(yawDegrees{{ $hotspot->id }}) > 180) {
                        console.warn('⚠️ Invalid coordinates for hotspot {{ $hotspot->id }} after conversion - using defaults');
                        pitchDegrees{{ $hotspot->id }} = 0;
                        yawDegrees{{ $hotspot->id }} = 0;
                        coordinateSystem{{ $hotspot->id }} = 'defaulted (invalid)';
                    }
                    
                    console.log('📍 Loading hotspot {{ $hotspot->id }}:');
                    console.log('  - DB Values: pitch=' + rawPitch{{ $hotspot->id }} + ', yaw=' + rawYaw{{ $hotspot->id }});
                    console.log('  - System detected: ' + coordinateSystem{{ $hotspot->id }});
                    console.log('  - Final degrees: pitch=' + pitchDegrees{{ $hotspot->id }}.toFixed(2) + '°, yaw=' + yawDegrees{{ $hotspot->id }}.toFixed(2) + '°');
                    
                    // Enhanced Pannellum hotspot configuration with tour features
                    const hotspotConfig{{ $hotspot->id }} = {
                        id: 'hotspot-{{ $hotspot->id }}',
                        pitch: pitchDegrees{{ $hotspot->id }},
                        yaw: yawDegrees{{ $hotspot->id }},
                        type: 'info',
                        text: '{{ addslashes($hotspot->title) }}'
                    };
                    
                    // Add tour-specific click handler
                    @if($hotspot->getHotspotType() === 'info' && $hotspot->hasImage())
                        hotspotConfig{{ $hotspot->id }}.clickHandlerFunc = function() {
                            showInfoHotspotPopup({{ $hotspot->id }}, '{{ addslashes($hotspot->title) }}', '{{ addslashes($hotspot->getContent() ?? '') }}', '{{ $hotspot->getImageUrl() }}');
                        };
                    @endif
                    
                    config.hotSpots.push(hotspotConfig{{ $hotspot->id }});
                    console.log('📌 Added hotspot {{ $hotspot->id }} to Pannellum config: {{ $hotspot->title }} at (' + pitchDegrees{{ $hotspot->id }}.toFixed(2) + '°, ' + yawDegrees{{ $hotspot->id }}.toFixed(2) + '°)');
                    
                    // Update management panel with coordinate information
                    updateHotspotInfoPanel({{ $hotspot->id }}, pitchDegrees{{ $hotspot->id }}, yawDegrees{{ $hotspot->id }}, coordinateSystem{{ $hotspot->id }});
                @endforeach
            @else
                console.log('⚠️ No active hotspots found in database for this location');
            @endif
            
            console.log('📊 Total hotspots in config:', config.hotSpots.length);

            viewer = pannellum.viewer('panorama-viewer', config);
            
            console.log('🔍 Viewer initialized:', !!viewer);
            
            console.log('✅ Pannellum viewer ready - setting up click handling');
            
            // Set up click handling immediately (don't wait for load)
            const panoramaContainer = document.getElementById('panorama-viewer');
            if (panoramaContainer) {
                console.log('📍 Panorama container found, adding click listener');
                panoramaContainer.addEventListener('click', function(e) {
                    console.log('🎯 DOM click detected at screen coordinates:', e.clientX, e.clientY);
                    console.log('🎯 Hotspot mode active:', hotspotMode);
                    console.log('🎯 Hotspot mode type:', typeof hotspotMode);
                    console.log('🎯 Event target:', e.target);
                    console.log('🎯 Event type:', e.type);
                    
                    if (hotspotMode) {
                        console.log('✅ Entering hotspot coordinate capture...');
                        handleHotspotClick(e);
                    } else {
                        console.log('❌ Hotspot mode is not active - click ignored');
                    }
                });
            } else {
                console.error('❌ Panorama container not found!');
            }
            
            // Pannellum best practice: Comprehensive event handling
            viewer.on('load', function() {
                console.log('✅ Pannellum panorama loaded successfully');
                console.log('🎯 Current HFOV:', viewer.getHfov());
                console.log('🎯 Current Pitch:', viewer.getPitch());
                console.log('🎯 Current Yaw:', viewer.getYaw());
                console.log('🎯 Panorama is now fully loaded and ready for hotspot clicks');
            });

            // Pannellum best practice: Error handling
            viewer.on('error', function(err) {
                console.error('❌ Pannellum panorama error:', err);
                document.getElementById('panorama-viewer').innerHTML = 
                    '<div class="flex items-center justify-center h-full text-red-600">' +
                    '<i class="fas fa-exclamation-triangle mr-2"></i>' +
                    'Error loading panorama: ' + (err.message || 'Unknown error') +
                    '</div>';
            });

            // Pannellum best practice: Additional event tracking with debug updates
            viewer.on('zoomchange', function(hfov) {
                console.log('🔍 Zoom changed - New HFOV:', hfov);
                updateDebugInfo();
            });

            viewer.on('animatefinished', function(args) {
                console.log('🎬 Animation finished - Pitch:', args.pitch, 'Yaw:', args.yaw, 'HFOV:', args.hfov);
                updateDebugInfo();
            });

            // Real-time coordinate tracking for debug panel
            let debugUpdateInterval = setInterval(() => {
                if (debugPanelVisible && viewer) {
                    updateDebugInfo();
                }
            }, 100); // Update 10 times per second when panel is open
        }

        // Dark mode functionality
        function toggleDarkMode() {
            document.documentElement.classList.toggle('dark');
            const isDark = document.documentElement.classList.contains('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
        }

        // Initialize dark mode from localStorage
        const savedTheme = localStorage.getItem('theme') || 'light';
        if (savedTheme === 'dark') {
            document.documentElement.classList.add('dark');
        }

        // Fullscreen functionality
        function toggleFullscreen() {
            const element = document.getElementById('panorama-viewer');
            
            if (!document.fullscreenElement) {
                element.requestFullscreen().catch(e => console.log('Fullscreen error:', e));
            } else {
                document.exitFullscreen().catch(e => console.log('Exit fullscreen error:', e));
            }
        }

        // Toggle hotspots visibility
        function toggleHotspots() {
            hotspotsVisible = !hotspotsVisible;
            const button = document.querySelector('[onclick="toggleHotspots()"]');
            
            if (hotspotsVisible) {
                // Show hotspots using default styling
                @foreach($gameLocation->activeHotspots as $hotspot)
                    viewer.addHotSpot({
                        id: 'hotspot-{{ $hotspot->id }}',
                        pitch: {{ $hotspot->pitch }} * (180 / Math.PI),
                        yaw: {{ $hotspot->yaw }} * (180 / Math.PI),
                        type: 'info',
                        text: '{{ addslashes($hotspot->title) }}'
                    });
                @endforeach
                button.classList.remove('bg-purple-600', 'hover:bg-purple-700');
                button.classList.add('bg-yellow-600', 'hover:bg-yellow-700');
            } else {
                // Hide hotspots
                @foreach($gameLocation->activeHotspots as $hotspot)
                    viewer.removeHotSpot('hotspot-{{ $hotspot->id }}');
                @endforeach
                button.classList.remove('bg-yellow-600', 'hover:bg-yellow-700');
                button.classList.add('bg-purple-600', 'hover:bg-purple-700');
            }
        }

        // Toggle hotspot creation mode
        function toggleHotspotMode() {
            hotspotMode = !hotspotMode;
            window.hotspotMode = hotspotMode; // Keep window variable in sync
            console.log('🎯 Hotspot mode toggled:', hotspotMode);
            console.log('🎯 Window.hotspotMode synced:', window.hotspotMode);
            const button = document.querySelector('[onclick="toggleHotspotMode()"]');
            
            if (hotspotMode) {
                button.classList.remove('bg-orange-600', 'hover:bg-orange-700');
                button.classList.add('bg-red-600', 'hover:bg-red-700');
                button.innerHTML = '<i class="fas fa-times"></i>';
                button.title = 'Exit Add Mode';
                alert('HOTSPOT MODE ACTIVE\\n\\n1. Click anywhere on the panorama image\\n2. Modal will open with coordinates\\n3. Fill in title and description');
                console.log('✅ Hotspot mode enabled - position your view then click');
            } else {
                button.classList.remove('bg-red-600', 'hover:bg-red-700');
                button.classList.add('bg-orange-600', 'hover:bg-orange-700');
                button.innerHTML = '<i class="fas fa-plus"></i>';
                button.title = 'Add Hotspot Mode';
                console.log('❌ Hotspot mode disabled');
            }
        }

        // Reset panorama view
        function resetView() {
            if (viewer) {
                viewer.lookAt({{ $gameLocation->default_pitch ?? 0 }}, {{ $gameLocation->default_yaw ?? 0 }}, 90);
            }
        }

        // Debug panel functionality
        let debugPanelVisible = false;
        function toggleDebugPanel() {
            debugPanelVisible = !debugPanelVisible;
            const panel = document.getElementById('debug-panel');
            if (debugPanelVisible) {
                panel.classList.remove('hidden');
                updateDebugInfo();
            } else {
                panel.classList.add('hidden');
            }
        }

        // Update debug information
        function updateDebugInfo() {
            if (viewer && debugPanelVisible) {
                document.getElementById('debug-pitch').textContent = viewer.getPitch().toFixed(2) + '°';
                document.getElementById('debug-yaw').textContent = viewer.getYaw().toFixed(2) + '°';
                document.getElementById('debug-hfov').textContent = viewer.getHfov().toFixed(2) + '°';
                
                // Check image ratio
                const img = new Image();
                img.onload = function() {
                    const ratio = (this.width / this.height).toFixed(2);
                    const isEquirectangular = Math.abs(ratio - 2.0) < 0.1;
                    document.getElementById('debug-image-ratio').innerHTML = 
                        `${this.width}×${this.height} (${ratio}:1) ${isEquirectangular ? '✅' : '❌ Not 2:1!'}`;
                };
                img.src = '{{ Storage::url($gameLocation->map_image_path) }}';
            }
        }

        // Update debug panel with click coordinates
        function updateClickDebugInfo(pitch, yaw, method) {
            if (document.getElementById('debug-click-pitch')) {
                document.getElementById('debug-click-pitch').textContent = pitch.toFixed(2) + '°';
                document.getElementById('debug-click-yaw').textContent = yaw.toFixed(2) + '°';
                document.getElementById('debug-click-method').textContent = method;
            }
        }

        // Refresh hotspots from database (for verification)
        function refreshHotspots() {
            if (!viewer) {
                alert('Panorama viewer not initialized');
                return;
            }

            console.log('🔄 Refreshing hotspots from database...');
            
            // Clear existing hotspots
            const existingHotspots = document.querySelectorAll('.custom-admin-hotspot, .pnlm-hotspot-base');
            existingHotspots.forEach(hotspot => {
                if (hotspot.parentNode) {
                    hotspot.parentNode.removeChild(hotspot);
                }
            });

            // Reload the page to fetch fresh hotspots from database
            window.location.reload();
        }

        // Show visual click indicator
        function showClickIndicator(screenX, screenY) {
            // Create click indicator element
            const indicator = document.createElement('div');
            indicator.style.position = 'fixed';
            indicator.style.left = (screenX - 10) + 'px';
            indicator.style.top = (screenY - 10) + 'px';
            indicator.style.width = '20px';
            indicator.style.height = '20px';
            indicator.style.borderRadius = '50%';
            indicator.style.backgroundColor = '#ff6b6b';
            indicator.style.border = '3px solid white';
            indicator.style.zIndex = '9999';
            indicator.style.pointerEvents = 'none';
            indicator.style.animation = 'ping 1s cubic-bezier(0, 0, 0.2, 1) infinite';
            
            document.body.appendChild(indicator);
            
            // Remove after 2 seconds
            setTimeout(() => {
                if (indicator.parentNode) {
                    indicator.parentNode.removeChild(indicator);
                }
            }, 2000);
            
            console.log('📍 Click indicator shown at screen position:', screenX, screenY);
        }

        // Convert degrees to radians
        function degreesToRadians(degrees) {
            return degrees * (Math.PI / 180);
        }
        
        // Convert radians to degrees
        function radiansToDegrees(radians) {
            return radians * (180 / Math.PI);
        }

        // Add hotspot at clicked position
        function addHotspotAtPosition(pitch, yaw) {
            console.log('📋 Opening hotspot modal for position - RAW (degrees):', pitch, yaw);
            console.log('📊 Coordinate validation:');
            console.log('  - Pitch range check (-90 to 90):', pitch >= -90 && pitch <= 90);
            console.log('  - Yaw range check (-180 to 180):', yaw >= -180 && yaw <= 180);
            console.log('  - Pitch is numeric:', !isNaN(pitch));
            console.log('  - Yaw is numeric:', !isNaN(yaw));
            
            // Ensure coordinates are within valid ranges
            const validatedPitch = Math.max(-90, Math.min(90, parseFloat(pitch) || 0));
            const validatedYaw = parseFloat(yaw) || 0;
            
            // Normalize yaw to -180 to 180 range
            let normalizedYaw = validatedYaw;
            while (normalizedYaw > 180) normalizedYaw -= 360;
            while (normalizedYaw < -180) normalizedYaw += 360;
            
            console.log('🔧 Validated coordinates (degrees):');
            console.log('  - Original: Pitch(' + pitch + '), Yaw(' + yaw + ')');
            console.log('  - Validated: Pitch(' + validatedPitch + '), Yaw(' + normalizedYaw + ')');
            
            // Convert to radians for storage (database stores in radians)
            const pitchRadians = degreesToRadians(validatedPitch);
            const yawRadians = degreesToRadians(normalizedYaw);
            
            console.log('🔄 Converted to radians for database:');
            console.log('  - Pitch: ' + validatedPitch + '° = ' + pitchRadians + ' rad');
            console.log('  - Yaw: ' + normalizedYaw + '° = ' + yawRadians + ' rad');
            
            pendingHotspotPosition = { 
                pitch: pitchRadians,  // Store as radians 
                yaw: yawRadians       // Store as radians
            };
            
            console.log('💾 Stored coordinates (radians):', pendingHotspotPosition);
            
            // Populate form fields with coordinate information (degrees only for number inputs)
            const pitchField = document.getElementById('hotspot-pitch');
            const yawField = document.getElementById('hotspot-yaw');
            
            if (pitchField && yawField) {
                pitchField.value = validatedPitch.toFixed(2);  // Only numeric value for number input
                yawField.value = normalizedYaw.toFixed(2);     // Only numeric value for number input
                
                console.log('📝 Form fields populated with degrees:', {
                    pitch: pitchField.value + '°',
                    yaw: yawField.value + '°',
                    stored_radians: pendingHotspotPosition
                });
            } else {
                console.error('❌ Form fields not found:', {
                    pitchField: !!pitchField,
                    yawField: !!yawField
                });
            }
            
            const modal = document.getElementById('hotspot-modal');
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                console.log('✅ Modal found and classes updated');
                console.log('📋 Modal current classes:', modal.className);
                console.log('📋 Modal display style:', window.getComputedStyle(modal).display);
            } else {
                console.error('❌ Modal element not found! ID: hotspot-modal');
            }
        }

        // Close hotspot modal
        function closeHotspotModal() {
            document.getElementById('hotspot-modal').classList.add('hidden');
            document.getElementById('hotspot-modal').classList.remove('flex');
            document.getElementById('hotspot-form').reset();
            pendingHotspotPosition = null;
        }

        // Handle hotspot form submission
        document.getElementById('hotspot-form').addEventListener('submit', function(e) {
            e.preventDefault();
            console.log('📝 Form submitted');
            
            if (!pendingHotspotPosition) {
                console.error('❌ No pending hotspot position');
                return;
            }
            
            const hotspotType = document.getElementById('hotspot-type').value;
            
            // Use FormData for file upload support
            const formData = new FormData();
            formData.append('title', document.getElementById('hotspot-title').value);
            formData.append('description', document.getElementById('hotspot-description').value);
            formData.append('pitch', pendingHotspotPosition.pitch);
            formData.append('yaw', pendingHotspotPosition.yaw);
            formData.append('type', 'info');
            formData.append('hotspot_type', hotspotType);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

            // Add tour-specific data based on hotspot type
            if (hotspotType === 'navigation') {
                formData.append('target_location_id', document.getElementById('target-location').value);
                formData.append('rich_content', 'Navigate to another scene');
            } else if (hotspotType === 'quiz') {
                const quizData = {
                    question: document.getElementById('quiz-question').value,
                    options: {
                        a: document.getElementById('quiz-option-a').value,
                        b: document.getElementById('quiz-option-b').value,
                        c: document.getElementById('quiz-option-c').value,
                        d: document.getElementById('quiz-option-d').value
                    },
                    correct_answer: document.getElementById('correct-answer').value,
                    points: parseInt(document.getElementById('points-value').value) || 10
                };
                formData.append('quiz_data', JSON.stringify(quizData));
                formData.append('rich_content', 'Quiz: ' + quizData.question.substring(0, 50) + '...');
            } else {
                // Info type - add image and content
                formData.append('rich_content', document.getElementById('rich-content').value);
                
                // Add image file if selected
                const imageFile = document.getElementById('info-image').files[0];
                if (imageFile) {
                    formData.append('info_image', imageFile);
                    console.log('📷 Image file added:', imageFile.name, 'Size:', (imageFile.size / 1024).toFixed(2) + 'KB');
                }
            }
            
            console.log('📤 Sending data to server:', formData);
            console.log('📊 Final coordinate check before send:');
            console.log('  - Pitch (should match clicked location):', formData.pitch);
            console.log('  - Yaw (should match clicked location):', formData.yaw);
            
            const url = '/admin/panorama/{{ $gameLocation->id }}/hotspot';
            console.log('🌐 Making request to:', url);
            
            fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: formData // FormData automatically sets the correct Content-Type with boundary
            })
            .then(response => {
                console.log('📥 Response status:', response.status);
                if (!response.ok) {
                    console.error('❌ HTTP Error:', response.status, response.statusText);
                    throw new Error(`HTTP error! status: ${response.status} - ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('📥 Response data:', data);
                if (data.success) {
                    // Add hotspot to viewer
                    console.log('✅ Adding hotspot to viewer with data:', data.hotspot);
                    console.log('📊 Server returned coordinates - Pitch:', data.hotspot.pitch, 'Yaw:', data.hotspot.yaw);
                    
                    // Parse coordinates from server response (in radians) and convert to degrees
                    const serverPitchRadians = parseFloat(data.hotspot.pitch);
                    const serverYawRadians = parseFloat(data.hotspot.yaw);
                    const serverPitchDegrees = radiansToDegrees(serverPitchRadians);
                    const serverYawDegrees = radiansToDegrees(serverYawRadians);
                    
                    console.log('🔄 Server coordinates conversion:');
                    console.log('  - Server radians: pitch=' + serverPitchRadians + ', yaw=' + serverYawRadians);
                    console.log('  - Converted degrees: pitch=' + serverPitchDegrees.toFixed(2) + ', yaw=' + serverYawDegrees.toFixed(2));
                    console.log('  - Coordinates valid?', !isNaN(serverPitchDegrees) && !isNaN(serverYawDegrees));
                    
                    // Simple Pannellum hotspot configuration using defaults
                    const newHotspot = {
                        id: 'hotspot-' + data.hotspot.id,
                        pitch: serverPitchDegrees,
                        yaw: serverYawDegrees,
                        type: 'info',
                        text: data.hotspot.title
                    };
                    
                    console.log('🎯 CRITICAL - Hotspot placement coordinates:');
                    console.log('  - Server Pitch (degrees):', serverPitchDegrees, '(Expected range: -90 to 90)');
                    console.log('  - Server Yaw (degrees):', serverYawDegrees, '(Expected range: -180 to 180)');
                    console.log('  - Coordinate types:', typeof serverPitchDegrees, typeof serverYawDegrees);
                    console.log('  - Are coordinates finite?', isFinite(serverPitchDegrees), isFinite(serverYawDegrees));
                    
                    console.log('🎯 Hotspot config:', newHotspot);
                    
                    // Final validation before adding to viewer
                    console.log('🔍 COORDINATE VERIFICATION:');
                    console.log('  - Original click (degrees):', {
                        pitch: radiansToDegrees(pendingHotspotPosition.pitch), 
                        yaw: radiansToDegrees(pendingHotspotPosition.yaw)
                    });
                    console.log('  - Sent to server (radians):', formData.pitch, formData.yaw);
                    console.log('  - Received from server (radians):', data.hotspot.pitch, data.hotspot.yaw);
                    console.log('  - Converted for Pannellum (degrees):', serverPitchDegrees, serverYawDegrees);
                    console.log('  - Types after conversion:', typeof serverPitchDegrees, typeof serverYawDegrees);
                    
                    if (serverPitchDegrees === 0 && serverYawDegrees === 0) {
                        console.error('🚨 WARNING: Hotspot coordinates are (0,0) - this will place it in wrong location!');
                        console.error('🚨 Original click coordinates were:', pendingHotspotPosition);
                        console.error('🚨 Check if coordinates are being lost during save/retrieve process');
                    }
                    
                    try {
                        viewer.addHotSpot(newHotspot);
                        console.log('✅ Hotspot added to viewer successfully');
                        console.log('🎯 New hotspot should now be visible on the panorama');
                    } catch (e) {
                        console.error('❌ Error adding hotspot to viewer:', e);
                    }
                    
                    closeHotspotModal();
                    
                    // Show success message with database confirmation
                    const storedPitchRadians = data.hotspot.pitch;
                    const storedYawRadians = data.hotspot.yaw;
                    const storedPitchDegrees = radiansToDegrees(storedPitchRadians);
                    const storedYawDegrees = radiansToDegrees(storedYawRadians);
                    console.log('🎉 SUCCESS: Hotspot stored in database!');
                    console.log('📋 Database Record:', {
                        id: data.hotspot.id,
                        title: data.hotspot.title,
                        pitchRadians: storedPitchRadians,
                        yawRadians: storedYawRadians,
                        pitchDegrees: storedPitchDegrees,
                        yawDegrees: storedYawDegrees,
                        created_at: data.hotspot.created_at || 'just now'
                    });
                    
                    alert(`✅ Hotspot "${data.hotspot.title}" saved successfully!\n\n` +
                          `Database ID: ${data.hotspot.id}\n` +
                          `Stored Coordinates:\n` +
                          `• Pitch: ${storedPitchDegrees.toFixed(2)}° (${storedPitchRadians.toFixed(6)} rad)\n` +
                          `• Yaw: ${storedYawDegrees.toFixed(2)}° (${storedYawRadians.toFixed(6)} rad)\n\n` +
                          `The hotspot is now visible on the panorama and permanently saved in the database.`);
                } else {
                    console.error('❌ Server error:', data.message);
                    alert('Error adding hotspot: ' + data.message);
                }
            })
            .catch(error => {
                console.error('❌ Network error:', error);
                console.error('❌ Error details:', {
                    message: error.message,
                    stack: error.stack,
                    requestUrl: url,
                    formData: formData
                });
                
                // More helpful error message
                let errorMessage = 'Network error adding hotspot';
                if (error.message.includes('404')) {
                    errorMessage = 'Route not found - please check if you have admin permissions';
                } else if (error.message.includes('403')) {
                    errorMessage = 'Access denied - admin permissions required';
                } else if (error.message.includes('500')) {
                    errorMessage = 'Server error - check server logs for details';
                }
                
                alert(errorMessage + '\n\nTechnical details: ' + error.message);
            });
        });

        // Hotspot Information Panel Management Functions
        function updateHotspotInfoPanel(hotspotId, pitchDegrees, yawDegrees, coordinateSystem) {
            // Update display coordinates
            const displayCoordsEl = document.getElementById('display-coords-' + hotspotId);
            if (displayCoordsEl) {
                displayCoordsEl.innerHTML = 
                    '<div>P: ' + pitchDegrees.toFixed(2) + '°</div>' +
                    '<div>Y: ' + yawDegrees.toFixed(2) + '°</div>';
            }
            
            // Update coordinate system badge
            const coordSystemEl = document.getElementById('coord-system-' + hotspotId);
            if (coordSystemEl) {
                let badgeClass = 'px-2 py-1 rounded-full text-xs font-medium ';
                let systemText = coordinateSystem;
                
                if (coordinateSystem.includes('radians')) {
                    badgeClass += 'bg-green-100 dark:bg-green-900/20 text-green-600 dark:text-green-400';
                } else if (coordinateSystem.includes('degrees')) {
                    badgeClass += 'bg-yellow-100 dark:bg-yellow-900/20 text-yellow-600 dark:text-yellow-400';
                } else {
                    badgeClass += 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400';
                }
                
                coordSystemEl.innerHTML = '<span class="' + badgeClass + '">' + systemText + '</span>';
            }
        }

        // Locate hotspot function - pan camera to hotspot location
        function locateHotspot(hotspotId) {
            if (!viewer) {
                alert('Panorama viewer not ready');
                return;
            }
            
            // Find hotspot coordinates from the config
            const hotspot = config.hotSpots.find(h => h.id === 'hotspot-' + hotspotId);
            if (!hotspot) {
                alert('Hotspot not found');
                return;
            }
            
            // Animate camera to hotspot position
            viewer.lookAt(hotspot.pitch, hotspot.yaw, 90, 1000);
            
            // Highlight the row temporarily
            const row = document.getElementById('hotspot-row-' + hotspotId);
            if (row) {
                row.classList.add('bg-blue-100', 'dark:bg-blue-900/20');
                setTimeout(() => {
                    row.classList.remove('bg-blue-100', 'dark:bg-blue-900/20');
                }, 2000);
            }
            
            console.log('📍 Located hotspot', hotspotId, 'at', hotspot.pitch + '°,', hotspot.yaw + '°');
        }

        // Delete hotspot function
        function deleteHotspot(hotspotId) {
            if (!confirm('Are you sure you want to delete this hotspot?')) {
                return;
            }
            
            // Make delete request
            fetch('/admin/panorama/hotspot/' + hotspotId, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove from viewer
                    viewer.removeHotSpot('hotspot-' + hotspotId);
                    
                    // Remove from table
                    const row = document.getElementById('hotspot-row-' + hotspotId);
                    if (row) {
                        row.remove();
                    }
                    
                    // Update statistics
                    updateHotspotStats();
                    
                    console.log('✅ Hotspot deleted successfully');
                } else {
                    alert('Failed to delete hotspot: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Delete error:', error);
                alert('Failed to delete hotspot: ' + error.message);
            });
        }

        // Update hotspot statistics
        function updateHotspotStats() {
            const totalHotspots = document.querySelectorAll('#hotspots-table tr').length;
            document.getElementById('total-hotspots').textContent = totalHotspots;
            document.getElementById('visible-hotspots').textContent = hotspotsVisible ? totalHotspots : '0';
        }

        // Update hotspot mode status
        function updateHotspotModeStatus() {
            const statusEl = document.getElementById('hotspot-mode-status');
            if (statusEl) {
                statusEl.textContent = hotspotMode ? 'Enabled' : 'Disabled';
                statusEl.parentElement.className = hotspotMode ? 
                    'bg-green-50 dark:bg-green-900/20 p-4 rounded-lg' : 
                    'bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg';
            }
        }

        // Toggle tour feature form sections
        function toggleTourFeatures() {
            const hotspotType = document.getElementById('hotspot-type').value;
            const navigationOptions = document.getElementById('navigation-options');
            const contentOptions = document.getElementById('content-options');
            const quizOptions = document.getElementById('quiz-options');
            
            // Hide all options first
            if (navigationOptions) navigationOptions.classList.add('hidden');
            if (quizOptions) quizOptions.classList.add('hidden');
            
            // Show relevant options based on type
            switch(hotspotType) {
                case 'navigation':
                    if (navigationOptions) navigationOptions.classList.remove('hidden');
                    if (contentOptions) contentOptions.classList.add('hidden');
                    break;
                case 'quiz':
                    if (quizOptions) quizOptions.classList.remove('hidden');
                    if (contentOptions) contentOptions.classList.add('hidden');
                    break;
                case 'info':
                default:
                    if (contentOptions) contentOptions.classList.remove('hidden');
                    break;
            }
        }

        // Image preview functionality
        function setupImagePreview() {
            const imageInput = document.getElementById('info-image');
            if (imageInput) {
                imageInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file && file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const previewContainer = document.getElementById('image-preview');
                            const previewImg = document.getElementById('preview-img');
                            
                            previewImg.src = e.target.result;
                            previewContainer.classList.remove('hidden');
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }
        }

        // Clear image preview
        function clearImagePreview() {
            const imageInput = document.getElementById('info-image');
            const previewContainer = document.getElementById('image-preview');
            const previewImg = document.getElementById('preview-img');
            
            imageInput.value = '';
            previewImg.src = '';
            previewContainer.classList.add('hidden');
        }

        // Show info hotspot popup with image
        function showInfoHotspotPopup(hotspotId, title, content, imageUrl) {
            console.log('📷 Showing info hotspot popup:', { hotspotId, title, content, imageUrl });
            
            // Create popup content
            let popupContent = `
                <div class="max-w-md bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6">
                    <div class="flex items-start justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">${title}</h3>
                        <button onclick="closeInfoPopup()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>`;
            
            // Add image if available
            if (imageUrl) {
                popupContent += `
                    <div class="mb-4">
                        <img src="${imageUrl}" alt="${title}" class="w-full h-48 object-cover rounded-lg border border-gray-200 dark:border-gray-600">
                    </div>`;
            }
            
            // Add content if available
            if (content && content.trim()) {
                popupContent += `
                    <div class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                        ${content.replace(/\n/g, '<br>')}
                    </div>`;
            }
            
            popupContent += '</div>';
            
            // Create popup overlay
            const overlay = document.createElement('div');
            overlay.id = 'info-popup-overlay';
            overlay.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            overlay.innerHTML = popupContent;
            
            // Close on overlay click
            overlay.addEventListener('click', function(e) {
                if (e.target === overlay) {
                    closeInfoPopup();
                }
            });
            
            document.body.appendChild(overlay);
        }

        // Close info popup
        function closeInfoPopup() {
            const overlay = document.getElementById('info-popup-overlay');
            if (overlay) {
                overlay.remove();
            }
        }

        // Initialize management panel
        document.addEventListener('DOMContentLoaded', function() {
            updateHotspotStats();
            setInterval(updateHotspotModeStatus, 1000); // Update status every second
            
            // Initialize tour feature toggle
            toggleTourFeatures();
            
            // Setup image preview
            setupImagePreview();
        });
    </script>
</body>
</html>