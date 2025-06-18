<!-- resources/views/livewire/admin/questionnaire-manager.blade.php -->
<div>
    <!-- Flash Messages -->
    @if (session('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('message') }}
        </div>
    @endif

    @if (session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    <!-- Header with Create Button -->
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-semibold">{{ __('quiz.questionnaire_management') }}</h2>
        <button wire:click="openCreateModal" 
                class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors duration-200">
            {{ __('quiz.create_new_questionnaire') }}
        </button>
    </div>

    <!-- Questionnaires Table -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <ul class="divide-y divide-gray-200">
            @forelse($questionnaires as $questionnaire)
                <li class="px-4 sm:px-6 py-4 hover:bg-gray-50 transition-colors duration-150">
                    <div class="flex flex-col space-y-4 lg:flex-row lg:items-center lg:justify-between lg:space-y-0">
                        <!-- Main Content -->
                        <div class="flex-1 min-w-0">
                            <!-- Title and Status -->
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-2">
                                <div class="flex items-center mb-2 sm:mb-0">
                                    <h3 class="text-base sm:text-lg font-medium text-gray-900 truncate">
                                        {{ $questionnaire->title }}
                                    </h3>
                                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $questionnaire->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                        {{ $questionnaire->is_active ? __('common.active') : __('common.inactive') }}
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Description -->
                            @if($questionnaire->description)
                                <p class="text-sm text-gray-600 mb-3 line-clamp-2">{{ $questionnaire->description }}</p>
                            @endif
                            
                            <!-- Stats Grid - Responsive -->
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4 text-sm text-gray-500">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="truncate">{{ $questionnaire->questions_count }} {{ __('quiz.questions') }}</span>
                                </div>
                                
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="truncate">{{ $questionnaire->time_limit }}min</span>
                                </div>
                                
                                <div class="flex items-center col-span-2 sm:col-span-1">
                                    <svg class="w-4 h-4 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    <span class="truncate">{{ $questionnaire->creator->name ?? 'Unknown' }}</span>
                                </div>
                                
                                <div class="flex items-center col-span-2 sm:col-span-1">
                                    <svg class="w-4 h-4 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a1 1 0 012 0v4M16 7V3a1 1 0 012 0v4M6 11h12v6a1 1 0 01-1 1H7a1 1 0 01-1-1v-6zM6 7h12a1 1 0 011 1v3H5V8a1 1 0 011-1z"></path>
                                    </svg>
                                    <span class="truncate">{{ $questionnaire->created_at->format('M d, Y') }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Action Buttons - Responsive Layout -->
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center space-y-2 sm:space-y-0 sm:space-x-2 lg:flex-col lg:space-y-2 lg:space-x-0 xl:flex-row xl:space-y-0 xl:space-x-2">
                            <!-- Questions Button -->
                            <button wire:click="openQuestionsModal({{ $questionnaire->id }})" 
                                    class="inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-indigo-600 border border-indigo-600 rounded-md hover:bg-indigo-50 transition-colors duration-200 w-full sm:w-auto">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span class="hidden sm:inline">Questions</span>
                                <span class="sm:hidden">Manage Questions</span>
                            </button>
                            
                            <!-- Toggle Active Button -->
                            <button wire:click="toggleActive({{ $questionnaire->id }})" 
                                    class="inline-flex items-center justify-center px-3 py-2 text-sm font-medium {{ $questionnaire->is_active ? 'text-red-600 border-red-600 hover:bg-red-50' : 'text-green-600 border-green-600 hover:bg-green-50' }} border rounded-md transition-colors duration-200 w-full sm:w-auto">
                                @if($questionnaire->is_active)
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Deactivate
                                @else
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Activate
                                @endif
                            </button>
                            
                            <!-- QR Code Button -->
                            <button wire:click="openQrModal({{ $questionnaire->id }})" 
                                    class="inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-gray-600 border border-gray-600 rounded-md hover:bg-gray-50 transition-colors duration-200 w-full sm:w-auto">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"></path>
                                </svg>
                                <span class="hidden sm:inline">QR Code</span>
                                <span class="sm:hidden">Generate QR</span>
                            </button>

                            <!-- Delete Button -->
                            <button wire:click="openDeleteModal({{ $questionnaire->id }})" 
                                    class="inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-red-600 border border-red-600 rounded-md hover:bg-red-50 transition-colors duration-200 w-full sm:w-auto">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                                <span class="hidden sm:inline">Delete</span>
                                <span class="sm:hidden">Delete</span>
                            </button>
                        </div>
                    </div>
                </li>
            @empty
                <li class="px-4 sm:px-6 py-8 text-center text-gray-500">
                    <div class="flex flex-col items-center">
                        <svg class="w-12 h-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <p class="text-lg font-medium text-gray-900 mb-1">No questionnaires yet</p>
                        <p class="text-sm text-gray-500">Get started by creating your first questionnaire.</p>
                    </div>
                </li>
            @endforelse
        </ul>
    </div>

    <!-- Create Modal -->
    @if($showCreateModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4" 
             wire:click.self="closeCreateModal">
            <div class="relative bg-white rounded-lg shadow-xl max-w-2xl w-full mx-auto max-h-[90vh] overflow-y-auto">
                <!-- Modal Header -->
                <div class="flex justify-between items-center p-6 border-b border-gray-200 sticky top-0 bg-white rounded-t-lg">
                    <h3 class="text-lg font-medium text-gray-900">Create New Questionnaire</h3>
                    <button wire:click="closeCreateModal" 
                            class="text-gray-400 hover:text-gray-600 transition-colors duration-200 p-1">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <!-- Modal Body -->
                <div class="p-6">
                    <livewire:admin.questionnaire-create-form />
                </div>
            </div>
        </div>
    @endif

    <!-- Questions Management Modal -->
    @if($showQuestionsModal && $selectedQuestionnaire)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" 
             wire:click.self="closeQuestionsModal">
            <div class="relative top-10 mx-auto p-5 border max-w-6xl shadow-lg rounded-md bg-white max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center mb-4 sticky top-0 bg-white pb-4 border-b">
                    <h3 class="text-lg font-medium text-gray-900">
                        Manage Questions - {{ $selectedQuestionnaire->title }}
                    </h3>
                    <button wire:click="closeQuestionsModal" 
                            class="text-gray-400 hover:text-gray-600 transition-colors duration-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <div class="mt-4">
                    <livewire:admin.question-manager :questionnaire="$selectedQuestionnaire" :key="'question-manager-'.$selectedQuestionnaire->id" />
                </div>
            </div>
        </div>
    @endif

    <!-- QR Code Modal -->
    @if($showQrModal && $qrQuestionnaire)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4" 
             wire:click.self="closeQrModal">
            <div class="relative bg-white rounded-lg shadow-xl max-w-lg w-full mx-auto max-h-[90vh] overflow-y-auto">
                <!-- Modal Header -->
                <div class="flex justify-between items-center p-6 border-b border-gray-200 sticky top-0 bg-white rounded-t-lg">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">QR Code</h3>
                        <p class="text-sm text-gray-500 mt-1">{{ Str::limit($qrQuestionnaire->title, 30) }}</p>
                    </div>
                    <button wire:click="closeQrModal" 
                            class="text-gray-400 hover:text-gray-600 transition-colors duration-200 p-1">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <!-- Modal Body -->
                <div class="p-6">
                    <livewire:admin.q-r-code-manager 
                        :questionnaire="$qrQuestionnaire" 
                        :key="'qr-manager-'.$qrQuestionnaire->id" />
                </div>
            </div>
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if($showDeleteModal && $deleteQuestionnaire)
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" 
         wire:click="closeDeleteModal">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white"
             wire:click.stop>
            <div class="mt-3 text-center">
                <!-- Warning Icon -->
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.728-.833-2.498 0L4.316 15.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                
                <!-- Modal Content -->
                <h3 class="text-lg font-medium text-gray-900 mt-3">Delete Questionnaire</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500 mb-3">
                        Are you sure you want to delete "<strong>{{ $deleteQuestionnaire->title }}</strong>"?
                    </p>
                    
                    @if($deleteQuestionnaire->questions_count > 0)
                    <div class="bg-yellow-50 border border-yellow-200 rounded-md p-3 mb-3">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    This questionnaire contains <strong>{{ $deleteQuestionnaire->questions_count }}</strong> question(s) that will also be permanently deleted.
                                </p>
                            </div>
                        </div>
                    </div>
                    @endif
                    
                    <p class="text-sm text-red-600 font-medium">
                        This action cannot be undone.
                    </p>
                </div>
                
                <!-- Modal Actions -->
                <div class="flex justify-center space-x-3 mt-4">
                    <button wire:click="closeDeleteModal" 
                            class="px-4 py-2 bg-gray-300 text-gray-700 text-base font-medium rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300 transition-colors duration-200">
                        Cancel
                    </button>
                    
                    <button wire:click="confirmDelete" 
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-50 cursor-not-allowed"
                            class="px-4 py-2 bg-red-600 text-white text-base font-medium rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 transition-colors duration-200">
                        <span wire:loading.remove>Delete Questionnaire</span>
                        <span wire:loading>Deleting...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>