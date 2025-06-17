{{-- resources/views/livewire/team-members/member-card.blade.php --}}
<div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-200 border border-gray-200">
    <div class="p-6">
        {{-- Avatar and Leader Badge --}}
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center space-x-3">
                <div class="inline-flex items-center justify-center w-12 h-12 bg-blue-100 rounded-full">
                    <span class="text-lg font-bold text-blue-600">
                        {{ substr($member->name, 0, 2) }}
                    </span>
                </div>
                @if($member->is_leader)
                    <span class="inline-block px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full">
                        Leader
                    </span>
                @endif
            </div>
        </div>

        {{-- Member Info --}}
        <div class="mb-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-1">{{ $member->name }}</h3>
            <p class="text-sm text-gray-600 mb-2">{{ $member->position }}</p>
            <p class="text-sm text-gray-500">{{ $member->email }}</p>
            @if($member->phone)
                <p class="text-sm text-gray-500">{{ $member->phone }}</p>
            @endif
        </div>

        {{-- Action Buttons --}}
        <div class="flex space-x-2">
            <button wire:click="$parent.openDetailModal({{ $member->id }})" 
                    class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-md text-sm font-medium transition duration-200">
                View
            </button>
            
            @if($isLeader)
                <button wire:click="$parent.openEditModal({{ $member->id }})" 
                        class="flex-1 bg-blue-100 hover:bg-blue-200 text-blue-700 px-3 py-2 rounded-md text-sm font-medium transition duration-200">
                    Edit
                </button>
                
                @if(!$member->is_leader)
                    <button wire:click="$parent.makeLeader({{ $member->id }})" 
                            class="flex-1 bg-green-100 hover:bg-green-200 text-green-700 px-3 py-2 rounded-md text-sm font-medium transition duration-200"
                            onclick="return confirm('Make this member the team leader?')">
                        Promote
                    </button>
                @endif
                
                <button wire:click="$parent.openDeleteModal({{ $member->id }})" 
                        class="bg-red-100 hover:bg-red-200 text-red-700 px-3 py-2 rounded-md text-sm font-medium transition duration-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </button>
            @endif
        </div>
    </div>
</div>