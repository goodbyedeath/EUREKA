{{-- The partner API connector: switch, member import, point push. Neutral on purpose — FEKDI x IFSE\n     was the first partner, and the next one should need configuration, not code. --}}
@extends('layouts.admin')

@section('title', 'Konektor API Partner')
@section('page-title', 'Konektor API Partner')

@section('content')
    @livewire('admin.partner-connector-manager')
@endsection
