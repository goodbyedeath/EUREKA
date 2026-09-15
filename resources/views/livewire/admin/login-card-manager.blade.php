<div class="p-4 sm:p-6 space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 mb-1">Kartu Login Tim</h1>
        <p class="text-gray-600">Buat banyak akun tim sekaligus dan cetak kartu QR. Tim login dengan scan kartu di aplikasi, lalu langsung ke setup tim. Email dan password di kartu tetap bisa dipakai sebagai cadangan.</p>
    </div>

    @if (session('cards_msg'))
        <div class="rounded-md bg-green-50 border border-green-200 p-3 text-sm text-green-800">{{ session('cards_msg') }}</div>
    @endif

    <section class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <h2 class="text-lg font-semibold text-gray-900 mb-3">Generate akun + kartu</h2>
        <form wire:submit="generate" class="flex flex-wrap items-end gap-3">
            <div>
                <label for="cardCount" class="block text-sm font-medium text-gray-700">Jumlah kartu</label>
                <input id="cardCount" type="number" min="1" max="200" wire:model="count" class="mt-1 block w-28 px-3 py-2 border border-gray-300 rounded-md">
                @error('count') <span class="text-red-600 text-xs block">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="cardPrefix" class="block text-sm font-medium text-gray-700">Nama akun</label>
                <input id="cardPrefix" type="text" maxlength="40" wire:model="prefix" class="mt-1 block w-40 px-3 py-2 border border-gray-300 rounded-md" placeholder="Tim">
                @error('prefix') <span class="text-red-600 text-xs block">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="cardHours" class="block text-sm font-medium text-gray-700">Durasi akses (jam)</label>
                <input id="cardHours" type="number" min="1" max="240" wire:model="hours" class="mt-1 block w-32 px-3 py-2 border border-gray-300 rounded-md" placeholder="tanpa batas">
                @error('hours') <span class="text-red-600 text-xs block">{{ $message }}</span> @enderror
            </div>
            <button type="submit" wire:loading.attr="disabled" class="px-4 py-2 rounded-md text-white bg-blue-600 hover:bg-blue-700">
                <span wire:loading.remove wire:target="generate">Generate</span>
                <span wire:loading wire:target="generate">Membuat…</span>
            </button>
        </form>
        <p class="mt-2 text-xs text-gray-500">Akun dibuat sebagai "{{ $prefix ?: 'Tim' }} 01", "{{ $prefix ?: 'Tim' }} 02", … tanpa tim. Durasi akses dihitung sejak login pertama; kosongkan untuk tanpa batas.</p>
    </section>

    @if ($batches->isNotEmpty())
        <section class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <h2 class="text-lg font-semibold text-gray-900 mb-2">Cetak</h2>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.login-cards.pdf') }}" class="px-3 py-2 rounded-md text-sm text-white bg-gray-800 hover:bg-gray-900"><i class="fas fa-file-pdf"></i> Semua kartu aktif ({{ $batches->sum() }})</a>
                @foreach ($batches as $batch => $n)
                    <a href="{{ route('admin.login-cards.pdf', ['batch' => $batch]) }}" class="px-3 py-2 rounded-md text-sm border border-gray-300 hover:bg-gray-50">Batch {{ $batch }} ({{ $n }})</a>
                @endforeach
            </div>
        </section>
    @endif

    <section class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-600">
                    <tr>
                        <th class="px-4 py-2 font-medium">Akun</th>
                        <th class="px-4 py-2 font-medium">Tim</th>
                        <th class="px-4 py-2 font-medium">Terakhir dipakai</th>
                        <th class="px-4 py-2 font-medium">Status</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($cards as $card)
                        <tr wire:key="card-{{ $card->id }}">
                            <td class="px-4 py-2">
                                <div class="font-medium text-gray-900">{{ $card->user?->name }}</div>
                                <div class="text-xs text-gray-500 font-mono">{{ $card->user?->email }}</div>
                            </td>
                            <td class="px-4 py-2">{{ $card->user?->team?->name ?? '— belum setup —' }}</td>
                            <td class="px-4 py-2 text-gray-600 whitespace-nowrap">{{ $card->last_used_at?->timezone(config('app.timezone'))->format('d M H:i') ?? '—' }}</td>
                            <td class="px-4 py-2">
                                @if ($card->revoked_at)
                                    <span class="text-red-700 font-medium">Dicabut</span>
                                @elseif ($card->user?->accessWindowExpired())
                                    <span class="text-amber-700">Waktu akses habis</span>
                                @else
                                    <span class="text-green-700">Aktif</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-right whitespace-nowrap">
                                <a href="{{ route('admin.login-cards.pdf', ['card' => $card->id]) }}" class="text-blue-600 hover:underline text-xs">Cetak</a>
                                <button type="button" wire:click="rotate({{ $card->id }})" wire:confirm="Ganti kartu ini? Kartu lama langsung tidak berlaku dan tim harus login ulang dengan kartu baru."
                                        class="ml-3 text-xs text-amber-700 hover:underline">Ganti kartu</button>
                                <button type="button" wire:click="toggleRevoke({{ $card->id }})" wire:confirm="{{ $card->revoked_at ? 'Aktifkan kembali akun ini?' : 'Cabut kartu? Akun dimatikan dan keluar dari semua perangkat.' }}"
                                        class="ml-3 text-xs {{ $card->revoked_at ? 'text-green-700' : 'text-red-700' }} hover:underline">{{ $card->revoked_at ? 'Aktifkan' : 'Cabut' }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">Belum ada kartu login.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $cards->links() }}</div>
    </section>
</div>
