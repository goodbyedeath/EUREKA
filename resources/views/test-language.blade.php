{{-- Test page to verify language switcher --}}
@extends('layouts.appUser')

@section('title', 'Language Test')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-lg p-6">
        <h1 class="text-2xl font-bold mb-4">{{ __('common.language') }} Test Page</h1>
        
        <div class="space-y-4">
            <p><strong>Current Locale:</strong> {{ app()->getLocale() }}</p>
            <p><strong>{{ __('common.welcome') }}:</strong> {{ __('common.welcome') }}</p>
            <p><strong>{{ __('common.dashboard') }}:</strong> {{ __('common.dashboard') }}</p>
            <p><strong>{{ __('quiz.scan_qr_code') }}:</strong> {{ __('quiz.scan_qr_code') }}</p>
            <p><strong>{{ __('common.team_members') }}:</strong> {{ __('common.team_members') }}</p>
            <p><strong>{{ __('auth.logout') }}:</strong> {{ __('auth.logout') }}</p>
        </div>

        <div class="mt-6">
            <h2 class="text-lg font-semibold mb-2">Language Switcher Test</h2>
            <p>Look for the language switcher in the top navigation bar. It should show flag buttons like 🇺🇸 EN and 🇮🇩 ID.</p>
        </div>

        <div class="mt-6 bg-blue-50 border border-blue-200 rounded p-4">
            <h3 class="font-semibold text-blue-800">Debug Info:</h3>
            <ul class="text-sm text-blue-700 mt-2">
                <li>Supported Locales: {{ implode(', ', array_keys(config('app.supported_locales', []))) }}</li>
                <li>Session Locale: {{ session('locale', 'not set') }}</li>
                <li>App Locale: {{ app()->getLocale() }}</li>
                <li>Request Lang: {{ request('lang', 'not set') }}</li>
            </ul>
        </div>
    </div>
</div>
@endsection