<div class="container mx-auto px-4 py-6">
    <div class="max-w-4xl mx-auto p-6 bg-white dark:bg-gray-800 rounded-lg shadow-lg">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">{{ __('common.team_registration') }}</h2>
    
    {{-- Information Banner --}}
    <div class="mb-6 p-4 bg-blue-100 border border-blue-300 text-blue-800 rounded-lg">
        <div class="flex items-center">
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            <div>
                <h3 class="font-medium mb-1">Pendaftaran Tim Baru</h3>
                <p class="text-sm">Formulir ini hanya untuk pengguna yang belum memiliki tim. Silakan masukkan data lengkap semua anggota tim Anda.</p>
            </div>
        </div>
    </div>
    
    {{-- Success Message --}}
    @if($showSuccessMessage)
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded" 
             x-data="{ show: true }" 
             x-show="show" 
             x-transition
             x-init="setTimeout(() => show = false, 3000)">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium">{{ __('common.team_registered_successfully') }}</p>
                </div>
            </div>
        </div>
    @endif

    <form wire:submit.prevent="save">
        {{-- Informasi Tim --}}
        <div class="mb-8 p-4 bg-gray-50 rounded-lg">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">{{ __('common.team_information') }}</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="teamName" class="block text-sm font-medium text-gray-700 mb-1">
                        Nama Tim <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="teamName" 
                           wire:model="teamName"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Masukkan nama tim">
                    @error('teamName') 
                        <span class="text-red-500 text-sm mt-1">{{ $message }}</span> 
                    @enderror
                </div>

                <div>
                    <label for="department" class="block text-sm font-medium text-gray-700 mb-1">
                        Departemen
                    </label>
                    <input type="text" 
                           id="department" 
                           wire:model="department"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Masukkan departemen">
                    @error('department') 
                        <span class="text-red-500 text-sm mt-1">{{ $message }}</span> 
                    @enderror
                </div>
            </div>

            <div class="mt-4">
                <label for="teamDescription" class="block text-sm font-medium text-gray-700 mb-1">
                    Deskripsi Tim
                </label>
                <textarea id="teamDescription" 
                          wire:model="teamDescription"
                          rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                          placeholder="Masukkan deskripsi tim (opsional)"></textarea>
                @error('teamDescription') 
                    <span class="text-red-500 text-sm mt-1">{{ $message }}</span> 
                @enderror
            </div>
        </div>

        {{-- Anggota Tim --}}
        <div class="mb-8">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-700">
                    Anggota Tim ({{ count($members) }}/10)
                </h3>
                <button type="button" 
                        wire:click="addMember"
                        @if(count($members) >= 10) disabled @endif
                        class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed">
                    + Tambah Anggota
                </button>
            </div>

            {{-- Error Messages --}}
            @error('leader')
                <div class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
                    {{ $message }}
                </div>
            @enderror

            @error('email_unique')
                <div class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
                    {{ $message }}
                </div>
            @enderror

            @error('save')
                <div class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
                    {{ $message }}
                </div>
            @enderror

            <div class="space-y-4">
                @foreach($members as $index => $member)
                    <div class="p-4 border border-gray-200 rounded-lg {{ $member['is_leader'] ? 'bg-yellow-50 border-yellow-300' : 'bg-white' }}">
                        <div class="flex justify-between items-start mb-3">
                            <h4 class="font-medium text-gray-800">
                                Anggota #{{ $index + 1 }}
                                @if($member['is_leader'])
                                    <span class="ml-2 px-2 py-1 bg-yellow-200 text-yellow-800 text-xs rounded-full">
                                        Ketua Tim
                                    </span>
                                @endif
                            </h4>
                            <div class="flex space-x-2">
                                <button type="button" 
                                        wire:click="setLeader({{ $index }})"
                                        class="px-3 py-1 text-xs {{ $member['is_leader'] ? 'bg-yellow-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }} rounded-md focus:outline-none focus:ring-2 focus:ring-yellow-500">
                                    {{ $member['is_leader'] ? 'Ketua' : 'Jadikan Ketua' }}
                                </button>
                                @if(count($members) > 1)
                                    <button type="button" 
                                            wire:click="removeMember({{ $index }})"
                                            class="px-3 py-1 text-xs bg-red-500 text-white rounded-md hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500"
                                            title="Hapus anggota">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Nama Lengkap <span class="text-red-500">*</span>
                                </label>
                                <input type="text" 
                                       wire:model="members.{{ $index }}.name"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="Masukkan nama lengkap">
                                @error("members.{$index}.name") 
                                    <span class="text-red-500 text-sm mt-1">{{ $message }}</span> 
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Email <span class="text-red-500">*</span>
                                </label>
                                <input type="email" 
                                       wire:model="members.{{ $index }}.email"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="Masukkan email">
                                @error("members.{$index}.email") 
                                    <span class="text-red-500 text-sm mt-1">{{ $message }}</span> 
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Nomor Telepon
                                </label>
                                <input type="tel" 
                                       wire:model="members.{{ $index }}.phone"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="Masukkan nomor telepon">
                                @error("members.{$index}.phone") 
                                    <span class="text-red-500 text-sm mt-1">{{ $message }}</span> 
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Posisi/Jabatan
                                </label>
                                <input type="text" 
                                       wire:model="members.{{ $index }}.position"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="Masukkan posisi/jabatan">
                                @error("members.{$index}.position") 
                                    <span class="text-red-500 text-sm mt-1">{{ $message }}</span> 
                                @enderror
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Info minimum member requirement --}}
            @if(count($members) < 1)
                <div class="mt-4 p-3 bg-blue-100 border border-blue-400 text-blue-700 rounded">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm">Tim harus memiliki minimal 1 anggota dan maksimal 10 anggota.</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Submit Button --}}
        <div class="flex justify-end">
            <button type="submit" 
                    wire:loading.attr="disabled"
                    class="px-6 py-3 bg-green-500 text-white font-medium rounded-md hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 disabled:opacity-50">
                <span wire:loading.remove>Daftarkan Tim</span>
                <span wire:loading>Menyimpan...</span>
            </button>
        </div>
    </form>
    </div>
</div>

@script
<script>
    $wire.on('success-message', () => {
        setTimeout(() => {
            $wire.set('showSuccessMessage', false);
        }, 3000);
    });

    $wire.on('redirect-to-dashboard', () => {
        setTimeout(() => {
            window.location.href = '{{ route("user.dashboard") }}';
        }, 2000); // 2 second delay to show success message
    });

    $wire.on('redirect-to-dashboard-immediate', () => {
        window.location.href = '{{ route("user.dashboard") }}';
    });
</script>
@endscript