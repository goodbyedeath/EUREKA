<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - {{ \App\Models\BrandSetting::appName() }}</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        [x-cloak] { display: none !important; }
        
        .error-animation {
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
        
        .pulse-slow {
            animation: pulse 3s infinite;
        }
        
        .fade-in {
            animation: fadeIn 0.8s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="font-sans antialiased bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 min-h-screen text-gray-900 dark:text-gray-100">
    <div class="min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8">
        <div class="max-w-lg w-full space-y-8">
            <!-- Error Icon and Code -->
            <div class="text-center fade-in">
                <div class="error-animation">
                    @yield('icon')
                </div>
                <h1 class="text-6xl sm:text-8xl font-bold text-gray-300 dark:text-gray-600 mb-2">
                    @yield('code')
                </h1>
            </div>
            
            <!-- Error Content -->
            <div class="text-center space-y-4 fade-in">
                <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-gray-100">
                    @yield('title')
                </h2>
                <p class="text-gray-600 dark:text-gray-400 text-base sm:text-lg max-w-md mx-auto">
                    @yield('message')
                </p>
            </div>
            
            <!-- Action Buttons -->
            <div class="text-center space-y-4 fade-in">
                @yield('actions')
                
                <!-- Default Actions -->
                <div class="flex flex-col sm:flex-row justify-center space-y-3 sm:space-y-0 sm:space-x-4">
                    <button 
                        onclick="window.history.back()" 
                        class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 dark:border-gray-600 rounded-lg text-base font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors duration-200">
                        <i class="fas fa-arrow-left mr-2"></i>
                        {{ __('common.back') }}
                    </button>
                    
                    <a href="{{ route('user.dashboard') }}" 
                       class="inline-flex items-center justify-center px-6 py-3 border border-transparent rounded-lg text-base font-medium text-white bg-blue-600 hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-800 transition-colors duration-200">
                        <i class="fas fa-home mr-2"></i>
                        {{ __('common.home') }}
                    </a>
                </div>
            </div>
            
            <!-- Additional Info -->
            <div class="text-center fade-in">
                @yield('additional-info')
                
                <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ __('common.error') }} ID: {{ Str::random(8) }} | 
                        <span class="text-gray-400 dark:text-gray-500">{{ now()->format('Y-m-d H:i:s') }}</span>
                    </p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">
                        &copy; {{ date('Y') }} {{ \App\Models\BrandSetting::appName() }}. All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <!-- Production JS -->
    
    <script>
        // Automatically refresh page after 30 seconds for 500 errors
        @if(request()->route() && request()->route()->getName() === '500')
            setTimeout(() => {
                if (confirm('{{ __("This page will refresh automatically. Continue?") }}')) {
                    location.reload();
                }
            }, 30000);
        @endif
        
        // Add some interactive features
        document.addEventListener('DOMContentLoaded', function() {
            // Add click effect to buttons
            const buttons = document.querySelectorAll('button, a');
            buttons.forEach(button => {
                button.addEventListener('mousedown', function() {
                    this.style.transform = 'scale(0.98)';
                });
                button.addEventListener('mouseup', function() {
                    this.style.transform = 'scale(1)';
                });
            });
        });
    </script>
</body>
</html>