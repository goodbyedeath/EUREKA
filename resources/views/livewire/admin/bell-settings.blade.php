<div class="mb-4">
    {{-- The bell's settings, read by the countdown script in outpost-access-panel at the moment it
         rings. Rendered even when the panel is collapsed, so a change applies without a reload. --}}
    <div id="race-bell-config"
         data-tone="{{ $tone }}"
         data-volume="{{ $volume }}"
         data-repeat="{{ $repeat }}"
         @if ($customUrl) data-src="{{ $customUrl }}" @endif></div>

    <div class="flex flex-wrap items-center gap-2">
        <button type="button" wire:click="$toggle('open')"
                class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600
                       bg-white dark:bg-gray-800 text-xs text-gray-700 dark:text-gray-300
                       hover:bg-gray-50 dark:hover:bg-gray-700">
            <span>🔔</span>
            <span>Bel waktu habis:
                <span class="font-medium">{{ $tone === 'custom' ? 'suara sendiri' : \Illuminate\Support\Str::before($tones[$tone] ?? 'Bel', ' —') }}</span>,
                {{ $volume }}%{{ $repeat > 1 ? ', '.$repeat.'×' : '' }}</span>
            <span class="text-gray-400">{{ $open ? '▲' : '▼' }}</span>
        </button>

        {{-- Arms the audio and plays the current setting. A browser will not make a sound before a
             click, so this button is also how the desk unlocks the bell at the start of an event. --}}
        <button type="button" onclick="window.raceBellTest && window.raceBellTest()"
                class="px-3 py-1.5 rounded-lg border border-blue-300 dark:border-blue-700
                       bg-blue-50 dark:bg-blue-900/30 text-xs font-medium text-blue-700 dark:text-blue-300
                       hover:bg-blue-100 dark:hover:bg-blue-900/50">
            Tes bel
        </button>
    </div>

    @if (session('bell_msg'))
        <p class="mt-2 text-xs text-green-700 dark:text-green-300">{{ session('bell_msg') }}</p>
    @endif

    @if ($open)
        <div class="mt-2 p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
            <div class="grid gap-3 sm:grid-cols-3">
                <label class="block">
                    <span class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Nada</span>
                    <select wire:model.live="tone"
                            class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600
                                   bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100">
                        @foreach ($tones as $id => $label)
                            <option value="{{ $id }}">{{ $label }}</option>
                        @endforeach
                        @if ($customUrl)
                            <option value="custom">Suara sendiri (terunggah)</option>
                        @endif
                    </select>
                    @error('tone') <span class="text-xs text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Volume — {{ $volume }}%
                    </span>
                    <input type="range" min="0" max="100" step="5"
                           wire:model.live.debounce.400ms="volume" class="w-full">
                </label>

                <label class="block">
                    <span class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Ulangi</span>
                    <select wire:model.live="repeat"
                            class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600
                                   bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100">
                        @for ($i = 1; $i <= 5; $i++)
                            <option value="{{ $i }}">{{ $i }}×</option>
                        @endfor
                    </select>
                </label>
            </div>

            <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                <span class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Suara sendiri
                </span>
                <div class="flex flex-wrap items-center gap-2">
                    <input type="file" wire:model="upload" accept=".mp3,.wav,.ogg,.m4a,.aac"
                           class="text-xs text-gray-700 dark:text-gray-300">
                    <button type="button" wire:click="saveUpload" wire:loading.attr="disabled" wire:target="upload,saveUpload"
                            class="px-3 py-1.5 rounded-lg bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900
                                   text-xs font-medium disabled:opacity-50">
                        Simpan suara
                    </button>
                    @if ($customUrl)
                        <button type="button" wire:click="removeUpload"
                                class="px-3 py-1.5 rounded-lg border border-red-300 dark:border-red-700
                                       text-xs text-red-700 dark:text-red-300">
                            Hapus suara
                        </button>
                    @endif
                    <span wire:loading wire:target="upload" class="text-xs text-gray-500 dark:text-gray-400">
                        mengunggah…
                    </span>
                </div>
                @error('upload') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    MP3, WAV, OGG, M4A atau AAC, maksimal 1 MB. Nada bawaan dibuat di browser, jadi
                    berbunyi walau jaringan mati; suara unggahan perlu sekali dimuat dulu.
                </p>
            </div>
        </div>
    @endif
</div>
