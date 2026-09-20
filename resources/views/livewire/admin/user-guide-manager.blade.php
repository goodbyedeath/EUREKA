<div class="p-4 sm:p-6 space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-1">Panduan Pengguna</h1>
        <p class="text-gray-600 dark:text-gray-400 max-w-3xl">
            Panduan cara memakai aplikasi, dalam bahasa sehari-hari. Halaman ini juga yang tampil di menu
            Panduan pada aplikasi peserta, jadi perbaikan kalimat di sini langsung sampai ke HP mereka.
            Tulis satu kalimat per baris; aplikasi menampilkannya sebagai daftar.
        </p>
    </div>

    @if (session('guide_msg'))
        <div class="rounded-md bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 p-3 text-sm text-green-800 dark:text-green-200">
            {{ session('guide_msg') }}
        </div>
    @endif

    <section class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                {{ $editingId ? 'Ubah bagian' : 'Tambah bagian' }}
            </h2>
            @if ($editingId)
                <button type="button" wire:click="resetForm" class="text-sm text-gray-600 dark:text-gray-400 underline">Batal</button>
            @else
                <button type="button" wire:click="addSection" class="text-sm text-blue-600 dark:text-blue-400 underline">Kosongkan form</button>
            @endif
        </div>

        <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <label class="text-sm md:col-span-1">
                <span class="block text-gray-700 dark:text-gray-300 mb-1">Ikon</span>
                <input type="text" wire:model="icon" maxlength="8" placeholder="🔑"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100">
                @error('icon') <span class="block text-xs text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
            </label>

            <label class="text-sm md:col-span-4">
                <span class="block text-gray-700 dark:text-gray-300 mb-1">Judul</span>
                <input type="text" wire:model="title" maxlength="120" placeholder="mis. Masuk ke aplikasi"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100">
                @error('title') <span class="block text-xs text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
            </label>

            <label class="text-sm md:col-span-1">
                <span class="block text-gray-700 dark:text-gray-300 mb-1">Urutan</span>
                <input type="number" wire:model="sort_order" min="0" max="9999"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100">
                @error('sort_order') <span class="block text-xs text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
            </label>

            <label class="text-sm md:col-span-6">
                <span class="block text-gray-700 dark:text-gray-300 mb-1">Isi — satu kalimat per baris</span>
                <textarea wire:model="body" rows="6" maxlength="4000"
                          class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100"
                          placeholder="Buka aplikasi, lalu tekan &quot;Scan kartu login&quot;.&#10;Arahkan kamera ke QR pada kartu tim Anda."></textarea>
                @error('body') <span class="block text-xs text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
            </label>

            <label class="text-sm md:col-span-6 flex items-center gap-2">
                <input type="checkbox" wire:model="is_active" class="rounded border-gray-300 dark:border-gray-600">
                <span class="text-gray-700 dark:text-gray-300">Tampilkan bagian ini</span>
            </label>
        </div>

        <button type="button" wire:click="save"
                class="mt-4 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">
            {{ $editingId ? 'Simpan perubahan' : 'Tambahkan' }}
        </button>
    </section>

    <section class="space-y-3">
        @forelse ($sections as $section)
            <article class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 {{ $section->is_active ? '' : 'opacity-60' }}">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        <span class="mr-1">{{ $section->icon ?: '•' }}</span>{{ $section->title }}
                        @unless ($section->is_active)
                            <span class="ml-1 px-2 py-0.5 text-xs rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">disembunyikan</span>
                        @endunless
                        <span class="ml-1 text-xs text-gray-400 dark:text-gray-500">urutan {{ $section->sort_order }}</span>
                    </h3>
                    <div class="flex gap-2 text-xs">
                        <button type="button" wire:click="edit({{ $section->id }})"
                                class="px-3 py-1.5 rounded-md bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300">Ubah</button>
                        <button type="button" wire:click="toggle({{ $section->id }})"
                                class="px-3 py-1.5 rounded-md bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200">
                            {{ $section->is_active ? 'Sembunyikan' : 'Tampilkan' }}
                        </button>
                        <button type="button" wire:click="delete({{ $section->id }})"
                                wire:confirm="Hapus bagian panduan ini?"
                                class="px-3 py-1.5 rounded-md bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300">Hapus</button>
                    </div>
                </div>

                <ul class="mt-2 space-y-1 text-sm text-gray-700 dark:text-gray-300">
                    @foreach ($section->lines() as $line)
                        <li class="flex gap-2"><span class="text-gray-400 dark:text-gray-500">•</span><span>{{ $line }}</span></li>
                    @endforeach
                </ul>
            </article>
        @empty
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-dashed border-gray-300 dark:border-gray-700 p-8 text-center text-gray-600 dark:text-gray-400">
                Panduan masih kosong. Tambahkan bagian pertama di atas.
            </div>
        @endforelse
    </section>
</div>
