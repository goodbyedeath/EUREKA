@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 transition-colors duration-300">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-6">Dark Mode Test Page</h1>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg transition-colors duration-300">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-3">Theme Controls</h2>
                    <div class="space-y-3">
                        <button onclick="window.DarkMode.setTheme('light')" 
                                class="w-full px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg transition-colors duration-200">
                            Set Light Mode
                        </button>
                        <button onclick="window.DarkMode.setTheme('dark')" 
                                class="w-full px-4 py-2 bg-gray-700 hover:bg-gray-800 text-white rounded-lg transition-colors duration-200">
                            Set Dark Mode
                        </button>
                        <button onclick="window.DarkMode.toggle()" 
                                class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors duration-200">
                            Toggle Theme
                        </button>
                        <button onclick="window.debugDarkMode()" 
                                class="w-full px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors duration-200">
                            Debug (Check Console)
                        </button>
                    </div>
                </div>
                
                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg transition-colors duration-300">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-3">Theme Info</h2>
                    <div class="space-y-2 text-sm">
                        <div class="text-gray-600 dark:text-gray-300">
                            <span class="font-medium">Current Theme:</span> 
                            <span id="current-theme">Loading...</span>
                        </div>
                        <div class="text-gray-600 dark:text-gray-300">
                            <span class="font-medium">LocalStorage:</span> 
                            <span id="storage-theme">Loading...</span>
                        </div>
                        <div class="text-gray-600 dark:text-gray-300">
                            <span class="font-medium">HTML Class:</span> 
                            <span id="html-class">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg transition-colors duration-300">
                <h3 class="text-lg font-semibold text-blue-800 dark:text-blue-200 mb-2">Instructions:</h3>
                <ol class="list-decimal list-inside space-y-1 text-blue-700 dark:text-blue-300 text-sm">
                    <li>Use the theme controls to test switching</li>
                    <li>Check the browser console for debug info</li>
                    <li>Try the navigation toggle buttons</li>
                    <li>Refresh the page to test persistence</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function updateThemeInfo() {
        const currentTheme = window.DarkMode?.getTheme() || 'unknown';
        const storageTheme = localStorage.getItem('theme') || 'none';
        const hasHtmlClass = document.documentElement.classList.contains('dark');
        
        document.getElementById('current-theme').textContent = currentTheme;
        document.getElementById('storage-theme').textContent = storageTheme;
        document.getElementById('html-class').textContent = hasHtmlClass ? 'dark' : 'light';
    }
    
    // Update info initially
    updateThemeInfo();
    
    // Update info every second
    setInterval(updateThemeInfo, 1000);
});
</script>
@endsection