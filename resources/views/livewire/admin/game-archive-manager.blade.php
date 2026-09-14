<div class="p-4 sm:p-6 space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 mb-1">Game Archives</h1>
        <p class="text-gray-600">Simpan riwayat sesi yang sudah berakhir, lalu tutup sesi itu dari sistem live.</p>
    </div>

    @if (session('success'))
        <div class="rounded-md bg-green-50 border border-green-200 p-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    {{-- The session currently in the live system --}}
    <section class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Sesi yang sedang berjalan</h2>
            <p class="text-sm text-gray-600">
                {{ $preview['teams']->count() }} tim · {{ $preview['accounts'] }} akun pemain · {{ $preview['stations_total'] }} pos dihitung
            </p>
        </div>

        @if ($preview['teams']->isEmpty())
            <p class="p-4 text-sm text-gray-500">Belum ada tim di sistem live.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-gray-600">
                        <tr>
                            <th class="px-4 py-2 font-medium">#</th>
                            <th class="px-4 py-2 font-medium">Tim</th>
                            <th class="px-4 py-2 font-medium text-right">Skor</th>
                            <th class="px-4 py-2 font-medium">Pos selesai</th>
                            <th class="px-4 py-2 font-medium text-right">Anggota</th>
                            <th class="px-4 py-2 font-medium text-right">Akun</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($preview['teams'] as $i => $t)
                            <tr>
                                <td class="px-4 py-2 text-gray-500">{{ $i + 1 }}</td>
                                <td class="px-4 py-2 font-medium text-gray-900">{{ $t['name'] }}</td>
                                <td class="px-4 py-2 text-right tabular-nums">{{ number_format($t['score']) }}</td>
                                <td class="px-4 py-2">
                                    <span class="font-medium {{ $t['stations_done'] < $t['stations_total'] ? 'text-amber-700' : 'text-green-700' }}">
                                        {{ $t['stations_done'] }}/{{ $t['stations_total'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-right tabular-nums">{{ $t['members'] }}</td>
                                <td class="px-4 py-2 text-right tabular-nums">{{ $t['accounts'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if ($preview['unfinished']->isNotEmpty() || $preview['live_sessions'] > 0 || $preview['accounts_without_team'] > 0)
            <div class="mx-4 mt-4 rounded-md bg-amber-50 border border-amber-200 p-3 text-sm text-amber-900 space-y-1">
                @if ($preview['unfinished']->isNotEmpty())
                    <p><strong>{{ $preview['unfinished']->count() }} tim belum menyelesaikan semua pos:</strong> {{ $preview['unfinished']->pluck('name')->join(', ') }}.</p>
                @endif
                @if ($preview['live_sessions'] > 0)
                    <p><strong>{{ $preview['live_sessions'] }} sesi pertanyaan masih berjalan.</strong> Diarsipkan apa adanya, sebagai belum selesai.</p>
                @endif
                @if ($preview['accounts_without_team'] > 0)
                    <p>{{ $preview['accounts_without_team'] }} akun pemain belum punya tim; akun ini ikut ditutup.</p>
                @endif
            </div>
        @endif

        <div class="p-4 space-y-4">
            <div class="text-sm text-gray-700">
                <p class="font-medium text-gray-900 mb-1">Saat diarsipkan:</p>
                <ul class="list-disc pl-5 space-y-1">
                    <li>Leaderboard, skor akhir, anggota tim, rincian per pos, dan semua foto disimpan permanen di arsip.</li>
                    <li>Semua tim dan akun pemain di atas dihapus dari sistem live; progres dan leaderboard kiosk menjadi kosong.</li>
                    <li>APK yang masih memakai akun-akun ini langsung ditolak dan menampilkan “Game sudah berakhir, silakan uninstall aplikasi ini”.</li>
                    <li>Pos, kuesioner, peta, objek 3D, dan branding tidak disentuh — siap untuk sesi berikutnya.</li>
                    <li><strong>Tidak bisa dibatalkan.</strong></li>
                </ul>
            </div>

            <form wire:submit="archive" x-data="{ typed: '' }" class="space-y-3 max-w-xl">
                <div>
                    <label for="archiveName" class="block text-sm font-medium text-gray-700">Nama sesi</label>
                    <input id="archiveName" type="text" wire:model="name" maxlength="150" placeholder="mis. Questerra Batch 3 — 20 Sep 2026"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    @error('name') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="archiveNotes" class="block text-sm font-medium text-gray-700">Catatan (opsional)</label>
                    <textarea id="archiveNotes" wire:model="notes" rows="2" maxlength="2000"
                              class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"></textarea>
                    @error('notes') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="archiveConfirm" class="block text-sm font-medium text-gray-700">Ketik <span class="font-mono font-semibold">{{ $confirmWord }}</span> untuk mengonfirmasi</label>
                    <input id="archiveConfirm" type="text" autocomplete="off" x-model="typed" wire:model="confirmText"
                           class="mt-1 block w-48 px-3 py-2 border border-gray-300 rounded-md shadow-sm font-mono focus:outline-none focus:ring-red-500 focus:border-red-500">
                    @error('confirmText') <span class="text-red-600 text-xs block">{{ $message }}</span> @enderror
                </div>
                <button type="submit" x-bind:disabled="typed !== '{{ $confirmWord }}'" wire:loading.attr="disabled"
                        class="px-4 py-2 rounded-md text-white bg-red-600 hover:bg-red-700 disabled:opacity-40 disabled:cursor-not-allowed">
                    <span wire:loading.remove wire:target="archive">Arsipkan &amp; tutup sesi</span>
                    <span wire:loading wire:target="archive">Mengarsipkan…</span>
                </button>
            </form>
        </div>
    </section>

    {{-- Past sessions --}}
    <section class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Arsip</h2>
        </div>
        @if ($archives->isEmpty())
            <p class="p-4 text-sm text-gray-500">Belum ada sesi yang diarsipkan.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-gray-600">
                        <tr>
                            <th class="px-4 py-2 font-medium">Sesi</th>
                            <th class="px-4 py-2 font-medium">Diarsipkan</th>
                            <th class="px-4 py-2 font-medium text-right">Tim</th>
                            <th class="px-4 py-2 font-medium">Juara</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($archives as $a)
                            <tr>
                                <td class="px-4 py-2 font-medium text-gray-900">{{ $a->name }}</td>
                                <td class="px-4 py-2 text-gray-600 whitespace-nowrap">{{ $a->archived_at?->timezone(config('app.timezone'))->format('d M Y H:i') }}</td>
                                <td class="px-4 py-2 text-right tabular-nums">{{ $a->team_count }}</td>
                                <td class="px-4 py-2 text-gray-700">{{ $a->winner_name ?? '—' }}@if (! is_null($a->winner_score)) <span class="text-gray-500 tabular-nums">({{ number_format($a->winner_score) }})</span>@endif</td>
                                <td class="px-4 py-2 text-right"><a href="{{ route('admin.game-archives.show', $a->id) }}" class="text-blue-600 hover:underline">Lihat</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>
