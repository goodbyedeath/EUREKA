{{-- resources/views/layouts/appUser.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', 'Dashboard') - {{ config('app.name', 'Eureka') }}</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('favicon.png') }}">
    
    <!-- Preconnect for performance -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    
    <!-- PWA Meta Tags -->
    <link rel="manifest" href="{{ route('pwa.manifest') }}">
    <meta name="theme-color" content="#6777ef">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Eureka">
    <link rel="apple-touch-icon" href="{{ asset('logo.png') }}">
    
    <!-- User Role Meta (for session timeout) -->
    @auth
        <meta name="user-role" content="{{ auth()->user()->role }}">
        <meta name="user-id" content="{{ auth()->user()->id }}">
    @endauth

    <!-- Fonts with display=swap for better performance -->
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet">

    <!-- Font Awesome with integrity check -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" 
          integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" 
          crossorigin="anonymous" referrerpolicy="no-referrer">

    <!-- Dark Mode Init Script - runs before page render -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            if (savedTheme === 'dark') {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    <!-- Tailwind via Vite -->

    @livewireStyles

    <!-- Custom CSS -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Panellum 360° Viewer (Local) -->
    <link rel="stylesheet" href="{{ asset('js/pannellum/pannellum.css') }}">
    <script src="{{ asset('js/pannellum/pannellum.js') }}"></script>

    <!-- MapLibre GL JS (CDN) -->
    <link href="https://unpkg.com/maplibre-gl@latest/dist/maplibre-gl.css" rel="stylesheet">
    <script src="https://unpkg.com/maplibre-gl@latest/dist/maplibre-gl.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        /* Optimize font loading */
        .font-sans { font-display: swap; }
    </style>

    @stack('styles')
</head>
<body class="font-sans antialiased bg-gray-100 dark:bg-gray-900 transition-colors duration-300">
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
        @yield('content')

        <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 mt-12 text-center text-sm text-gray-500 dark:text-gray-400 py-6 transition-colors duration-300">
            &copy; {{ date('Y') }} {{ config('app.name', 'Laravel') }}. All rights reserved.
        </footer>
    </div>

    @livewireScripts
    
    <!-- QR Scanner functionality is handled by npm package via Vite -->

    <!-- Session Timer is loaded via Vite in app.js -->

    @stack('scripts')

    <!-- Flash Messages Component -->
    @include('components.flash-messages')
    
    <!-- Initialize user-specific features -->
    <script>
        document.addEventListener('livewire:init', () => {
            // Initialize dark mode on Livewire navigation
            document.addEventListener('livewire:navigated', () => {
                if (window.DarkMode) {
                    window.DarkMode.updateToggleButtons();
                }
            });
            
            // User-specific initialization
            @auth
                @if(auth()->user()->role === 'user')
                    // Initialize session timeout monitoring
                    if (typeof initSessionTimeout === 'function') {
                        initSessionTimeout();
                    }
                @endif
            @endauth
        });
    </script>
</body>
</html>