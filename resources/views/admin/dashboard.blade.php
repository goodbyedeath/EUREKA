@extends('layouts.admin')

@section('page-title', 'Admin Dashboard')

@section('content')
<!-- Stats Cards Component -->
<livewire:admin.dashboard-stats :stats="$stats" />

<!-- Questionnaire Manager Component -->
<livewire:admin.questionnaire-manager />
@endsection