@extends('layouts.admin')

@section('page-title', 'Game Management')

@section('content')
{{-- The shared 3D asset library sits above the locations that use it. --}}
<livewire:admin.ar-model-library />

<livewire:admin.game-manager />
@endsection