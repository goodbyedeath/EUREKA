<div class="p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Game Assessment</h1>
        <p class="text-gray-600">Assess fun game performances and assign deposit scores</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left Panel: Quiz Attempts List --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="p-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Quiz Attempts with Games</h2>
                    
                    {{-- Search --}}
                    <div class="mb-4">
                        <input type="text" 
                               wire:model.live.debounce.300ms="searchTerm"
                               placeholder="Search by user name or email..."
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="max-h-96 overflow-y-auto">
                    @forelse($attempts as $attempt)
                        <div class="p-4 border-b border-gray-100 hover:bg-gray-50 cursor-pointer {{ $selectedAttempt && $selectedAttempt->id === $attempt->id ? 'bg-blue-50 border-blue-200' : '' }}"
                             wire:click="selectAttempt({{ $attempt->id }})">
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-medium text-gray-900">{{ $attempt->user->name }}</span>
                                <span class="text-xs text-gray-500">
                                    {{ $attempt->completed_at ? $attempt->completed_at->format('M d, Y') : 'In Progress' }}
                                </span>
                            </div>
                            <div class="text-sm text-gray-600 mb-1">{{ $attempt->questionnaire->title }}</div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500">{{ $attempt->user->email }}</span>
                                <div class="flex items-center space-x-2">
                                    @php
                                        $pendingCount = $attempt->gameAssessments->where('is_assessed', false)->count();
                                        $totalCount = $attempt->gameAssessments->count();
                                    @endphp
                                    @if($pendingCount > 0)
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                            {{ $pendingCount }} pending
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Complete
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-gray-500">
                            <i class="fas fa-gamepad text-3xl mb-2"></i>
                            <p>No game attempts found</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Right Panel: Assessment Details --}}
        <div class="lg:col-span-2">
            @if($selectedAttempt)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h2 class="text-xl font-semibold text-gray-900">{{ $selectedAttempt->user->name }}</h2>
                                <p class="text-gray-600">{{ $selectedAttempt->questionnaire->title }}</p>
                            </div>
                            <div class="text-right">
                                <div class="text-sm text-gray-500">{{ $selectedAttempt->completed_at ? 'Completed' : 'In Progress' }}</div>
                                <div class="text-lg font-semibold text-gray-900">
                                    {{ $selectedAttempt->completed_at ? $selectedAttempt->completed_at->format('M d, Y H:i') : 'Ongoing' }}
                                </div>
                            </div>
                        </div>

                        {{-- User Info --}}
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-500">Email:</span>
                                <span class="ml-2 text-gray-900">{{ $selectedAttempt->user->email }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Total Score:</span>
                                <span class="ml-2 text-gray-900">{{ number_format($selectedAttempt->total_score, 1) }}%</span>
                            </div>
                        </div>
                    </div>

                    {{-- Game Assessments --}}
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Fun Game Assessments</h3>
                        
                        @if($assessments->count() > 0)
                            <div class="space-y-4">
                                @foreach($assessments as $assessment)
                                    <div class="border border-gray-200 rounded-lg p-4 {{ $assessment->is_assessed ? 'bg-green-50' : 'bg-orange-50' }}">
                                        <div class="flex items-center justify-between mb-3">
                                            <div>
                                                <h4 class="font-medium text-gray-900">
                                                    {{ $assessment->question->game_name ?? 'Fun Game' }}
                                                </h4>
                                                <p class="text-sm text-gray-600">{{ $assessment->question->question }}</p>
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                @if($assessment->is_assessed)
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        <i class="fas fa-check mr-1"></i>
                                                        Assessed
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                                        <i class="fas fa-clock mr-1"></i>
                                                        Pending
                                                    </span>
                                                @endif
                                                
                                                <button wire:click="editAssessment({{ $assessment->id }})"
                                                        class="px-3 py-1 text-xs bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                                    {{ $assessment->is_assessed ? 'Edit' : 'Assess' }}
                                                </button>
                                            </div>
                                        </div>

                                        @if($assessment->is_assessed)
                                            <div class="grid grid-cols-4 gap-4 text-sm">
                                                <div>
                                                    <span class="text-gray-500">Deposit:</span>
                                                    <span class="ml-1 font-medium text-green-600">{{ number_format($assessment->deposit, 2) }}</span>
                                                </div>
                                                <div>
                                                    <span class="text-gray-500">Penalty:</span>
                                                    <span class="ml-1 font-medium text-red-600">{{ number_format($assessment->penalty, 2) }}</span>
                                                </div>
                                                <div>
                                                    <span class="text-gray-500">Total:</span>
                                                    <span class="ml-1 font-bold {{ $assessment->total_deposit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                                        {{ number_format($assessment->total_deposit, 2) }}
                                                    </span>
                                                </div>
                                                <div>
                                                    <span class="text-gray-500">By:</span>
                                                    <span class="ml-1 text-gray-900">{{ $assessment->assessedBy->name ?? 'N/A' }}</span>
                                                </div>
                                            </div>
                                            @if($assessment->notes)
                                                <div class="mt-3 p-3 bg-gray-100 rounded-md">
                                                    <span class="text-sm text-gray-600">Notes:</span>
                                                    <p class="text-sm text-gray-800 mt-1">{{ $assessment->notes }}</p>
                                                </div>
                                            @endif
                                        @endif

                                        {{-- Show game description and images if available --}}
                                        @if($assessment->question->description || $assessment->question->images)
                                            <details class="mt-3">
                                                <summary class="text-sm text-blue-600 cursor-pointer hover:text-blue-800">View Game Details</summary>
                                                <div class="mt-2 p-3 bg-blue-50 rounded-md">
                                                    @if($assessment->question->description)
                                                        <p class="text-sm text-blue-800 mb-2">{{ $assessment->question->description }}</p>
                                                    @endif
                                                    @if($assessment->question->images)
                                                        <div class="grid grid-cols-3 gap-2">
                                                            @foreach($assessment->question->images as $image)
                                                                @php
                                                                    $imageUrl = asset('storage/' . $image);
                                                                @endphp
                                                                <img src="{{ $imageUrl }}" 
                                                                     alt="Game Image" 
                                                                     class="w-full h-20 object-cover rounded cursor-pointer hover:opacity-75 transition-opacity"
                                                                     onclick="openImageModal('{{ $imageUrl }}')"
                                                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                                                <div style="display:none;" class="w-full h-20 bg-gray-200 flex items-center justify-center text-gray-500 rounded">
                                                                    <i class="fas fa-image"></i>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            </details>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            {{-- Summary --}}
                            <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                                <h4 class="font-medium text-gray-900 mb-2">Assessment Summary</h4>
                                @php
                                    $totalDeposit = $assessments->sum('total_deposit');
                                    $assessedCount = $assessments->where('is_assessed', true)->count();
                                    $totalCount = $assessments->count();
                                @endphp
                                <div class="grid grid-cols-3 gap-4 text-sm">
                                    <div>
                                        <span class="text-gray-500">Progress:</span>
                                        <span class="ml-1 font-medium">{{ $assessedCount }}/{{ $totalCount }} games</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Total Deposit:</span>
                                        <span class="ml-1 font-bold {{ $totalDeposit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                            {{ number_format($totalDeposit, 2) }}
                                        </span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Status:</span>
                                        @if($assessedCount === $totalCount)
                                            <span class="ml-1 text-green-600 font-medium">Complete</span>
                                        @else
                                            <span class="ml-1 text-orange-600 font-medium">{{ $totalCount - $assessedCount }} pending</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-gamepad text-3xl mb-2"></i>
                                <p>No fun games found in this quiz attempt</p>
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
                    <i class="fas fa-clipboard-list text-gray-400 text-4xl mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Select a Quiz Attempt</h3>
                    <p class="text-gray-600">Choose a quiz attempt from the left panel to view and assess fun games</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Assessment Modal --}}
    @if($editingAssessment)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity"></div>

                <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-edit text-blue-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Assess Game Performance
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    {{ $editingAssessment->question->game_name ?? 'Fun Game' }} - {{ $editingAssessment->user->name }}
                                </p>
                            </div>
                            
                            <form wire:submit="saveAssessment" class="mt-4 space-y-4">
                                <div>
                                    <label for="deposit" class="block text-sm font-medium text-gray-700">Deposit</label>
                                    <input type="number" 
                                           wire:model.live="deposit" 
                                           step="0.01" 
                                           min="0"
                                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    @error('deposit') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label for="penalty" class="block text-sm font-medium text-gray-700">Penalty</label>
                                    <input type="number" 
                                           wire:model.live="penalty" 
                                           step="0.01" 
                                           min="0"
                                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    @error('penalty') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
                                    <textarea wire:model="notes" 
                                              rows="3"
                                              class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                              placeholder="Additional notes about the assessment..."></textarea>
                                    @error('notes') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <div class="bg-gray-50 p-3 rounded-md">
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-600">Total Deposit:</span>
                                        <span class="font-bold {{ $totalDeposit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                            {{ number_format($totalDeposit, 2) }}
                                        </span>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                        <button type="submit" 
                                wire:click="saveAssessment"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Save Assessment
                        </button>
                        <button type="button" 
                                wire:click="cancelEdit"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Success/Error Messages --}}
    @if(session()->has('success'))
        <div class="fixed top-4 right-4 bg-green-50 border border-green-200 rounded-md p-4 z-50" 
             x-data="{ show: true }" 
             x-show="show" 
             x-init="setTimeout(() => show = false, 5000)"
             x-transition>
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-green-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-green-800">{{ session('success') }}</p>
                </div>
                <div class="ml-auto pl-3">
                    <button @click="show = false" class="text-green-400 hover:text-green-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Image Modal --}}
    <div id="imageModal" class="fixed inset-0 bg-black bg-opacity-75 z-50 hidden flex items-center justify-center" onclick="closeImageModal()">
        <div class="max-w-4xl max-h-full p-4">
            <img id="modalImage" src="" alt="Game Image" class="max-w-full max-h-full object-contain rounded-lg">
            <button onclick="closeImageModal()" class="absolute top-4 right-4 text-white text-2xl hover:text-gray-300">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
</div>

<script>
function openImageModal(imageSrc) {
    const modal = document.getElementById('imageModal');
    const modalImage = document.getElementById('modalImage');
    modalImage.src = imageSrc;
    modal.classList.remove('hidden');
}

function closeImageModal() {
    const modal = document.getElementById('imageModal');
    modal.classList.add('hidden');
}
</script>