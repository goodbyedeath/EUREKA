<div class="min-h-screen bg-gray-50 py-6" x-data="{
    capturePhoto: async function() {
        if (window.CameraCapture && window.CameraCapture.isSupported()) {
            try {
                const camera = new window.CameraCapture();
                const photoResult = await camera.capturePhoto();

                if (!photoResult.error) {
                    @this.set('facilitatorPhoto', photoResult.photo);
                    return true;
                } else {
                    alert('Camera error: ' + photoResult.message);
                    return false;
                }
            } catch (error) {
                console.error('Camera capture error:', error);
                alert('Failed to capture photo: ' + error.message);
                return false;
            }
        } else {
            @this.set('facilitatorPhoto', null);
            return true;
        }
    },
    async handleSaveAssessment(event) {
        event.preventDefault();
        const photosCaptured = await this.capturePhoto();
        if (photosCaptured) {
            @this.saveAssessment();
        }
    }
}">
    <div class="max-w-4xl mx-auto px-4">
        {{-- Header --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Game Assessment</h1>
                    <p class="text-gray-600">{{ $attempt->questionnaire->title }}</p>
                </div>
                <div class="text-right text-sm text-gray-500">
                    <div>Game Completed: {{ $assessment->created_at->format('M d, Y H:i') }}</div>
                    <div>User: {{ $assessment->user->name }}</div>
                </div>
            </div>

            {{-- Game Details --}}
            <div class="bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-lg p-4">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-gamepad text-2xl"></i>
                    <div>
                        <h2 class="text-xl font-bold">{{ $question->game_name ?? 'Fun Game' }}</h2>
                        <p class="text-purple-100">{{ $question->question }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Game Content --}}
        @if($question->description || $question->images)
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Game Details</h3>
                
                @if($question->description)
                    <div class="mb-4">
                        <p class="text-gray-700">{{ $question->description }}</p>
                    </div>
                @endif

                @if($question->images)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($question->images as $image)
                            @php $imageUrl = asset('storage/' . $image); @endphp
                            <div class="bg-gray-100 rounded-lg overflow-hidden">
                                <img src="{{ $imageUrl }}" 
                                     alt="Game Image" 
                                     class="w-full h-40 object-cover cursor-pointer hover:opacity-75 transition-opacity"
                                     onclick="openImageModal('{{ $imageUrl }}')"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                <div style="display:none;" class="w-full h-40 bg-gray-200 flex items-center justify-center text-gray-500">
                                    <i class="fas fa-image text-2xl"></i>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        {{-- Assessment Form --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Performance Assessment</h3>
            
            <form @submit="handleSaveAssessment($event)" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {{-- Deposit (Read-only) --}}
                    <div>
                        <label for="deposit" class="block text-sm font-medium text-gray-700 mb-2">
                            Base Points
                            <span class="text-gray-500">(team initial points)</span>
                        </label>
                        <div class="mb-2 p-2 bg-blue-50 border border-blue-200 rounded text-sm text-blue-800">
                            <i class="fas fa-info-circle mr-1"></i>
                            Fixed at your team's initial points
                        </div>
                        <input type="number" 
                               value="{{ $deposit }}"
                               readonly
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm bg-gray-50 text-gray-600 cursor-not-allowed">
                    </div>

                    {{-- Additional Points --}}
                    <div>
                        <label for="additionalPoints" class="block text-sm font-medium text-gray-700 mb-2">
                            Additional Points
                            <span class="text-gray-500">(from questionnaire)</span>
                        </label>
                        <div class="mb-2 p-2 bg-green-50 border border-green-200 rounded text-sm text-green-800">
                            <i class="fas fa-star mr-1"></i>
                            Default: Total questionnaire points ({{ number_format((float)($attempt->questionnaire->questions()->sum('points') ?? 0), 0) }})
                        </div>
                        <input type="number" 
                               wire:model.live="additionalPoints" 
                               step="1" 
                               min="0"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                               placeholder="0">
                        @error('additionalPoints') 
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Penalty --}}
                    <div>
                        <label for="penalty" class="block text-sm font-medium text-gray-700 mb-2">
                            Penalty Points
                            <span class="text-gray-500">(deducted points)</span>
                        </label>
                        <div class="mb-2 p-2 bg-red-50 border border-red-200 rounded text-sm text-red-800">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            Points to deduct for penalties
                        </div>
                        <input type="number" 
                               wire:model.live="penalty" 
                               step="1" 
                               min="0"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500"
                               placeholder="0">
                        @error('penalty') 
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Notes --}}
                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                        Assessment Notes
                        <span class="text-gray-500">(optional)</span>
                    </label>
                    <textarea wire:model="notes" 
                              rows="4"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                              placeholder="Add any notes about the game performance..."></textarea>
                    @error('notes') 
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Total Calculation --}}
                <div class="bg-gray-50 p-4 rounded-lg">
                    <div class="flex items-center justify-between text-lg">
                        <span class="font-medium text-gray-900">Total Score:</span>
                        <span class="font-bold {{ $totalDeposit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ number_format((float)$totalDeposit, 2) }} points
                        </span>
                    </div>
                    <div class="text-sm text-gray-600 mt-1">
                        Calculation: {{ number_format((float)$deposit, 2) }} (base) + {{ number_format((float)$additionalPoints, 2) }} (additional) - {{ number_format((float)$penalty, 2) }} (penalty)
                    </div>
                    <div class="text-xs text-gray-500 mt-2 flex items-center justify-between">
                        <span>Base Points: {{ number_format((float)$deposit, 2) }}</span>
                        <span>Additional Points: {{ number_format((float)$additionalPoints, 2) }}</span>
                        <span>Penalty: -{{ number_format((float)$penalty, 2) }}</span>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                    <button type="button" 
                            wire:click="skipAssessment"
                            class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                        Skip Assessment
                    </button>
                    
                    <div class="flex space-x-3">
                        <a href="{{ route('quiz.results', ['attemptId' => $attempt->id]) }}" 
                           class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                            Cancel
                        </a>
                        <button type="submit"
                                class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors flex items-center gap-2">
                            <i class="fas fa-camera"></i>
                            <i class="fas fa-save"></i>
                            <span>Take Photo & Save</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Image Modal --}}
    <div id="imageModal" class="fixed inset-0 bg-black bg-opacity-75 z-50 hidden flex items-center justify-center" onclick="closeImageModal()">
        <div class="max-w-4xl max-h-full p-4">
            <img id="modalImage" src="" alt="Game Image" class="max-w-full max-h-full object-contain rounded-lg">
            <button onclick="closeImageModal()" class="absolute top-4 right-4 text-white text-2xl hover:text-gray-300">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    {{-- Success/Error Messages --}}
    @if(session()->has('success'))
        <div id="success-message" class="fixed top-4 right-4 bg-green-50 border border-green-200 rounded-md p-4 z-50">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-green-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-green-800">{{ session('success') }}</p>
                </div>
                <div class="ml-auto pl-3">
                    <button onclick="closeSuccessMessage()" class="text-green-400 hover:text-green-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
        <script>
            // Auto-hide success message after 5 seconds
            setTimeout(() => {
                const message = document.getElementById('success-message');
                if (message) {
                    message.style.display = 'none';
                }
            }, 5000);

            function closeSuccessMessage() {
                const message = document.getElementById('success-message');
                if (message) {
                    message.style.display = 'none';
                }
            }
        </script>
    @endif
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