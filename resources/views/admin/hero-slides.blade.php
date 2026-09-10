@extends('layouts.admin')

@section('title', 'Hero Slides Management')

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- The logo sits above the slides: both are the front-of-house look, and an admin
         dressing the app for a client sets them in the same sitting. --}}
    @livewire('admin.brand-logo-manager')

    @livewire('admin.hero-slide-management')
</div>
@endsection
