{{--
    Admin navigation, VS Code style.

    Everything lives on the left instead of in a grid of cards above the work, so the page
    you are actually using gets the full width. Each category folds away on its own, and
    the whole rail folds away too — the working area then runs edge to edge, which matters
    on the wide tables and the indoor plan editor.

    What is open is remembered in localStorage, per browser. A tool people operate during a
    live event should not need re-arranging every time they open it.

    Colour classes are written out in full: Tailwind is compiled by Vite here and scans for
    literal names, so anything assembled from a variable compiles to nothing.
--}}

@php
    $nav = [
        [
            'key' => 'setup',
            'label' => 'Persiapan acara',
            'icon' => 'fa-box-open',
            'items' => [
                ['admin.users',                'fa-users',          'Account Management', 'text-blue-500'],
                ['admin.team-management',      'fa-users-cog',      'Team Management',    'text-indigo-500'],
                ['admin.dashboard-management', 'fa-cogs',           'Questionnaires',     'text-emerald-500'],
                ['admin.games',                'fa-gamepad',        'AR Outposts',        'text-purple-500'],
                ['admin.race-start',           'fa-flag-checkered', 'Race Start',         'text-rose-500'],
            ],
        ],
        [
            'key' => 'outdoor',
            'label' => 'Outdoor',
            'icon' => 'fa-tree',
            'items' => [
                ['admin.quest-locations',      'fa-map-marker-alt', 'Quest Locations',    'text-green-500'],
                ['admin.gps-tracking',         'fa-map-marked-alt', 'GPS Tracking Map',   'text-red-500'],
            ],
        ],
        [
            'key' => 'indoor',
            'label' => 'Indoor',
            'icon' => 'fa-building',
            'items' => [
                ['admin.indoor-maps',          'fa-map',            'Indoor Maps',        'text-teal-500'],
                ['admin.outpost-access',       'fa-unlock',         'Outpost Access',     'text-amber-500'],
            ],
        ],
        [
            'key' => 'during',
            'label' => 'Selama & sesudah',
            'icon' => 'fa-chart-line',
            'items' => [
                ['admin.user-progress',        'fa-chart-line',     'Team Progress',      'text-cyan-500'],
                ['admin.game-assessments',     'fa-clipboard-check','Game Assessments',   'text-violet-500'],
            ],
        ],
        [
            'key' => 'system',
            'label' => 'Tampilan & sistem',
            'icon' => 'fa-sliders-h',
            'items' => [
                ['admin.hero-slides',          'fa-images',         'Hero Slides',        'text-pink-500'],
                ['admin.feature-management',   'fa-toggle-on',      'Feature Control',    'text-orange-500'],
            ],
        ],
    ];
@endphp

{{-- z-50 on the drawer, z-40 on the scrim behind it. On a phone the rail is a fixed
     overlay, so anything that sits above it swallows every tap — which is exactly what
     happened when the modal repair layer handed the scrim a z-index of its own. --}}
<aside x-show="$store.adminNav.open"
       x-transition:enter="transition ease-out duration-150"
       x-transition:enter-start="opacity-0 -translate-x-4"
       x-transition:enter-end="opacity-100 translate-x-0"
       x-cloak
       @keydown.escape.window="if (window.innerWidth < 1024) $store.adminNav.close()"
       {{-- Lock the page behind the drawer, phones only: scrolling the list under an open
            drawer is the most disorienting thing a mobile nav can do. --}}
       x-effect="document.body.classList.toggle('nav-drawer-open', $store.adminNav.open && window.innerWidth < 1024)"
       class="w-[85vw] max-w-[17rem] lg:w-64 shrink-0 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700
              lg:sticky lg:top-0 lg:h-screen overflow-y-auto overscroll-contain
              fixed inset-y-0 left-0 z-50 lg:z-auto shadow-2xl lg:shadow-none">

    <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-start justify-between gap-2">
        <div class="min-w-0">
            <div class="text-sm font-bold text-gray-900 dark:text-gray-100 truncate">Team Building</div>
            <div class="text-xs text-gray-500 dark:text-gray-400 truncate">Management System</div>
        </div>
        {{-- A drawer needs a visible way out. The scrim closes it too, but on a narrow
             phone the rail covers 85% of the screen and there is little scrim left to aim
             at. Hidden on desktop, where the rail is part of the page. --}}
        <button type="button" @click="$store.adminNav.close()"
                aria-label="Tutup menu"
                class="lg:hidden -mr-1 -mt-1 w-9 h-9 shrink-0 rounded-lg flex items-center justify-center
                       text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <nav class="p-2 pb-8">
        {{-- Di atas semua kategori dan tidak bisa dilipat: kalau seseorang tersesat, ini
             tempat pertama yang harus terlihat. --}}
        <a href="{{ route('admin.guide') }}"
           @click="if (window.innerWidth < 1024) $store.adminNav.close()"
           class="flex items-center gap-3 px-2 py-2 mb-2 rounded-md text-sm transition-colors
                  {{ request()->routeIs('admin.guide')
                     ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 font-semibold'
                     : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
            <i class="fas fa-book-open text-indigo-500 w-4 text-center"></i>
            <span class="truncate">Panduan Setup</span>
        </a>

        @foreach ($nav as $group)
            @php
                // A category containing the page you are on opens itself, so a fresh
                // browser never hides where you already are.
                $hasActive = collect($group['items'])->contains(fn ($i) => request()->routeIs($i[0]));
            @endphp

            <div class="mb-1" x-data="{ key: '{{ $group['key'] }}' }">
                <button type="button"
                        @click="$store.adminNav.toggleGroup(key, {{ $hasActive ? 'true' : 'false' }})"
                        class="w-full flex items-center gap-2 px-2 py-2 rounded-md text-xs font-bold uppercase tracking-wider
                               text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <i class="fas fa-chevron-right text-[10px] transition-transform duration-150"
                       :class="$store.adminNav.isOpen(key, {{ $hasActive ? 'true' : 'false' }}) ? 'rotate-90' : ''"></i>
                    <i class="fas {{ $group['icon'] }} text-xs opacity-70"></i>
                    <span class="truncate">{{ $group['label'] }}</span>
                </button>

                <div x-show="$store.adminNav.isOpen(key, {{ $hasActive ? 'true' : 'false' }})" x-cloak class="mt-0.5 space-y-0.5">
                    @foreach ($group['items'] as [$route, $icon, $label, $iconColour])
                        <a href="{{ route($route) }}"
                           @click="if (window.innerWidth < 1024) $store.adminNav.close()"
                           class="flex items-center gap-3 pl-8 pr-2 py-2 rounded-md text-sm transition-colors
                                  {{ request()->routeIs($route)
                                     ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 font-semibold'
                                     : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                            <i class="fas {{ $icon }} {{ $iconColour }} w-4 text-center"></i>
                            <span class="truncate">{{ $label }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach

        {{-- The big screen is a page for a projector, not an admin tool, so it opens in its
             own tab and sits apart from the rest. --}}
        <div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-700">
            <a href="{{ route('kiosk.led') }}" target="_blank" rel="noopener"
               class="flex items-center gap-3 px-2 py-2 rounded-md text-sm text-gray-700 dark:text-gray-300
                      hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                <i class="fas fa-tv text-slate-500 w-4 text-center"></i>
                <span class="truncate">LED Screen</span>
                <i class="fas fa-external-link-alt text-[10px] opacity-50 ml-auto"></i>
            </a>
        </div>
    </nav>
</aside>

{{-- On a phone the rail covers the page, so a tap outside closes it.

     data-overlay-ignore keeps the modal repair layer's z-index script away from this
     element: it is .fixed.inset-0 and outermost, so the script counted it as a dialog
     overlay and raised it above the rail, making the whole menu untappable. --}}
<div x-show="$store.adminNav.open" x-cloak
     data-overlay-ignore
     @click="$store.adminNav.close()"
     x-transition:enter="transition-opacity ease-out duration-150"
     x-transition:enter-start="opacity-0"
     x-transition:leave="transition-opacity ease-in duration-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 bg-black/40 z-40 lg:hidden"></div>
