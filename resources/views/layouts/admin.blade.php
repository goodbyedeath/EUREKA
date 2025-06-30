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
    
    @livewireStyles
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
        
        /* Modern admin animations */
        .fade-in {
            animation: fadeIn 0.4s ease-out;
        }
        
        @keyframes fadeIn {
            from { 
                opacity: 0; 
                transform: translateY(20px); 
            }
            to { 
                opacity: 1; 
                transform: translateY(0); 
            }
        }
        
        .nav-item {
            transition: all 0.3s ease;
            position: relative;
        }
        
        .nav-item:hover {
            transform: translateY(-1px);
        }
        
        .nav-item.active::before {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #6366f1, #8b5cf6);
            border-radius: 2px;
        }
        
        .admin-card {
            transition: all 0.3s ease;
        }
        
        .admin-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 via-blue-50 to-indigo-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 min-h-screen transition-colors duration-300">
    <div class="min-h-screen">
        <!-- Modern Admin Header with Gradient -->
        <nav class="bg-gradient-to-r from-indigo-600 via-purple-600 to-indigo-800 shadow-xl" x-data="{ mobileMenuOpen: false }">
            <div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-6">
                <div class="flex justify-between h-16">
                    <!-- Logo & Brand -->
                    <div class="flex items-center">
                        <div class="flex items-center space-x-2 bg-white/10 backdrop-blur-sm rounded-lg px-3 py-1.5">
                            <img src="/logo/horizonlogo.png" alt="Eureka! Performa" class="h-8 w-auto">
                            <div class="border-l border-white/30 h-6"></div>
                            <div>
                                <h1 class="text-lg font-bold text-white">@yield('page-title', 'Admin Panel')</h1>
                                <div class="text-xs text-indigo-100">Management System</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Desktop Navigation -->
                    <div class="hidden md:flex items-center space-x-3">
                        <!-- Dark Mode Toggle -->
                        <button data-theme-toggle
                                class="bg-white/10 backdrop-blur-sm rounded-lg px-2 py-1.5 text-white hover:bg-white/20 transition-all duration-200"
                                title="Toggle Dark Mode">
                            <svg class="w-5 h-5 sun-icon" fill="currentColor" viewBox="0 0 20 20" style="display: none;">
                                <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"></path>
                            </svg>
                            <svg class="w-5 h-5 moon-icon" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
                            </svg>
                        </button>
                        
                        <!-- Language Switcher -->
                        <div class="bg-white/10 backdrop-blur-sm rounded-lg px-2 py-1.5">
                            @include('components.simple-language-switcher')
                        </div>
                        
                        <!-- User Profile Section -->
                        <div class="flex items-center space-x-2 bg-white/10 backdrop-blur-sm rounded-lg px-3 py-1.5">
                            <!-- Admin Avatar -->
                            <div class="w-7 h-7 bg-white/20 rounded-full flex items-center justify-center">
                                <i class="fas fa-user-shield text-white text-xs"></i>
                            </div>
                            <div class="hidden lg:block">
                                <span class="text-white font-medium text-sm">{{ auth()->user()->name }}</span>
                                <div class="text-indigo-100 text-xs">Administrator</div>
                            </div>
                            
                            <!-- Action Buttons -->
                            <a href="{{ route('user.dashboard') }}" class="text-white/80 hover:text-white hover:bg-white/10 transition-all duration-200 px-2 py-1 rounded text-sm flex items-center space-x-1">
                                <i class="fas fa-tachometer-alt text-xs"></i>
                                <span class="hidden sm:inline">{{ __('common.dashboard') }}</span>
                            </a>
                            
                            <form method="POST" action="{{ route('logout') }}" class="inline">
                                @csrf
                                <button type="submit" class="text-white/80 hover:text-white hover:bg-white/10 transition-all duration-200 px-2 py-1 rounded text-sm flex items-center space-x-1">
                                    <i class="fas fa-sign-out-alt text-xs"></i>
                                    <span class="hidden sm:inline">{{ __('auth.logout') }}</span>
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Mobile menu button -->
                    <div class="md:hidden flex items-center">
                        <button @click="mobileMenuOpen = !mobileMenuOpen" 
                                class="text-white/80 hover:text-white hover:bg-white/10 transition-all duration-200 p-1.5 rounded">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                <path x-show="mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
                
                <!-- Mobile Navigation Menu -->
                <div x-show="mobileMenuOpen" 
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 transform scale-95"
                     x-transition:enter-end="opacity-100 transform scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 transform scale-100"
                     x-transition:leave-end="opacity-0 transform scale-95"
                     class="md:hidden border-t border-white/20 bg-black/10 backdrop-blur-sm">
                    <div class="px-3 py-3 space-y-3">
                        <!-- Mobile Dark Mode Toggle -->
                        <div class="bg-white/10 rounded-lg p-2 mb-3">
                            <button data-theme-toggle
                                    class="w-full flex items-center justify-center space-x-2 text-white hover:bg-white/10 transition-all duration-200 py-2 rounded-md"
                                    title="Toggle Dark Mode">
                                <svg class="w-5 h-5 sun-icon" fill="currentColor" viewBox="0 0 20 20" style="display: none;">
                                    <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"></path>
                                </svg>
                                <svg class="w-5 h-5 moon-icon" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
                                </svg>
                                <span class="text-sm font-medium">Dark Mode</span>
                            </button>
                        </div>
                        
                        <!-- Mobile Language Switcher -->
                        <div class="bg-white/10 rounded-lg p-2">
                            <div class="flex items-center space-x-2 mb-1">
                                <span class="text-sm text-white font-medium">{{ __('common.language') }}:</span>
                            </div>
                            @include('components.simple-language-switcher')
                        </div>
                        
                        <!-- Mobile Admin Info -->
                        <div class="bg-white/10 rounded-lg p-2">
                            <div class="flex items-center space-x-2 mb-2">
                                <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center">
                                    <i class="fas fa-user-shield text-white text-xs"></i>
                                </div>
                                <div>
                                    <div class="text-white font-medium text-sm">{{ auth()->user()->name }}</div>
                                    <div class="text-indigo-100 text-xs">Administrator</div>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <a href="{{ route('user.dashboard') }}" class="w-full text-left text-white/80 hover:text-white hover:bg-white/10 transition-all duration-200 px-2 py-1.5 rounded text-sm flex items-center space-x-2">
                                    <i class="fas fa-tachometer-alt text-xs"></i>
                                    <span>{{ __('common.dashboard') }}</span>
                                </a>
                                <form method="POST" action="{{ route('logout') }}" class="w-full">
                                    @csrf
                                    <button type="submit" class="w-full text-left text-white/80 hover:text-white hover:bg-white/10 transition-all duration-200 px-2 py-1.5 rounded text-sm flex items-center space-x-2">
                                        <i class="fas fa-sign-out-alt text-xs"></i>
                                        <span>{{ __('auth.logout') }}</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Compact Admin Content Area -->
        <div class="py-4">
            <div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-6">
                <!-- Modern Flash Messages -->
                @if(session('message'))
                    <div class="mb-4 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-l-4 border-green-400 dark:border-green-500 p-3 rounded-r-lg shadow-sm fade-in">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle text-green-400 dark:text-green-300"></i>
                            </div>
                            <div class="ml-2">
                                <p class="text-green-700 dark:text-green-300 font-medium text-sm">{{ session('message') }}</p>
                            </div>
                        </div>
                    </div>
                @endif
                
                @if(session('error'))
                    <div class="mb-4 bg-gradient-to-r from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/20 border-l-4 border-red-400 dark:border-red-500 p-3 rounded-r-lg shadow-sm fade-in">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-circle text-red-400 dark:text-red-300"></i>
                            </div>
                            <div class="ml-2">
                                <p class="text-red-700 dark:text-red-300 font-medium text-sm">{{ session('error') }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Enhanced Tab Navigation -->
                <div class="mb-6">
                    <!-- Desktop Navigation -->
                    <div class="hidden md:block bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-100 dark:border-gray-700 overflow-hidden transition-colors duration-300">
                        <nav class="flex" aria-label="Admin Tabs">
                            <a href="{{ route('admin.dashboard') }}" 
                               class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active bg-gradient-to-r from-indigo-500 to-purple-600 text-white' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-700' }} flex-1 px-4 py-3 text-center font-medium text-sm transition-all duration-200">
                                <i class="fas fa-tachometer-alt mr-2"></i>
                                Dashboard
                            </a>
                            <a href="{{ route('admin.users') }}" 
                               class="nav-item {{ request()->routeIs('admin.users') ? 'active bg-gradient-to-r from-indigo-500 to-purple-600 text-white' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-700' }} flex-1 px-4 py-3 text-center font-medium text-sm transition-all duration-200 border-l border-gray-100 dark:border-gray-600">
                                <i class="fas fa-users mr-2"></i>
                                Users
                            </a>
                            <a href="{{ route('admin.quest-locations') }}" 
                               class="nav-item {{ request()->routeIs('admin.quest-locations') ? 'active bg-gradient-to-r from-indigo-500 to-purple-600 text-white' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-700' }} flex-1 px-4 py-3 text-center font-medium text-sm transition-all duration-200 border-l border-gray-100 dark:border-gray-600">
                                <i class="fas fa-map-marked-alt mr-2"></i>
                                Maps
                            </a>
                            <a href="{{ route('admin.games') }}" 
                               class="nav-item {{ request()->routeIs('admin.games') ? 'active bg-gradient-to-r from-indigo-500 to-purple-600 text-white' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-700' }} flex-1 px-4 py-3 text-center font-medium text-sm transition-all duration-200 border-l border-gray-100 dark:border-gray-600">
                                <i class="fas fa-gamepad mr-2"></i>
                                Games
                            </a>
                            <a href="{{ route('admin.user-progress') }}" 
                               class="nav-item {{ request()->routeIs('admin.user-progress') ? 'active bg-gradient-to-r from-indigo-500 to-purple-600 text-white' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-700' }} flex-1 px-4 py-3 text-center font-medium text-sm transition-all duration-200 border-l border-gray-100 dark:border-gray-600">
                                <i class="fas fa-chart-line mr-2"></i>
                                Progress
                            </a>
                            <a href="{{ route('admin.hero-slides') }}" 
                               class="nav-item {{ request()->routeIs('admin.hero-slides') ? 'active bg-gradient-to-r from-indigo-500 to-purple-600 text-white' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-700' }} flex-1 px-4 py-3 text-center font-medium text-sm transition-all duration-200 border-l border-gray-100 dark:border-gray-600">
                                <i class="fas fa-images mr-2"></i>
                                Slides
                            </a>
                            <a href="{{ route('admin.team-management') }}" 
                               class="nav-item {{ request()->routeIs('admin.team-management') ? 'active bg-gradient-to-r from-indigo-500 to-purple-600 text-white' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-700' }} flex-1 px-4 py-3 text-center font-medium text-sm transition-all duration-200 border-l border-gray-100 dark:border-gray-600">
                                <i class="fas fa-users-cog mr-2"></i>
                                Teams
                            </a>
                        </nav>
                    </div>
                    
                    <!-- Mobile Navigation Dropdown -->
                    <div class="md:hidden">
                        <select id="mobile-admin-nav" class="block w-full px-3 py-3 border border-gray-300 dark:border-gray-600 rounded-xl shadow-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors duration-300" onchange="window.location.href=this.value">
                            <option value="{{ route('admin.dashboard') }}" {{ request()->routeIs('admin.dashboard') ? 'selected' : '' }}>📊 Dashboard</option>
                            <option value="{{ route('admin.users') }}" {{ request()->routeIs('admin.users') ? 'selected' : '' }}>👥 User Management</option>
                            <option value="{{ route('admin.quest-locations') }}" {{ request()->routeIs('admin.quest-locations') ? 'selected' : '' }}>🗺️ Map Management</option>
                            <option value="{{ route('admin.games') }}" {{ request()->routeIs('admin.games') ? 'selected' : '' }}>🎮 Game Management</option>
                            <option value="{{ route('admin.user-progress') }}" {{ request()->routeIs('admin.user-progress') ? 'selected' : '' }}>📈 User Progress</option>
                            <option value="{{ route('admin.hero-slides') }}" {{ request()->routeIs('admin.hero-slides') ? 'selected' : '' }}>🖼️ Hero Slides</option>
                            <option value="/livewire/admin/team-manager" {{ request()->is('livewire/admin/team-manager*') ? 'selected' : '' }}>👥 Team Management</option>
                        </select>
                    </div>
                </div>

                <!-- Enhanced Main Content -->
                <div class="admin-card bg-white dark:bg-gray-800 overflow-hidden shadow-lg rounded-xl border border-gray-100 dark:border-gray-700 transition-colors duration-300">
                    <div class="p-4 lg:p-6">
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