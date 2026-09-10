{{--
    Facilitator scoring for fun_game answers.

    Previously reachable only as a tab inside Questionnaires, which meant it had no menu
    entry of its own — an admin who did not know the tab existed could not find it.
--}}
@extends('layouts.admin')

@section('title', 'Game Assessments')
@section('page-title', 'Game Assessments')

@section('content')
    @livewire('admin.game-assessment')
@endsection
