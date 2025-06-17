{{-- Statistics Cards Section --}}
@if($team->members->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mt-8">
        {{-- Total Members Card --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-all duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Total Anggota</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $team->members->count() }}</p>
                    <p class="text-xs text-gray-500 mt-1">
                        <i class="fas fa-arrow-up text-green-500 mr-1"></i>
                        +{{ $team->members->where('created_at', '>=', now()->subMonth())->count() }} bulan ini
                    </p>
                </div>
                <div class="bg-blue-100 p-3 rounded-full">
                    <i class="fas fa-users text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>

        {{-- Leaders Card --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-all duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Ketua Tim</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $team->members->where('is_leader', true)->count() }}</p>
                    <p class="text-xs text-gray-500 mt-1">
                        @if($team->members->where('is_leader', true)->count() > 0)
                            <i class="fas fa-check text-green-500 mr-1"></i>
                            Tersedia
                        @else
                            <i class="fas fa-exclamation text-orange-500 mr-1"></i>
                            Belum ada
                        @endif
                    </p>
                </div>
                <div class="bg-yellow-100 p-3 rounded-full">
                    <i class="fas fa-crown text-yellow-600 text-xl"></i>
                </div>
            </div>
        </div>

        {{-- Unique Positions Card --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-all duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Posisi Unik</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $this->positions->count() }}</p>
                    <p class="text-xs text-gray-500 mt-1">
                        <i class="fas fa-briefcase text-purple-500 mr-1"></i>
                        Berbagai peran
                    </p>
                </div>
                <div class="bg-purple-100 p-3 rounded-full">
                    <i class="fas fa-briefcase text-purple-600 text-xl"></i>
                </div>
            </div>
        </div>

        {{-- Recent Joins Card --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-all duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Bergabung Minggu Ini</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $team->members->where('created_at', '>=', now()->subWeek())->count() }}</p>
                    <p class="text-xs text-gray-500 mt-1">
                        @if($team->members->where('created_at', '>=', now()->subWeek())->count() > 0)
                            <i class="fas fa-trending-up text-green-500 mr-1"></i>
                            Aktif tumbuh
                        @else
                            <i class="fas fa-minus text-gray-500 mr-1"></i>
                            Tidak ada
                        @endif
                    </p>
                </div>
                <div class="bg-green-100 p-3 rounded-full">
                    <i class="fas fa-user-plus text-green-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Detailed Statistics --}}
    @if($this->positions->count() > 0)
        <div class="mt-6 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-chart-bar text-blue-600 mr-2"></i>
                Distribusi Posisi
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($this->positions as $position)
                    @php
                        $count = $team->members->where('position', $position)->count();
                        $percentage = ($count / $team->members->count()) * 100;
                    @endphp
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex-1">
                            <p class="font-medium text-gray-900">{{ $position }}</p>
                            <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                                <div class="bg-blue-600 h-2 rounded-full transition-all duration-500" 
                                     style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                        <div class="ml-4 text-right">
                            <p class="text-lg font-bold text-gray-900">{{ $count }}</p>
                            <p class="text-xs text-gray-500">{{ number_format($percentage, 1) }}%</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
@endif