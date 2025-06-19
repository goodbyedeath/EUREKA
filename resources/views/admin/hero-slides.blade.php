@extends('layouts.admin')

@section('title', 'Hero Slides Management')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Hero Slides Management</h1>
        <p class="text-gray-600 mt-2">Manage the carousel slides on the landing page</p>
    </div>

    @livewire('admin.hero-slide-management')
</div>
@endsection