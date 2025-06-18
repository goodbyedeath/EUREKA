@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Profile Header -->
    <div class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <h1 class="text-xl font-semibold text-gray-900">Profile Settings</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700">Hello, {{ auth()->user()->name }}</span>
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="text-blue-600 hover:text-blue-800">Admin Dashboard</a>
                    @else
                        <a href="{{ route('user.dashboard') }}" class="text-blue-600 hover:text-blue-800">Dashboard</a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <livewire:profile.update-profile-information-form />
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <livewire:profile.update-password-form />
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <livewire:profile.delete-user-form />
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
