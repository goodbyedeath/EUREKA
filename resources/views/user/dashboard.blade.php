{{-- resources/views/user/dashboard.blade.php --}}
@extends('layouts.appUser')

@section('title', __('common.user_dashboard'))

@section('content')
<!-- Compact Modern Navigation Header -->
<nav class="bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-800 shadow-lg" x-data="{ mobileMenuOpen: false }">
    <div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-6">
        <div class="flex justify-between h-16">
            <!-- Logo & Brand -->
            <div class="flex items-center">
                <div class="flex items-center space-x-2 bg-white/10 backdrop-blur-sm rounded-lg px-3 py-1.5">
                    <img src="/logo/horizonlogo.png" alt="Eureka! Performa" class="h-8 w-auto">
                    <div class="border-l border-white/30 h-6"></div>
                    <a href="{{ route('user.dashboard') }}" class="text-lg font-bold text-white hover:text-blue-100 transition-colors">
                        Dashboard
                    </a>
                </div>
            </div>
            
            <!-- Desktop Navigation -->
            <div class="hidden md:flex items-center space-x-3">
                <!-- Language Switcher -->
                <div class="bg-white/10 backdrop-blur-sm rounded-lg px-2 py-1.5">
                    @include('components.simple-language-switcher')
                </div>
                
                @auth
                    <!-- User Profile Section -->
                    <div class="flex items-center space-x-2 bg-white/10 backdrop-blur-sm rounded-lg px-3 py-1.5">
                        <!-- User Avatar -->
                        <div class="w-7 h-7 bg-white/20 rounded-full flex items-center justify-center">
                            <i class="fas fa-user text-white text-xs"></i>
                        </div>
                        <div class="hidden lg:block">
                            <span class="text-white font-medium text-sm">{{ auth()->user()->name }}</span>
                            <div class="text-blue-100 text-xs">{{ $team->name ?? 'Team Member' }}</div>
                        </div>
                        
                        <!-- Logout Button -->
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-white/80 hover:text-white hover:bg-white/10 transition-all duration-200 px-2 py-1 rounded text-sm flex items-center space-x-1">
                                <i class="fas fa-sign-out-alt text-xs"></i>
                                <span class="hidden sm:inline">{{ __('auth.logout') }}</span>
                            </button>
                        </form>
                    </div>
                @endauth
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
        
        <!-- Compact Mobile Navigation Menu -->
        <div x-show="mobileMenuOpen" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 transform scale-95"
             x-transition:enter-end="opacity-100 transform scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 transform scale-100"
             x-transition:leave-end="opacity-0 transform scale-95"
             class="md:hidden border-t border-white/20 bg-black/10 backdrop-blur-sm">
            <div class="px-3 py-3 space-y-3">
                <!-- Mobile Language Switcher -->
                <div class="bg-white/10 rounded-lg p-2">
                    <div class="flex items-center space-x-2 mb-1">
                        <span class="text-sm text-white font-medium">{{ __('common.language') }}:</span>
                    </div>
                    @include('components.simple-language-switcher')
                </div>
                
                @auth
                    <!-- Mobile User Info -->
                    <div class="bg-white/10 rounded-lg p-2">
                        <div class="flex items-center space-x-2 mb-2">
                            <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center">
                                <i class="fas fa-user text-white text-xs"></i>
                            </div>
                            <div>
                                <div class="text-white font-medium text-sm">{{ auth()->user()->name }}</div>
                                <div class="text-blue-100 text-xs">{{ $team->name ?? 'Team Member' }}</div>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <button type="submit" class="w-full text-left text-white/80 hover:text-white hover:bg-white/10 transition-all duration-200 px-2 py-1.5 rounded text-sm flex items-center space-x-2">
                                <i class="fas fa-sign-out-alt text-xs"></i>
                                <span>{{ __('auth.logout') }}</span>
                            </button>
                        </form>
                    </div>
                @endauth
            </div>
        </div>
    </div>
</nav>

<!-- Compact Flash Messages -->
<div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-6 pt-4">
    @if(session('message'))
        <div class="mb-4 bg-gradient-to-r from-green-50 to-emerald-50 border-l-4 border-green-400 p-3 rounded-r-lg shadow-sm animate-scale-in">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-green-400"></i>
                </div>
                <div class="ml-2">
                    <p class="text-green-700 font-medium text-sm">{{ session('message') }}</p>
                </div>
            </div>
        </div>
    @endif
    
    @if(session('error'))
        <div class="mb-4 bg-gradient-to-r from-red-50 to-rose-50 border-l-4 border-red-400 p-3 rounded-r-lg shadow-sm animate-scale-in">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle text-red-400"></i>
                </div>
                <div class="ml-2">
                    <p class="text-red-700 font-medium text-sm">{{ session('error') }}</p>
                </div>
            </div>
        </div>
    @endif
</div>

<!-- Compact Hero Section with Stats -->
<div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-6 py-4">
    <!-- Welcome Hero Card -->
    <div class="bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 rounded-xl shadow-lg border border-blue-100 p-4 lg:p-5 mb-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div class="flex-1 mb-4 lg:mb-0">
                <!-- Compact Title with Icon -->
                <div class="flex items-center space-x-2 mb-4">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center shadow-md">
                        <i class="fas fa-chart-line text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-xl lg:text-2xl font-bold text-gray-900">
                            {{ $team->name ?? 'Your Team' }} {{ __('common.dashboard') }}
                        </h1>
                        <p class="text-gray-600">
                            {{ __('common.welcome') }}, <span class="font-semibold text-blue-700">{{ auth()->user()->name }}</span>!
                        </p>
                    </div>
                </div>
                
                <!-- Compact Quick Stats -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                    <div class="stats-card bg-white/60 backdrop-blur-sm rounded-lg p-3 border border-white/50 shadow-sm cursor-pointer">
                        <div class="flex items-center space-x-2">
                            <div class="w-8 h-8 bg-gradient-to-br from-blue-400 to-blue-600 rounded-lg flex items-center justify-center shadow-sm">
                                <i class="fas fa-trophy text-white text-sm"></i>
                            </div>
                            <div>
                                <div class="text-xl font-bold text-gray-900">{{ $team->total_score ?? '0' }}</div>
                                <div class="text-gray-600 text-xs">Total Score</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stats-card bg-white/60 backdrop-blur-sm rounded-lg p-3 border border-white/50 shadow-sm cursor-pointer">
                        <div class="flex items-center space-x-2">
                            <div class="w-8 h-8 bg-gradient-to-br from-green-400 to-green-600 rounded-lg flex items-center justify-center shadow-sm">
                                <i class="fas fa-tasks text-white text-sm"></i>
                            </div>
                            <div>
                                <div class="text-xl font-bold text-gray-900">{{ $team->completed_quests ?? '0' }}</div>
                                <div class="text-gray-600 text-xs">Completed</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stats-card bg-white/60 backdrop-blur-sm rounded-lg p-3 border border-white/50 shadow-sm cursor-pointer">
                        <div class="flex items-center space-x-2">
                            <div class="w-8 h-8 bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-lg flex items-center justify-center shadow-sm">
                                <i class="fas fa-clock text-white text-sm"></i>
                            </div>
                            <div>
                                <div class="text-xl font-bold text-gray-900">{{ $team->pending_quests ?? '0' }}</div>
                                <div class="text-gray-600 text-xs">Pending</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stats-card bg-white/60 backdrop-blur-sm rounded-lg p-3 border border-white/50 shadow-sm cursor-pointer">
                        <div class="flex items-center space-x-2">
                            <div class="w-8 h-8 bg-gradient-to-br from-purple-400 to-purple-600 rounded-lg flex items-center justify-center shadow-sm">
                                <i class="fas fa-users text-white text-sm"></i>
                            </div>
                            <div>
                                <div class="text-xl font-bold text-gray-900">{{ $team->members_count ?? '1' }}</div>
                                <div class="text-gray-600 text-xs">Members</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Enhanced Action Section -->
            <div class="flex-shrink-0 lg:ml-6 w-full lg:w-auto mt-4 lg:mt-0">
                <div class="bg-white/80 backdrop-blur-sm rounded-xl p-4 border border-white/60 shadow-lg">
                    @livewire('user.dashboard-header')
                </div>
            </div>
        </div>
    </div>

    <!-- Compact Tab Navigation -->
    <div class="mb-5">
        <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
            @livewire('user.dashboard-tabs')
        </div>
    </div>

    <!-- Compact Main Content Area -->
    <main>
        <div id="dashboard-content" class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
            <div class="p-4 lg:p-5">
                @livewire('user.dashboard-content')
            </div>
        </div>
    </main>
</div>

{{-- Include QR Scanner Modal --}}
@livewire('user.q-r-scanner-modal')

@push('styles')
<style>
    /* Enhanced animations */
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

    .animate-scale-in {
        animation: scaleIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    @keyframes scaleIn {
        from { 
            opacity: 0; 
            transform: scale(0.8) translateY(-10px); 
        }
        to { 
            opacity: 1; 
            transform: scale(1) translateY(0); 
        }
    }

    /* Floating animation for stats cards */
    .stats-card {
        transition: all 0.3s ease;
    }

    .stats-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }

    /* Glassmorphism effect */
    .glass-effect {
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
    }

    /* Gradient text effect */
    .gradient-text {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        background-clip: text;
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    /* Enhanced button hover effects */
    .enhanced-button {
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .enhanced-button::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left 0.5s ease;
    }

    .enhanced-button:hover::before {
        left: 100%;
    }

    /* Pulse animation for important elements */
    .pulse-glow {
        animation: pulseGlow 2s infinite;
    }

    @keyframes pulseGlow {
        0%, 100% {
            box-shadow: 0 0 5px rgba(59, 130, 246, 0.5);
        }
        50% {
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.8), 0 0 30px rgba(59, 130, 246, 0.6);
        }
    }

    /* Smooth background transitions */
    .bg-transition {
        transition: background-color 0.3s ease, border-color 0.3s ease;
    }

    /* Custom scrollbar for content areas */
    .custom-scrollbar::-webkit-scrollbar {
        width: 8px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 4px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /* Loading skeleton animation */
    .skeleton {
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: skeleton-loading 1.5s infinite;
    }

    @keyframes skeleton-loading {
        0% {
            background-position: 200% 0;
        }
        100% {
            background-position: -200% 0;
        }
    }
</style>
@endpush

@push('scripts')

@endpush