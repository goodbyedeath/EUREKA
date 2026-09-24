@extends('layouts.admin')

@section('page-title', 'Outpost Access')

@section('content')
{{-- Indoor outposts are opened by hand, so this page is used live during an event. --}}
{{-- The bell above the desk, and what it sounds like: the crew changes it here, not in the code. --}}
<livewire:admin.bell-settings />
<livewire:admin.outpost-access-panel />
@endsection
