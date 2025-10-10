<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ darkMode: false }" x-init="
    darkMode = localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)
    $watch('darkMode', val => localStorage.setItem('theme', val ? 'dark' : 'light'))
" :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name'))</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    
    <!-- TailwindCSS CDN (for no-build mode) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Figtree', 'ui-sans-serif', 'system-ui', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'Helvetica Neue', 'Arial', 'Noto Sans', 'sans-serif', 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji'],
                    },
                },
            },
        }
    </script>
    
    <!-- MapLibre GL CSS (CDN) -->
    <link href="https://unpkg.com/maplibre-gl@latest/dist/maplibre-gl.css" rel="stylesheet">
    
    <!-- AlpineJS -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Raw CSS from resources (no build required) -->
    <style>
        /* Include your custom styles here if needed */
        .line-clamp-3 {
            overflow: hidden;
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 3;
        }
    </style>

    @stack('styles')
</head>
<body class="font-sans antialiased bg-gray-50 dark:bg-gray-900">
    <div class="min-h-screen">
        {{ $slot }}
    </div>

    <!-- MapLibre GL JS (CDN) -->
    <script src="https://unpkg.com/maplibre-gl@latest/dist/maplibre-gl.js"></script>
    
    <!-- QR Scanner CDN -->
    <script type="module">
        import QrScanner from 'https://unpkg.com/qr-scanner@1.4.2/qr-scanner.min.js';
        window.QrScanner = QrScanner;
    </script>
    
    <!-- Raw JavaScript files (served directly, no build) -->
    <script src="{{ asset('resources/js/panellum-utils.js') }}"></script>
    <script src="{{ asset('resources/js/map-utils.js') }}"></script>
    <script src="{{ asset('resources/js/scanner-utils.js') }}"></script>
    <script src="{{ asset('resources/js/game-utils.js') }}"></script>
    <script src="{{ asset('resources/js/app.js') }}"></script>
    
    @livewireScripts
    @stack('scripts')
</body>
</html>