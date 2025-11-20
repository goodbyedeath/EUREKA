<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- User Role Meta (for session timeout) -->
    @auth
        <meta name="user-role" content="{{ auth()->user()->role }}">
        <meta name="user-id" content="{{ auth()->user()->id }}">
    @endauth
    
    <title>Quiz - {{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Tailwind via Vite -->

    <!-- Livewire Styles -->
    @livewireStyles

    <!-- Custom CSS -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Dark Mode Init Script - runs before page render -->
    <script>
        // Apply theme immediately to prevent flash
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            if (savedTheme === 'dark') {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    <style>
        [x-cloak] { display: none !important; }
        
        /* Disable browser back button and right-click context menu during quiz */
        body.quiz-mode {
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }
        
        /* Prevent F5 refresh */
        body.quiz-mode {
            overflow-x: hidden;
        }
    </style>

    @stack('styles')
</head>
<body class="font-sans antialiased bg-gray-100 dark:bg-gray-900 transition-colors duration-300 quiz-mode">
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
        <!-- Quiz Content Area - No Navigation -->
        <main class="w-full">
            @yield('content')
        </main>
        
        <!-- Quiz Protection Scripts -->
        <script>
            // Disable common exit shortcuts during quiz
            document.addEventListener('keydown', function(e) {
                // Disable F5 (refresh)
                if (e.key === 'F5' || e.keyCode === 116) {
                    e.preventDefault();
                    return false;
                }
                
                // Disable Ctrl+R (refresh)
                if ((e.ctrlKey || e.metaKey) && (e.key === 'r' || e.keyCode === 82)) {
                    e.preventDefault();
                    return false;
                }
                
                // Disable Alt+F4 (close window)
                if (e.altKey && e.keyCode === 115) {
                    e.preventDefault();
                    return false;
                }
                
                // Disable Ctrl+W (close tab)
                if ((e.ctrlKey || e.metaKey) && (e.key === 'w' || e.keyCode === 87)) {
                    e.preventDefault();
                    return false;
                }
                
                // Disable browser back (Alt + Left Arrow or Backspace)
                if ((e.altKey && e.keyCode === 37) || (e.keyCode === 8 && !['INPUT', 'TEXTAREA'].includes(e.target.tagName))) {
                    e.preventDefault();
                    return false;
                }
            });
            
            // Disable right-click context menu
            document.addEventListener('contextmenu', function(e) {
                e.preventDefault();
                return false;
            });
            
            // Warning before page unload
            window.addEventListener('beforeunload', function(e) {
                const message = 'Are you sure you want to leave? Your quiz progress may be lost.';
                e.returnValue = message;
                return message;
            });
            
            // Disable browser back button
            history.pushState(null, null, window.location.href);
            window.addEventListener('popstate', function(event) {
                history.pushState(null, null, window.location.href);
                // Optional: Show a warning message
                alert('Please complete the quiz before navigating away.');
            });
        </script>
    </div>

    <!-- Livewire Scripts -->
    @livewireScripts
    
    <!-- Camera Capture Script -->
    <script src="{{ asset('js/camera-capture.js') }}"></script>
    
    @stack('scripts')
</body>
</html>