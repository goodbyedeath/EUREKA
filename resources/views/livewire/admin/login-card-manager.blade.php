<div class="p-4 sm:p-6 space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-1">Kartu Login Tim</h1>
        <p class="text-gray-600 dark:text-gray-400">Buat banyak akun tim sekaligus dan cetak kartu QR. Tim login dengan scan kartu di aplikasi, lalu langsung ke setup tim. Email dan password di kartu tetap bisa dipakai sebagai cadangan.</p>
    </div>

    @if (session('cards_msg'))
        <div class="rounded-md bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 p-3 text-sm text-green-800 dark:text-green-200">{{ session('cards_msg') }}</div>
    @endif

    {{-- Asked inside the page: a browser that has blocked dialogs cannot silence this one. --}}
    @if ($pending)
        <div class="rounded-md border-2 border-amber-400 dark:border-amber-600 bg-amber-50 dark:bg-amber-900/30 p-4">
            <p class="text-sm text-amber-900 dark:text-amber-100">
                @switch ($pending['kind'])
                    @case ('rotate')
                        <strong>Ganti kartu {{ $pending['name'] }}?</strong>
                        Kode dan sandi lama langsung tidak berlaku, akun keluar dari semua perangkat,
                        dan kartunya harus dicetak ulang.
                        @break
                    @case ('revoke')
                        <strong>Cabut kartu {{ $pending['name'] }}?</strong>
                        Akunnya dimatikan dan dikeluarkan dari semua perangkat. Bisa diaktifkan lagi kapan saja.
                        @break
                    @default
                        <strong>Hapus kartu {{ $pending['name'] }} beserta akunnya?</strong>
                        Hanya bisa untuk akun yang belum bergabung ke tim dan belum bermain. Tidak bisa dibatalkan.
                @endswitch
            </p>
            <div class="mt-3 flex gap-2">
                <button type="button" wire:click="confirmPending" wire:loading.attr="disabled"
                        class="px-3 py-1.5 rounded-md text-sm text-white bg-red-600 hover:bg-red-700">
                    Ya, lanjutkan
                </button>
                <button type="button" wire:click="cancel"
                        class="px-3 py-1.5 rounded-md text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200">
                    Batal
                </button>
            </div>
        </div>
    @endif

    @if (session('cards_error'))
        <div class="rounded-md bg-amber-50 dark:bg-amber-900/30 border border-amber-300 dark:border-amber-800 p-3 text-sm text-amber-800 dark:text-amber-200">{{ session('cards_error') }}</div>
    @endif

    <section class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">Generate akun + kartu</h2>
        <form wire:submit="generate" class="flex flex-wrap items-end gap-3">
            <div>
                <label for="cardCount" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Jumlah kartu</label>
                <input id="cardCount" type="number" min="1" max="200" wire:model="count" class="mt-1 block w-28 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md">
                @error('count') <span class="text-red-600 dark:text-red-400 text-xs block">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="cardPrefix" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nama akun</label>
                <input id="cardPrefix" type="text" maxlength="40" wire:model="prefix" class="mt-1 block w-40 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md" placeholder="Tim">
                @error('prefix') <span class="text-red-600 dark:text-red-400 text-xs block">{{ $message }}</span> @enderror
            </div>
            <div>
                {{-- The crew reads these numbers off paper and calls them over a radio, so a batch
                     that starts at 04 is a real problem. Empty = continue after the highest. --}}
                <label for="cardStart" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Mulai dari nomor</label>
                <input id="cardStart" type="number" min="1" max="999" wire:model="startAt" class="mt-1 block w-32 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md" placeholder="{{ str_pad((string) $nextNumber, 2, '0', STR_PAD_LEFT) }}">
                @error('startAt') <span class="text-red-600 dark:text-red-400 text-xs block">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="cardHours" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Durasi akses (jam)</label>
                <input id="cardHours" type="number" min="1" max="240" wire:model="hours" class="mt-1 block w-32 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md" placeholder="tanpa batas">
                @error('hours') <span class="text-red-600 dark:text-red-400 text-xs block">{{ $message }}</span> @enderror
            </div>
            <button type="submit" wire:loading.attr="disabled" class="px-4 py-2 rounded-md text-white bg-blue-600 hover:bg-blue-700">
                <span wire:loading.remove wire:target="generate">Generate</span>
                <span wire:loading wire:target="generate">Membuat…</span>
            </button>
        </form>
        @php
            $firstNumber = $startAt !== '' && (int) $startAt > 0 ? (int) $startAt : $nextNumber;
            $pad = fn ($n) => str_pad((string) $n, 2, '0', STR_PAD_LEFT);
        @endphp
        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
            Akun dibuat sebagai <strong>"{{ $prefix ?: 'Tim' }} {{ $pad($firstNumber) }}"</strong> sampai
            <strong>"{{ $prefix ?: 'Tim' }} {{ $pad($firstNumber + max(1, (int) $count) - 1) }}"</strong>, tanpa tim.
            Kosongkan "mulai dari nomor" untuk melanjutkan setelah nomor tertinggi yang sudah ada.
            Durasi akses dihitung sejak login pertama; kosongkan untuk tanpa batas.
        </p>
    </section>

    @if ($batches->isNotEmpty())
        <section class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Cetak</h2>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.login-cards.pdf') }}" class="px-3 py-2 rounded-md text-sm text-white bg-gray-800 hover:bg-gray-900"><i class="fas fa-file-pdf"></i> Semua kartu aktif ({{ $batches->sum() }})</a>
                @foreach ($batches as $batch => $n)
                    <a href="{{ route('admin.login-cards.pdf', ['batch' => $batch]) }}" class="px-3 py-2 rounded-md text-sm border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700">Batch {{ $batch }} ({{ $n }})</a>
                @endforeach
            </div>
        </section>
    @endif

    <section class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900 text-left text-gray-600 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-2 font-medium">Akun</th>
                        <th class="px-4 py-2 font-medium">Tim</th>
                        <th class="px-4 py-2 font-medium">Terakhir dipakai</th>
                        <th class="px-4 py-2 font-medium">Status</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($cards as $card)
                        <tr wire:key="card-{{ $card->id }}">
                            <td class="px-4 py-2">
                                <div class="font-medium text-gray-900 dark:text-gray-100">{{ $card->user?->name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 font-mono">{{ $card->user?->email }}</div>
                            </td>
                            <td class="px-4 py-2">{{ $card->user?->team?->name ?? '— belum setup —' }}</td>
                            <td class="px-4 py-2 text-gray-600 dark:text-gray-400 whitespace-nowrap">{{ $card->last_used_at?->timezone(config('app.timezone'))->format('d M H:i') ?? '—' }}</td>
                            <td class="px-4 py-2">
                                @if ($card->revoked_at)
                                    <span class="text-red-700 dark:text-red-300 font-medium">Dicabut</span>
                                @elseif ($card->user?->accessWindowExpired())
                                    <span class="text-amber-700 dark:text-amber-300">Waktu akses habis</span>
                                @else
                                    <span class="text-green-700 dark:text-green-300">Aktif</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-right whitespace-nowrap">
                                <a href="{{ route('admin.login-cards.pdf', ['card' => $card->id]) }}" class="text-blue-600 dark:text-blue-400 hover:underline text-xs">Cetak</a>
                                <button type="button" wire:click="ask('rotate', {{ $card->id }})"
                                        class="ml-3 text-xs text-amber-700 dark:text-amber-300 hover:underline">Ganti kartu</button>
                                <button type="button" wire:click="ask('revoke', {{ $card->id }})"
                                        class="ml-3 text-xs {{ $card->revoked_at ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300' }} hover:underline">{{ $card->revoked_at ? 'Aktifkan' : 'Cabut' }}</button>
                                {{-- For a card that should never have been printed. Refused once the
                                     account has played anything — that is Force Delete's job. --}}
                                <button type="button" wire:click="ask('delete', {{ $card->id }})"
                                        class="ml-3 text-xs text-red-800 dark:text-red-400 hover:underline">Hapus</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">Belum ada kartu login.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $cards->links() }}</div>
    </section>
</div>
