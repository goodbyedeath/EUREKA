<div class="text-center">
    <div class="mb-4">
        {!! $this->generateQrCode() !!}
    </div>
    
    <p class="text-sm text-gray-600 mb-2">
        QR Code for: <strong>{{ $questionnaire->title }}</strong>
    </p>
    
    <p class="text-xs text-gray-500 mb-4">
        Code: <code class="bg-gray-100 px-2 py-1 rounded">{{ $questionnaire->qr_code }}</code>
    </p>
    
    @if(session()->has('error'))
        <div class="text-red-500 text-sm mb-4">
            {{ session('error') }}
        </div>
    @endif
    
    @if(session()->has('message'))
        <div class="text-green-500 text-sm mb-4">
            {{ session('message') }}
        </div>
    @endif
    
    <div class="space-y-2">
        <button wire:click="downloadQrCode" 
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-indigo-600 bg-indigo-100 border border-transparent rounded-md hover:bg-indigo-200 w-full justify-center">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Download QR Code
        </button>
        
        {{-- <button wire:click="regenerateQrCode" 
                wire:confirm="Are you sure you want to regenerate the QR code? The old code will no longer work."
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-600 bg-gray-100 border border-transparent rounded-md hover:bg-gray-200 w-full justify-center">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            Regenerate QR Code
        </button> --}}
    </div>
</div>