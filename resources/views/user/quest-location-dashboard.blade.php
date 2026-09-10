{{--
    The team's list of outdoor posts.

    `QuestLocationController::index()` has always rendered `user.quest-location-dashboard`,
    but the file did not exist — only a `.blade.php.backup` under livewire/user/ — so the
    Quest Locations item in the team navigation returned a 500 rather than a page.

    The body already existed as `user.partials.quest-locations-content`, which the
    controller's `dashboardContent()` renders on its own for in-place refreshes. This is
    the page shell around that same partial, given the same four variables from
    `getQuestLocationData()`, so the two routes cannot drift apart.
--}}
@extends('layouts.appUser')

@section('title', __('Quest Locations'))

@section('content')
    @include('user.partials.quest-locations-content')
@endsection
