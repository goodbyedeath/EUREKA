{{-- resources/views/user/dashboard.blade.php --}}
@extends('layouts.appUser')

@section('title', __('common.user_dashboard'))

@section('content')

<!-- Pass session timeout to JavaScript -->
<script>
    window.userSessionTimeout = {{ auth()->user()->getSessionTimeout() / 60 }}; // Convert to minutes
</script>

<div class="dashboard-premium">
    <!-- Floating Particles Background -->
    <div class="particles-background">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <!-- Gradient Orbs -->
    <div class="gradient-orbs">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
        <div class="orb orb-4"></div>
    </div>

    <!-- Premium Navigation Header -->
    <nav class="premium-nav" x-data="{ mobileMenuOpen: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20">
                <!-- Logo & Brand -->
                <div class="flex items-center">
                    <div class="brand-container">
                        <img src="{{ \App\Models\BrandSetting::horizontalUrl() }}" alt="Eureka! Performa" class="brand-logo">
                        <div class="brand-divider"></div>
                        <a href="{{ route('user.dashboard') }}" class="brand-text">
                            Dashboard
                        </a>
                    </div>
                </div>
                
                <!-- Desktop Navigation -->
                <div class="hidden md:flex items-center space-x-4">
                    <!-- Dark Mode Toggle -->
                    <div class="nav-item">
                        <button data-theme-toggle class="dark-mode-btn" title="Toggle Dark Mode">
                            <i class="fas fa-sun sun-icon" style="display: none;"></i>
                            <i class="fas fa-moon moon-icon"></i>
                        </button>
                    </div>
                    
                    <!-- Language Switcher -->
                    <div class="nav-item">
                        @include('components.simple-language-switcher')
                    </div>
                    
                    @auth
                        <!-- Session Timer Display -->
                        @if(auth()->user()->role === 'user' && class_exists('\App\Models\FeatureSetting') && \App\Models\FeatureSetting::isEnabled('user_dashboard_session_timer'))
                            @php
                                $workflowTimersEnabled = class_exists('\App\Models\FeatureSetting') && \App\Models\FeatureSetting::isEnabled('workflow_timers');
                                $dashboardContent = app()->make('App\Livewire\User\DashboardContent');
                                $dashboardContent->mount();
                                $timerStatus = $workflowTimersEnabled ? $dashboardContent->getSessionTimerStatus() : 'normal';
                                $displayTime = $workflowTimersEnabled ? $dashboardContent->getFormattedSessionTime() : '--:--';
                            @endphp
                            <div class="session-timer-display {{ $timerStatus }}" id="session-timer-container" style="display: {{ $workflowTimersEnabled ? 'flex' : 'none' }};">
                                <div class="timer-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="timer-info">
                                    <span class="timer-label">Session</span>
                                    <div class="timer-value" id="session-timer-value">{{ $displayTime }}</div>
                                </div>
                            </div>
                        @endif
                        
                        <!-- User Profile Section -->
                        <div class="user-profile-section">
                            <!-- User Avatar -->
                            <div class="user-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="user-info">
                                <span class="user-name">{{ auth()->user()->name }}</span>
                                <div class="user-role">Team Member</div>
                            </div>
                            
                            <!-- Logout Button -->
                            <form method="POST" action="{{ route('logout') }}" class="inline">
                                @csrf
                                <button type="submit" class="logout-btn">
                                    <i class="fas fa-sign-out-alt"></i>
                                    <span class="hidden sm:inline">{{ __('auth.logout') }}</span>
                                </button>
                            </form>
                        </div>
                    @endauth
                </div>
                
                <!-- Mobile menu button -->
                <div class="md:hidden flex items-center">
                    <button @click="mobileMenuOpen = !mobileMenuOpen" class="mobile-menu-btn">
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
                 class="mobile-menu">
                <div class="mobile-menu-content">
                    <!-- Mobile Dark Mode Toggle -->
                    <div class="mobile-item">
                        <div class="mobile-item-header">
                            <span>Theme:</span>
                        </div>
                        <button data-theme-toggle class="mobile-dark-mode-btn">
                            <i class="fas fa-sun sun-icon" style="display: none;"></i>
                            <i class="fas fa-moon moon-icon"></i>
                            <span class="mobile-theme-text">Dark Mode</span>
                        </button>
                    </div>
                    
                    <!-- Mobile Language Switcher -->
                    <div class="mobile-item">
                        <div class="mobile-item-header">
                            <span>{{ __('common.language') }}:</span>
                        </div>
                        @include('components.simple-language-switcher')
                    </div>
                    
                    @auth
                        <!-- Mobile Session Timer -->
                        @if(auth()->user()->role === 'user' && class_exists('\App\Models\FeatureSetting') && \App\Models\FeatureSetting::isEnabled('user_dashboard_session_timer'))
                            <div class="mobile-item mobile-session-timer {{ $timerStatus ?? 'normal' }}" id="mobile-session-timer" style="display: {{ $workflowTimersEnabled ?? false ? 'block' : 'none' }};">
                                <div class="mobile-item-header">
                                    <span>Session Time Remaining:</span>
                                </div>
                                <div class="mobile-session-display">
                                    <i class="fas fa-clock text-blue-500"></i>
                                    <span id="mobile-session-timer-value">{{ $displayTime ?? '--:--' }}</span>
                                </div>
                            </div>
                        @endif
                        
                        <!-- Mobile User Info -->
                        <div class="mobile-item">
                            <div class="mobile-user-info">
                                <div class="mobile-user-avatar">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div>
                                    <div class="mobile-user-name">{{ auth()->user()->name }}</div>
                                    <div class="mobile-user-role">Team Member</div>
                                </div>
                            </div>
                            <form method="POST" action="{{ route('logout') }}" class="w-full mt-3">
                                @csrf
                                <button type="submit" class="mobile-logout-btn">
                                    <i class="fas fa-sign-out-alt"></i>
                                    <span>{{ __('auth.logout') }}</span>
                                </button>
                            </form>
                        </div>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Flash Messages handled by layout -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
    </div>

    <!-- Premium Dashboard Content -->
    <div class="dashboard-content">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            
            <!-- Hero Welcome Section -->
            <div class="hero-section">
                <div class="hero-background-effect"></div>
                <div class="hero-content">
                    <div class="hero-left">
                        <div class="hero-title-section">
                            <div class="hero-icon">
                                <i class="fas fa-rocket"></i>
                            </div>
                            <div class="hero-text">
                                <h1 class="hero-title">
                                    {{ $team->name ?? 'Your Team' }} {{ __('common.dashboard') }}
                                </h1>
                                <p class="hero-subtitle">
                                    {{ __('common.welcome') }}, <span class="hero-username">{{ auth()->user()->name }}</span>!
                                </p>
                            </div>
                        </div>
                        
                    </div>
                    
                    <!-- Action Panel -->
                    <div class="action-panel">
                        @livewire('user.dashboard-header')
                    </div>
                </div>
            </div>


            <!-- Dashboard Stats -->
            <div class="stats-section">
                @livewire('user.dashboard-stats')
            </div>

            <!-- Tab Navigation -->
            <div class="tab-navigation">
                @livewire('user.dashboard-tabs')
            </div>

            <!-- Main Content Area -->
            <div class="main-content-area">
                <div class="content-card">
                    @livewire('user.dashboard-content')
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Include QR Scanner Modal (Alpine.js) --}}
@include('components.qr-scanner-modal')

{{-- Include Score Breakdown Modal --}}
@livewire('user.score-breakdown')

@push('styles')
<style>
    /* Premium Dashboard Styles */
    .dashboard-premium {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 25%, #f093fb 50%, #f5576c 75%, #4facfe 100%);
        min-height: 100vh;
        position: relative;
        overflow-x: hidden;
        transition: all 0.3s ease;
    }
    
    /* Dark mode dashboard background */
    .dark .dashboard-premium {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 25%, #334155 50%, #475569 75%, #1e293b 100%);
    }
    
    /* Floating Particles */
    .particles-background {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: 1;
    }
    
    .particle {
        position: absolute;
        width: 6px;
        height: 6px;
        background: rgba(255, 255, 255, 0.4);
        border-radius: 50%;
        animation: particleFloat 25s infinite linear;
        transition: background 0.3s ease;
    }
    
    .dark .particle {
        background: rgba(148, 163, 184, 0.3);
    }
    
    .particle:nth-child(1) { left: 5%; animation-delay: 0s; animation-duration: 20s; }
    .particle:nth-child(2) { left: 15%; animation-delay: 3s; animation-duration: 25s; }
    .particle:nth-child(3) { left: 25%; animation-delay: 6s; animation-duration: 22s; }
    .particle:nth-child(4) { left: 35%; animation-delay: 9s; animation-duration: 28s; }
    .particle:nth-child(5) { left: 45%; animation-delay: 12s; animation-duration: 18s; }
    .particle:nth-child(6) { left: 55%; animation-delay: 15s; animation-duration: 26s; }
    .particle:nth-child(7) { left: 65%; animation-delay: 18s; animation-duration: 24s; }
    .particle:nth-child(8) { left: 75%; animation-delay: 21s; animation-duration: 30s; }
    .particle:nth-child(9) { left: 85%; animation-delay: 24s; animation-duration: 19s; }
    .particle:nth-child(10) { left: 95%; animation-delay: 27s; animation-duration: 23s; }
    
    /* Gradient Orbs */
    .gradient-orbs {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: 0;
    }
    
    .orb {
        position: absolute;
        border-radius: 50%;
        filter: blur(60px);
        animation: orbFloat 40s infinite ease-in-out;
    }
    
    .orb-1 {
        width: 400px;
        height: 400px;
        background: radial-gradient(circle, rgba(102, 126, 234, 0.3) 0%, transparent 70%);
        top: 5%;
        left: 5%;
        animation-delay: 0s;
    }
    
    .orb-2 {
        width: 350px;
        height: 350px;
        background: radial-gradient(circle, rgba(245, 87, 108, 0.3) 0%, transparent 70%);
        top: 60%;
        right: 5%;
        animation-delay: 15s;
    }
    
    .orb-3 {
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, rgba(79, 172, 254, 0.3) 0%, transparent 70%);
        bottom: 5%;
        left: 20%;
        animation-delay: 30s;
    }
    
    .orb-4 {
        width: 280px;
        height: 280px;
        background: radial-gradient(circle, rgba(240, 147, 251, 0.3) 0%, transparent 70%);
        top: 30%;
        left: 60%;
        animation-delay: 45s;
    }
    
    /* Premium Navigation */
    .premium-nav {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        position: relative;
        z-index: 50;
        transition: all 0.3s ease;
    }
    
    .dark .premium-nav {
        background: rgba(15, 23, 42, 0.95);
        border-bottom: 1px solid rgba(51, 65, 85, 0.3);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    }
    
    .brand-container {
        display: flex;
        align-items: center;
        gap: 12px;
        background: rgba(102, 126, 234, 0.1);
        backdrop-filter: blur(10px);
        border-radius: 16px;
        padding: 12px 16px;
        border: 1px solid rgba(102, 126, 234, 0.2);
        transition: all 0.3s ease;
    }
    
    .brand-container:hover {
        background: rgba(102, 126, 234, 0.15);
        transform: translateY(-1px);
        box-shadow: 0 8px 24px rgba(102, 126, 234, 0.2);
    }
    
    .brand-logo {
        height: 32px;
        width: auto;
    }
    
    .brand-divider {
        width: 1px;
        height: 24px;
        background: rgba(102, 126, 234, 0.3);
    }
    
    .brand-text {
        font-size: 18px;
        font-weight: 700;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        text-decoration: none;
        transition: all 0.3s ease;
    }
    
    .nav-item {
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(10px);
        border-radius: 12px;
        padding: 8px 12px;
        border: 1px solid rgba(255, 255, 255, 0.3);
        transition: all 0.3s ease;
    }
    
    .dark .nav-item {
        background: rgba(51, 65, 85, 0.7);
        border: 1px solid rgba(71, 85, 105, 0.3);
    }
    
    /* Dark Mode Toggle */
    .dark-mode-btn {
        background: transparent;
        border: none;
        color: #374151;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 8px;
    }
    
    .dark-mode-btn:hover {
        background: rgba(0, 0, 0, 0.1);
        color: #667eea;
        transform: scale(1.1);
    }
    
    .mobile-dark-mode-btn {
        width: 100%;
        background: rgba(255, 255, 255, 0.5);
        border: 1px solid rgba(255, 255, 255, 0.3);
        border-radius: 8px;
        padding: 12px 16px;
        display: flex;
        align-items: center;
        gap: 12px;
        color: #374151;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .mobile-dark-mode-btn:hover {
        background: rgba(255, 255, 255, 0.7);
        transform: translateY(-1px);
    }
    
    .dark .mobile-dark-mode-btn {
        background: rgba(51, 65, 85, 0.5);
        border: 1px solid rgba(71, 85, 105, 0.3);
        color: #f1f5f9;
    }
    
    .dark .mobile-dark-mode-btn:hover {
        background: rgba(51, 65, 85, 0.7);
    }
    
    .mobile-theme-text {
        font-size: 14px;
        font-weight: 500;
    }
    
    .user-profile-section {
        display: flex;
        align-items: center;
        gap: 12px;
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(10px);
        border-radius: 16px;
        padding: 8px 16px;
        border: 1px solid rgba(255, 255, 255, 0.3);
        transition: all 0.3s ease;
    }
    
    .dark .user-profile-section {
        background: rgba(51, 65, 85, 0.7);
        border: 1px solid rgba(71, 85, 105, 0.3);
    }
    
    .user-profile-section:hover {
        background: rgba(255, 255, 255, 0.85);
        transform: translateY(-1px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
    }
    
    .user-avatar {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 16px;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }
    
    .user-info {
        display: flex;
        flex-direction: column;
    }
    
    .user-name {
        font-weight: 600;
        color: #1f2937;
        font-size: 14px;
        transition: color 0.3s ease;
    }
    
    .dark .user-name {
        color: #f1f5f9;
    }
    
    .user-role {
        font-size: 12px;
        color: #6b7280;
        transition: color 0.3s ease;
    }
    
    .dark .user-role {
        color: #94a3b8;
    }
    
    .logout-btn {
        background: rgba(239, 68, 68, 0.1);
        color: #dc2626;
        border: 1px solid rgba(239, 68, 68, 0.2);
        border-radius: 10px;
        padding: 8px 12px;
        font-size: 14px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .logout-btn:hover {
        background: rgba(239, 68, 68, 0.15);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2);
    }
    
    .mobile-menu-btn {
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(10px);
        border-radius: 12px;
        padding: 8px;
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: #374151;
        transition: all 0.3s ease;
    }
    
    .mobile-menu {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        border-top: 1px solid rgba(255, 255, 255, 0.2);
        margin-top: 8px;
        border-radius: 16px;
        overflow: hidden;
    }
    
    .dark .mobile-menu {
        background: rgba(15, 23, 42, 0.95);
        border-top: 1px solid rgba(51, 65, 85, 0.3);
    }
    
    .mobile-menu-content {
        padding: 16px;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }
    
    .mobile-item {
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(10px);
        border-radius: 12px;
        padding: 12px;
        border: 1px solid rgba(255, 255, 255, 0.3);
    }
    
    .dark .mobile-item {
        background: rgba(51, 65, 85, 0.7);
        border: 1px solid rgba(71, 85, 105, 0.3);
    }
    
    .mobile-item-header {
        font-size: 14px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
    }
    
    .dark .mobile-item-header {
        color: #f1f5f9;
    }
    
    .mobile-user-info {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
    }
    
    .mobile-user-avatar {
        width: 36px;
        height: 36px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 14px;
    }
    
    .mobile-user-name {
        font-weight: 600;
        color: #1f2937;
        font-size: 14px;
    }
    
    .dark .mobile-user-name {
        color: #f1f5f9;
    }
    
    .mobile-user-role {
        font-size: 12px;
        color: #6b7280;
    }
    
    .dark .mobile-user-role {
        color: #94a3b8;
    }
    
    .mobile-logout-btn {
        width: 100%;
        background: rgba(239, 68, 68, 0.1);
        color: #dc2626;
        border: 1px solid rgba(239, 68, 68, 0.2);
        border-radius: 10px;
        padding: 12px;
        font-size: 14px;
        font-weight: 500;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .mobile-logout-btn:hover {
        background: rgba(239, 68, 68, 0.15);
    }
    
    /* Session Timer Display */
    .session-timer-display {
        display: flex;
        align-items: center;
        gap: 8px;
        background: rgba(79, 172, 254, 0.1);
        backdrop-filter: blur(10px);
        border-radius: 12px;
        padding: 8px 12px;
        border: 1px solid rgba(79, 172, 254, 0.2);
        transition: all 0.3s ease;
        animation: timerPulse 2s infinite;
    }
    
    .dark .session-timer-display {
        background: rgba(79, 172, 254, 0.15);
        border: 1px solid rgba(79, 172, 254, 0.3);
    }
    
    .session-timer-display.warning {
        background: rgba(245, 158, 11, 0.1);
        border-color: rgba(245, 158, 11, 0.3);
        animation: timerWarning 1s infinite;
    }
    
    .session-timer-display.critical {
        background: rgba(239, 68, 68, 0.1);
        border-color: rgba(239, 68, 68, 0.3);
        animation: timerCritical 0.5s infinite;
    }
    
    .timer-icon {
        width: 24px;
        height: 24px;
        background: linear-gradient(135deg, #4facfe 0%, #3b82f6 100%);
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 12px;
    }
    
    .session-timer-display.warning .timer-icon {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    }
    
    .session-timer-display.critical .timer-icon {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    }
    
    .timer-info {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
    }
    
    .timer-label {
        font-size: 10px;
        font-weight: 500;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        line-height: 1;
    }
    
    .dark .timer-label {
        color: #94a3b8 !important;
    }
    
    .timer-value {
        font-size: 12px;
        font-weight: 700;
        color: #1f2937;
        font-family: 'SF Mono', 'Monaco', monospace;
        line-height: 1;
        margin-top: 2px;
    }
    
    .dark .timer-value {
        color: #f1f5f9 !important;
    }
    
    .session-timer-display.warning .timer-value {
        color: #d97706;
    }
    
    .session-timer-display.critical .timer-value {
        color: #dc2626;
    }
    
    /* Mobile Session Timer */
    .mobile-session-display {
        display: flex;
        align-items: center;
        gap: 8px;
        background: rgba(79, 172, 254, 0.1);
        border: 1px solid rgba(79, 172, 254, 0.2);
        border-radius: 8px;
        padding: 8px 12px;
        font-family: 'SF Mono', 'Monaco', monospace;
        font-weight: 600;
        font-size: 14px;
        color: #1f2937;
    }
    
    .dark .mobile-session-display {
        background: rgba(79, 172, 254, 0.15);
        border-color: rgba(79, 172, 254, 0.3);
        color: #f1f5f9 !important;
    }
    
    .mobile-session-timer.warning .mobile-session-display {
        background: rgba(245, 158, 11, 0.1);
        border-color: rgba(245, 158, 11, 0.3);
        color: #d97706;
    }
    
    .mobile-session-timer.critical .mobile-session-display {
        background: rgba(239, 68, 68, 0.1);
        border-color: rgba(239, 68, 68, 0.3);
        color: #dc2626;
    }
    
    /* Timer Animations */
    @keyframes timerPulse {
        0%, 100% { 
            box-shadow: 0 0 0 0 rgba(79, 172, 254, 0.3);
        }
        50% { 
            box-shadow: 0 0 0 4px rgba(79, 172, 254, 0.1);
        }
    }
    
    @keyframes timerWarning {
        0%, 100% { 
            box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.4);
        }
        50% { 
            box-shadow: 0 0 0 6px rgba(245, 158, 11, 0.2);
        }
    }
    
    @keyframes timerCritical {
        0%, 100% { 
            box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.5);
            transform: scale(1);
        }
        50% { 
            box-shadow: 0 0 0 8px rgba(239, 68, 68, 0.2);
            transform: scale(1.02);
        }
    }
    
    /* Flash Messages */
    .flash-message {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px 20px;
        border-radius: 16px;
        margin-bottom: 16px;
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        animation: slideInDown 0.5s ease-out;
    }
    
    .flash-message.success {
        background: rgba(16, 185, 129, 0.1);
        border-left: 4px solid #10b981;
    }
    
    .flash-message.error {
        background: rgba(239, 68, 68, 0.1);
        border-left: 4px solid #ef4444;
    }
    
    .flash-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }
    
    .flash-message.success .flash-icon {
        background: rgba(16, 185, 129, 0.2);
        color: #059669;
    }
    
    .flash-message.error .flash-icon {
        background: rgba(239, 68, 68, 0.2);
        color: #dc2626;
    }
    
    .flash-content p {
        margin: 0;
        font-weight: 600;
        font-size: 14px;
    }
    
    .flash-message.success .flash-content p {
        color: #065f46;
    }
    
    .flash-message.error .flash-content p {
        color: #991b1b;
    }
    
    /* Dashboard Content */
    .dashboard-content {
        position: relative;
        z-index: 10;
    }
    
    /* Hero Section */
    .hero-section {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        border-radius: 24px;
        padding: 40px;
        margin-bottom: 32px;
        border: 1px solid rgba(255, 255, 255, 0.3);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
    }
    
    .dark .hero-section {
        background: rgba(15, 23, 42, 0.95);
        border: 1px solid rgba(51, 65, 85, 0.3);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
    }
    
    .hero-background-effect {
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: conic-gradient(from 0deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1), rgba(245, 87, 108, 0.1), rgba(102, 126, 234, 0.1));
        animation: rotateBackground 60s linear infinite;
        pointer-events: none;
    }
    
    .hero-content {
        position: relative;
        z-index: 2;
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 40px;
        align-items: start;
    }
    
    .hero-title-section {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-bottom: 32px;
    }
    
    .hero-icon {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: white;
        box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
        animation: iconPulse 3s infinite;
    }
    
    .hero-title {
        font-size: 32px;
        font-weight: 900;
        background: linear-gradient(135deg, #1f2937 0%, #374151 50%, #1f2937 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        line-height: 1.2;
        margin-bottom: 8px;
    }
    
    .hero-subtitle {
        font-size: 16px;
        color: #6b7280;
        font-weight: 500;
    }
    
    .hero-username {
        font-weight: 700;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    /* Quick Stats Grid */
    .quick-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    
    .stat-card {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(15px);
        border-radius: 16px;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 16px;
        border: 1px solid rgba(255, 255, 255, 0.3);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
        cursor: pointer;
    }

    .clickable-stat {
        cursor: pointer;
        user-select: none;
    }

    .clickable-stat:hover {
        transform: translateY(-4px) scale(1.02);
        box-shadow: 0 16px 40px rgba(0, 0, 0, 0.2);
    }

    .clickable-stat:active {
        transform: translateY(-2px) scale(1.01);
    }
    
    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 32px rgba(0, 0, 0, 0.15);
    }
    
    .stat-card:hover .stat-glow {
        opacity: 1;
    }
    
    .stat-glow {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        opacity: 0;
        transition: opacity 0.3s ease;
        pointer-events: none;
        border-radius: 16px;
    }
    
    .stat-card.stat-primary .stat-glow {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
    }
    
    .stat-card.stat-success .stat-glow {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(5, 150, 105, 0.1) 100%);
    }
    
    .stat-card.stat-warning .stat-glow {
        background: linear-gradient(135deg, rgba(245, 158, 11, 0.1) 0%, rgba(217, 119, 6, 0.1) 100%);
    }
    
    .stat-card.stat-info .stat-glow {
        background: linear-gradient(135deg, rgba(79, 172, 254, 0.1) 0%, rgba(59, 130, 246, 0.1) 100%);
    }
    
    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        color: white;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }
    
    .stat-card.stat-primary .stat-icon {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .stat-card.stat-success .stat-icon {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    }
    
    .stat-card.stat-warning .stat-icon {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    }
    
    .stat-card.stat-info .stat-icon {
        background: linear-gradient(135deg, #4facfe 0%, #3b82f6 100%);
    }
    
    .stat-value {
        font-size: 24px;
        font-weight: 900;
        color: #1f2937;
        line-height: 1;
        font-family: 'SF Mono', 'Monaco', monospace;
    }
    
    .stat-label {
        font-size: 12px;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-top: 4px;
    }
    
    /* Action Panel */
    .action-panel {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(15px);
        border-radius: 20px;
        padding: 24px;
        border: 1px solid rgba(255, 255, 255, 0.3);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
        min-width: 300px;
    }
    
    /* Stats Section */
    .stats-section {
        margin-bottom: 32px;
    }
    
    /* Tab Navigation */
    .tab-navigation {
        margin-bottom: 24px;
    }
    
    /* Main Content */
    .main-content-area {
        position: relative;
    }
    
    .content-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        border-radius: 20px;
        border: 1px solid rgba(255, 255, 255, 0.3);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        padding: 32px;
        transition: all 0.3s ease;
    }
    
    .dark .content-card {
        background: rgba(15, 23, 42, 0.95);
        border: 1px solid rgba(51, 65, 85, 0.3);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
    }
    
    /* Animations */
    @keyframes particleFloat {
        0%, 100% { transform: translateY(0px) translateX(0px) rotate(0deg); opacity: 0.3; }
        25% { transform: translateY(-30px) translateX(15px) rotate(90deg); opacity: 0.6; }
        50% { transform: translateY(-60px) translateX(-10px) rotate(180deg); opacity: 0.9; }
        75% { transform: translateY(-30px) translateX(-20px) rotate(270deg); opacity: 0.6; }
    }
    
    @keyframes orbFloat {
        0%, 100% { transform: translateY(0px) translateX(0px) scale(1); }
        25% { transform: translateY(-40px) translateX(30px) scale(1.1); }
        50% { transform: translateY(20px) translateX(-20px) scale(0.9); }
        75% { transform: translateY(-20px) translateX(-30px) scale(1.05); }
    }
    
    @keyframes rotateBackground {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    @keyframes iconPulse {
        0%, 100% { transform: scale(1); box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3); }
        50% { transform: scale(1.05); box-shadow: 0 12px 32px rgba(102, 126, 234, 0.5); }
    }
    
    @keyframes slideInDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    /* Responsive Design */
    @media (max-width: 1024px) {
        .hero-content {
            grid-template-columns: 1fr;
            gap: 24px;
        }
        
        .action-panel {
            min-width: auto;
            width: 100%;
        }
        
        .quick-stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (max-width: 768px) {
        .hero-section {
            padding: 24px;
        }
        
        .hero-title {
            font-size: 24px;
        }
        
        .quick-stats-grid {
            grid-template-columns: 1fr;
        }
        
        .content-card {
            padding: 20px;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    // Error handling for Livewire components
    window.addEventListener('error', function(e) {
        if (e.message && e.message.includes("Cannot read properties of undefined (reading 'call')")) {
            console.warn('Livewire component not ready, retrying...', e);
            e.preventDefault();
            return false;
        }
    });

    // Wait for Livewire to be fully loaded
    document.addEventListener('livewire:init', function() {
        // Livewire initialized
    });

    // Dashboard-specific dark mode setup
    function initDashboardDarkMode() {
        // Check if the global DarkMode is available
        if (typeof window.DarkMode !== 'undefined') {
            
            // Wait a bit for DOM to be fully ready, then update buttons
            setTimeout(() => {
                if (window.DarkMode.updateToggleButtons) {
                    window.DarkMode.updateToggleButtons();
                }
                updateMobileThemeText();
            }, 100);
            
        } else {
            // Simple fallback if the main system isn't loaded yet
            const buttons = document.querySelectorAll('[data-theme-toggle]');
            
            buttons.forEach((button) => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Simple toggle
                    const isDark = document.documentElement.classList.toggle('dark');
                    localStorage.setItem('theme', isDark ? 'dark' : 'light');
                    
                    // Update icons manually
                    updateDashboardIcons();
                    updateMobileThemeText();
                });
            });
            
            // Set initial state
            const savedTheme = localStorage.getItem('theme') || 'light';
            if (savedTheme === 'dark') {
                document.documentElement.classList.add('dark');
            }
            updateDashboardIcons();
            updateMobileThemeText();
        }
    }
    
    function updateDashboardIcons() {
        const isDark = document.documentElement.classList.contains('dark');
        const buttons = document.querySelectorAll('[data-theme-toggle]');
        
        buttons.forEach(button => {
            const sunIcon = button.querySelector('.sun-icon');
            const moonIcon = button.querySelector('.moon-icon');
            
            if (sunIcon && moonIcon) {
                if (isDark) {
                    sunIcon.style.display = 'block';
                    moonIcon.style.display = 'none';
                } else {
                    sunIcon.style.display = 'none';
                    moonIcon.style.display = 'block';
                }
            }
        });
    }

    function updateMobileThemeText() {
        const isDark = document.documentElement.classList.contains('dark');
        const themeTexts = document.querySelectorAll('.mobile-theme-text');
        
        themeTexts.forEach(text => {
            text.textContent = isDark ? 'Light Mode' : 'Dark Mode';
        });
    }

    // Add smooth scrolling and other interactions
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize dashboard dark mode
        initDashboardDarkMode();
        
        
        // Animate stats on load
        const statCards = document.querySelectorAll('.stat-card');
        statCards.forEach((card, index) => {
            setTimeout(() => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'all 0.6s ease';
                
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 100);
            }, index * 100);
        });
        
        // Add click effects to stat cards
        statCards.forEach(card => {
            card.addEventListener('click', function() {
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 150);
            });
        });
    });
</script>

@endpush

@endsection