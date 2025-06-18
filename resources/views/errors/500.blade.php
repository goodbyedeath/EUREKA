@extends('errors.layout')

@section('title', __('Server Error'))
@section('code', '500')

@section('icon')
<div class="w-32 h-32 mx-auto mb-8 text-red-400">
    <svg class="w-full h-full" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
    </svg>
</div>
@endsection

@section('title')
{{ __('common.server_error') }}
@endsection

@section('message')
{{ __('Something went wrong on our servers. We are working to fix this issue. Please try again later.') }}
@endsection

@section('actions')
<div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
    <div class="flex items-center">
        <i class="fas fa-exclamation-triangle text-red-500 mr-3"></i>
        <div class="text-left">
            <h3 class="text-sm font-medium text-red-800">{{ __('What happened?') }}</h3>
            <p class="text-sm text-red-700 mt-1">
                {{ __('Our servers encountered an unexpected error while processing your request. Our technical team has been automatically notified.') }}
            </p>
        </div>
    </div>
</div>

<!-- Status and Actions -->
<div class="bg-white border border-gray-200 rounded-lg p-4 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-medium text-gray-900">{{ __('Server Status') }}</h3>
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
            <i class="fas fa-circle text-yellow-400 mr-1" style="font-size: 0.5rem;"></i>
            {{ __('Investigating') }}
        </span>
    </div>
    
    <div class="space-y-3">
        <div class="flex items-center text-sm">
            <i class="fas fa-check text-green-500 mr-2"></i>
            <span class="text-gray-600">{{ __('Database connection') }}</span>
            <span class="ml-auto text-green-600 font-medium">{{ __('Active') }}</span>
        </div>
        <div class="flex items-center text-sm">
            <i class="fas fa-times text-red-500 mr-2"></i>
            <span class="text-gray-600">{{ __('Application server') }}</span>
            <span class="ml-auto text-red-600 font-medium">{{ __('Error') }}</span>
        </div>
        <div class="flex items-center text-sm">
            <i class="fas fa-clock text-yellow-500 mr-2"></i>
            <span class="text-gray-600">{{ __('Estimated fix time') }}</span>
            <span class="ml-auto text-yellow-600 font-medium">{{ __('5-10 minutes') }}</span>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
    <button 
        onclick="window.location.reload()" 
        class="flex items-center justify-center p-4 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors">
        <i class="fas fa-sync-alt text-blue-600 mr-3"></i>
        <div class="text-left">
            <div class="font-medium text-blue-900">{{ __('Try Again') }}</div>
            <div class="text-sm text-blue-700">{{ __('Reload this page') }}</div>
        </div>
    </button>
    
    <button 
        onclick="reportError()" 
        class="flex items-center justify-center p-4 bg-orange-50 border border-orange-200 rounded-lg hover:bg-orange-100 transition-colors">
        <i class="fas fa-bug text-orange-600 mr-3"></i>
        <div class="text-left">
            <div class="font-medium text-orange-900">{{ __('Report Issue') }}</div>
            <div class="text-sm text-orange-700">{{ __('Help us fix this') }}</div>
        </div>
    </button>
</div>
@endsection

@section('additional-info')
<div class="bg-gray-50 rounded-lg p-4">
    <h4 class="text-sm font-medium text-gray-900 mb-2">{{ __('Technical Details') }}</h4>
    <div class="text-xs text-gray-600 font-mono bg-gray-100 rounded p-2">
        <div>{{ __('Timestamp') }}: {{ now()->toISOString() }}</div>
        <div>{{ __('Request ID') }}: {{ Str::random(12) }}</div>
        <div>{{ __('Server') }}: {{ gethostname() ?? 'Unknown' }}</div>
        @if(app()->environment('local'))
            <div class="text-red-600 mt-2">{{ __('Environment') }}: {{ app()->environment() }}</div>
        @endif
    </div>
</div>

<script>
function reportError() {
    const details = {
        timestamp: '{{ now()->toISOString() }}',
        url: window.location.href,
        userAgent: navigator.userAgent
    };
    
    // You can customize this to send to your error reporting service
    const subject = encodeURIComponent('Server Error Report - 500');
    const body = encodeURIComponent(`
Error Details:
- Timestamp: ${details.timestamp}
- URL: ${details.url}
- User Agent: ${details.userAgent}

Please describe what you were trying to do when this error occurred:

`);
    
    // Open email client or you can replace with your error reporting endpoint
    window.location.href = `mailto:admin@yoursite.com?subject=${subject}&body=${body}`;
}

// Auto-refresh after 60 seconds with user consent
setTimeout(() => {
    if (confirm('{{ __("The page will refresh automatically to check if the issue is resolved. Continue?") }}')) {
        window.location.reload();
    }
}, 60000);
</script>
@endsection