<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quest Location Debug Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .debug-section { border: 1px solid #e5e7eb; margin: 1rem 0; padding: 1rem; border-radius: 0.5rem; }
        .debug-log { font-family: monospace; font-size: 0.8rem; background: #f3f4f6; padding: 0.5rem; border-radius: 0.25rem; white-space: pre-wrap; }
        .status-success { color: #16a34a; }
        .status-error { color: #dc2626; }
        .status-warning { color: #ea580c; }
    </style>
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Quest Location Debug Dashboard</h1>
        
        <!-- Quick Actions -->
        <div class="debug-section bg-white">
            <h2 class="text-xl font-semibold mb-4">Quick Actions</h2>
            <div class="flex space-x-4">
                <button onclick="runSystemHealth()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    Run System Health Check
                </button>
                <button onclick="runDatabaseTest()" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                    Test Database
                </button>
                <button onclick="clearLogs()" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
                    Clear Logs
                </button>
                <button onclick="runJSDebug()" class="bg-purple-500 text-white px-4 py-2 rounded hover:bg-purple-600">
                    Test JavaScript Functions
                </button>
            </div>
        </div>
        
        <!-- System Health Results -->
        <div class="debug-section bg-white">
            <h2 class="text-xl font-semibold mb-4">System Health</h2>
            <div id="health-results" class="debug-log">Click "Run System Health Check" to see results...</div>
        </div>
        
        <!-- Database Test Results -->
        <div class="debug-section bg-white">
            <h2 class="text-xl font-semibold mb-4">Database Tests</h2>
            <div id="database-results" class="debug-log">Click "Test Database" to see results...</div>
        </div>
        
        <!-- JavaScript Debug -->
        <div class="debug-section bg-white">
            <h2 class="text-xl font-semibold mb-4">JavaScript Debug</h2>
            <div id="js-results" class="debug-log">Click "Test JavaScript Functions" to see results...</div>
        </div>
        
        <!-- Live Console -->
        <div class="debug-section bg-white">
            <h2 class="text-xl font-semibold mb-4">Live Console</h2>
            <div id="console-output" class="debug-log" style="max-height: 400px; overflow-y: auto;">Console output will appear here...</div>
        </div>
        
        <!-- Test Quest Location Functions -->
        <div class="debug-section bg-white">
            <h2 class="text-xl font-semibold mb-4">Test Quest Location Functions</h2>
            <div class="space-y-2">
                <button onclick="testOpenFullMap()" class="bg-indigo-500 text-white px-4 py-2 rounded hover:bg-indigo-600">
                    Test openFullMap()
                </button>
                <button onclick="testCloseFullMap()" class="bg-indigo-500 text-white px-4 py-2 rounded hover:bg-indigo-600">
                    Test closeFullMap()
                </button>
                <button onclick="testRequestLocation()" class="bg-indigo-500 text-white px-4 py-2 rounded hover:bg-indigo-600">
                    Test requestLocation()
                </button>
            </div>
            <div id="function-test-results" class="debug-log mt-4">Function test results will appear here...</div>
        </div>
    </div>

    <script>
        // Capture console output
        const originalLog = console.log;
        const originalError = console.error;
        const originalWarn = console.warn;
        
        function logToDiv(type, ...args) {
            const consoleDiv = document.getElementById('console-output');
            const timestamp = new Date().toISOString();
            const message = args.map(arg => typeof arg === 'object' ? JSON.stringify(arg, null, 2) : String(arg)).join(' ');
            
            const color = type === 'error' ? 'status-error' : type === 'warn' ? 'status-warning' : 'status-success';
            consoleDiv.innerHTML += `<div class="${color}">[${timestamp}] ${type.toUpperCase()}: ${message}</div>`;
            consoleDiv.scrollTop = consoleDiv.scrollHeight;
        }
        
        console.log = function(...args) {
            originalLog.apply(console, args);
            logToDiv('log', ...args);
        };
        
        console.error = function(...args) {
            originalError.apply(console, args);
            logToDiv('error', ...args);
        };
        
        console.warn = function(...args) {
            originalWarn.apply(console, args);
            logToDiv('warn', ...args);
        };
        
        // API call functions
        async function runSystemHealth() {
            try {
                const response = await fetch('/debug/quest-health');
                const data = await response.json();
                document.getElementById('health-results').textContent = JSON.stringify(data, null, 2);
                console.log('System health check completed', data);
            } catch (error) {
                console.error('System health check failed', error);
                document.getElementById('health-results').textContent = 'Error: ' + error.message;
            }
        }
        
        async function runDatabaseTest() {
            try {
                const response = await fetch('/debug/test-database');
                const data = await response.json();
                document.getElementById('database-results').textContent = JSON.stringify(data, null, 2);
                console.log('Database test completed', data);
            } catch (error) {
                console.error('Database test failed', error);
                document.getElementById('database-results').textContent = 'Error: ' + error.message;
            }
        }
        
        async function clearLogs() {
            try {
                const response = await fetch('/debug/clear-logs', { method: 'POST' });
                const data = await response.json();
                console.log('Logs cleared', data);
                alert('Logs cleared successfully');
            } catch (error) {
                console.error('Failed to clear logs', error);
                alert('Failed to clear logs: ' + error.message);
            }
        }
        
        function runJSDebug() {
            const results = document.getElementById('js-results');
            results.textContent = '';
            
            console.log('Running JavaScript debug tests...');
            
            // Test 1: Check if functions exist
            const functions = ['openFullMap', 'closeFullMap', 'requestLocation'];
            const functionResults = {};
            
            functions.forEach(funcName => {
                const exists = typeof window[funcName] === 'function';
                functionResults[funcName] = exists ? 'EXISTS' : 'MISSING';
                console.log(`Function ${funcName}:`, exists ? 'EXISTS' : 'MISSING');
            });
            
            // Test 2: Check questDebug object
            const questDebugExists = typeof window.questDebug === 'object';
            console.log('questDebug object:', questDebugExists ? 'EXISTS' : 'MISSING');
            
            if (questDebugExists && window.questDebug.test) {
                try {
                    window.questDebug.test();
                } catch (e) {
                    console.error('questDebug.test() failed:', e);
                }
            }
            
            results.textContent = JSON.stringify({
                functions: functionResults,
                questDebug: questDebugExists,
                timestamp: new Date().toISOString()
            }, null, 2);
        }
        
        function testOpenFullMap() {
            const results = document.getElementById('function-test-results');
            console.log('Testing openFullMap function...');
            
            try {
                if (typeof window.openFullMap === 'function') {
                    // Test with sample coordinates (Jakarta)
                    window.openFullMap(-6.2088, 106.8456, 'Test Location');
                    results.textContent = 'openFullMap() called successfully - check if modal opened';
                    console.log('openFullMap() test completed');
                } else {
                    results.textContent = 'ERROR: openFullMap is not a function';
                    console.error('openFullMap is not a function');
                }
            } catch (error) {
                results.textContent = 'ERROR calling openFullMap: ' + error.message;
                console.error('Error calling openFullMap:', error);
            }
        }
        
        function testCloseFullMap() {
            console.log('Testing closeFullMap function...');
            
            try {
                if (typeof window.closeFullMap === 'function') {
                    window.closeFullMap();
                    console.log('closeFullMap() test completed');
                } else {
                    console.error('closeFullMap is not a function');
                }
            } catch (error) {
                console.error('Error calling closeFullMap:', error);
            }
        }
        
        function testRequestLocation() {
            console.log('Testing requestLocation function...');
            
            try {
                if (typeof window.requestLocation === 'function') {
                    window.requestLocation();
                    console.log('requestLocation() called - check browser for permission prompt');
                } else {
                    console.error('requestLocation is not a function');
                }
            } catch (error) {
                console.error('Error calling requestLocation:', error);
            }
        }
        
        // Auto-run system health on page load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Debug dashboard loaded');
            setTimeout(runSystemHealth, 1000);
        });
    </script>
</body>
</html>