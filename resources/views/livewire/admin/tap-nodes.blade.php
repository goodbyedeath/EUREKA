<div class="p-4 sm:p-6 space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-1">RFID &amp; Reader Pos</h1>
        <p class="text-gray-600 dark:text-gray-400 max-w-3xl">
            Satu kartu per <strong>tim</strong>, satu reader per <strong>pos</strong>. Tim menempelkan kartunya
            di booth, tap-nya lewat LoRa ke gateway di rak server, dan pos itu terbuka untuk tim itu —
            baris yang sama persis dengan yang ditulis Outpost Access. Outpost Access tetap ada sebagai
            cadangan kalau sebuah reader mati di tengah acara.
        </p>
    </div>

    @if (session('tap_msg'))
        <div class="rounded-lg border border-green-300 dark:border-green-800 bg-green-50 dark:bg-green-900/30 p-3 text-sm text-green-800 dark:text-green-200">
            {{ session('tap_msg') }}
        </div>
    @endif
    @if (session('tap_error'))
        <div class="rounded-lg border border-red-300 dark:border-red-800 bg-red-50 dark:bg-red-900/30 p-3 text-sm text-red-800 dark:text-red-200">
            {{ session('tap_error') }}
        </div>
    @endif

    @if ($silent || $lowBattery)
        <div class="rounded-lg border border-amber-300 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/30 p-4 text-amber-800 dark:text-amber-200">
            <strong>
                @if ($silent){{ $silent }} reader tidak terdengar lebih dari 15 menit.@endif
                @if ($lowBattery) {{ $lowBattery }} reader baterainya lemah.@endif
            </strong>
            Booth berjalan dengan powerbank tanpa listrik, jadi ini cara melihat pos yang akan mati
            sebelum kru mendengarnya dari antrean tim.
        </div>
    @endif

    {{-- Kartu yang sudah tap tapi belum punya tim: ini alur provisioning yang sebenarnya. --}}
    @if ($unknown->isNotEmpty())
        <div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Kartu yang belum punya tim</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                Tempelkan setiap kartu sekali di booth mana pun, lalu tetapkan timnya di sini.
            </p>
            <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800/60 text-left text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        <tr>
                            <th class="px-3 py-2">UID</th>
                            <th class="px-3 py-2">Tap</th>
                            <th class="px-3 py-2">Terakhir di reader</th>
                            <th class="px-3 py-2">Tetapkan ke tim</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($unknown as $row)
                            <tr wire:key="unknown-{{ $row->uid }}">
                                <td class="px-3 py-2 font-mono text-xs text-gray-900 dark:text-gray-100">{{ $row->uid }}</td>
                                <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $row->taps }}×</td>
                                <td class="px-3 py-2 text-gray-500 dark:text-gray-400">node {{ $row->node_id }} · {{ $row->last_tap }}</td>
                                <td class="px-3 py-2">
                                    {{-- One action with both values: choosing the team IS the assignment.
                                         No shared property across rows, and no $set-then-$wire chain,
                                         which is how dead buttons have shipped here before. --}}
                                    <select wire:change="assignTo('{{ $row->uid }}', $event.target.value)"
                                        class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm">
                                        <option value="">— pilih tim untuk menetapkan —</option>
                                        @foreach ($teams as $team)
                                            <option value="{{ $team->id }}">{{ $team->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Reader per pos --}}
    <div>
        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Reader</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
            <code>node_id</code> adalah dua byte yang ikut di frame LoRa. Pos-nya ada di sini, bukan di
            frame — jadi memindahkan booth berarti mengubah satu baris, bukan memprogram ulang dua gateway.
        </p>

        <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800/60 text-left text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    <tr>
                        <th class="px-3 py-2">node_id</th>
                        <th class="px-3 py-2">Nama</th>
                        <th class="px-3 py-2">Pos</th>
                        <th class="px-3 py-2">Baterai</th>
                        <th class="px-3 py-2">Terakhir terdengar</th>
                        <th class="px-3 py-2">Kunci HMAC</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($nodes as $node)
                        <tr wire:key="node-{{ $node->id }}" class="{{ $node->is_active ? '' : 'opacity-50' }}">
                            <td class="px-3 py-2 font-mono text-gray-900 dark:text-gray-100">{{ $node->node_id }}</td>
                            <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $node->label }}</td>
                            <td class="px-3 py-2">
                                <select wire:change="setPost({{ $node->id }}, $event.target.value)"
                                    class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm">
                                    <option value="" @selected(! $node->game_location_id)>— belum ditaruh —</option>
                                    @foreach ($posts as $post)
                                        <option value="{{ $post->id }}" @selected($node->game_location_id === $post->id)>{{ $post->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-3 py-2 {{ $node->batteryLow() ? 'text-red-600 dark:text-red-400 font-medium' : 'text-gray-700 dark:text-gray-300' }}">
                                {{ $node->vbat_mv ? number_format($node->vbat_mv / 1000, 2).' V' : '—' }}
                            </td>
                            <td class="px-3 py-2 {{ $node->is_active && $node->isSilent() ? 'text-amber-700 dark:text-amber-300' : 'text-gray-500 dark:text-gray-400' }}">
                                {{ $node->last_seen_at ? $node->last_seen_at->diffForHumans() : 'belum pernah' }}
                            </td>
                            <td class="px-3 py-2">
                                <code class="text-xs text-gray-500 dark:text-gray-400">{{ $node->node_key ? substr($node->node_key, 0, 8).'…' : '—' }}</code>
                                <button wire:click="rotateKey({{ $node->id }})"
                                    wire:confirm="Ganti kunci reader {{ $node->node_id }}? Node itu harus di-provisioning ulang."
                                    class="ml-1 text-xs text-indigo-600 dark:text-indigo-400 hover:underline">ganti</button>
                            </td>
                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                <button wire:click="toggleNode({{ $node->id }})" class="text-xs text-gray-600 dark:text-gray-400 hover:underline">
                                    {{ $node->is_active ? 'Matikan' : 'Nyalakan' }}
                                </button>
                                <button wire:click="deleteNode({{ $node->id }})"
                                    wire:confirm="Hapus reader {{ $node->node_id }}? Riwayat tap-nya tetap tersimpan."
                                    class="ml-2 text-xs text-red-600 dark:text-red-400 hover:underline">Hapus</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-4 text-center text-gray-500 dark:text-gray-400">
                                Belum ada reader. Tambahkan satu di bawah, lalu tulis <code>node_id</code> dan
                                kuncinya ke NVS node itu saat provisioning.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <form wire:submit.prevent="addNode" class="mt-3 flex flex-wrap items-end gap-2">
            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400">node_id</label>
                <input type="number" min="1" max="65535" wire:model="newNodeId" placeholder="3"
                    class="w-24 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm">
            </div>
            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400">Nama</label>
                <input type="text" wire:model="newNodeLabel" placeholder="Booth 3"
                    class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm">
            </div>
            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400">Pos</label>
                <select wire:model="newNodePost" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm">
                    <option value="">— belum ditaruh —</option>
                    @foreach ($posts as $post)
                        <option value="{{ $post->id }}">{{ $post->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                Tambah reader
            </button>
        </form>
    </div>

    {{-- Kartu per tim --}}
    <div>
        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Kartu tim</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
            UID saja sudah cukup: server memetakan UID → tim, dan sebuah tap hanya membuka pos
            <em>untuk tim itu</em> — jadi kartu yang dikloning tidak memberi apa pun yang berharga.
        </p>

        <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800/60 text-left text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    <tr>
                        <th class="px-3 py-2">UID</th>
                        <th class="px-3 py-2">Tim</th>
                        <th class="px-3 py-2">Terakhir dipakai</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($tags as $tag)
                        <tr wire:key="tag-{{ $tag->id }}" class="{{ $tag->is_active ? '' : 'opacity-50' }}">
                            <td class="px-3 py-2 font-mono text-xs text-gray-900 dark:text-gray-100">{{ $tag->uid }}</td>
                            <td class="px-3 py-2 text-gray-700 dark:text-gray-300">
                                {{ $tag->team?->name ?? '— belum ada tim —' }}
                            </td>
                            <td class="px-3 py-2 text-gray-500 dark:text-gray-400">
                                {{ $tag->last_used_at ? $tag->last_used_at->diffForHumans() : 'belum pernah' }}
                            </td>
                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                <button wire:click="toggleTag({{ $tag->id }})" class="text-xs text-gray-600 dark:text-gray-400 hover:underline">
                                    {{ $tag->is_active ? 'Matikan' : 'Nyalakan' }}
                                </button>
                                <button wire:click="deleteTag({{ $tag->id }})" wire:confirm="Lepas kartu {{ $tag->uid }}?"
                                    class="ml-2 text-xs text-red-600 dark:text-red-400 hover:underline">Hapus</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-3 py-4 text-center text-gray-500 dark:text-gray-400">
                                Belum ada kartu terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <form wire:submit.prevent="addTag" class="mt-3 flex flex-wrap items-end gap-2">
            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400">UID (hex)</label>
                <input type="text" wire:model="newTagUid" placeholder="04A2B3C4"
                    class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm font-mono">
            </div>
            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400">Tim</label>
                <select wire:model="newTagTeam" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm">
                    <option value="">— pilih tim —</option>
                    @foreach ($teams as $team)
                        <option value="{{ $team->id }}">{{ $team->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                Daftarkan kartu
            </button>
        </form>
    </div>

    {{-- Riwayat tap: termasuk yang ditolak, karena kartu yang tidak jalan itulah yang perlu dilihat kru. --}}
    <div>
        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Tap terakhir</h2>
        <div class="mt-2 overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800/60 text-left text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    <tr>
                        <th class="px-3 py-2">Waktu tap</th>
                        <th class="px-3 py-2">Reader</th>
                        <th class="px-3 py-2">Kartu</th>
                        <th class="px-3 py-2">Tim</th>
                        <th class="px-3 py-2">Pos</th>
                        <th class="px-3 py-2">Hasil</th>
                        <th class="px-3 py-2">Radio</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($arrivals as $a)
                        <tr wire:key="arrival-{{ $a->id }}">
                            <td class="px-3 py-2 text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $a->tapped_at?->format('H:i:s') ?? '—' }}</td>
                            <td class="px-3 py-2 font-mono text-xs text-gray-700 dark:text-gray-300">{{ $a->node_id }}/{{ $a->seq }}</td>
                            <td class="px-3 py-2 font-mono text-xs text-gray-700 dark:text-gray-300">{{ $a->uid ?? '—' }}</td>
                            <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $a->team?->name ?? '—' }}</td>
                            <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $a->gameLocation?->name ?? '—' }}</td>
                            <td class="px-3 py-2">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium
                                    {{ $a->worked()
                                        ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200'
                                        : ($a->outcome === \App\Models\PostArrival::BAD_SIGNATURE
                                            ? 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200'
                                            : ($a->outcome === \App\Models\PostArrival::HEARTBEAT
                                                ? 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300'
                                                : 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200')) }}">
                                    {{ $labels[$a->outcome] ?? $a->outcome }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                {{-- Ternaries, not @if: a directive preceded by a word character is not a
                                     directive (CLAUDE.md), and `dBm@endif` is exactly that. --}}
                                {{ $a->rssi !== null ? $a->rssi.' dBm' : '' }}
                                {{ $a->snr !== null ? '· '.$a->snr.' dB' : '' }}
                                {{ $a->gateway ? '· gw'.$a->gateway : '' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-4 text-center text-gray-500 dark:text-gray-400">
                                Belum ada tap. Jalankan <code>php artisan eureka:tap-listen /dev/ttyUSB0</code>
                                di workstation, lalu tempelkan sebuah kartu di booth.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
