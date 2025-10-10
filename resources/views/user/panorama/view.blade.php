<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
    
    <!-- Pannellum CSS & JS -->
    <link href="{{ asset('js/pannellum/pannellum.css') }}" rel="stylesheet">
    <script src="{{ asset('js/pannellum/pannellum.js') }}"></script>
    
    <style>
        /* Standard Pannellum hotspot styling - ensure visibility and proper sprite */
        .pnlm-hotspot-base {
            position: absolute !important;
            visibility: visible !important;  /* FIXED: Make sure base is visible */
            cursor: default !important;
            vertical-align: middle !important;
            top: 0 !important;
            z-index: 1 !important;
        }

        .pnlm-hotspot {
            height: 26px !important;
            width: 26px !important;
            border-radius: 13px !important;
            cursor: pointer !important;
            visibility: visible !important;  /* FIXED: Ensure hotspots are visible */
            opacity: 1 !important;           /* FIXED: Make sure they're not transparent */
            display: block !important;       /* FIXED: Make sure they're displayed */
            background-image: url('data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2226%22%20height%3D%22208%22%3E%0A%3Ccircle%20fill-opacity%3D%22.78%22%20cy%3D%22117%22%20cx%3D%2213%22%20r%3D%2211%22%20fill%3D%22%23fff%22%2F%3E%0A%3Ccircle%20fill-opacity%3D%22.78%22%20cy%3D%22143%22%20cx%3D%2213%22%20r%3D%2211%22%20fill%3D%22%23fff%22%2F%3E%0A%3Ccircle%20cy%3D%22169%22%20cx%3D%2213%22%20r%3D%227%22%20fill%3D%22none%22%20stroke%3D%22%23000%22%20stroke-width%3D%222%22%2F%3E%0A%3Ccircle%20cy%3D%22195%22%20cx%3D%2213%22%20r%3D%227%22%20fill%3D%22none%22%20stroke%3D%22%23000%22%20stroke-width%3D%222%22%2F%3E%0A%3Ccircle%20cx%3D%2213%22%20cy%3D%22195%22%20r%3D%222.5%22%2F%3E%0A%3Cpath%20d%3D%22m5%2083v6h2v-4h4v-2zm10%200v2h4v4h2v-6zm-5%205v6h6v-6zm-5%205v6h6v-2h-4v-4zm14%200v4h-4v2h6v-6z%22%2F%3E%0A%3Cpath%20d%3D%22m13%20110a7%207%200%200%200%20-7%207%207%207%200%200%200%207%207%207%207%200%200%200%207%20-7%207%207%200%200%200%20-7%20-7zm-1%203h2v2h-2zm0%203h2v5h-2z%22%2F%3E%0A%3Cpath%20d%3D%22m5%2057v6h2v-4h4v-2zm10%200v2h4v4h2v-6zm-10%2010v6h6v-2h-4v-4zm14%200v4h-4v2h6v-6z%22%2F%3E%0A%3Cpath%20d%3D%22m17%2038v2h-8v-2z%22%2F%3E%0A%3Cpath%20d%3D%22m12%209v3h-3v2h3v3h2v-3h3v-2h-3v-3z%22%2F%3E%0A%3Cpath%20d%3D%22m13%20136-6.125%206.125h4.375v7.875h3.5v-7.875h4.375z%22%2F%3E%0A%3Cpath%20d%3D%22m10.428%20173.33v-5.77l5-2.89v5.77zm1-1.73%203-1.73-3.001-1.74z%22%2F%3E%0A%3C%2Fsvg%3E%0A') !important;
            background-repeat: no-repeat !important;
        }

        .pnlm-hotspot:hover {
            background-color: rgba(255,255,255,0.2) !important;
        }

        /* Standard Pannellum info hotspots - official sprite background */
        .pnlm-hotspot.pnlm-info {
            background-position: 0 -104px !important;
        }

        /* Standard Pannellum scene hotspots - official sprite background */  
        .pnlm-hotspot.pnlm-scene {
            background-position: 0 -130px !important;
        }

        /* Legacy class for backward compatibility */
        .user-hotspot {
            background: linear-gradient(135deg, #3b82f6, #1e40af) !important;
            border: 3px solid white !important;
            border-radius: 50% !important;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4) !important;
            animation: pulse-hotspot 2s infinite !important;
            width: 20px !important;
            height: 20px !important;
            cursor: pointer !important;
        }

        @keyframes pulse-hotspot {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.9; }
            100% { transform: scale(1); opacity: 1; }
        }

        @keyframes bounce-navigation {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-8px); }
            60% { transform: translateY(-4px); }
        }

        @keyframes glow-quiz {
            0% { 
                transform: scale(1);
                box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
            }
            50% { 
                transform: scale(1.05);
                box-shadow: 0 6px 20px rgba(245, 158, 11, 0.7);
            }
            100% { 
                transform: scale(1);
                box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
            }
        }
        
        /* Fullscreen styles */
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
                    <button onclick="history.back()" class="text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back
                    </button>
                </div>
                <div class="text-center">
                    <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                        {{ $gameLocation->name }}
                    </h1>
                </div>
                <div class="flex items-center space-x-3">
                    <button onclick="debugUserPanoramaState()" 
                            class="p-2 text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white"
                            title="Show View Coordinates">
                        <i class="fas fa-crosshairs"></i>
                    </button>
                    <button onclick="toggleFullscreen()" 
                            class="p-2 text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white"
                            title="Toggle Fullscreen">
                        <i class="fas fa-expand"></i>
                    </button>
                    <button onclick="toggleDarkMode()" 
                            class="p-2 text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white"
                            title="Toggle Dark Mode">
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
        
        <!-- Coordinate Display -->
        <div id="user-coordinates" class="fixed top-20 left-4 z-20"></div>
        
        <!-- Floating Controls -->
        <div id="floating-controls" class="fixed bottom-4 right-4 flex flex-col space-y-2 z-10">
            <button onclick="resetView()" 
                    class="bg-blue-600 hover:bg-blue-700 text-white p-3 rounded-lg shadow-lg transition-colors duration-200"
                    title="Reset View">
                <i class="fas fa-home"></i>
            </button>
            <button onclick="toggleHotspots()" 
                    class="bg-green-600 hover:bg-green-700 text-white p-3 rounded-lg shadow-lg transition-colors duration-200"
                    title="Toggle Hotspots">
                <i class="fas fa-map-marker-alt"></i>
            </button>
        </div>
    </main>

    <!-- Tour Feature Popups (will be dynamically created) -->
    <!-- Info popup with image support -->
    <!-- Quiz popup with questions -->  
    <!-- Navigation popup with confirmation -->

    <script>
        let viewer = null;
        let hotspotsVisible = true;
        let hotspotsData = [];

        // Initialize panorama viewer
        document.addEventListener('DOMContentLoaded', function() {
            initializeUserPanoramaViewer();
        });

        function initializeUserPanoramaViewer() {
            const imageUrl = '{{ Storage::url($gameLocation->map_image_path) }}';
            
            const config = {
                type: 'equirectangular',
                panorama: imageUrl,
                autoLoad: true,
                showControls: true,
                showFullscreenCtrl: false,
                showZoomCtrl: true,
                mouseZoom: true,
                keyboardZoom: true,
                draggable: true,
                // Use default pitch/yaw from database
                default: {
                    pitch: {{ $gameLocation->default_pitch ?? 0 }},
                    yaw: {{ $gameLocation->default_yaw ?? 0 }},
                    hfov: 90
                },
                hfov: 90,
                minHfov: 50,
                maxHfov: 120,
                minPitch: -90,
                maxPitch: 90,
                // Smooth auto-rotation for immersive experience
                autoRotate: -1,
                autoRotateInactivityDelay: 5000,
                autoRotateStopDelay: 1000,
                hotSpots: []
            };

            // Add existing hotspots from database with tour features
            
            @if($gameLocation->activeHotspots->count() > 0)
                @foreach($gameLocation->activeHotspots as $hotspot)
                    // USE MODEL'S toPannellumConfig() method for perfect coordinate conversion
                    const hotspotConfig{{ $hotspot->id }} = {!! json_encode($hotspot->toPannellumConfig()) !!};
                    
                    // Override with standard Pannellum configuration - let Pannellum handle CSS classes
                    hotspotConfig{{ $hotspot->id }}.type = 'info';  // Standard Pannellum info type
                    delete hotspotConfig{{ $hotspot->id }}.cssClass;  // Remove custom class, let Pannellum use defaults
                    
                    // Add tour-specific click handler with image support
                    @if($hotspot->getHotspotType() === 'navigation')
                        hotspotConfig{{ $hotspot->id }}.clickHandlerFunc = function() {
                            handleNavigationHotspot({{ $hotspot->id }}, {{ $hotspot->getTargetLocationId() ?? 'null' }}, {!! json_encode($hotspot->title) !!});
                        };
                    @elseif($hotspot->getHotspotType() === 'quiz')
                        hotspotConfig{{ $hotspot->id }}.clickHandlerFunc = function() {
                            handleQuizHotspot({{ $hotspot->id }}, @json($hotspot->getQuizData()), {!! json_encode($hotspot->title) !!});
                        };
                    @else
                        hotspotConfig{{ $hotspot->id }}.clickHandlerFunc = function() {
                            showUserInfoPopup({{ $hotspot->id }}, {!! json_encode($hotspot->title) !!}, {!! json_encode($hotspot->getContent() ?? $hotspot->description ?? '') !!}, {!! $hotspot->hasImage() ? json_encode($hotspot->getImageUrl()) : 'null' !!});
                        };
                    @endif
                    
                    config.hotSpots.push(hotspotConfig{{ $hotspot->id }});
                @endforeach
            @endif

            viewer = pannellum.viewer('panorama-viewer', config);
            
            // Add click listener for coordinate detection (like admin side)
            viewer.on('click', function(event) {
                updateCoordinateDisplay(event.pitch, event.yaw);
            });
            
            // Event handling
            viewer.on('load', function() {
                // Panorama loaded successfully
            });

            viewer.on('error', function(err) {
                document.getElementById('panorama-viewer').innerHTML = 
                    '<div class="flex items-center justify-center h-full text-red-600 text-center p-4">' +
                    '<div><i class="fas fa-exclamation-triangle text-4xl mb-4"></i><br>' +
                    'Unable to load 360° view<br><span class="text-sm">' + (err.message || 'Please try again later') + '</span></div>' +
                    '</div>';
            });
        }

        // Get CSS class based on hotspot type for users
        function getUserHotspotCssClass(hotspotType) {
            switch (hotspotType) {
                case 'navigation':
                    return 'user-hotspot-navigation';
                case 'quiz':
                    return 'user-hotspot-quiz';
                case 'info':
                default:
                    return 'user-hotspot-info';
            }
        }

        // Update coordinate display (like admin side)
        function updateCoordinateDisplay(pitch, yaw) {
            const coordDisplay = document.getElementById('user-coordinates');
            if (coordDisplay) {
                coordDisplay.innerHTML = `
                    <div class="bg-gray-900 bg-opacity-75 text-white px-3 py-2 rounded-lg text-sm">
                        <div class="font-medium">Current View</div>
                        <div>Pitch: ${pitch.toFixed(2)}° | Yaw: ${yaw.toFixed(2)}°</div>
                    </div>
                `;
                
                // Auto-hide after 3 seconds
                setTimeout(() => {
                    if (coordDisplay) {
                        coordDisplay.innerHTML = '';
                    }
                }, 3000);
            }
        }

        // Get current view angles (like admin side)
        function getCurrentViewAngles() {
            if (viewer) {
                return {
                    pitch: viewer.getPitch(),
                    yaw: viewer.getYaw(),
                    hfov: viewer.getHfov()
                };
            }
            return null;
        }

        // Debug function to show current panorama state
        function debugUserPanoramaState() {
            const angles = getCurrentViewAngles();
            if (angles) {
                updateCoordinateDisplay(angles.pitch, angles.yaw);
            }
        }
        
        // Add to window for easy console access
        window.debugUserPanoramaState = debugUserPanoramaState;
        window.getCurrentViewAngles = getCurrentViewAngles;

        // Show user info popup with image support
        function showUserInfoPopup(hotspotId, title, content, imageUrl) {
            
            // Create popup content
            let popupContent = `
                <div class="max-w-lg bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 m-4">
                    <div class="flex items-start justify-between mb-4">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white">${title}</h3>
                        <button onclick="closeUserPopup()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 ml-4">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>`;
            
            // Add image if available
            if (imageUrl) {
                popupContent += `
                    <div class="mb-4">
                        <img src="${imageUrl}" alt="${title}" class="w-full h-56 object-cover rounded-lg border border-gray-200 dark:border-gray-600 shadow-sm">
                    </div>`;
            }
            
            // Add content if available
            if (content && content.trim()) {
                popupContent += `
                    <div class="text-gray-700 dark:text-gray-300 leading-relaxed mb-4">
                        ${content.replace(/\n/g, '<br>')}
                    </div>`;
            }
            
            popupContent += `
                    <div class="flex justify-end">
                        <button onclick="closeUserPopup()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                            Close
                        </button>
                    </div>
                </div>`;
            
            showUserPopupOverlay(popupContent);
        }

        // Handle navigation hotspot
        function handleNavigationHotspot(hotspotId, targetLocationId, title) {
            
            if (!targetLocationId) {
                showUserInfoPopup(hotspotId, title, 'Navigation target not configured.', null);
                return;
            }
            
            // Show confirmation popup
            const popupContent = `
                <div class="max-w-md bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 m-4">
                    <div class="text-center">
                        <div class="mb-4">
                            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 dark:bg-green-900/30">
                                <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                                </svg>
                            </div>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Navigate to Location</h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-6">${title}</p>
                        <div class="flex justify-center space-x-3">
                            <button onclick="closeUserPopup()" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg transition-colors">
                                Cancel
                            </button>
                            <button onclick="navigateToLocation(${targetLocationId})" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors">
                                Go There
                            </button>
                        </div>
                    </div>
                </div>`;
            
            showUserPopupOverlay(popupContent);
        }

        // Handle quiz hotspot
        function handleQuizHotspot(hotspotId, quizData, title) {
            
            if (!quizData || !quizData.question) {
                showUserInfoPopup(hotspotId, title, 'Quiz data not available.', null);
                return;
            }
            
            // Show quiz popup
            let optionsHtml = '';
            if (quizData.options) {
                Object.keys(quizData.options).forEach(key => {
                    if (quizData.options[key]) {
                        optionsHtml += `
                            <button onclick="submitQuizAnswer(${hotspotId}, '${key}', '${quizData.correct_answer}')" 
                                    class="w-full text-left bg-gray-50 dark:bg-gray-700 hover:bg-yellow-50 dark:hover:bg-yellow-900/30 p-3 rounded-lg border border-gray-200 dark:border-gray-600 transition-colors mb-2">
                                <span class="font-medium">${key.toUpperCase()}.</span> ${quizData.options[key]}
                            </button>`;
                    }
                });
            }
            
            const popupContent = `
                <div class="max-w-lg bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 m-4">
                    <div class="text-center mb-6">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 dark:bg-yellow-900/30 mb-4">
                            <svg class="h-6 w-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">${title}</h3>
                    </div>
                    <div class="mb-6">
                        <p class="text-gray-700 dark:text-gray-300 mb-4 font-medium">${quizData.question}</p>
                        <div class="space-y-2">
                            ${optionsHtml}
                        </div>
                    </div>
                    <div class="text-center">
                        <button onclick="closeUserPopup()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 text-sm">
                            Close Quiz
                        </button>
                    </div>
                </div>`;
            
            showUserPopupOverlay(popupContent);
        }

        // Submit quiz answer
        function submitQuizAnswer(hotspotId, selectedAnswer, correctAnswer) {
            const isCorrect = selectedAnswer === correctAnswer;
            
            // Show result
            const resultHtml = `
                <div class="max-w-md bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 m-4">
                    <div class="text-center">
                        <div class="mb-4">
                            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full ${isCorrect ? 'bg-green-100 dark:bg-green-900/30' : 'bg-red-100 dark:bg-red-900/30'}">
                                ${isCorrect ? 
                                    '<svg class="h-8 w-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>' :
                                    '<svg class="h-8 w-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>'
                                }
                            </div>
                        </div>
                        <h3 class="text-xl font-semibold ${isCorrect ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200'} mb-2">
                            ${isCorrect ? '🎉 Correct!' : '❌ Incorrect'}
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-6">
                            ${isCorrect ? 
                                'Great job! You got it right.' : 
                                `The correct answer was ${correctAnswer.toUpperCase()}.`
                            }
                        </p>
                        <button onclick="closeUserPopup()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors">
                            Continue
                        </button>
                    </div>
                </div>`;
            
            // Update popup content
            const overlay = document.getElementById('user-popup-overlay');
            if (overlay) {
                overlay.innerHTML = resultHtml;
            }
        }

        // Navigate to another location
        function navigateToLocation(targetLocationId) {
            closeUserPopup();
            
            // Redirect to the target location
            window.location.href = `/user/panorama/${targetLocationId}`;
        }

        // Show user popup overlay
        function showUserPopupOverlay(content) {
            // Create popup overlay
            const overlay = document.createElement('div');
            overlay.id = 'user-popup-overlay';
            overlay.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4';
            overlay.innerHTML = content;
            
            // Close on overlay click
            overlay.addEventListener('click', function(e) {
                if (e.target === overlay) {
                    closeUserPopup();
                }
            });
            
            document.body.appendChild(overlay);
        }

        // Close user popup
        function closeUserPopup() {
            const overlay = document.getElementById('user-popup-overlay');
            if (overlay) {
                overlay.remove();
            }
        }

        // Reset panorama view to default position
        function resetView() {
            if (viewer) {
                viewer.lookAt({{ $gameLocation->default_pitch ?? 0 }}, {{ $gameLocation->default_yaw ?? 0 }}, 90);
            }
        }

        // Toggle hotspots visibility
        function toggleHotspots() {
            if (!viewer) return;
            
            hotspotsVisible = !hotspotsVisible;
            const button = document.querySelector('[onclick="toggleHotspots()"]');
            
            if (hotspotsVisible) {
                // Show hotspots
                hotspotsData.forEach(hotspot => {
                    try {
                        viewer.addHotSpot(hotspot);
                    } catch (e) {
                        console.warn('Failed to show hotspot:', e);
                    }
                });
                button.classList.remove('bg-green-600', 'hover:bg-green-700');
                button.classList.add('bg-orange-600', 'hover:bg-orange-700');
                button.title = 'Hide Hotspots';
            } else {
                // Hide hotspots
                hotspotsData.forEach(hotspot => {
                    try {
                        viewer.removeHotSpot(hotspot.id);
                    } catch (e) {
                        console.warn('Failed to hide hotspot:', e);
                    }
                });
                button.classList.remove('bg-orange-600', 'hover:bg-orange-700');
                button.classList.add('bg-green-600', 'hover:bg-green-700');
                button.title = 'Show Hotspots';
            }
        }

        // Fullscreen functionality
        function toggleFullscreen() {
            const element = document.getElementById('panorama-viewer');
            
            if (!document.fullscreenElement) {
                element.requestFullscreen().catch(e => {});
            } else {
                document.exitFullscreen().catch(e => {});
            }
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
    </script>
</body>
</html>