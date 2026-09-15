<div class="p-4 sm:p-6 space-y-6">
    @php
        $when = fn ($v) => $v ? \Illuminate\Support\Carbon::parse($v)->timezone(config('app.timezone'))->format('d M Y H:i') : '—';
        $num = fn ($n) => number_format((int) $n, 0, ',', '.');
    @endphp

    <div>
        <h1 class="text-2xl font-bold text-gray-900 mb-1">Integrasi FEKDI x IFSE</h1>
        <p class="text-gray-600">Peserta dari website klien untuk setup tim di APK, dan kiriman poin tim ke leaderboard klien. Dipakai 24–27 September 2026.</p>
    </div>

    @if (session('fekdi_msg'))
        <div class="rounded-md bg-green-50 border border-green-200 p-3 text-sm text-green-800">{{ session('fekdi_msg') }}</div>
    @endif
    @if (session('fekdi_error'))
        <div class="rounded-md bg-red-50 border border-red-200 p-3 text-sm text-red-800">{{ session('fekdi_error') }}</div>
    @endif

    <section class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex-1 min-w-[14rem]">
                <h2 class="text-lg font-semibold text-gray-900">
                    Status:
                    @if ($enabled && $configured)
                        <span class="text-green-700">Aktif</span>
                    @elseif ($enabled)
                        <span class="text-amber-700">Menyala, tapi belum dikonfigurasi</span>
                    @else
                        <span class="text-gray-600">Mati</span>
                    @endif
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    Website klien: <span class="font-mono">{{ $host ?: 'belum diatur' }}</span> ·
                    API key: {{ $configured ? 'terpasang' : 'belum diatur' }}
                </p>
                <p class="text-sm text-gray-600 mt-1">
                    Saat mati: pilihan “sudah terdaftar” hilang dari APK (tim tetap bisa mengetik anggota manual) dan tidak ada poin yang dikirim.
                    Poin tetap dicatat di sini.
                </p>
            </div>
            <button type="button" wire:click="toggle" wire:loading.attr="disabled"
                    class="px-4 py-2 rounded-md text-sm font-medium text-white {{ $enabled ? 'bg-gray-700 hover:bg-gray-800' : 'bg-green-600 hover:bg-green-700' }}">
                {{ $enabled ? 'Matikan integrasi' : 'Nyalakan integrasi' }}
            </button>
        </div>

        <dl class="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm">
            <div class="rounded-md bg-gray-50 p-3"><dt class="text-gray-500">Peserta</dt><dd class="text-lg font-semibold tabular-nums">{{ $num($stats['participants']) }}</dd></div>
            <div class="rounded-md bg-gray-50 p-3"><dt class="text-gray-500">Terdaftar di tim</dt><dd class="text-lg font-semibold tabular-nums">{{ $num($stats['linked']) }}</dd></div>
            <div class="rounded-md bg-gray-50 p-3"><dt class="text-gray-500">Poin menunggu kirim</dt><dd class="text-lg font-semibold tabular-nums">{{ $num($stats['pending']) }}</dd></div>
            <div class="rounded-md {{ $stats['attention'] ? 'bg-amber-50' : 'bg-gray-50' }} p-3"><dt class="text-gray-500">Perlu dicek</dt><dd class="text-lg font-semibold tabular-nums {{ $stats['attention'] ? 'text-amber-800' : '' }}">{{ $num($stats['attention']) }}</dd></div>
        </dl>
        <p class="mt-2 text-xs text-gray-500">
            Tarik data terakhir: {{ $when($stats['last_import']) }} · kiriman poin terakhir: {{ $when($stats['last_sync']) }}
            @if ($stats['decreases']) · <span class="text-amber-700">{{ $stats['decreases'] }} peserta poinnya turun (tidak dikirim otomatis)</span>@endif
        </p>

        <div class="mt-4 flex flex-wrap gap-2">
            <button type="button" wire:click="importNow" wire:loading.attr="disabled" wire:target="importNow"
                    class="px-3 py-2 rounded-md text-sm border border-gray-300 hover:bg-gray-50">
                <span wire:loading.remove wire:target="importNow">Tarik data peserta sekarang</span>
                <span wire:loading wire:target="importNow">Menarik…</span>
            </button>
            <button type="button" wire:click="syncNow" wire:loading.attr="disabled" wire:target="syncNow"
                    class="px-3 py-2 rounded-md text-sm border border-gray-300 hover:bg-gray-50">
                <span wire:loading.remove wire:target="syncNow">Kirim poin sekarang</span>
                <span wire:loading wire:target="syncNow">Mengirim…</span>
            </button>
        </div>
        <p class="mt-2 text-xs text-gray-500">
            Otomatis: poin dikirim setiap menit dan data peserta ditarik setiap jam, selama integrasi aktif dan cron berjalan.
            Cron terakhir berjalan:
            @if ($heartbeat)
                @php($beat = \Illuminate\Support\Carbon::parse($heartbeat))
                <span class="{{ $beat->lt(now()->subMinutes(5)) ? 'text-red-700 font-medium' : 'text-green-700' }}">{{ $when($heartbeat) }}</span>
                @if ($beat->lt(now()->subMinutes(5))) — cron tampaknya berhenti, cek hPanel.@endif
            @else
                <span class="text-amber-700">belum pernah tercatat</span>
            @endif
        </p>
    </section>

    <section class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-4 border-b border-gray-200 flex flex-wrap items-center gap-3">
            <input type="search" wire:model.live.debounce.400ms="search" placeholder="Cari nama, email, atau tim…"
                   class="flex-1 min-w-[12rem] px-3 py-2 border border-gray-300 rounded-md text-sm">
            <select wire:model.live="filter" class="px-3 py-2 border border-gray-300 rounded-md text-sm">
                <option value="all">Semua peserta</option>
                <option value="linked">Terdaftar di tim</option>
                <option value="attention">Perlu tindakan</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-600">
                    <tr>
                        <th class="px-4 py-2 font-medium">Peserta</th>
                        <th class="px-4 py-2 font-medium">Tim</th>
                        <th class="px-4 py-2 font-medium text-right">Poin</th>
                        <th class="px-4 py-2 font-medium text-right">Diterima klien</th>
                        <th class="px-4 py-2 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($rows as $p)
                        <tr wire:key="fekdi-{{ $p->id }}">
                            <td class="px-4 py-2">
                                <div class="font-medium text-gray-900">{{ $p->name ?? '—' }}</div>
                                <div class="text-xs text-gray-500">{{ $p->email }}</div>
                            </td>
                            <td class="px-4 py-2">
                                {{ $p->team_name ?? '—' }}
                                @if ($p->team_name && $p->is_leader) <span class="text-xs text-violet-700">(ketua)</span>@endif
                                @if ($p->team_name && ! $p->team_id) <span class="text-xs text-gray-500">(diarsipkan)</span>@endif
                            </td>
                            <td class="px-4 py-2 text-right tabular-nums">{{ $p->team_name ? $num($p->points) : '—' }}</td>
                            <td class="px-4 py-2 text-right tabular-nums">{{ $p->team_name ? $num($p->points_synced) : '—' }}</td>
                            <td class="px-4 py-2">
                                @if ($p->sync_state === 'uncertain')
                                    <div class="text-amber-800 font-medium">Perlu dicek</div>
                                    <div class="text-xs text-gray-600 max-w-xs">{{ $p->sync_error }}</div>
                                    <div class="text-xs text-gray-600">Cek saldo luring peserta di website klien, lalu:</div>
                                    <div class="mt-1 flex gap-2">
                                        <button type="button" wire:click="markSynced({{ $p->id }})" class="px-2 py-1 text-xs rounded bg-green-600 text-white">Sudah masuk</button>
                                        <button type="button" wire:click="retry({{ $p->id }})" class="px-2 py-1 text-xs rounded border border-gray-300">Belum masuk, kirim ulang</button>
                                    </div>
                                @elseif ($p->sync_state === 'error')
                                    <div class="text-red-700 font-medium">Ditolak klien</div>
                                    <div class="text-xs text-gray-600 max-w-xs">{{ $p->sync_error }}</div>
                                    <button type="button" wire:click="retry({{ $p->id }})" class="mt-1 px-2 py-1 text-xs rounded border border-gray-300">Coba lagi</button>
                                @elseif ($p->sync_state === 'sending')
                                    <span class="text-blue-700">Mengirim…</span>
                                @elseif (! $p->team_name)
                                    <span class="text-gray-400">Belum di tim</span>
                                @elseif ($p->points > $p->points_synced)
                                    <span class="text-blue-700">Menunggu kirim +{{ $num($p->points - $p->points_synced) }}</span>
                                @elseif ($p->points < $p->points_synced)
                                    <span class="text-amber-700">Turun {{ $num($p->points_synced - $p->points) }} — tidak dikirim otomatis</span>
                                @else
                                    <span class="text-green-700">Sinkron</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">Belum ada peserta. Nyalakan integrasi lalu tarik data peserta.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $rows->links() }}</div>
    </section>
</div>
