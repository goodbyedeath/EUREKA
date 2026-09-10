<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:justify-between lg:items-center mb-6 gap-4">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Guidance Gallery</h1>
        
        <!-- Search -->
        <div class="relative">
            <input type="text" wire:model.live.debounce.400ms="search" placeholder="Search guidance..." 
                   class="pl-8 pr-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm transition-colors duration-200">
            <svg class="w-4 h-4 absolute left-2.5 top-2.5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
        </div>
    </div>

    <!-- Error Message -->
    @if (session()->has('error'))
        <div class="bg-red-100 dark:bg-red-800 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-200 px-4 py-3 rounded-lg mb-4 transition-colors duration-200">
            <div class="flex">
                <svg class="w-4 h-4 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                {{ session('error') }}
            </div>
        </div>
    @endif

    <!-- Guidance Gallery Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        @forelse($guidances as $guidance)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition-shadow duration-300 cursor-pointer" 
                 wire:click="viewGuidance({{ $guidance->id }})">
                
                <!-- Image Preview -->
                <div class="relative h-48 bg-gray-200 dark:bg-gray-700">
                    @if($guidance->first_image)
                        <img src="{{ Storage::url($guidance->first_image) }}" 
                             alt="{{ $guidance->title }}" 
                             class="w-full h-full object-cover">
                        
                        <!-- Image Count Badge -->
                        @if($guidance->image_count > 1)
                            <div class="absolute top-2 right-2 bg-black bg-opacity-75 text-white px-2 py-1 rounded-full text-xs">
                                {{ $guidance->image_count }} images
                            </div>
                        @endif
                    @else
                        <div class="w-full h-full flex items-center justify-center">
                            <svg class="w-16 h-16 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                    @endif
                </div>

                <!-- Content -->
                <div class="p-4">
                    <h3 class="font-semibold text-lg text-gray-900 dark:text-gray-100 mb-2 truncate">
                        {{ $guidance->title }}
                    </h3>
                    
                    @if($guidance->description)
                        <p class="text-gray-600 dark:text-gray-400 text-sm line-clamp-3">
                            {{ Str::limit($guidance->description, 100) }}
                        </p>
                    @endif

                    <div class="mt-3 flex items-center justify-between">
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            by {{ $guidance->creator->name }}
                        </span>
                        
                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-12">
                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">No guidance available</h3>
                <p class="text-gray-600 dark:text-gray-400">Check back later for new guidance content.</p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($guidances->hasPages())
        <div class="mt-8">
            {{ $guidances->links() }}
        </div>
    @endif

    <!-- Image Gallery Modal -->
    @if($showModal && $selectedGuidance)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-black bg-opacity-75 transition-opacity" wire:click="closeModal"></div>
            
            <div class="inline-block align-middle bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-4xl sm:w-full">
                
                <!-- Modal Header -->
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">
                                {{ $selectedGuidance->title }}
                            </h3>
                            @if($selectedGuidance->description)
                                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                    {{ $selectedGuidance->description }}
                                </p>
                            @endif
                        </div>
                        <button type="button" wire:click="closeModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Image Gallery -->
                @if($selectedGuidance->images && count($selectedGuidance->images) > 0)
                    <div class="bg-white dark:bg-gray-800">
                        <!-- Main Image Display -->
                        <div class="relative bg-black">
                            <img src="{{ Storage::url($selectedGuidance->images[$currentImageIndex]) }}" 
                                 alt="Image {{ $currentImageIndex + 1 }}" 
                                 class="w-full max-h-96 object-contain">
                            
                            <!-- Navigation Arrows (only show if more than 1 image) -->
                            @if(count($selectedGuidance->images) > 1)
                                <button wire:click="prevImage" 
                                        class="absolute left-4 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-50 hover:bg-opacity-75 text-white p-2 rounded-full transition-all">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                    </svg>
                                </button>
                                
                                <button wire:click="nextImage" 
                                        class="absolute right-4 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-50 hover:bg-opacity-75 text-white p-2 rounded-full transition-all">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </button>

                                <!-- Image Counter -->
                                <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 bg-black bg-opacity-75 text-white px-3 py-1 rounded-full text-sm">
                                    {{ $currentImageIndex + 1 }} / {{ count($selectedGuidance->images) }}
                                </div>
                            @endif
                        </div>

                        <!-- Thumbnail Navigation (only show if more than 1 image) -->
                        @if(count($selectedGuidance->images) > 1)
                            <div class="p-4 bg-gray-100 dark:bg-gray-700">
                                <div class="flex space-x-2 overflow-x-auto">
                                    @foreach($selectedGuidance->images as $index => $image)
                                        <button wire:click="goToImage({{ $index }})" 
                                                class="flex-shrink-0 w-16 h-16 rounded-lg overflow-hidden {{ $index === $currentImageIndex ? 'ring-2 ring-blue-500' : '' }}">
                                            <img src="{{ Storage::url($image) }}" 
                                                 alt="Thumbnail {{ $index + 1 }}" 
                                                 class="w-full h-full object-cover">
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="bg-white dark:bg-gray-800 p-8 text-center">
                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <p class="text-gray-500 dark:text-gray-400">No images available for this guidance.</p>
                    </div>
                @endif

                <!-- Modal Footer -->
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 text-right">
                    <button wire:click="closeModal" 
                            class="inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:text-sm">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@push('styles')
<style>
    .line-clamp-3 {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>
@endpush