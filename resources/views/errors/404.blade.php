@extends('errors.layout')

@section('title', __('Page Not Found'))
@section('code', '404')

@section('icon')
<div class="w-32 h-32 mx-auto mb-8 text-blue-400">
    <svg class="w-full h-full" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
    </svg>
</div>
@endsection

@section('title')
{{ __('common.page_not_found') }}
@endsection

@section('message')
{{ __('The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.') }}
@endsection

@section('actions')
<div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
    <div class="flex items-center">
        <i class="fas fa-lightbulb text-blue-500 mr-3"></i>
        <div class="text-left">
            <h3 class="text-sm font-medium text-blue-800">{{ __('Suggestions') }}</h3>
            <ul class="text-sm text-blue-700 mt-1 list-disc list-inside">
                <li>{{ __('Check the URL for typos') }}</li>
                <li>{{ __('Use the navigation menu to find what you need') }}</li>
                <li>{{ __('Search for the content you are looking for') }}</li>
            </ul>
        </div>
    </div>
</div>

<!-- Quick Navigation -->
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
    @auth
        @if(auth()->user()->isAdmin())
            <a href="{{ route('admin.dashboard') }}" 
               class="flex items-center p-4 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                <i class="fas fa-tachometer-alt text-purple-500 mr-3"></i>
                <div class="text-left">
                    <div class="font-medium text-gray-900">{{ __('Admin Dashboard') }}</div>
                    <div class="text-sm text-gray-500">{{ __('Manage the system') }}</div>
                </div>
            </a>
        @else
            <a href="{{ route('user.dashboard') }}" 
               class="flex items-center p-4 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                <i class="fas fa-user text-blue-500 mr-3"></i>
                <div class="text-left">
                    <div class="font-medium text-gray-900">{{ __('common.user_dashboard') }}</div>
                    <div class="text-sm text-gray-500">{{ __('Access your dashboard') }}</div>
                </div>
            </a>
        @endif
    @else
        <a href="{{ route('login') }}" 
           class="flex items-center p-4 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
            <i class="fas fa-sign-in-alt text-green-500 mr-3"></i>
            <div class="text-left">
                <div class="font-medium text-gray-900">{{ __('common.login') }}</div>
                <div class="text-sm text-gray-500">{{ __('Sign in to your account') }}</div>
            </div>
        </a>
    @endauth
    
    <a href="{{ url('/') }}" 
       class="flex items-center p-4 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
        <i class="fas fa-home text-orange-500 mr-3"></i>
        <div class="text-left">
            <div class="font-medium text-gray-900">{{ __('common.home') }}</div>
            <div class="text-sm text-gray-500">{{ __('Go to homepage') }}</div>
        </div>
    </a>
</div>
@endsection

@section('additional-info')
<div class="bg-gray-50 rounded-lg p-4">
    <h4 class="text-sm font-medium text-gray-900 mb-2">{{ __('Need Help?') }}</h4>
    <p class="text-sm text-gray-600">
        {{ __('If you believe this is an error, please contact the administrator or try again later.') }}
    </p>
</div>
@endsection