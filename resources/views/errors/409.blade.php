@extends('errors.layout')

@section('title', __('Conflict Error'))
@section('code', '409')

@section('icon')
<div class="w-32 h-32 mx-auto mb-8 text-yellow-400">
    <svg class="w-full h-full" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
    </svg>
</div>
@endsection

@section('title')
{{ __('Data Conflict Detected') }}
@endsection

@section('message')
{{ __('The request could not be completed due to a conflict with the current state of the resource. This usually happens when trying to update data that has been modified by another user.') }}
@endsection

@section('actions')
<div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
    <div class="flex items-start">
        <i class="fas fa-exclamation-triangle text-yellow-500 mr-3 mt-1"></i>
        <div class="text-left">
            <h3 class="text-sm font-medium text-yellow-800">{{ __('What caused this conflict?') }}</h3>
            <ul class="text-sm text-yellow-700 mt-2 space-y-1">
                <li>• {{ __('Another user modified the same data while you were editing') }}</li>
                <li>• {{ __('You tried to create something that already exists') }}</li>
                <li>• {{ __('The data you are trying to update is no longer available') }}</li>
                <li>• {{ __('Your session may have expired during the operation') }}</li>
            </ul>
        </div>
    </div>
</div>

<!-- Recommended Actions -->
<div class="bg-white border border-gray-200 rounded-lg p-4 mb-6">
    <h3 class="text-sm font-medium text-gray-900 mb-4">{{ __('Recommended Actions') }}</h3>
    
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <button 
            onclick="window.location.reload()" 
            class="flex items-center p-3 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors">
            <i class="fas fa-sync-alt text-blue-600 mr-3"></i>
            <div class="text-left">
                <div class="font-medium text-blue-900 text-sm">{{ __('Refresh Page') }}</div>
                <div class="text-xs text-blue-700">{{ __('Get latest data') }}</div>
            </div>
        </button>
        
        <button 
            onclick="clearFormData()" 
            class="flex items-center p-3 bg-orange-50 border border-orange-200 rounded-lg hover:bg-orange-100 transition-colors">
            <i class="fas fa-eraser text-orange-600 mr-3"></i>
            <div class="text-left">
                <div class="font-medium text-orange-900 text-sm">{{ __('Clear Form') }}</div>
                <div class="text-xs text-orange-700">{{ __('Start fresh') }}</div>
            </div>
        </button>
        
        <button 
            onclick="saveAsNew()" 
            class="flex items-center p-3 bg-green-50 border border-green-200 rounded-lg hover:bg-green-100 transition-colors">
            <i class="fas fa-plus text-green-600 mr-3"></i>
            <div class="text-left">
                <div class="font-medium text-green-900 text-sm">{{ __('Create New') }}</div>
                <div class="text-xs text-green-700">{{ __('Save as new item') }}</div>
            </div>
        </button>
        
        <button 
            onclick="contactSupport()" 
            class="flex items-center p-3 bg-purple-50 border border-purple-200 rounded-lg hover:bg-purple-100 transition-colors">
            <i class="fas fa-life-ring text-purple-600 mr-3"></i>
            <div class="text-left">
                <div class="font-medium text-purple-900 text-sm">{{ __('Get Help') }}</div>
                <div class="text-xs text-purple-700">{{ __('Contact support') }}</div>
            </div>
        </button>
    </div>
</div>

<!-- Data Recovery Info -->
<div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
    <div class="flex items-start">
        <i class="fas fa-info-circle text-blue-500 mr-3 mt-1"></i>
        <div class="text-left">
            <h3 class="text-sm font-medium text-blue-800">{{ __('Data Recovery') }}</h3>
            <p class="text-sm text-blue-700 mt-1">
                {{ __('If you were editing a form, your data might be temporarily saved in your browser. Try refreshing the page to see if your changes can be recovered.') }}
            </p>
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
                    <div class="text-sm text-gray-500">{{ __('Return to admin panel') }}</div>
                </div>
            </a>
        @endif
        
        <a href="{{ route('user.dashboard') }}" 
           class="flex items-center p-4 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
            <i class="fas fa-user text-blue-500 mr-3"></i>
            <div class="text-left">
                <div class="font-medium text-gray-900">{{ __('common.user_dashboard') }}</div>
                <div class="text-sm text-gray-500">{{ __('Go to your dashboard') }}</div>
            </div>
        </a>
    @endauth
</div>
@endsection

@section('additional-info')
<div class="bg-gray-50 rounded-lg p-4">
    <h4 class="text-sm font-medium text-gray-900 mb-2">{{ __('Technical Information') }}</h4>
    <div class="text-xs text-gray-600 space-y-1">
        <div><strong>{{ __('Error Code') }}:</strong> 409 - Conflict</div>
        <div><strong>{{ __('Timestamp') }}:</strong> {{ now()->format('Y-m-d H:i:s T') }}</div>
        <div><strong>{{ __('Request Method') }}:</strong> {{ request()->method() }}</div>
        <div><strong>{{ __('Resource') }}:</strong> {{ request()->path() }}</div>
    </div>
</div>

<script>
function clearFormData() {
    if (confirm('{{ __("This will clear all form data. Are you sure?") }}')) {
        // Clear localStorage
        localStorage.clear();
        
        // Clear sessionStorage
        sessionStorage.clear();
        
        // Go back or reload
        if (window.history.length > 1) {
            window.history.back();
        } else {
            window.location.href = '{{ route("user.dashboard") }}';
        }
    }
}

function saveAsNew() {
    if (confirm('{{ __("This will attempt to save your data as a new item. Continue?") }}')) {
        // Try to recover form data from localStorage
        const formData = localStorage.getItem('formData');
        if (formData) {
            // You can implement custom logic here to handle the data
            alert('{{ __("Form data recovery feature coming soon.") }}');
        } else {
            window.history.back();
        }
    }
}

function contactSupport() {
    const subject = encodeURIComponent('Data Conflict Error - Need Help');
    const body = encodeURIComponent(`
I encountered a data conflict error (409) while using the system.

Details:
- Timestamp: {{ now()->toISOString() }}
- Page: {{ request()->url() }}
- Method: {{ request()->method() }}

Please help me resolve this issue.
`);
    
    window.location.href = `mailto:support@yoursite.com?subject=${subject}&body=${body}`;
}

// Auto-save form data to localStorage for recovery
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('input', function() {
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            localStorage.setItem('formData_' + form.id, JSON.stringify(data));
        });
    });
});
</script>
@endsection