<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Eureka</title>
    
    <!-- PWA Meta Tags -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#6777ef">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Eureka">
    <link rel="apple-touch-icon" href="/logo.png">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"> 
    <script src="https://cdn.tailwindcss.com"></script>
    
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Admin Header -->
        <div class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <h1 class="text-xl font-semibold text-gray-900">@yield('page-title', 'Admin Dashboard')</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        @include('components.simple-language-switcher')
                        <span class="text-gray-700">{{ __('common.welcome') }}, {{ auth()->user()->name }}</span>
                        <a href="{{ route('user.dashboard') }}" class="text-blue-600 hover:text-blue-800">{{ __('common.dashboard') }}</a>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-red-600 hover:text-red-800">{{ __('auth.logout') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Flash Messages -->
                @if(session('message'))
                    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                        {{ session('message') }}
                    </div>
                @endif
                
                @if(session('error'))
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                        {{ session('error') }}
                    </div>
                @endif
                <!-- Tab Navigation -->
                <div class="mb-6">
                    <nav class="flex space-x-8" aria-label="Tabs">
                        <a href="{{ route('admin.dashboard') }}" 
                           class="{{ request()->routeIs('admin.dashboard') ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                            Dashboard
                        </a>
                        <a href="{{ route('admin.users') }}" 
                           class="{{ request()->routeIs('admin.users') ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                            User Management
                        </a>
                        <a href="{{ route('admin.quest-locations') }}" 
                            class="{{ request()->routeIs('admin.quest-locations') ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                            Map Management
                        </a>
                        <a href="{{ route('admin.games') }}" 
                            class="{{ request()->routeIs('admin.games') ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                            Game Management
                        </a>
                        <a href="{{ route('admin.user-progress') }}" 
                            class="{{ request()->routeIs('admin.user-progress') ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                            User Progress
                        </a>
                        <a href="{{ route('admin.hero-slides') }}" 
                            class="{{ request()->routeIs('admin.hero-slides') ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                            Hero Slides
                        </a>
                    </nav>
                </div>

                <!-- Main Content -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        @yield('content')
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- PWA Install Prompt -->
    <livewire:pwa-install-prompt />
 
    <!-- Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js')
                    .then(function(registration) {
                        console.log('ServiceWorker registration successful:', registration.scope);
                    })
                    .catch(function(error) {
                        console.log('ServiceWorker registration failed:', error);
                    });
            });
        }
    </script>
 
    <!-- Add this stack for any additional scripts that might be pushed from other components -->
    @stack('scripts')
    @livewireScripts
    
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('console-log', (data) => {
                console.log('DEBUG:', data.message);
            });
        });
    </script>
</body>
</html>