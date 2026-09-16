{{-- .visible stops the poll when the tab is in the background; without it the desk
     re-rendered every ten seconds forever, which reads as a flicker. --}}
<div class="container mx-auto px-4 py-8" wire:poll.10s.visible>

    <div class="flex flex-col lg:flex-row lg:justify-between lg:items-start mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Outpost Access</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 max-w-2xl">
                Indoor outposts are handed out in a random order, so the app holds no sequence — you do.
                When crew radio in, tap the team to open their 3D camera. Tap again to close it.
                Each team sees only the posts on its own floor plan (set in Team Management); a post that
                is not on a team's plan is greyed out for that team.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            @if (session('access_msg'))
                <span class="px-3 py-2 rounded-md bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-200 text-sm">
                    {{ session('access_msg') }}
                </span>
            @endif

            {{-- Several routes run at once: narrow the desk to one plan's posts and teams. --}}
            <select wire:model.live="plan"
                    class="px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">
                <option value="">Semua denah</option>
                @foreach ($planNames as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>

            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Find a team…"
                   class="px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">

            <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap">
                <input type="checkbox" wire:model.live="manualOnly" class="rounded border-gray-300 dark:border-gray-600">
                Indoor only
            </label>
        </div>
    </div>

    @if (session('access_error'))
        <div class="mb-4 rounded-md border border-amber-300 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/30 px-4 py-3 text-sm text-amber-800 dark:text-amber-200">
            {{ session('access_error') }}
        </div>
    @endif

    @forelse ($locations as $location)
        @php
            $plansHere = $postPlans->get($location->id, []);
            $hereCount = $teams->filter(fn ($t) => in_array($location->id, $open[$t->id] ?? []))->count();
            // Teams that can actually use this post: on one of its plans, or any team when the
            // post is on no plan at all.
            $eligible = $teams->filter(fn ($t) => ! $plansHere || in_array($teamPlan[$t->id] ?? null, $plansHere, true))->count();
        @endphp

        <section class="mb-5 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">

            <header class="flex items-center justify-between gap-3 px-4 py-3 bg-gray-50 dark:bg-gray-800">
                <div>
                    <h2 class="font-semibold text-gray-900 dark:text-gray-100">{{ $location->name }}</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $hereCount }} of {{ $eligible }} teams open
                        @if ($plansHere)
                            · denah:
                            @foreach ($plansHere as $pid)
                                <span class="inline-block px-1.5 py-0.5 rounded bg-indigo-100 dark:bg-indigo-900/40 text-indigo-800 dark:text-indigo-200">{{ $planNames[$pid] ?? '#'.$pid }}</span>
                            @endforeach
                        @else
                            · <span class="text-gray-400 dark:text-gray-500">tidak ada di denah mana pun</span>
                        @endif
                        @unless ($location->access_mode === 'manual')
                            · <span class="text-amber-600 dark:text-amber-400">outdoor — opens by radius, these are ignored</span>
                        @endunless
                    </p>
                </div>

                @if ($hereCount)
                    <button type="button" wire:click="closeAll({{ $location->id }})"
                            class="px-3 py-1.5 text-xs rounded-md bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-300 dark:hover:bg-gray-600 whitespace-nowrap">
                        Close all
                    </button>
                @endif
            </header>

            <div class="p-4">
                @if ($teams->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $search !== '' ? 'No teams match “'.$search.'”.' : 'No teams on this floor plan.' }}
                    </p>
                @else
                    <div class="flex flex-wrap gap-2">
                        @foreach ($teams as $team)
                            @php
                                $mine = $open[$team->id] ?? [];
                                $isHere = in_array($location->id, $mine);
                                // With a random order a team belongs at one post at a time, so
                                // being open somewhere else is usually a leftover worth seeing.
                                $elsewhere = ! $isHere && count($mine) > 0;
                                $myPlan = $teamPlan[$team->id] ?? null;
                                // Not on this team's plan: opening it would change nothing they can
                                // see. Still closable if it was opened before plans were assigned.
                                $offPlan = $plansHere && ! in_array($myPlan, $plansHere, true);
                                $locked = $offPlan && ! $isHere;
                            @endphp

                            <button type="button"
                                    wire:key="acc-{{ $location->id }}-{{ $team->id }}"
                                    wire:click="toggle({{ $location->id }}, {{ $team->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="toggle({{ $location->id }}, {{ $team->id }})"
                                    @disabled($locked)
                                    title="{{ $locked
                                        ? 'Bukan di denah tim ini ('.($planNames[$myPlan] ?? 'tanpa denah').')'
                                        : ($isHere ? 'Open — tap to close' : ($elsewhere ? 'Open at another outpost' : 'Closed — tap to open')) }}"
                                    class="px-4 py-2 rounded-lg text-sm font-medium border-2 transition-colors text-left
                                           {{ $isHere
                                              ? 'bg-green-600 border-green-600 text-white hover:bg-green-700'
                                              : ($locked
                                                  ? 'bg-gray-100 dark:bg-gray-900 border-dashed border-gray-300 dark:border-gray-700 text-gray-400 dark:text-gray-600 cursor-not-allowed'
                                                  : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-green-500') }}">
                                <span class="mr-1.5">{{ $isHere ? '●' : ($locked ? '⊘' : '○') }}</span>{{ $team->name }}
                                @if ($elsewhere)
                                    <span class="ml-1 text-amber-500" title="This team is open at another outpost">▲</span>
                                @endif
                                {{-- The plan this team is on, so the crew can see why a button is greyed. --}}
                                <span class="block text-[11px] font-normal {{ $isHere ? 'text-green-100' : 'text-gray-400 dark:text-gray-500' }}">
                                    {{ $myPlan ? ($planNames[$myPlan] ?? '#'.$myPlan) : 'tanpa denah' }}{{ $team->team?->indoor_map_id ? '' : ' · dari START' }}
                                </span>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    @empty
        <div class="rounded-lg border border-dashed border-gray-300 dark:border-gray-700 p-10 text-center text-gray-500 dark:text-gray-400">
            @if ($plan !== '')
                No outposts are linked to markers on this floor plan yet. Link them on the Indoor Maps page.
            @else
                No {{ $manualOnly ? 'indoor' : 'active' }} outposts yet.
                Set a game location's access mode to <span class="font-mono">manual</span> to control it here.
            @endif
        </div>
    @endforelse

    @if ($locations->isNotEmpty())
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-4">
            <span class="text-green-600">●</span> open ·
            <span>○</span> closed ·
            <span>⊘</span> not on that team's floor plan ·
            <span class="text-amber-500">▲</span> that team is open at another outpost
        </p>
    @endif
</div>
