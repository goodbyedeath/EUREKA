{{-- Header Section --}}
<div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-6 border border-blue-100">
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-xl font-bold text-gray-900 flex items-center">
                <i class="fas fa-users text-blue-600 mr-3"></i>
                Daftar Anggota Tim
            </h3>
            <p class="text-gray-600 mt-1">
                Total {{ $team->members->count() }} anggota dalam tim
            </p>
        </div>
        <div class="flex space-x-2">
            <button wire:click="exportMembers" 
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors flex items-center">
                <i class="fas fa-download mr-2"></i>
                Export
            </button>
        </div>
    </div>
</div>