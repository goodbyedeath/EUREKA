{{-- The player's how-to: shown here and on the app's Panduan page. --}}
@extends('layouts.admin')

@section('title', 'Panduan Pengguna')
@section('page-title', 'Panduan Pengguna')

@section('content')
    @livewire('admin.user-guide-manager')
@endsection
