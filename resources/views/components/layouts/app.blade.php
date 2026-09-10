<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ isset($title) ? $title . ' - ' : '' }}{{ \App\Models\BrandSetting::appName() }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ \App\Models\BrandSetting::iconUrl() }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com?v=3.4.0" defer></script>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.4.0/dist/tailwind.min.css" rel="stylesheet">
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof tailwind !== 'undefined') {
                tailwind.config = {
                    darkMode: 'class',
                    theme: {
                        extend: {
                            colors: {
                                primary: {
                                    50: '#eff6ff',
                                    100: '#dbeafe', 
                                    200: '#bfdbfe',
                                    300: '#93c5fd',
                                    400: '#60a5fa',
                                    500: '#3b82f6',
                                    600: '#2563eb',
                                    700: '#1d4ed8',
                                    800: '#1e40af',
                                    900: '#1e3a8a',
                                }
                            }
                        }
                    }
                };
            }
        });
    </script>
    
    <!-- Custom styles -->
    <style>
        [x-cloak] { display: none !important; }
        
        /* Ensure Tailwind CDN is working - this will be overridden if Tailwind loads */
        .tailwind-test {
            background-color: red !important;
            color: white !important;
            padding: 1rem !important;
        }
    </style>

    <!-- Livewire Styles -->
    @livewireStyles
    
    <!-- Custom CSS (minimal) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Dark Mode Init Script -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            if (savedTheme === 'dark') {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    <style>
        [x-cloak] { display: none !important; }
    </style>

    @stack('styles')
</head>
<body class="font-sans antialiased bg-gray-100 dark:bg-gray-900 transition-colors duration-300">
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
        <!-- Navigation Header -->
        <nav class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 flex items-center">
                            <img src="{{ \App\Models\BrandSetting::iconUrl() }}" alt="EUREKA Logo" class="w-10 h-10">
                            <div class="mx-3 text-gray-400 text-2xl font-light">|</div>
                            <h1 class="text-2xl font-bold text-blue-600">{{ \App\Models\BrandSetting::appName() }}</h1>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        @auth
                            <a href="{{ route('user.dashboard') }}" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium transition duration-300">
                                Dashboard
                            </a>
                            <form method="POST" action="{{ route('logout') }}" class="inline">
                                @csrf
                                <button type="submit" class="text-gray-600 dark:text-gray-300 hover:text-red-600 px-3 py-2 rounded-md text-sm font-medium transition duration-300">
                                    Logout
                                </button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium transition duration-300">
                                Login
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="py-6">
            {{ $slot }}
        </main>

        <!-- Footer -->
        <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 mt-12 text-center text-sm text-gray-500 dark:text-gray-400 py-6 transition-colors duration-300">
            &copy; {{ date('Y') }} {{ \App\Models\BrandSetting::appName() }}. All rights reserved.
        </footer>
    </div>

    <!-- External QR Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jsqr/1.4.0/jsQR.min.js"></script>

    <!-- Livewire Scripts -->
    @livewireScripts

    @stack('scripts')

    <!-- Flash Messages -->
    @if (session('message'))
        <div x-data="{ show: true }" 
             x-show="show" 
             x-transition
             x-init="setTimeout(() => show = false, 3000)"
             role="alert"
             class="fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50">
            {{ session('message') }}
        </div>
    @endif

    @if (session('success'))
        <div x-data="{ show: true }" 
             x-show="show" 
             x-transition
             x-init="setTimeout(() => show = false, 5000)"
             role="alert"
             class="fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div x-data="{ show: true }" 
             x-show="show" 
             x-transition
             x-init="setTimeout(() => show = false, 5000)"
             role="alert"
             class="fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50">
            {{ session('error') }}
        </div>
    @endif
    @include("partials.service-worker")
    @include('partials.resource-loader')
</body>
</html>