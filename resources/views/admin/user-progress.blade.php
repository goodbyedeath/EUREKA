@extends('layouts.admin')

@section('page-title', 'Team Progress Analytics')

@section('content')
{{-- The race clocks and the bell that rings when a team's time runs out. Moved here from Outpost
     Access on 25 Sep (operator): this is the screen the crew watches while the desk opens posts. --}}
<livewire:admin.bell-settings />
<livewire:admin.race-clock-strip />
    <livewire:admin.user-progress />
@endsection