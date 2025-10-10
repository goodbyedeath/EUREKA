@extends('layouts.app')

@section('title', 'Offline Functionality Test')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">
                🧪 PWA Offline Functionality Test
            </h1>
            
            <!-- Status Panel -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                    <h3 class="font-semibold text-blue-900 dark:text-blue-100">Connection Status</h3>
                    <p id="connection-status" class="text-blue-700 dark:text-blue-300">Checking...</p>
                </div>
                
                <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                    <h3 class="font-semibold text-green-900 dark:text-green-100">Service Worker</h3>
                    <p id="sw-status" class="text-green-700 dark:text-green-300">Checking...</p>
                </div>
                
                <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg">
                    <h3 class="font-semibold text-purple-900 dark:text-purple-100">IndexedDB</h3>
                    <p id="db-status" class="text-purple-700 dark:text-purple-300">Checking...</p>
                </div>
                
                <div class="bg-orange-50 dark:bg-orange-900/20 p-4 rounded-lg">
                    <h3 class="font-semibold text-orange-900 dark:text-orange-100">Cache API</h3>
                    <p id="cache-status" class="text-orange-700 dark:text-orange-300">Checking...</p>
                </div>
            </div>

            <!-- Test Actions -->
            <div class="space-y-6">
                <div class="border-t pt-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        🔧 Test Actions
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <button id="test-cache-assets" 
                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition-colors">
                            Test Asset Caching
                        </button>
                        
                        <button id="test-offline-data" 
                                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md transition-colors">
                            Test Offline Data Storage
                        </button>
                        
                        <button id="test-offline-form" 
                                class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-md transition-colors">
                            Test Offline Form Submission
                        </button>
                        
                        <button id="simulate-offline" 
                                class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md transition-colors">
                            Simulate Offline Mode
                        </button>
                        
                        <button id="clear-database" 
                                class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-md transition-colors">
                            Clear Database
                        </button>
                    </div>
                </div>

                <!-- Test Results -->
                <div class="border-t pt-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        📊 Test Results
                    </h2>
                    
                    <div id="test-results" class="bg-gray-50 dark:bg-gray-700 p-4 rounded-md min-h-32 max-h-64 overflow-y-auto">
                        <p class="text-gray-600 dark:text-gray-400">Test results will appear here...</p>
                    </div>
                </div>

                <!-- Offline Features Test -->
                <div class="border-t pt-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        📱 Offline Feature Tests
                    </h2>
                    
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded">
                            <span>Dashboard Navigation (Offline)</span>
                            <button onclick="testOfflineNavigation('/user/dashboard')" 
                                    class="text-blue-600 hover:text-blue-800 dark:text-blue-400">
                                Test
                            </button>
                        </div>
                        
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded">
                            <span>Quiz Page (Offline)</span>
                            <button onclick="testOfflineNavigation('/quiz/1')" 
                                    class="text-blue-600 hover:text-blue-800 dark:text-blue-400">
                                Test
                            </button>
                        </div>
                        
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded">
                            <span>Team Registration (Offline)</span>
                            <button onclick="testOfflineNavigation('/team-registration')" 
                                    class="text-blue-600 hover:text-blue-800 dark:text-blue-400">
                                Test
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Performance Metrics -->
                <div class="border-t pt-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        ⚡ Performance Metrics
                    </h2>
                    
                    <div id="performance-metrics" class="bg-gray-50 dark:bg-gray-700 p-4 rounded-md">
                        <p class="text-gray-600 dark:text-gray-400">Run tests to see performance metrics...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/js/offline-manager.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Update status indicators
    function updateStatus() {
        // Connection status
        document.getElementById('connection-status').textContent = 
            navigator.onLine ? '🟢 Online' : '🔴 Offline';
        
        // Service Worker status
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.ready.then(() => {
                document.getElementById('sw-status').textContent = '🟢 Active';
            }).catch(() => {
                document.getElementById('sw-status').textContent = '🔴 Error';
            });
        } else {
            document.getElementById('sw-status').textContent = '❌ Not Supported';
        }
        
        // IndexedDB status
        if ('indexedDB' in window) {
            document.getElementById('db-status').textContent = '🟢 Available';
        } else {
            document.getElementById('db-status').textContent = '❌ Not Supported';
        }
        
        // Cache API status
        if ('caches' in window) {
            document.getElementById('cache-status').textContent = '🟢 Available';
        } else {
            document.getElementById('cache-status').textContent = '❌ Not Supported';
        }
    }

    // Log test results
    function logResult(message, type = 'info') {
        const results = document.getElementById('test-results');
        const timestamp = new Date().toLocaleTimeString();
        const colorClass = {
            'info': 'text-blue-600 dark:text-blue-400',
            'success': 'text-green-600 dark:text-green-400',
            'error': 'text-red-600 dark:text-red-400',
            'warning': 'text-yellow-600 dark:text-yellow-400'
        }[type];
        
        const logEntry = document.createElement('p');
        logEntry.className = `text-sm ${colorClass} mb-1`;
        logEntry.textContent = `[${timestamp}] ${message}`;
        
        results.appendChild(logEntry);
        results.scrollTop = results.scrollHeight;
    }

    // Test asset caching
    document.getElementById('test-cache-assets').addEventListener('click', async function() {
        logResult('Testing asset caching...', 'info');
        
        try {
            const testAssets = ['/css/app.css', '/js/app.js', '/images/logo.png'];
            const results = [];
            
            for (const asset of testAssets) {
                const start = performance.now();
                const response = await fetch(asset);
                const end = performance.now();
                
                results.push({
                    asset: asset,
                    cached: response.headers.get('cache-control') !== null,
                    loadTime: Math.round(end - start)
                });
            }
            
            results.forEach(result => {
                logResult(`${result.asset}: ${result.cached ? '✅' : '❌'} (${result.loadTime}ms)`, 
                         result.cached ? 'success' : 'warning');
            });
            
        } catch (error) {
            logResult(`Asset caching test failed: ${error.message}`, 'error');
        }
    });

    // Test offline data storage
    document.getElementById('test-offline-data').addEventListener('click', async function() {
        logResult('Testing offline data storage...', 'info');
        
        try {
            if (window.offlineManager) {
                // Test storing data
                const testData = { id: 1, name: 'Test User', timestamp: Date.now() };
                await window.offlineManager.storeData('userData', 'test-user', testData);
                logResult('✅ Data stored successfully', 'success');
                
                // Test retrieving data
                const retrievedData = await window.offlineManager.getData('userData', 'test-user');
                if (retrievedData && retrievedData.id === testData.id) {
                    logResult('✅ Data retrieved successfully', 'success');
                } else {
                    logResult('❌ Data retrieval failed', 'error');
                }
                
                // Test offline action queuing
                await window.offlineManager.queueOfflineAction({
                    type: 'test-action',
                    data: { test: true },
                    url: '/test-endpoint'
                });
                logResult('✅ Offline action queued', 'success');
                
            } else {
                logResult('❌ Offline Manager not available', 'error');
            }
        } catch (error) {
            logResult(`Data storage test failed: ${error.message}`, 'error');
        }
    });

    // Test offline form submission
    document.getElementById('test-offline-form').addEventListener('click', async function() {
        logResult('Testing offline form submission...', 'info');
        
        try {
            const formData = {
                name: 'Test Submission',
                data: 'Test data for offline submission',
                timestamp: Date.now()
            };
            
            const response = await fetch('/api/test-submission', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 'test-token'
                },
                body: JSON.stringify(formData)
            });
            
            if (response.ok) {
                const result = await response.json();
                if (result.offline) {
                    logResult('✅ Form queued for offline sync', 'success');
                } else {
                    logResult('✅ Form submitted online', 'success');
                }
            } else {
                logResult(`❌ Form submission failed: ${response.status}`, 'error');
            }
            
        } catch (error) {
            logResult(`Form submission test failed: ${error.message}`, 'error');
        }
    });

    // Simulate offline mode
    document.getElementById('simulate-offline').addEventListener('click', function() {
        logResult('Simulating offline mode...', 'info');
        
        // This would normally be done through browser dev tools
        // But we can simulate the behavior
        if (navigator.onLine) {
            logResult('💡 To test offline mode:', 'info');
            logResult('1. Open browser Developer Tools (F12)', 'info');
            logResult('2. Go to Network tab', 'info');
            logResult('3. Check "Offline" checkbox', 'info');
            logResult('4. Refresh page to test offline functionality', 'info');
        } else {
            logResult('🔴 Already in offline mode', 'warning');
        }
    });

    // Clear database
    document.getElementById('clear-database').addEventListener('click', async function() {
        logResult('Clearing IndexedDB...', 'info');
        
        try {
            // Close any existing connections
            if (window.offlineManager && window.offlineManager.db) {
                window.offlineManager.db.close();
            }
            
            // Delete the database
            const deleteRequest = indexedDB.deleteDatabase('EurekaOfflineDB');
            
            deleteRequest.onsuccess = () => {
                logResult('✅ Database cleared successfully', 'success');
                logResult('💡 Refresh page to reinitialize database', 'info');
            };
            
            deleteRequest.onerror = () => {
                logResult('❌ Failed to clear database', 'error');
            };
            
            deleteRequest.onblocked = () => {
                logResult('⚠️ Database deletion blocked - close other tabs', 'warning');
            };
            
        } catch (error) {
            logResult(`Database clear failed: ${error.message}`, 'error');
        }
    });

    // Test offline navigation
    window.testOfflineNavigation = function(path) {
        logResult(`Testing offline navigation to: ${path}`, 'info');
        
        // Check if path is cached
        if ('caches' in window) {
            caches.match(path).then(response => {
                if (response) {
                    logResult(`✅ ${path} is cached and available offline`, 'success');
                } else {
                    logResult(`❌ ${path} is not cached`, 'warning');
                }
            });
        }
        
        // Test if offline manager considers it offline-capable
        if (window.offlineManager) {
            const feature = path.includes('dashboard') ? 'dashboard' : 
                           path.includes('quiz') ? 'quiz-view' : 
                           path.includes('team') ? 'team-view' : 'unknown';
            
            const canWork = window.offlineManager.canWorkOffline(feature);
            logResult(`${path} offline capability: ${canWork ? '✅' : '❌'}`, canWork ? 'success' : 'warning');
        }
    };

    // Performance monitoring
    function updatePerformanceMetrics() {
        const metrics = document.getElementById('performance-metrics');
        
        if ('performance' in window && performance.navigation) {
            const navigation = performance.navigation;
            const timing = performance.timing;
            
            const loadTime = timing.loadEventEnd - timing.navigationStart;
            const domReady = timing.domContentLoadedEventEnd - timing.navigationStart;
            
            metrics.innerHTML = `
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div>
                        <strong>Page Load:</strong><br>
                        ${loadTime}ms
                    </div>
                    <div>
                        <strong>DOM Ready:</strong><br>
                        ${domReady}ms
                    </div>
                    <div>
                        <strong>Navigation Type:</strong><br>
                        ${navigation.type === 0 ? 'Navigate' : navigation.type === 1 ? 'Reload' : 'Back/Forward'}
                    </div>
                    <div>
                        <strong>Connection:</strong><br>
                        ${navigator.onLine ? 'Online' : 'Offline'}
                    </div>
                </div>
            `;
        }
    }

    // Initialize
    updateStatus();
    updatePerformanceMetrics();
    
    // Update status on connectivity changes
    window.addEventListener('online', updateStatus);
    window.addEventListener('offline', updateStatus);
    
    // Log initial status
    logResult('Offline functionality test page loaded', 'info');
    logResult(`Browser supports: SW=${!!navigator.serviceWorker}, IDB=${!!window.indexedDB}, Cache=${!!window.caches}`, 'info');
});
</script>
@endsection