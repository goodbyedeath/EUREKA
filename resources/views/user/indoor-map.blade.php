@extends('layouts.appUser')

@section('title', $map?->name ?? __('Indoor Map'))

@section('content')
<div class="container mx-auto px-4 py-6">

    @if (! $map)
        <div class="rounded-lg border border-dashed border-gray-300 dark:border-gray-700 p-10 text-center text-gray-500 dark:text-gray-400">
            {{ __('No indoor map has been published yet.') }}
        </div>
    @else
        <div class="mb-4">
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ $map->name }}</h1>
            @if ($map->description)
                <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $map->description }}</p>
            @endif
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                {{ __('Tap a marker to see what is there.') }}
            </p>

            @if (isset($session) && $session && $session->hasStarted())
                {{-- Read from the server's start time, not counted on the device: a phone that
                     sleeps or reloads must neither gain nor lose time. --}}
                <div class="mt-3 inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-gray-100 dark:bg-gray-800"
                     x-data="{ s: {{ $session->elapsedSeconds() }} }" x-init="setInterval(() => s++, 1000)">
                    <span class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Elapsed') }}</span>
                    <span class="font-mono text-base tabular-nums text-gray-800 dark:text-gray-100"
                          x-text="String(Math.floor(s/3600)).padStart(2,'0') + ':' +
                                  String(Math.floor((s%3600)/60)).padStart(2,'0') + ':' +
                                  String(s%60).padStart(2,'0')"></span>
                </div>
            @endif
        </div>

        {{-- Markers are positioned in percentages, so the plan can be any width and they
             still land on the right room. Pinch-zoom works because the wrapper scrolls. --}}
        <div class="relative w-full overflow-auto rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900"
             x-data="{ open: null }">
            <div class="relative inline-block min-w-full">
                <img src="{{ $map->imageUrl() }}" alt="{{ $map->name }}" class="block w-full select-none">

                @foreach ($map->activeSpots as $spot)
                    @php $isOpen = $spot->game_location_id && $openIds->contains($spot->game_location_id); @endphp
                    {{-- A spot the crew has released pulses, so the team can pick it out of a
                         plan full of identical markers without being told which is theirs. --}}
                    <button type="button" @click="open = {{ $spot->id }}"
                            aria-label="{{ $spot->name }}{{ $isOpen ? ' — ' . __('open for your team') : '' }}"
                            class="{{ $isOpen ? 'animate-pulse' : '' }}"
                            style="position:absolute; left:{{ $spot->x }}%; top:{{ $spot->y }}%;
                                   width:{{ $spot->size }}px; height:{{ $spot->size }}px;
                                   background:{{ $spot->color }};
                                   transform:translate(-50%,-50%) {{ $spot->shape === 'diamond' ? 'rotate(45deg)' : '' }};
                                   border-radius:{{ $spot->shape === 'circle' ? '9999px' : ($spot->shape === 'pin' ? '9999px 9999px 9999px 2px' : '4px') }};
                                   border:{{ $isOpen ? '3px solid #fff' : '2px solid rgba(255,255,255,.85)' }};
                                   box-shadow:{{ $isOpen ? '0 0 0 4px rgba(34,197,94,.65), 0 1px 8px rgba(0,0,0,.5)' : '0 1px 6px rgba(0,0,0,.45)' }}">
                    </button>
                @endforeach
            </div>

            {{-- One sheet per spot, shown on tap --}}
            @foreach ($map->activeSpots as $spot)
                <div x-show="open === {{ $spot->id }}" x-cloak x-transition
                     class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/60 p-4"
                     @click.self="open = null">
                    <div class="bg-white dark:bg-gray-800 rounded-t-2xl sm:rounded-2xl shadow-xl w-full max-w-md max-h-[85vh] overflow-y-auto">
                        @if ($spot->imageUrl())
                            <img src="{{ $spot->imageUrl() }}" alt="{{ $spot->name }}"
                                 class="w-full rounded-t-2xl sm:rounded-t-2xl object-cover max-h-64">
                        @endif

                        <div class="p-5">
                            <div class="flex items-start justify-between gap-3">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    <span class="inline-block w-3 h-3 rounded-full align-middle mr-2" style="background:{{ $spot->color }}"></span>
                                    {{ $spot->name }}
                                </h2>
                                <button type="button" @click="open = null"
                                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-xl leading-none">&times;</button>
                            </div>

                            @if ($spot->content)
                                <p class="mt-3 text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $spot->content }}</p>
                            @endif

                            @if (! $spot->hasContent())
                                <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">{{ __('Nothing more to show here yet.') }}</p>
                            @endif

                            @if ($spot->game_location_id && $openIds->contains($spot->game_location_id))
                                <p class="mt-3 inline-flex items-center gap-2 text-sm font-medium text-green-700 dark:text-green-300">
                                    ● {{ __('The crew has opened this outpost for your team.') }}
                                </p>
                            @endif

                            @if ($spot->gameLocation && $spot->gameLocation->usesAr())
                                <a href="{{ route('user.ar.view', $spot->gameLocation->id) }}"
                                   class="mt-4 inline-flex items-center justify-center w-full px-4 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-medium">
                                    {{ __('Open 3D Camera') }}
                                </a>
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 text-center">
                                    {{ __('Opens only once the crew has released this outpost for your team.') }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
