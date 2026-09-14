{{-- Archive the finished game session; list and open past archives. --}}
@extends('layouts.admin')

@section('title', 'Game Archives')
@section('page-title', 'Game Archives')

@section('content')
    @livewire('admin.game-archive-manager')
@endsection
