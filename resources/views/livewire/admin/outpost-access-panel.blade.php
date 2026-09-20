{{-- .visible stops the poll when the tab is in the background; without it the desk
     re-rendered every ten seconds forever, which reads as a flicker. --}}
<div class="container mx-auto px-4 py-8" wire:poll.10s.visible>

    <div class="flex flex-col lg:flex-row lg:justify-between lg:items-start mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Outpost Access</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 max-w-2xl">
                Indoor outposts are handed out in a random order, so the app holds no sequence — you do.
                When crew radio in, tap the team to open their 3D camera. Tap again to close it.
                Tiap tim memakai denahnya sendiri (diatur di Team Management); pos yang tidak ada di denah
                sebuah tim tampil redup untuk tim itu.
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

            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari tim…"
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
            $hereCount = $rows->filter(fn ($r) => in_array($location->id, $open[$r->key] ?? []))->count();
            // Teams that can actually use this post: on one of its plans, or any team when the
            // post is on no plan at all.
            $eligible = $rows->filter(fn ($r) => ! $plansHere || in_array($r->plan, $plansHere, true))->count();
        @endphp

        <section class="mb-5 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">

            <header class="flex items-center justify-between gap-3 px-4 py-3 bg-gray-50 dark:bg-gray-800">
                <div>
                    <h2 class="font-semibold text-gray-900 dark:text-gray-100">{{ $location->name }}</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $hereCount }} dari {{ $eligible }} tim terbuka
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
                        Tutup semua
                    </button>
                @endif
            </header>

            <div class="p-4">
                @if ($rows->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $search !== '' ? 'Tidak ada tim yang cocok dengan “'.$search.'”.' : 'Belum ada tim di denah ini.' }}
                    </p>
                @else
                    <div class="flex flex-wrap gap-2">
                        @foreach ($rows as $row)
                            @php
                                $mine = $open[$row->key] ?? [];
                                $isHere = in_array($location->id, $mine);
                                // With a random order a team belongs at one post at a time, so
                                // being open somewhere else is usually a leftover worth seeing.
                                $elsewhere = ! $isHere && count($mine) > 0;
                                // Not on this team's plan: opening it would change nothing they can
                                // see. Still closable if it was opened before plans were assigned.
                                $offPlan = $plansHere && ! in_array($row->plan, $plansHere, true);
                                $noAccount = empty($row->accounts);
                                $locked = ($offPlan || $noAccount) && ! $isHere;
                            @endphp

                            <button type="button"
                                    wire:key="acc-{{ $location->id }}-{{ $row->key }}"
                                    @if ($row->kind === 'team')
                                        wire:click="toggleTeam({{ $location->id }}, {{ $row->id }})"
                                        wire:target="toggleTeam({{ $location->id }}, {{ $row->id }})"
                                    @else
                                        wire:click="toggle({{ $location->id }}, {{ $row->id }})"
                                        wire:target="toggle({{ $location->id }}, {{ $row->id }})"
                                    @endif
                                    wire:loading.attr="disabled"
                                    @disabled($locked)
                                    title="{{ $noAccount
                                        ? 'Tim ini belum punya akun login'
                                        : ($locked
                                            ? 'Bukan di denah tim ini ('.($planNames[$row->plan] ?? 'tanpa denah').')'
                                            : ($isHere ? 'Terbuka — ketuk untuk menutup' : ($elsewhere ? 'Terbuka di pos lain' : 'Tertutup — ketuk untuk membuka'))) }}"
                                    class="px-4 py-2 rounded-lg text-sm font-medium border-2 transition-colors text-left
                                           {{ $isHere
                                              ? 'bg-green-600 border-green-600 text-white hover:bg-green-700'
                                              : ($locked
                                                  ? 'bg-gray-100 dark:bg-gray-900 border-dashed border-gray-300 dark:border-gray-700 text-gray-400 dark:text-gray-600 cursor-not-allowed'
                                                  : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-green-500') }}">
                                <span class="mr-1.5">{{ $isHere ? '●' : ($locked ? '⊘' : '○') }}</span>{{ $row->name }}
                                @if ($elsewhere)
                                    <span class="ml-1 text-amber-500" title="Tim ini terbuka di pos lain">▲</span>
                                @endif
                                {{-- The plan this team is on, and which accounts it opens, so the crew
                                     can see why a button is greyed. --}}
                                <span class="block text-[11px] font-normal {{ $isHere ? 'text-green-100' : 'text-gray-400 dark:text-gray-500' }}">
                                    @if ($noAccount)
                                        belum ada akun login
                                    @else
                                        {{ $row->plan ? ($planNames[$row->plan] ?? '#'.$row->plan) : 'tanpa denah' }}{{ $row->assigned ? '' : ' · dari START' }}
                                        @if ($row->kind === 'user') · akun tanpa tim @endif
                                    @endif
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
                Belum ada pos yang terhubung ke penanda di denah ini. Hubungkan lewat halaman Indoor Maps.
            @else
                No {{ $manualOnly ? 'indoor' : 'active' }} outposts yet.
                Set a game location's access mode to <span class="font-mono">manual</span> to control it here.
            @endif
        </div>
    @endforelse

    @if ($locations->isNotEmpty())
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-4">
            <span class="text-green-600">●</span> terbuka ·
            <span>○</span> tertutup ·
            <span>⊘</span> bukan di denah tim itu, atau tim belum punya akun ·
            <span class="text-amber-500">▲</span> tim itu terbuka di pos lain
        </p>
    @endif
</div>
