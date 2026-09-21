<div class="bg-white dark:bg-gray-800 border rounded-lg p-4 sm:p-6 shadow-sm transition-all duration-300" id="question-form" x-data="{ editing: @entangle('isEditing') }">
    <h3 class="text-lg font-medium mb-4 text-gray-900 dark:text-gray-100">
        @if($isEditing)
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                <span>Edit Question</span>
                <span class="ml-2 px-2 py-1 text-xs font-medium bg-indigo-100 dark:bg-indigo-900/40 text-indigo-800 dark:text-indigo-200 rounded-full">Editing Mode</span>
            </div>
        @else
            Add New Question
        @endif
    </h3>
    
    <!-- Success/Error Messages -->
    @if (session()->has('questions_message'))
        <div class="mb-4 p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-md">
            <p class="text-sm text-green-800 dark:text-green-200">{{ session('questions_message') }}</p>
        </div>
    @endif

    @if (session()->has('questions_error'))
        <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-md">
            <p class="text-sm text-red-800 dark:text-red-200">{{ session('questions_error') }}</p>
        </div>
    @endif

    @if (session()->has('questions_warning'))
        <div class="mb-4 p-4 bg-amber-50 dark:bg-amber-900/30 border border-amber-400 rounded-md animate-pulse">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
                <p class="text-sm text-amber-800 dark:text-amber-200 font-medium">{{ session('questions_warning') }}</p>
            </div>
        </div>
    @endif
    
    <form wire:submit="addQuestion" class="space-y-6">
        <!-- Question Text -->
        <div>
            <label for="question-text" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Question <span class="text-red-500">*</span>
            </label>
            <textarea 
                id="question-text"
                wire:model="newQuestion.question" 
                rows="3"
                placeholder="Enter your question here..."
                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors duration-200"
            ></textarea>
            @error('newQuestion.question') 
                <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> 
            @enderror
        </div>

        <!-- Question Type and Points -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="question-type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Question Type <span class="text-red-500">*</span>
                </label>
                <select 
                    id="question-type"
                    wire:model.live="newQuestion.type" 
                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors duration-200"
                >
                    <option value="text">Text Answer</option>
                    <option value="multiple_choice">Multiple Choice</option>
                    <option value="true_false">True/False</option>
                    <option value="fun_game">Fun Game</option>
                    <option value="brief">Brief Feedback</option>
                    <option value="group_photo">Foto Bersama</option>
                    <option value="picture_puzzle">Tebak Gambar</option>
                </select>
                @error('newQuestion.type') 
                    <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> 
                @enderror
            </div>

            <div>
                <label for="question-points" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Points 
                    @if($newQuestion['type'] !== 'brief')
                        <span class="text-red-500">*</span>
                    @else
                        <span class="text-gray-400 dark:text-gray-500 text-xs">(Not applicable for feedback)</span>
                    @endif
                </label>
                <input 
                    type="number" 
                    id="question-points"
                    wire:model="newQuestion.points" 
                    min="1"
                    max="100"
                    step="1"
                    @if($newQuestion['type'] === 'brief') 
                        readonly value="0" 
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 cursor-not-allowed"
                    @else
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors duration-200"
                    @endif
                >
                @if($newQuestion['type'] === 'brief')
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Feedback questions don't award points</p>
                @endif
                @error('newQuestion.points') 
                    <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> 
                @enderror
            </div>
        </div>

        <!-- Multiple Choice Options -->
        @if($newQuestion['type'] === 'multiple_choice')
            <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg border">
                <div class="flex items-center justify-between mb-3">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Answer Options <span class="text-red-500">*</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400 block mt-1">Minimum 2 options, maximum 8 options</span>
                    </label>
                    @if(count($newQuestion['options']) < 8)
                        <button 
                            type="button" 
                            wire:click="addOption"
                            class="inline-flex items-center px-3 py-1 text-xs font-medium text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-200 dark:border-indigo-800 rounded-md hover:bg-indigo-100 transition-colors duration-200"
                        >
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Add Option
                        </button>
                    @endif
                </div>
                
                <div class="space-y-3">
                    @foreach($newQuestion['options'] as $index => $option)
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0 w-8 h-8 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-full flex items-center justify-center text-sm font-medium text-gray-600 dark:text-gray-400">
                                {{ chr(65 + $index) }}
                            </div>
                            <input 
                                type="text" 
                                wire:model.blur="newQuestion.options.{{ $index }}" 
                                placeholder="Enter option {{ chr(65 + $index) }}"
                                maxlength="255"
                                class="flex-1 rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors duration-200 @if($newQuestion['correct_answer'] === $option && !empty(trim($option))) border-green-300 bg-green-50 dark:bg-green-900/30 @endif"
                            >
                            @if($newQuestion['correct_answer'] === $option && !empty(trim($option)))
                                <div class="flex-shrink-0 text-green-600 dark:text-green-400" title="Correct Answer">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            @endif
                            @if(count($newQuestion['options']) > 2)
                                <button 
                                    type="button" 
                                    wire:click="removeOption({{ $index }})"
                                    class="flex-shrink-0 text-red-500 hover:text-red-700 transition-colors duration-200 p-1"
                                    title="Remove option"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            @endif
                        </div>
                    @endforeach
                </div>
                
                @error('newQuestion.options') 
                    <div class="text-red-500 text-sm mt-2 flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        {{ $message }}
                    </div>
                @enderror
                
                @php
                    $nonEmptyOptions = array_filter($newQuestion['options'], fn($opt) => !empty(trim($opt)));
                    $duplicates = array_diff_assoc($nonEmptyOptions, array_unique($nonEmptyOptions));
                @endphp
                
                @if(!empty($duplicates))
                    <div class="text-orange-600 dark:text-orange-400 text-sm mt-2 flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        Warning: Duplicate options detected. Each option should be unique.
                    </div>
                @endif
            </div>
        @endif

        <!-- Fun Game Fields -->
        @if($newQuestion['type'] === 'group_photo')
            <div class="space-y-4 rounded-lg border border-gray-200 dark:border-gray-700 p-4 bg-gray-50 dark:bg-gray-900">
                <div>
                    <label for="photo-instruction" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Instruksi untuk peserta <span class="text-red-500">*</span>
                    </label>
                    <textarea wire:model="newQuestion.description" id="photo-instruction" rows="3"
                              class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                              placeholder="mis. Foto bersama seluruh anggota tim di depan panggung, semua wajah terlihat."></textarea>
                    @error('newQuestion.description') <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="photo-frame" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Frame foto</label>
                    <input type="file" wire:model="newFrame" id="photo-frame" accept="image/png,image/jpeg"
                           class="text-sm text-gray-600 dark:text-gray-400 file:mr-2 file:py-1 file:px-3 file:rounded file:border-0 file:text-sm file:bg-blue-50 file:text-blue-700">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        PNG dengan bagian tengah transparan, supaya wajah peserta terlihat dan tepinya berisi bingkai acara.
                        Pakai ukuran potret 1080×1350 atau persegi 1080×1080 agar pas di media sosial. Maksimal 4 MB.
                    </p>
                    <div wire:loading wire:target="newFrame" class="text-xs text-blue-600 dark:text-blue-400 mt-1">Mengunggah…</div>
                    @error('newFrame') <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror

                    @if ($newFrame && in_array(strtolower((string) $newFrame->getClientOriginalExtension()), ["png", "jpg", "jpeg"]))
                        <img src="{{ $newFrame->temporaryUrl() }}" alt="" class="mt-2 h-32 rounded border border-gray-300 dark:border-gray-600 bg-white">
                    @elseif (!empty($newQuestion['frame_path']))
                        <img src="{{ Storage::url($newQuestion['frame_path']) }}" alt="" class="mt-2 h-32 rounded border border-gray-300 dark:border-gray-600 bg-white">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Frame sekarang. Pilih berkas baru untuk menggantinya.</p>
                    @else
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Tanpa frame pun bisa: peserta tetap berfoto, hanya tanpa bingkai acara.</p>
                    @endif
                </div>

                <div>
                    <label for="photo-caption" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Caption saat dibagikan</label>
                    <input type="text" wire:model="newQuestion.share_caption" id="photo-caption" maxlength="500"
                           class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                           placeholder="mis. Tim kami di #FEXDIxIFSE2026 bersama @questerra">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Teks ini yang aplikasi tawarkan saat peserta membagikan fotonya ke media sosial.</p>
                    @error('newQuestion.share_caption') <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </div>
            </div>
        @endif

        {{-- Tebak Gambar: one picture, several labelled boxes. A crossword with five across and five
             down is ten boxes; five company logos are five. --}}
        @if($newQuestion['type'] === 'picture_puzzle')
            <div class="space-y-5 rounded-lg border border-amber-200 dark:border-amber-800 p-4 bg-amber-50 dark:bg-amber-900/20">
                <div>
                    <label for="puzzle-instruction" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Instruksi untuk peserta</label>
                    <textarea wire:model="newQuestion.description" id="puzzle-instruction" rows="2"
                              class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                              placeholder="mis. Isi teka-teki silang di gambar. Setiap jawaban satu kata."></textarea>
                    @error('newQuestion.description') <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </div>

                {{-- The picture --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Gambar teka-teki <span class="text-red-500">*</span>
                            <span class="block text-xs font-normal text-gray-500 dark:text-gray-400">JPEG, PNG atau WebP, maks. 4 MB. Peserta bisa memperbesarnya di HP, jadi pakai resolusi yang cukup tajam.</span>
                        </span>
                        @if(count($uploadedImages) + count($newQuestion['images'] ?? []) < 3)
                            <label class="inline-flex items-center px-3 py-1 text-xs font-medium text-amber-700 dark:text-amber-300 bg-white dark:bg-gray-800 border border-amber-300 dark:border-amber-700 rounded-md hover:bg-amber-100 dark:hover:bg-amber-900/40 cursor-pointer">
                                <i class="fas fa-upload mr-1"></i> Unggah gambar
                                <input type="file" wire:model="newImage" accept="image/jpeg,image/png,image/jpg,image/webp" class="hidden">
                            </label>
                        @endif
                    </div>
                    <div wire:loading wire:target="newImage" class="text-xs text-amber-700 dark:text-amber-300 mb-2">Mengunggah…</div>

                    <div class="flex flex-wrap gap-3">
                        @foreach($newQuestion['images'] ?? [] as $index => $imagePath)
                            <div class="relative">
                                <img src="{{ Storage::url($imagePath) }}" alt="Gambar teka-teki {{ $index + 1 }}" class="h-32 rounded border border-gray-300 dark:border-gray-600 bg-white">
                                <button type="button" wire:click="removeExistingImage({{ $index }})" title="Hapus gambar"
                                        class="absolute top-1 right-1 bg-red-600 text-white rounded-full w-6 h-6 text-xs">&times;</button>
                            </div>
                        @endforeach
                        @foreach($uploadedImages as $index => $uploadedImage)
                            @if($uploadedImage)
                                <div class="relative">
                                    <img src="{{ $uploadedImage->temporaryUrl() }}" alt="" class="h-32 rounded border border-gray-300 dark:border-gray-600 bg-white">
                                    <button type="button" wire:click="removeUploadedImage({{ $index }})" title="Batalkan gambar ini"
                                            class="absolute top-1 right-1 bg-red-600 text-white rounded-full w-6 h-6 text-xs">&times;</button>
                                </div>
                            @endif
                        @endforeach
                    </div>
                    @error('uploadedImages') <span class="block text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</span> @enderror
                    @error('uploadedImages.*') <span class="block text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</span> @enderror
                    @error('newImage') <span class="block text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</span> @enderror
                </div>

                {{-- Shortcuts --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="rounded-md bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-3">
                        <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Teka-teki silang</div>
                        <div class="flex flex-wrap items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            mendatar <input type="number" min="0" max="15" wire:model="crosswordAcross" class="w-16 rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100">
                            menurun <input type="number" min="0" max="15" wire:model="crosswordDown" class="w-16 rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100">
                            <button type="button" wire:click="generateCrossword" class="px-3 py-1.5 rounded-md bg-amber-600 hover:bg-amber-700 text-white text-xs">Buat kolom</button>
                        </div>
                    </div>
                    <div class="rounded-md bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-3">
                        <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Daftar bernomor (logo, bendera, wajah…)</div>
                        <div class="flex flex-wrap items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <input type="number" min="1" max="{{ \App\Services\PicturePuzzle::MAX_SLOTS }}" wire:model="listCount" class="w-16 rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100"> kolom
                            <button type="button" wire:click="generateList" class="px-3 py-1.5 rounded-md bg-amber-600 hover:bg-amber-700 text-white text-xs">Buat kolom</button>
                        </div>
                    </div>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 -mt-2">
                    Tombol "Buat kolom" mengganti daftar selama belum ada jawaban yang diisi; setelah ada, kolom baru ditambahkan di bawahnya.
                </p>

                {{-- The boxes --}}
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Kolom jawaban ({{ count($newQuestion['answer_slots'] ?? []) }}) <span class="text-red-500">*</span>
                        </span>
                        <button type="button" wire:click="addSlot" class="text-xs text-amber-700 dark:text-amber-300 underline">+ Tambah kolom</button>
                    </div>
                    @error('newQuestion.answer_slots') <span class="block text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror

                    @forelse($newQuestion['answer_slots'] ?? [] as $i => $slot)
                        <div wire:key="slot-{{ $slot['key'] ?? 'new' }}-{{ $i }}"
                             class="grid grid-cols-12 gap-2 items-start rounded-md bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-2">
                            <div class="col-span-12 sm:col-span-3">
                                <input type="text" wire:model="newQuestion.answer_slots.{{ $i }}.label" maxlength="100" placeholder="Label, mis. Mendatar 1"
                                       class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 text-sm">
                                @error("newQuestion.answer_slots.$i.label") <span class="text-xs text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-span-12 sm:col-span-6">
                                <textarea wire:model="newQuestion.answer_slots.{{ $i }}.answers" rows="2" placeholder="Jawaban benar — satu per baris kalau ada lebih dari satu"
                                          class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 text-sm"></textarea>
                                @error("newQuestion.answer_slots.$i.answers") <span class="text-xs text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-span-9 sm:col-span-2">
                                <input type="number" min="1" max="50" wire:model="newQuestion.answer_slots.{{ $i }}.length" placeholder="Huruf"
                                       title="Jumlah huruf (opsional) — ditampilkan ke peserta sebagai petunjuk"
                                       class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 text-sm">
                            </div>
                            <div class="col-span-3 sm:col-span-1 text-right">
                                <button type="button" wire:click="removeSlot({{ $i }})" title="Hapus kolom"
                                        class="px-2 py-1.5 rounded-md bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 text-xs">&times;</button>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-sm text-gray-500 dark:text-gray-400 py-4 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-md">
                            Belum ada kolom. Pakai salah satu tombol "Buat kolom" di atas, atau "+ Tambah kolom".
                        </div>
                    @endforelse
                </div>

                <div class="text-xs text-gray-600 dark:text-gray-400 bg-white dark:bg-gray-800 rounded-md p-3 border border-gray-200 dark:border-gray-700 space-y-1">
                    <p><strong>Penilaian:</strong> poin soal dibagi rata per kolom dan dibulatkan ke bawah — 7 dari 10 benar pada soal 10 poin = 7 poin. Kolom kosong bernilai 0.</p>
                    <p><strong>Pencocokan:</strong> huruf besar/kecil, spasi, titik, dan tanda hubung diabaikan — "Coca-Cola", "coca cola", dan "COCACOLA" sama.</p>
                    <p><strong>Umpan balik:</strong> peserta baru tahu kolom mana yang benar setelah sesi disubmit, dan jawaban benarnya tidak pernah ditampilkan — tim berikutnya di pos ini mendapat teka-teki yang sama.</p>
                </div>
            </div>
        @endif

        @if($newQuestion['type'] === 'fun_game')
            <div class="bg-purple-50 dark:bg-purple-900/30 p-4 rounded-lg border border-purple-200 dark:border-purple-800">
                <h4 class="text-sm font-medium text-purple-900 mb-4 flex items-center">
                    <i class="fas fa-gamepad mr-2"></i>
                    Fun Game Configuration
                </h4>
                
                <!-- Game Name -->
                <div class="mb-4">
                    <label for="game-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Game Name <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="game-name"
                        wire:model="newQuestion.game_name" 
                        placeholder="Enter the name of the fun game..."
                        maxlength="200"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors duration-200"
                    >
                    @error('newQuestion.game_name') 
                        <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> 
                    @enderror
                </div>

                <!-- Game Description -->
                <div class="mb-4">
                    <label for="game-description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Game Instructions/Description <span class="text-red-500">*</span>
                    </label>
                    <textarea 
                        id="game-description"
                        wire:model="newQuestion.description" 
                        rows="4"
                        placeholder="Enter detailed instructions for the game..."
                        maxlength="2000"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors duration-200"
                    ></textarea>
                    @error('newQuestion.description') 
                        <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> 
                    @enderror
                </div>

                <!-- Game Images -->
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Game Images
                            <span class="text-xs text-gray-500 dark:text-gray-400 block mt-1">Optional - Upload up to 10 images (JPEG, PNG, WebP, max 2MB each)</span>
                        </label>
                        @if(count($uploadedImages) + count($newQuestion['images'] ?? []) < 10)
                            <label class="inline-flex items-center px-3 py-1 text-xs font-medium text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/30 border border-purple-200 dark:border-purple-800 rounded-md hover:bg-purple-100 transition-colors duration-200 cursor-pointer">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Upload Image
                                <input type="file" wire:model="newImage" accept="image/jpeg,image/png,image/jpg,image/webp" class="hidden">
                            </label>
                        @endif
                    </div>
                    
                    <!-- Display existing images (for editing mode) -->
                    @if(!empty($newQuestion['images']))
                        <div class="mb-4">
                            <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Current Images:</h5>
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                                @foreach($newQuestion['images'] as $index => $imagePath)
                                    <div class="relative group">
                                        <div class="aspect-square bg-gray-100 dark:bg-gray-700 rounded-lg overflow-hidden">
                                            <img src="{{ asset('storage/' . $imagePath) }}" alt="Game image {{ $index + 1 }}" class="w-full h-full object-cover">
                                        </div>
                                        <button 
                                            type="button" 
                                            wire:click="removeExistingImage({{ $index }})"
                                            class="absolute top-1 right-1 bg-red-500 text-white rounded-full p-1 hover:bg-red-600 transition-colors duration-200 opacity-0 group-hover:opacity-100"
                                            title="Remove image"
                                        >
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    
                    <!-- Display newly uploaded images -->
                    @if(count($uploadedImages) > 0)
                        <div class="mb-4">
                            <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ !empty($newQuestion['images']) ? 'New Images:' : 'Uploaded Images:' }}</h5>
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                                @foreach($uploadedImages as $index => $uploadedImage)
                                    @if($uploadedImage)
                                        <div class="relative group">
                                            {{-- Filename, not a thumbnail: the signed preview URL is
                                                 stripped of its query string by this host's image
                                                 optimisation and 401s. The upload still works. --}}
                                            <div class="aspect-square bg-gray-100 dark:bg-gray-700 rounded-lg flex flex-col items-center justify-center p-2 text-center">
                                                <i class="fas fa-image text-gray-400 dark:text-gray-500 text-2xl"></i>
                                                <p class="mt-2 text-xs text-gray-600 dark:text-gray-400 break-all line-clamp-3">{{ $uploadedImage->getClientOriginalName() }}</p>
                                            </div>
                                            <button 
                                                type="button" 
                                                wire:click="removeUploadedImage({{ $index }})"
                                                class="absolute top-1 right-1 bg-red-500 text-white rounded-full p-1 hover:bg-red-600 transition-colors duration-200 opacity-0 group-hover:opacity-100"
                                                title="Remove image"
                                            >
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                    
                    @if(empty($newQuestion['images']) && count($uploadedImages) === 0)
                        <div class="text-center py-8 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg">
                            <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No images uploaded yet</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500">Click "Upload Image" to add visual content for your game</p>
                        </div>
                    @endif
                    
                    @error('uploadedImages') 
                        <div class="text-red-500 text-sm mt-2 flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                            {{ $message }}
                        </div>
                    @enderror
                    
                    @error('uploadedImages.*') 
                        <div class="text-red-500 text-sm mt-2 flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                            {{ $message }}
                        </div>
                    @enderror
                    
                    @error('newImage') 
                        <div class="text-red-500 text-sm mt-2 flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-md">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-blue-400 mt-0.5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                        <div class="text-sm text-blue-800 dark:text-blue-200">
                            <strong>Note:</strong> Fun games don't require correct answers. Users complete the activity and are assessed manually by administrators using deposit/penalty scoring.
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Brief Feedback Configuration -->
        @if($newQuestion['type'] === 'brief')
            <div class="bg-blue-50 dark:bg-blue-900/30 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
                <h4 class="text-sm font-medium text-blue-900 mb-4 flex items-center">
                    <i class="fas fa-comments mr-2"></i>
                    Feedback Question Configuration
                </h4>
                
                <!-- Description/Guidance -->
                <div class="mb-4">
                    <label for="brief-description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Additional Guidance <span class="text-gray-500 dark:text-gray-400 text-xs">(Optional)</span>
                    </label>
                    <textarea 
                        id="brief-description"
                        wire:model="newQuestion.description" 
                        rows="3"
                        placeholder="Enter additional guidance or context for teams filling out this feedback..."
                        maxlength="1000"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors duration-200"
                    ></textarea>
                    @error('newQuestion.description') 
                        <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> 
                    @enderror
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ strlen($newQuestion['description'] ?? '') }}/1000 characters</p>
                </div>

                <div class="p-3 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-md">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-blue-400 mt-0.5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                        <div class="text-sm text-blue-800 dark:text-blue-200">
                            <strong>About Feedback Questions:</strong> These questions are designed to collect user experience feedback and aren't scored. Users can provide text responses that help you understand their experience better.
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Correct Answer -->
        @if(! in_array($newQuestion['type'], ['fun_game', 'brief', 'group_photo', 'picture_puzzle'], true))
            <div>
                <label for="correct-answer" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Correct Answer <span class="text-red-500">*</span>
                </label>
            
            @if($newQuestion['type'] === 'true_false')
                <select 
                    id="correct-answer"
                    wire:model="newQuestion.correct_answer" 
                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors duration-200"
                >
                    <option value="">Select the correct answer...</option>
                    <option value="true">True</option>
                    <option value="false">False</option>
                </select>
            @elseif($newQuestion['type'] === 'multiple_choice')
                <select 
                    id="correct-answer"
                    wire:model="newQuestion.correct_answer" 
                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors duration-200"
                >
                    <option value="">Select the correct option...</option>
                    @foreach($newQuestion['options'] as $option)
                        @if(!empty(trim($option)))
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endif
                    @endforeach
                </select>
                @php
                    $nonEmptyOptions = array_filter($newQuestion['options'], fn($opt) => !empty(trim($opt)));
                @endphp
                @if(count($nonEmptyOptions) < 2)
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Please add at least two non-empty options first.</p>
                @endif
            @else
                <input 
                    type="text" 
                    id="correct-answer"
                    wire:model="newQuestion.correct_answer" 
                    placeholder="Enter the correct answer..."
                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors duration-200"
                >
            @endif
            
                @error('newQuestion.correct_answer') 
                    <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> 
                @enderror
            </div>
        @endif

        <!-- Form Actions -->
        <div class="flex justify-between items-center pt-4 border-t border-gray-200 dark:border-gray-700">
            <div class="flex space-x-3">
                @if($isEditing)
                    <button 
                        type="button" 
                        wire:click="cancelEdit"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200"
                    >
                        Cancel Edit
                    </button>
                @endif
                <button 
                    type="button" 
                    wire:click="resetNewQuestion"
                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200"
                >
                    Reset Form
                </button>
            </div>
            
            <button 
                type="submit" 
                class="px-6 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove>
                    @if($isEditing)
                        Update Question
                    @else
                        Add Question
                    @endif
                </span>
                <span wire:loading class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    @if($isEditing)
                        Updating...
                    @else
                        Adding...
                    @endif
                </span>
            </button>
        </div>
    </form>

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('question-loaded-for-edit', () => {
                const formElement = document.getElementById('question-form');
                if (formElement) {
                    // Scroll to form smoothly
                    formElement.scrollIntoView({ behavior: 'smooth', block: 'start' });

                    // Add visual highlight effect
                    formElement.classList.add('ring-4', 'ring-indigo-300', 'ring-opacity-50');

                    // Remove highlight after 2 seconds
                    setTimeout(() => {
                        formElement.classList.remove('ring-4', 'ring-indigo-300', 'ring-opacity-50');
                    }, 2000);
                }
            });
        });
    </script>
</div>