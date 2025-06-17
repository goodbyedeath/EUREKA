{{-- Empty State Section --}}
<div class="text-center py-12 bg-white rounded-xl shadow-sm border border-gray-200">
    <div class="max-w-sm mx-auto">
        {{-- Empty State Icon --}}
        <div class="mb-6">
            <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center">
                <i class="fas fa-users text-gray-400 text-3xl"></i>
            </div>
        </div>
        
        {{-- Empty State Content --}}
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-2">
                @if($search || $filterPosition)
                    Tidak Ada Anggota yang Ditemukan
                @else
                    Belum Ada Anggota Tim
                @endif
            </h3>
            <p class="text-gray-600 leading-relaxed">
                @if($search || $filterPosition)
                    Tidak ada anggota yang sesuai dengan kriteria pencarian atau filter yang Anda pilih. Coba ubah kata kunci pencarian atau reset filter.
                @else
                    Tim ini belum memiliki anggota. Mulai dengan menambahkan anggota pertama untuk membangun tim yang solid.
                @endif
            </p>
        </div>
        
        {{-- Action Buttons --}}
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            @if($search || $filterPosition)
                <button wire:click="clearFilters" 
                        class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors">
                    <i class="fas fa-times mr-2"></i>
                    Reset Filter
                </button>
            @else
                <button onclick="window.location.href='{{ route('teams.members.create', $team) }}'" 
                        class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors font-medium">
                    <i class="fas fa-plus mr-2"></i>
                    Tambah Anggota Pertama
                </button>
            @endif
        </div>
        
        @if(!$search && !$filterPosition)
            {{-- Additional Help Text --}}
            <div class="mt-8 p-4 bg-blue-50 rounded-lg border border-blue-200">
                <div class="flex items-start space-x-3">
                    <i class="fas fa-lightbulb text-blue-600 mt-1"></i>
                    <div class="text-left">
                        <h4 class="font-medium text-blue-900 mb-1">Tips Membangun Tim</h4>
                        <p class="text-sm text-blue-700">
                            Mulai dengan menambahkan anggota inti, tentukan peran masing-masing, dan pilih ketua tim untuk memimpin koordinasi.
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>