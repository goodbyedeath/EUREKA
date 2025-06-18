{{-- resources/views/user/dashboard.blade.php --}}
@extends('layouts.appUser')

@section('title', __('common.user_dashboard'))

@section('content')
<!-- User Navigation Header -->
<nav class="bg-white shadow-sm border-b" x-data="{ mobileMenuOpen: false }">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <!-- Logo -->
            <div class="flex items-center">
                <a href="{{ route('user.dashboard') }}" class="text-xl font-bold text-gray-800">Eureka</a>
            </div>
            
            <!-- Desktop Navigation -->
            <div class="hidden md:flex items-center space-x-4">
                <!-- Language Switcher -->
                @include('components.simple-language-switcher')
                
                @auth
                    <!-- User Profile/Settings -->
                    <div class="flex items-center space-x-3">
                        <span class="text-gray-600 text-sm hidden lg:block">{{ auth()->user()->name }}</span>
                        
                        <!-- Logout -->
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-red-600 hover:text-red-800 text-sm px-3 py-1 rounded">
                                <i class="fas fa-sign-out-alt mr-1"></i>
                                <span class="hidden sm:inline">{{ __('auth.logout') }}</span>
                            </button>
                        </form>
                    </div>
                @endauth
            </div>
            
            <!-- Mobile menu button -->
            <div class="md:hidden flex items-center">
                <button @click="mobileMenuOpen = !mobileMenuOpen" 
                        class="text-gray-500 hover:text-gray-700 focus:outline-none focus:text-gray-700 p-2">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
             class="md:hidden border-t border-gray-200 bg-white">
            <div class="px-4 py-3 space-y-3">
                <!-- Mobile Language Switcher -->
                <div class="flex items-center space-x-2">
                    <span class="text-sm text-gray-600">{{ __('common.language') }}:</span>
                    @include('components.simple-language-switcher')
                </div>
                
                @auth
                    <!-- Mobile User Info -->
                    <div class="border-t pt-3">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 text-sm">{{ auth()->user()->name }}</span>
                            <form method="POST" action="{{ route('logout') }}" class="inline">
                                @csrf
                                <button type="submit" class="text-red-600 hover:text-red-800 text-sm px-3 py-1 rounded border border-red-200">
                                    <i class="fas fa-sign-out-alt mr-1"></i>
                                    {{ __('auth.logout') }}
                                </button>
                            </form>
                        </div>
                    </div>
                @endauth
            </div>
        </div>
    </div>
</nav>

<!-- Flash Messages -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
    @if(session('message'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded text-sm">
            {{ session('message') }}
        </div>
    @endif
    
    @if(session('error'))
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded text-sm">
            {{ session('error') }}
        </div>
    @endif
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 lg:py-6">
    {{-- Page Title Section --}}
    <div class="flex flex-col space-y-4 md:flex-row md:justify-between md:items-center md:space-y-0 mb-6">
        <div class="min-w-0 flex-1">
            <h1 class="text-xl md:text-2xl font-bold text-gray-900 truncate">
                {{ $team->name }} {{ __('common.dashboard') }}
            </h1>
            <p class="text-gray-600 mt-1 text-sm md:text-base">
                {{ __('common.welcome') }}, {{ auth()->user()->name }}
            </p>
        </div>
        <div class="flex-shrink-0">
            {{-- Dashboard specific actions --}}
            @livewire('user.dashboard-header')
        </div>
    </div>

    {{-- Tab Navigation --}}
    <div class="mb-6">
        @livewire('user.dashboard-tabs')
    </div>

    {{-- Main Content --}}
    <main>
        <div id="dashboard-content">
            {{-- Content will be loaded dynamically by the tabs component --}}
            @livewire('user.dashboard-content')
        </div>
    </main>
</div>

{{-- Include QR Scanner Modal --}}
@livewire('user.q-r-scanner-modal')

@push('styles')
<style>
    .fade-in {
        animation: fadeIn 0.3s ease-in-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .animate-scale-in {
        animation: scaleIn 0.2s ease-out;
    }

    @keyframes scaleIn {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
    }
</style>
@endpush

@push('scripts')

@endpush