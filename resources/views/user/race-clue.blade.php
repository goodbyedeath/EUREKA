@extends('layouts.appUser')

@section('title', __('Clue'))

@section('content')
<div class="container mx-auto px-4 py-8 max-w-lg">

    <div class="text-center mb-6">
        <p class="text-xs uppercase tracking-widest text-gray-500 dark:text-gray-400">{{ $map->name }}</p>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mt-1">{{ __('Find the outpost') }}</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
            {{ __('Answer correctly and the venue plan opens.') }}
        </p>
    </div>

    {{-- The clock is already running; showing it is what makes the clue feel like a race. --}}
    <div class="flex items-center justify-center gap-2 mb-6 text-gray-600 dark:text-gray-300"
         x-data="{ s: {{ $session->elapsedSeconds() }} }"
         x-init="setInterval(() => s++, 1000)">
        <span class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Elapsed') }}</span>
        <span class="font-mono text-lg tabular-nums"
              x-text="String(Math.floor(s/3600)).padStart(2,'0') + ':' +
                      String(Math.floor((s%3600)/60)).padStart(2,'0') + ':' +
                      String(s%60).padStart(2,'0')"></span>
    </div>

    @if ($wrong)
        {{-- The flow asks for a red cross and another go, with no lockout. --}}
        <div class="mb-5 flex items-start gap-3 rounded-lg border border-red-300 dark:border-red-800 bg-red-50 dark:bg-red-900/30 p-4">
            <span class="text-2xl leading-none text-red-600 dark:text-red-400">&times;</span>
            <div>
                <p class="font-semibold text-red-800 dark:text-red-200">{{ __('Not quite.') }}</p>
                <p class="text-sm text-red-700 dark:text-red-300">
                    {{ __('Try again — wrong answers are counted.') }}
                    ({{ trans_choice(':count wrong so far|:count wrong so far', $session->clue_wrong_attempts, ['count' => $session->clue_wrong_attempts]) }})
                </p>
            </div>
        </div>
    @endif

    <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
        <p class="text-lg text-gray-800 dark:text-gray-100 whitespace-pre-line">{{ $map->clue_question }}</p>

        <form method="POST" action="{{ route('user.race.clue.answer', $map->id) }}" class="mt-5">
            @csrf
            <label for="answer" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ __('Your answer') }}
            </label>
            <input type="text" name="answer" id="answer" autocomplete="off" autofocus required
                   class="mt-1 w-full px-4 py-3 text-lg border {{ $wrong ? 'border-red-400' : 'border-gray-300 dark:border-gray-600' }} rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
            @error('answer')
                <span class="text-red-500 text-xs">{{ $message }}</span>
            @enderror

            <button type="submit"
                    class="mt-4 w-full px-4 py-3 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold">
                {{ __('Submit answer') }}
            </button>
        </form>
    </div>

    <p class="text-xs text-center text-gray-500 dark:text-gray-400 mt-4">
        {{ __('Spelling and spacing are forgiven; the answer only has to match.') }}
    </p>
</div>
@endsection
