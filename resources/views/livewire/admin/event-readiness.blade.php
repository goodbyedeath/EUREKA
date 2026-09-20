<div class="p-4 sm:p-6 space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-1">Cek Kesiapan Acara</h1>
        <p class="text-gray-600 dark:text-gray-400 max-w-3xl">
            Rantai acara: <strong>Tim → Denah → Pos → Kuesioner</strong>. Tiap bagian diatur di halaman berbeda,
            jadi halaman ini menelusuri rantainya untuk tiap tim dan menyebut apa yang masih putus.
            Merah berarti tim itu belum bisa bermain.
        </p>
    </div>

    @php
        $blocked = $rows->where('blocked', true)->count();
    @endphp

    <div class="rounded-lg border p-4 {{ $blocked
        ? 'border-red-300 dark:border-red-800 bg-red-50 dark:bg-red-900/30 text-red-800 dark:text-red-200'
        : 'border-green-300 dark:border-green-800 bg-green-50 dark:bg-green-900/30 text-green-800 dark:text-green-200' }}">
        @if ($blocked)
            <strong>{{ $blocked }} dari {{ $rows->count() }} tim belum siap.</strong>
            Perbaiki yang merah dulu; sisanya hanya catatan.
        @else
            <strong>Semua {{ $rows->count() }} tim siap.</strong>
            Rantai tim, denah, pos, dan kuesionernya lengkap.
        @endif
        @if ($startMap)
            <span class="block text-xs mt-1 opacity-80">QR START aktif menunjuk denah "{{ $startMap->name }}" — dipakai tim yang belum diberi denah sendiri.</span>
        @else
            <span class="block text-xs mt-1 opacity-80">QR START aktif belum menunjuk denah mana pun.</span>
        @endif
    </div>

    @forelse ($rows as $row)
        <section class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border {{ $row->blocked ? 'border-red-300 dark:border-red-800' : 'border-gray-200 dark:border-gray-700' }} p-4 space-y-3">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        {{ $row->team->name }}
                        @if ($row->blocked)
                            <span class="ml-1 px-2 py-0.5 text-xs rounded-full bg-red-100 dark:bg-red-900/40 text-red-800 dark:text-red-200">belum siap</span>
                        @else
                            <span class="ml-1 px-2 py-0.5 text-xs rounded-full bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-200">siap</span>
                        @endif
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ $row->accounts->count() }} akun login ·
                        denah:
                        @if ($row->plan)
                            <strong>{{ $row->plan->name }}</strong> {{ $row->assigned ? '' : '(dari QR START)' }}
                        @else
                            <span class="text-red-600 dark:text-red-400">belum ada</span>
                        @endif
                        · {{ $row->posts->count() }} pos
                    </p>
                </div>
                <div class="flex flex-wrap gap-2 text-xs">
                    <a href="{{ route('admin.team-management') }}" class="px-2 py-1 rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200">Atur tim</a>
                    <a href="{{ route('admin.indoor-maps') }}" class="px-2 py-1 rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200">Atur denah</a>
                    <a href="{{ route('admin.dashboard-management') }}" class="px-2 py-1 rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200">Atur kuesioner</a>
                </div>
            </div>

            @if ($row->problems)
                <ul class="text-sm text-amber-800 dark:text-amber-200 bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 rounded-lg p-3 space-y-1">
                    @foreach ($row->problems as $problem)
                        <li>• {{ $problem }}</li>
                    @endforeach
                </ul>
            @endif

            @if ($row->posts->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-gray-500 dark:text-gray-400">
                            <tr>
                                <th class="py-2 pr-3">Pos di denah ini</th>
                                <th class="py-2 pr-3">Kuesioner</th>
                                <th class="py-2 pr-3">Berlaku</th>
                                <th class="py-2">Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach ($row->posts as $post)
                                @if ($post->questionnaires->isEmpty())
                                    <tr>
                                        <td class="py-2 pr-3 text-gray-900 dark:text-gray-100">{{ $post->post->name }}</td>
                                        <td class="py-2 pr-3 text-red-600 dark:text-red-400" colspan="3">belum ada kuesioner untuk pos ini</td>
                                    </tr>
                                @else
                                    @foreach ($post->questionnaires as $i => $q)
                                        <tr>
                                            <td class="py-2 pr-3 text-gray-900 dark:text-gray-100">{{ $i === 0 ? $post->post->name : '' }}</td>
                                            <td class="py-2 pr-3 text-gray-700 dark:text-gray-300">{{ $q->q->title }}</td>
                                            <td class="py-2 pr-3 whitespace-nowrap {{ $q->available ? 'text-gray-600 dark:text-gray-400' : 'text-red-600 dark:text-red-400' }}">{{ $q->window }}</td>
                                            <td class="py-2">
                                                @forelse ($q->problems as $p)
                                                    <span class="inline-block px-2 py-0.5 mr-1 rounded-full text-xs bg-red-100 dark:bg-red-900/40 text-red-800 dark:text-red-200">{{ $p }}</span>
                                                @empty
                                                    <span class="text-xs text-green-700 dark:text-green-300">siap</span>
                                                @endforelse
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                                @foreach ($post->problems as $p)
                                    <tr><td colspan="4" class="pb-2 text-xs text-amber-700 dark:text-amber-300">• {{ $post->post->name }}: {{ $p }}</td></tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @empty
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-dashed border-gray-300 dark:border-gray-700 p-8 text-center text-gray-600 dark:text-gray-400">
            Belum ada tim. Buat akun tim di Kartu Login Tim, atau biarkan tim mendaftar lewat aplikasi.
        </div>
    @endforelse

    @if ($orphanQuestionnaires->isNotEmpty())
        <section class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-amber-300 dark:border-amber-800 p-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-1">Kuesioner yang belum terjangkau</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                Aktif, tapi tidak menempel pada pos yang ada di denah mana pun — jadi tidak akan pernah bisa discan.
            </p>
            <ul class="text-sm space-y-1">
                @foreach ($orphanQuestionnaires as $q)
                    <li class="text-gray-700 dark:text-gray-300">
                        • {{ $q->title }}
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            ({{ $q->venue_mode ?: 'tanpa mode' }}{{ $q->game_location_id ? ', pos #'.$q->game_location_id.' tidak ada di denah' : ', belum dihubungkan ke pos' }})
                        </span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
