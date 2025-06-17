{{-- resources/views/livewire/user/partials/team-member-filters.blade.php --}}

<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
    {{-- Filter Header --}}
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center">
            <i class="fas fa-filter text-gray-400 mr-2"></i>
            <h3 class="text-lg font-medium text-gray-900">Filter & Pencarian</h3>
            @if($this->hasActiveFilters())
                <span class="ml-2 px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                    {{ $this->getFilteredMemberCount() }} hasil
                </span>
            @endif
        </div>
        
        @if($this->hasActiveFilters())
            <button wire:click="clearAllFilters"
                    class="text-sm text-gray-500 hover:text-gray-700 underline">
                <i class="fas fa-times mr-1"></i>
                Reset Filter
            </button>
        @endif
    </div>

    {{-- Filter Row --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Search Input --}}
        <div class="relative">
            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">
                Cari Anggota
            </label>
            <div class="relative">
                <input wire:model.live.debounce.300ms="search"
                       id="search"
                       type="text"
                       class="w-full pl-10 pr-10 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                       placeholder="Nama, email, posisi...">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
                @if(!empty($search))
                    <button wire:click="clearSearch"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                @endif
            </div>
        </div>

        {{-- Position Filter --}}
        <div>
            <label for="filter_position" class="block text-sm font-medium text-gray-700 mb-1">
                Filter Posisi
            </label>
            <select wire:model.live="filterPosition"
                    id="filter_position"
                    class="w-full py-2 px-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">Semua Posisi</option>
                @foreach($this->positions as $position)
                    <option value="{{ $position }}">{{ $position }}</option>
                @endforeach
                <option value="no_position">Tanpa Posisi</option>
            </select>
        </div>

        {{-- Sort Options --}}
        <div>
            <label for="sort_by" class="block text-sm font-medium text-gray-700 mb-1">
                Urutkan Berdasarkan
            </label>
            <select wire:model.live="sortBy"
                    id="sort_by"
                    class="w-full py-2 px-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                @foreach($this->getSortOptions() as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

       

    {{-- Advanced Filters (Collapsible) --}}
    <div x-data="{ showAdvanced: false }" class="mt-4">
        <button @click="showAdvanced = !showAdvanced"
                class="flex items-center text-sm text-gray-600 hover:text-gray-800">
            <i class="fas fa-chevron-right mr-2 transition-transform duration-200"
               :class="{ 'rotate-90': showAdvanced }"></i>
            Filter Lanjutan
        </button>
        
        <div x-show="showAdvanced"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-collapse
             class="mt-3 pt-3 border-t border-gray-200">
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                {{-- Date Range Filter --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Tanggal Bergabung
                    </label>
                    <div class="flex space-x-2">
                        <input wire:model.live="dateFrom"
                               type="date"
                               class="flex-1 py-2 px-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                               placeholder="Dari">
                        <input wire:model.live="dateTo"
                               type="date"
                               class="flex-1 py-2 px-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                               placeholder="Sampai">
                    </div>
                </div>

                {{-- Contact Info Filter --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Info Kontak
                    </label>
                    <div class="flex flex-col space-y-2">
                        <label class="flex items-center">
                            <input wire:model.live="hasEmail"
                                   type="checkbox"
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">Memiliki Email</span>
                        </label>
                        <label class="flex items-center">
                            <input wire:model.live="hasPhone"
                                   type="checkbox"
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">Memiliki Telepon</span>
                        </label>
                    </div>
                </div>

                {{-- Status Filter --}}
                <div>
                    <label for="status_filter" class="block text-sm font-medium text-gray-700 mb-1">
                        Status Anggota
                    </label>
                    <select wire:model.live="statusFilter"
                            id="status_filter"
                            class="w-full py-2 px-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                        <option value="">Semua Status</option>
                        <option value="active">Aktif</option>
                        <option value="inactive">Tidak Aktif</option>
                        <option value="pending">Menunggu</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Summary --}}
    @if($this->hasActiveFilters())
        <div class="mt-4 p-3 bg-blue-50 rounded-lg border border-blue-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                    <span class="text-sm text-blue-700">
                        Filter aktif: {{ $this->getFilterSummary() }}
                    </span>
                </div>
                <div class="flex items-center space-x-2">
                    <button wire:click="exportFilteredMembers"
                            class="px-3 py-1 text-xs bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-download mr-1"></i>
                        Ekspor
                    </button>
                    <button wire:click="clearAllFilters"
                            class="px-3 py-1 text-xs bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                        <i class="fas fa-times mr-1"></i>
                        Reset
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>