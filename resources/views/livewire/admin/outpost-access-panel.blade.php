{{-- .visible stops the poll when the tab is in the background; without it the desk
     re-rendered every ten seconds forever, which reads as a flicker. --}}
<div class="container mx-auto px-4 py-8" wire:poll.10s.visible>

    <div class="flex flex-col lg:flex-row lg:justify-between lg:items-start mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Outpost Access</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 max-w-2xl">
                Indoor outposts are handed out in a random order, so the app holds no sequence — you do.
                When crew radio in, tap the team to open their 3D camera. Tap again to close it.
            </p>
        </div>

        <div class="flex items-center gap-3">
            @if (session('access_msg'))
                <span class="px-3 py-2 rounded-md bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-200 text-sm">
                    {{ session('access_msg') }}
                </span>
            @endif

            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Find a team…"
                   class="px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">

            <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap">
                <input type="checkbox" wire:model.live="manualOnly" class="rounded border-gray-300">
                Indoor only
            </label>
        </div>
    </div>

    @forelse ($locations as $location)
        @php $hereCount = $teams->filter(fn ($t) => in_array($location->id, $open[$t->id] ?? []))->count(); @endphp

        <section class="mb-5 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">

            <header class="flex items-center justify-between gap-3 px-4 py-3 bg-gray-50 dark:bg-gray-800">
                <div>
                    <h2 class="font-semibold text-gray-900 dark:text-gray-100">{{ $location->name }}</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $hereCount }} of {{ $teams->count() }} teams open
                        @unless ($location->access_mode === 'manual')
                            · <span class="text-amber-600 dark:text-amber-400">outdoor — opens by radius, these are ignored</span>
                        @endunless
                    </p>
                </div>

                @if ($hereCount)
                    <button type="button" wire:click="closeAll({{ $location->id }})"
                            class="px-3 py-1.5 text-xs rounded-md bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-300 whitespace-nowrap">
                        Close all
                    </button>
                @endif
            </header>

            <div class="p-4">
                @if ($teams->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400">No teams match “{{ $search }}”.</p>
                @else
                    <div class="flex flex-wrap gap-2">
                        @foreach ($teams as $team)
                            @php
                                $mine = $open[$team->id] ?? [];
                                $isHere = in_array($location->id, $mine);
                                // With a random order a team belongs at one post at a time, so
                                // being open somewhere else is usually a leftover worth seeing.
                                $elsewhere = ! $isHere && count($mine) > 0;
                            @endphp

                            <button type="button"
                                    wire:key="acc-{{ $location->id }}-{{ $team->id }}"
                                    wire:click="toggle({{ $location->id }}, {{ $team->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="toggle({{ $location->id }}, {{ $team->id }})"
                                    title="{{ $isHere ? 'Open — tap to close' : ($elsewhere ? 'Open at another outpost' : 'Closed — tap to open') }}"
                                    class="px-4 py-2.5 rounded-lg text-sm font-medium border-2 transition-colors
                                           {{ $isHere
                                              ? 'bg-green-600 border-green-600 text-white hover:bg-green-700'
                                              : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-green-500 hover:text-green-700 dark:hover:text-green-400' }}">
                                <span class="mr-1.5">{{ $isHere ? '●' : '○' }}</span>{{ $team->name }}
                                @if ($elsewhere)
                                    <span class="ml-1 text-amber-500" title="This team is open at another outpost">▲</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    @empty
        <div class="rounded-lg border border-dashed border-gray-300 dark:border-gray-700 p-10 text-center text-gray-500 dark:text-gray-400">
            No {{ $manualOnly ? 'indoor' : 'active' }} outposts yet.
            Set a game location's access mode to <span class="font-mono">manual</span> to control it here.
        </div>
    @endforelse

    @if ($locations->isNotEmpty())
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-4">
            <span class="text-green-600">●</span> open ·
            <span>○</span> closed ·
            <span class="text-amber-500">▲</span> that team is open at another outpost
        </p>
    @endif
</div>
