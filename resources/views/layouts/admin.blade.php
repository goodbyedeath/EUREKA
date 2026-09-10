<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ \App\Models\BrandSetting::title('Admin') }}</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ \App\Models\BrandSetting::iconUrl() }}">
    <link rel="shortcut icon" type="image/png" href="{{ \App\Models\BrandSetting::iconUrl() }}">
    
    <!-- PWA Meta Tags -->
    <link rel="manifest" href="{{ route('pwa.manifest.live') }}">
    <meta name="theme-color" content="#6777ef">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="{{ \App\Models\BrandSetting::appName() }}">
    <link rel="apple-touch-icon" sizes="192x192" href="{{ \App\Services\BrandIconService::url(192) }}">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Panellum 360° Viewer (Local) -->
    
    @livewireStyles
    
    <!-- Vite CSS and JS -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- PWA Functions only -->
    <script src="{{ asset('js/pwa-installer.js') }}"></script>
    
    <!-- MapLibre GL JS (CDN Fallback) -->
    <link href="/vendor/maplibre/4.7.1/maplibre-gl.css" rel="stylesheet">
    @include('partials.map-config')
    <script src="/vendor/maplibre/4.7.1/maplibre-gl.js"></script>
    
    <!-- Fix modal flickering -->
    <style>
        [x-cloak] { display: none !important; }
    </style>
    
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
        
        /* No transform here, and none anywhere that wraps page content.
           A transformed element becomes the containing block for its position:fixed
           descendants, so a hover lift on this wrapper made every modal inside it
           position against the card instead of the viewport — and, with overflow-hidden
           on the same element, get clipped to it. The 0.3s transition then animated that
           on every mouse move across the page, which is the flicker that was reported.
           A full-width page container has no business lifting on hover in any case. */
        .admin-card {
            transition: box-shadow 0.3s ease;
        }

        .admin-card:hover {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        /* Set by the sidebar while the drawer is open on a phone. The modal repair layer
           has its own body.modal-open for dialogs; the drawer is not a dialog and is
           deliberately excluded from that script, so it locks scrolling itself. */
        body.nav-drawer-open { overflow: hidden; }

        /* Comfortable touch targets. Pointer-coarse rather than a width query: a small
           tablet with a mouse does not need them, and a large phone does. */
        @media (pointer: coarse) {
            aside nav a,
            aside nav button { min-height: 2.75rem; }
        }
    </style>
    @include('partials.modal-fixes')
    <script>
        // Sidebar state lives in a store so the topbar toggle and the rail itself can
        // share it without nesting. Persisted because a tool used during a live event
        // should not need re-arranging every time it is opened.
        document.addEventListener('alpine:init', () => {
            Alpine.store('adminNav', {
                open: window.innerWidth >= 1024,
                groups: {},

                init() {
                    try {
                        const raw = localStorage.getItem('adminNav');
                        if (raw) {
                            const s = JSON.parse(raw);
                            // Never restore an open rail onto a phone: it would cover the page.
                            if (window.innerWidth >= 1024 && typeof s.open === 'boolean') this.open = s.open;
                            if (s.groups) this.groups = s.groups;
                        }
                    } catch (e) { /* private mode, cleared storage — defaults are fine */ }
                },

                save() {
                    try {
                        localStorage.setItem('adminNav', JSON.stringify({ open: this.open, groups: this.groups }));
                    } catch (e) {}
                },

                toggle() { this.open = !this.open; this.save(); },

                // Rotating a tablet or dragging a window narrow must not leave an overlay
                // sitting on top of the page. Widening again restores what was saved.
                onResize() {
                    const wide = window.innerWidth >= 1024;
                    if (!wide && this.open) {
                        this.open = false;              // not saved: this is the window's doing
                    } else if (wide && !this.open) {
                        try {
                            const raw = localStorage.getItem('adminNav');
                            this.open = raw ? (JSON.parse(raw).open !== false) : true;
                        } catch (e) { this.open = true; }
                    }
                },
                close()  { this.open = false; this.save(); },

                // A group with no stored preference follows the page: the category holding
                // the current page starts open, the rest start closed.
                isOpen(key, fallback) {
                    return this.groups[key] === undefined ? fallback : this.groups[key];
                },

                // The fallback is passed in rather than looked up: the template already
                // knows whether this group holds the current page, and a DOM lookup here
                // was reading an attribute that does not exist.
                toggleGroup(key, fallback) {
                    this.groups[key] = !this.isOpen(key, fallback);
                    this.save();
                },
            });
            Alpine.store('adminNav').init();
            // Debounced: a drag-resize fires this continuously otherwise.
            let t; window.addEventListener('resize', () => {
                clearTimeout(t);
                t = setTimeout(() => Alpine.store('adminNav').onResize(), 150);
            });
        });
    </script>
</head>
<body class="bg-gradient-to-br from-gray-50 via-blue-50 to-indigo-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 min-h-screen transition-colors duration-300">
    <div class="min-h-screen">
        <!-- Modern Admin Header with Gradient -->
        <nav class="bg-gradient-to-r from-indigo-600 via-purple-600 to-indigo-800 shadow-xl" x-data="{ mobileMenuOpen: false }">
            <div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-6">
                <div class="flex justify-between h-16">
                    <!-- Logo & Brand -->
                    <div class="flex items-center">
                        {{-- Collapsing the rail is the point: the tables and the plan editor
                             want the whole width. --}}
                        <button type="button" @click="$store.adminNav.toggle()"
                                title="Show or hide the sidebar"
                                class="mr-3 w-9 h-9 rounded-lg bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors">
                            <i class="fas fa-bars"></i>
                        </button>
                        <div class="flex items-center space-x-2 bg-white/10 backdrop-blur-sm rounded-lg px-3 py-1.5">
                            <img src="{{ \App\Models\BrandSetting::horizontalUrl() }}" alt="Eureka! Performa" class="h-8 w-auto">
                            <div class="border-l border-white/30 h-6"></div>
                            <div>
                                {{-- The page title is back here now that the brand lives in the
                                     sidebar; the topbar says where you are, the rail says what
                                     else there is. --}}
                                <h1 class="text-lg font-bold text-white">@yield('page-title', 'Admin Panel')</h1>
                                <div class="text-xs text-indigo-100">Team Building Management System</div>
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
                            
                            
                            <form method="POST" action="{{ route('logout') }}" class="inline">
                                @csrf
                                <button type="submit" class="text-white/80 hover:text-white hover:bg-white/10 transition-all duration-200 px-2 py-1 rounded text-sm flex items-center space-x-1">
                                    <i class="fas fa-sign-out-alt text-xs"></i>
                                    <span class="hidden sm:inline">{{ __('auth.logout') }}</span>
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    {{-- This menu holds dark mode, language and the account — not navigation,
                         which now lives in the rail. Two identical hamburgers side by side on a
                         phone read as a bug, so this one shows what it actually opens. --}}
                    <div class="md:hidden flex items-center">
                        <button @click="mobileMenuOpen = !mobileMenuOpen" title="Account and settings"
                                class="text-white/80 hover:text-white hover:bg-white/10 transition-all duration-200 w-9 h-9 rounded-lg flex items-center justify-center">
                            <i class="fas" :class="mobileMenuOpen ? 'fa-times' : 'fa-ellipsis-v'"></i>
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

        <!-- Sidebar + content. The rail folds away and the page takes the full width. -->
        {{-- x-data is required even though the state lives in a store: Alpine only walks
             the tree from a root, so directives on an element with no x-data ancestor are
             never initialised. Without this the sidebar simply never appeared. --}}
        <div class="flex" x-data>
            @include('admin.partials.sidebar')

            <div class="flex-1 min-w-0 py-4">
                {{-- max-w is generous rather than absent: the rail already takes width,
                     and an unbounded line length is hard to read on a wide monitor. --}}
                <div class="max-w-[1600px] mx-auto px-3 sm:px-4 lg:px-6">
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

                <!-- Enhanced Main Content -->
                {{-- No overflow-hidden: it clips any position:fixed modal a page renders inside it. --}}
                <div class="admin-card bg-white dark:bg-gray-800 shadow-lg rounded-xl border border-gray-100 dark:border-gray-700 transition-colors duration-300">
                    <div class="p-4 lg:p-6">
                        @yield('content')
                    </div>
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
                navigator.serviceWorker.register('/sw.js?v={{ @filemtime(public_path('sw.js')) ?: 0 }}')
                    .then(function(registration) {
                    })
                    .catch(function(error) {
                    });
            });
        }
        
    </script>
 
    <!-- MapLibre GL JS - Global Loading for Admin -->
    <!-- MapLibre GL JS is now loaded via CDN -->
    
    <script>
        // Global MapLibre availability checker for Admin - updated for lazy loading
        window.waitForMapLibre = async function(callback, timeout = 5000) {
            try {
                if (window.loadMapUtils) {
                    await window.loadMapUtils();
                    callback(true);
                    return;
                }
                
                // Fallback to checking if it's already loaded
                const startTime = Date.now();
                
                function check() {
                    if (typeof window.maplibregl !== 'undefined') {
                        callback(true);
                    } else if (Date.now() - startTime > timeout) {
                        console.error('[ADMIN LAYOUT] MapLibre GL JS failed to load within timeout');
                        callback(false);
                    } else {
                        setTimeout(check, 50);
                    }
                }
                
                check();
            } catch (error) {
                console.error('[ADMIN LAYOUT] Failed to load MapLibre:', error);
                callback(false);
            }
        };
        
        // Log when MapLibre is loaded
        document.addEventListener('DOMContentLoaded', function() {
            waitForMapLibre(function(loaded) {
                if (loaded) {
                    document.dispatchEvent(new CustomEvent('maplibre:loaded'));
                } else {
                    console.error('[ADMIN LAYOUT] ✗ MapLibre GL JS failed to load');
                    document.dispatchEvent(new CustomEvent('maplibre:failed'));
                }
            });
        });
    </script>

    <!-- Add this stack for any additional scripts that might be pushed from other components -->
    @stack('scripts')
    
    <!-- Marzipano is now loaded via Vite as npm package -->
    
    @livewireScripts
    
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('console-log', (data) => {
            });
        });
    </script>
</body>
</html>