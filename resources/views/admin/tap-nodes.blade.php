{{-- The crew's side of the tap system: reader → post, card → team (SCALING-PLAN §6). --}}
@extends('layouts.admin')

@section('title', 'RFID & Reader Pos')
@section('page-title', 'RFID & Reader Pos')

@section('content')
    @livewire('admin.tap-nodes')
@endsection
