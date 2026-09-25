@extends('layouts.admin')

@section('page-title', 'Outpost Access')

@section('content')
{{-- Indoor outposts are opened by hand, so this page is used live during an event. --}}
<livewire:admin.outpost-access-panel />
@endsection
