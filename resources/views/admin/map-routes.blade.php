{{-- Routes recorded in the GPS Tracker app, and which of them the event uses. --}}
@extends('layouts.admin')

@section('title', 'Jalur Peta')
@section('page-title', 'Jalur Peta')

@section('content')
    @livewire('admin.map-route-manager')
@endsection
