{{-- Team → plan → post → questionnaire, checked end to end before the event. --}}
@extends('layouts.admin')

@section('title', 'Cek Kesiapan Acara')
@section('page-title', 'Cek Kesiapan Acara')

@section('content')
    @livewire('admin.event-readiness')
@endsection
