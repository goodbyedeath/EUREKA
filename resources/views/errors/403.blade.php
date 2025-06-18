@extends('errors.layout')

@section('title', __('Access Forbidden'))
@section('code', '403')

@section('icon')
<div class="w-32 h-32 mx-auto mb-8 text-red-400">
    <svg class="w-full h-full" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
    </svg>
</div>
@endsection

@section('title')
{{ __('common.unauthorized') }}
@endsection

@section('message')
{{ __('You do not have permission to access this resource. Please contact an administrator if you believe this is an error.') }}
@endsection

@section('actions')
<div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
    <div class="flex items-center">
        <i class="fas fa-shield-alt text-red-500 mr-3"></i>
        <div class="text-left">
            <h3 class="text-sm font-medium text-red-800">{{ __('Access Denied') }}</h3>
            <p class="text-sm text-red-700 mt-1">
                {{ __('This resource requires special permissions that your account does not have.') }}
            </p>
        </div>
    </div>
</div>
@endsection