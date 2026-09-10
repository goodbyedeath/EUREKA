{{--
    Questionnaires.

    This page used to be three tools behind Alpine tabs — questionnaires, game assessments
    and a second copy of the team manager. Two problems with that:

    - The team manager was the *same* Livewire component already served at
      /admin/team-management, so two menu entries showed the same screen.
    - Each tab was wrapped in <template x-if>. Alpine tears the subtree out of the DOM when
      a tab is inactive and clones it back in on click, which is not something a Livewire
      component survives: it is rendered on the server with a wire:id, then its node is
      discarded and re-inserted as a copy. All three rendered on every load (255 KB) and two
      were pulled out again immediately.

    One page, one tool. Game assessments moved to their own menu entry.
--}}
@extends('layouts.admin')

@section('title', 'Questionnaires')
@section('page-title', 'Questionnaires')

@section('content')
    @livewire('admin.questionnaire-manager')
@endsection
