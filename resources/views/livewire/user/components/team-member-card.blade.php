{{-- resources/views/livewire/user/components/team-member-card.blade.php --}}

@if(!$member || !$member->exists)
    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
        <div class="flex items-center">
            <svg class="w-5 h-5 text-red-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <p class="text-red-600 text-sm font-medium">Member data not available</p>
        </div>
    </div>
@else
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-all duration-200 {{ $isLoading ? 'opacity-50 pointer-events-none' : '' }}"
         x-data="{ showActions: false }"
         @mouseenter="showActions = true"
         @mouseleave="showActions = false"
         wire:key="member-card-{{ $member->id }}-{{ $member->updated_at?->timestamp ?? time() }}">

        {{-- Loading Overlay --}}
        @if($isLoading)
            <div class="absolute inset-0 bg-white bg-opacity-75 rounded-lg flex items-center justify-center z-10">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-primary-600"></div>
            </div>
        @endif

        <div class="p-4 relative">
            {{-- Header --}}
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center space-x-3">
                    <div class="relative">
                        <div class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center">
                            <span class="text-primary-600 font-semibold text-lg">
                                {{ strtoupper(substr($member->name, 0, 1)) }}
                            </span>
                        </div>
                        {{-- Leader Badge --}}
                        @if($member->is_leader)
                            <div class="absolute -top-1 -right-1 w-4 h-4 bg-yellow-400 rounded-full flex items-center justify-center">
                                <svg class="w-2 h-2 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            </div>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="font-medium text-gray-900 truncate">{{ $member->name }}</h3>
                        <p class="text-sm text-gray-500 truncate">{{ $member->position ?: 'Anggota Tim' }}</p>
                    </div>
                </div>
                
                {{-- Action Buttons --}}
                <div class="flex items-center space-x-1" 
                     x-show="showActions" 
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 transform scale-95"
                     x-transition:enter-end="opacity-100 transform scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 transform scale-100"
                     x-transition:leave-end="opacity-0 transform scale-95">
                    
                    @if($canEdit)
                        <button wire:click="editMember" 
                                class="p-1.5 text-gray-600 hover:text-primary-600 hover:bg-primary-50 rounded-full transition-colors duration-200"
                                title="Edit Anggota"
                                wire:loading.attr="disabled">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>
                    @endif
                    
                    @if($canDelete)
                        <button wire:click="confirmDeleteMember"
                                class="p-1.5 text-gray-600 hover:text-red-600 hover:bg-red-50 rounded-full transition-colors duration-200"
                                title="Hapus Anggota"
                                wire:loading.attr="disabled">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    @endif
                    
                    {{-- View Details Button --}}
                    <button wire:click="showMemberDetail"
                            class="p-1.5 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-full transition-colors duration-200"
                            title="Lihat Detail"
                            wire:loading.attr="disabled">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Contact Info --}}
            <div class="space-y-2 text-sm">
                <div class="flex items-center text-gray-600">
                    <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    <span class="truncate">{{ $member->email ?: 'Email tidak tersedia' }}</span>
                </div>
                @if($member->phone)
                    <div class="flex items-center text-gray-600">
                        <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        <span class="truncate">{{ $member->phone }}</span>
                    </div>
                @endif
            </div>

            {{-- Status Badge and Actions --}}
            <div class="mt-3 flex items-center justify-between">
                <div class="flex items-center space-x-2 flex-wrap gap-1">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $member->is_leader ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800' }}">
                        {{ $member->is_leader ? 'Ketua Tim' : 'Anggota Tim' }}
                    </span>
                    
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $member->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $member->is_active ? 'Aktif' : 'Tidak Aktif' }}
                    </span>
                </div>
                
                @if($canMakeLeader && !$member->is_leader)
                    <button wire:click="makeLeader"
                            class="text-xs text-primary-600 hover:text-primary-700 font-medium transition-colors duration-200 flex-shrink-0"
                            title="Jadikan Ketua Tim"
                            wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="makeLeader">Jadikan Ketua</span>
                        <span wire:loading wire:target="makeLeader" class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-1 h-3 w-3 text-primary-600" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Loading...
                        </span>
                    </button>
                @endif
            </div>

            <!-- Member Join Date -->
            <div class="mt-2 text-xs text-gray-500">
                Bergabung: {{ $member->created_at->format('d M Y') }}
            </div>
        </div>
    </div>
@endif