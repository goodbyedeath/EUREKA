{{-- resources/views/user/dashboard.blade.php --}}
@extends('layouts.appUser')

@section('title', 'Dashboard')

@section('content')
<div class="min-h-screen bg-gray-50">
    {{-- Header --}}
    <div class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <h1 class="text-2xl font-bold text-gray-900">{{ $team->name }} Dashboard</h1>
                </div>
                <div class="flex items-center space-x-4">
                    {{-- These buttons will be handled by a Livewire component --}}
                    @livewire('user.dashboard-header')
                </div>
            </div>
        </div>
    </div>

    {{-- Tab Navigation --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
        @livewire('user.dashboard-tabs')
    </div>

    {{-- Main Content --}}
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div id="dashboard-content">
            {{-- Content will be loaded dynamically by the tabs component --}}
            @livewire('user.dashboard-content')
        </div>
    </main>
</div>

{{-- Include QR Scanner Modal --}}
@livewire('user.q-r-scanner-modal')

<!-- Trigger button -->
<button wire:click="$dispatch('open-qr-scanner')">
    <i class="fas fa-qrcode"></i> Scan QR Code
</button>

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