<div class="p-4 sm:p-6 space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 mb-1">Jalur Peta</h1>
        <p class="text-gray-600">
            Jalur digambar di aplikasi tracker, lalu ditarik ke sini. Yang ditandai aktif akan muncul di peta
            peserta, layar kiosk, dan aplikasi Android, termasuk saat offline. Penanda di jalur bisa dijadikan
            pos permainan, supaya radius check-in dan poinnya berlaku.
        </p>
    </div>

    @if (session('route_msg'))
        <div class="rounded-md bg-green-50 border border-green-200 p-3 text-sm text-green-800">{{ session('route_msg') }}</div>
    @endif
    @if (session('route_error'))
        <div class="rounded-md bg-red-50 border border-red-200 p-3 text-sm text-red-800">{{ session('route_error') }}</div>
    @endif

    <section class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex flex-wrap items-center gap-3">
            <button type="button" wire:click="sync" wire:loading.attr="disabled" wire:target="sync"
                    class="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white px-4 py-2 rounded-lg text-sm">
                <span wire:loading.remove wire:target="sync">Tarik dari tracker</span>
                <span wire:loading wire:target="sync">Menarik…</span>
            </button>
            @if ($trackerUrl)
                <a href="{{ $trackerUrl }}" target="_blank" rel="noopener" class="text-sm text-blue-600 underline">Buka aplikasi tracker untuk menggambar jalur</a>
            @else
                <span class="text-sm text-red-600">TRACKER_BASE_URL belum diatur di .env</span>
            @endif
            <span class="text-xs text-gray-500">Otomatis tiap jam. Tekan tombol ini kalau baru selesai merekam.</span>
        </div>
    </section>

    <section class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <h2 class="text-lg font-semibold text-gray-900 mb-1">Nilai awal untuk pos baru</h2>
        <p class="text-sm text-gray-600 mb-3">Dipakai saat menekan "Jadikan pos". Bisa diubah lagi di halaman Quest Locations.</p>
        <div class="flex flex-wrap gap-4">
            <label class="text-sm">
                <span class="block text-gray-700 mb-1">Radius check-in (meter)</span>
                <input type="number" wire:model="radius" min="5" max="500" class="w-32 border-gray-300 rounded-lg">
                @error('radius') <span class="block text-xs text-red-600">{{ $message }}</span> @enderror
            </label>
            <label class="text-sm">
                <span class="block text-gray-700 mb-1">Poin check-in</span>
                <input type="number" wire:model="questPoints" min="0" max="10000" class="w-32 border-gray-300 rounded-lg">
                @error('questPoints') <span class="block text-xs text-red-600">{{ $message }}</span> @enderror
            </label>
        </div>
    </section>

    @forelse ($routes as $route)
        <section class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 space-y-3">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">
                        <span class="inline-block w-3 h-3 rounded-full align-middle" style="background: {{ $route->color }}"></span>
                        {{ $route->name }}
                        @if ($route->is_active)
                            <span class="ml-1 px-2 py-0.5 text-xs rounded-full bg-green-100 text-green-800">tampil di peta</span>
                        @else
                            <span class="ml-1 px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-600">disembunyikan</span>
                        @endif
                    </h2>
                    <p class="text-sm text-gray-600">
                        {{ $route->distanceForHumans() }} · {{ $route->point_count }} titik · {{ $route->markers->count() }} penanda
                        @if ($route->recorded_at) · direkam {{ $route->recorded_at->timezone('Asia/Jakarta')->format('d M Y H:i') }} @endif
                        @if ($route->recorded_by) · oleh {{ $route->recorded_by }} @endif
                    </p>
                </div>
                <button type="button" wire:click="toggle({{ $route->id }})"
                        class="px-3 py-2 text-sm rounded-lg {{ $route->is_active ? 'bg-gray-200 hover:bg-gray-300 text-gray-800' : 'bg-green-600 hover:bg-green-700 text-white' }}">
                    {{ $route->is_active ? 'Sembunyikan' : 'Pakai di acara' }}
                </button>
            </div>

            @if ($route->markers->isEmpty())
                <p class="text-sm text-gray-500">Jalur ini tanpa penanda. Tambahkan penanda di aplikasi tracker, lalu tarik lagi.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-gray-500">
                            <tr>
                                <th class="py-2 pr-3">Penanda</th>
                                <th class="py-2 pr-3">Koordinat</th>
                                <th class="py-2 pr-3">Status</th>
                                <th class="py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($route->markers as $marker)
                                <tr>
                                    <td class="py-2 pr-3">
                                        <span class="mr-1">{{ $marker->icon ?: '📍' }}</span>{{ $marker->title ?: '(tanpa judul)' }}
                                        @if ($marker->description)
                                            <span class="block text-xs text-gray-500">{{ \Illuminate\Support\Str::limit($marker->description, 80) }}</span>
                                        @endif
                                    </td>
                                    <td class="py-2 pr-3 text-gray-600 whitespace-nowrap">{{ number_format((float) $marker->latitude, 6) }}, {{ number_format((float) $marker->longitude, 6) }}</td>
                                    <td class="py-2 pr-3">
                                        @if ($marker->questLocation)
                                            <span class="px-2 py-0.5 text-xs rounded-full bg-blue-100 text-blue-800">pos: {{ $marker->questLocation->name }}</span>
                                        @elseif ($near = ($nearby[$marker->id] ?? null))
                                            <span class="px-2 py-0.5 text-xs rounded-full bg-amber-100 text-amber-800"
                                                  title="Pos ini sudah ada di Quest Locations, sangat dekat dengan penanda.">
                                                dekat pos "{{ $near['post']->name }}" (~{{ $near['metres'] }} m)
                                            </span>
                                        @else
                                            <span class="text-xs text-gray-500">penanda peta saja</span>
                                        @endif
                                    </td>
                                    <td class="py-2 text-right whitespace-nowrap">
                                        @if ($marker->questLocation)
                                            <a href="{{ route('admin.quest-locations') }}" class="text-blue-600 underline text-xs mr-2">Atur pos</a>
                                            <button type="button" wire:click="detach({{ $marker->id }})" class="text-xs text-gray-600 underline">Lepas</button>
                                        @elseif ($near = ($nearby[$marker->id] ?? null))
                                            {{-- Same place, two doors: connect rather than create a twin post. --}}
                                            <button type="button" wire:click="attach({{ $marker->id }}, {{ $near['post']->id }})"
                                                    class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs">Hubungkan ke pos ini</button>
                                            <button type="button" wire:click="promote({{ $marker->id }}, true)"
                                                    class="ml-1 text-xs text-gray-600 underline">Tetap buat pos baru</button>
                                        @else
                                            <button type="button" wire:click="promote({{ $marker->id }})"
                                                    class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs">Jadikan pos</button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @empty
        <section class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 text-center text-gray-600">
            Belum ada jalur. Rekam jalur di aplikasi tracker, lalu tekan "Tarik dari tracker".
        </section>
    @endforelse
</div>
