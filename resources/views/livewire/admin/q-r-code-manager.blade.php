<div class="text-center space-y-6">
    <!-- QR Code Display with Enhanced Styling -->
    <div class="bg-white p-6 rounded-xl shadow-sm border-2 border-gray-100 inline-block">
        <div id="qr-code-display" class="mb-2">
            {!! $this->generateQrCode(250) !!}
        </div>
        <div class="flex items-center justify-center space-x-2 text-xs text-gray-500">
            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M12.316 3.051a1 1 0 01.633 1.265l-4 12a1 1 0 11-1.898-.632l4-12a1 1 0 011.265-.633zM5.707 6.293a1 1 0 010 1.414L3.414 10l2.293 2.293a1 1 0 11-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0zm8.586 0a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 11-1.414-1.414L16.586 10l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd"></path>
            </svg>
            <span>{{ $questionnaire->qr_code }}</span>
        </div>
    </div>
    
    <!-- Questionnaire Info -->
    <div class="bg-gray-50 rounded-lg p-4">
        <h4 class="font-medium text-gray-900 mb-1">{{ $questionnaire->title }}</h4>
        @if($questionnaire->description)
            <p class="text-sm text-gray-600 mb-3">{{ Str::limit($questionnaire->description, 100) }}</p>
        @endif
        <div class="flex items-center justify-center space-x-4 text-xs text-gray-500">
            <div class="flex items-center">
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                {{ $questionnaire->questions_count ?? 0 }} questions
            </div>
            <div class="flex items-center">
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                {{ $questionnaire->time_limit }}min
            </div>
        </div>
    </div>
    
    <!-- QR Code Instructions -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="text-center">
            <div class="flex items-center justify-center mb-2">
                <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="text-sm text-blue-700 font-medium">{{ __('quiz.how_to_use_qr_code') }}</p>
            </div>
            <p class="text-xs text-blue-600 leading-relaxed">
                {{ __('quiz.qr_code_instructions', ['code' => $questionnaire->qr_code]) }}
            </p>
            <button onclick="copyToClipboard('{{ $questionnaire->qr_code }}')" 
                    class="mt-2 inline-flex items-center px-3 py-1 text-xs font-medium text-blue-600 bg-blue-100 border border-blue-200 rounded-md hover:bg-blue-200 transition-colors duration-200">
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                </svg>
                {{ __('quiz.copy_code') }}
            </button>
        </div>
    </div>
    
    <!-- Flash Messages -->
    @if(session()->has('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
            <div class="flex items-center">
                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                {{ session('error') }}
            </div>
        </div>
    @endif
    
    @if(session()->has('message'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
            <div class="flex items-center">
                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                {{ session('message') }}
            </div>
        </div>
    @endif
    
    <!-- Action Buttons Grid -->
    <div class="grid grid-cols-2 gap-3">
        <!-- Download Options -->
        <div class="space-y-2">
            <button wire:click="downloadQrCode('png', 512)" 
                    class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-lg hover:bg-indigo-700 w-full transition-colors duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                {{ __('quiz.download_png') }}
            </button>
            <button wire:click="downloadQrCode('svg', 512)" 
                    class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-indigo-600 bg-indigo-50 border border-indigo-200 rounded-lg hover:bg-indigo-100 w-full transition-colors duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                {{ __('quiz.download_svg') }}
            </button>
        </div>
        
        <!-- Utility Actions -->
        <div class="space-y-2">
            <button onclick="printQrCode()" 
                    class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 w-full transition-colors duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                </svg>
                {{ __('quiz.print') }}
            </button>
            <button onclick="shareQrCode('{{ $questionnaire->title }}', '{{ $questionnaire->qr_code }}')" 
                    class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 w-full transition-colors duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"></path>
                </svg>
                {{ __('quiz.share') }}
            </button>
        </div>
    </div>
    
    <!-- Regenerate Button (Commented out as in original) -->
    {{-- <div class="pt-4 border-t border-gray-200">
        <button wire:click="regenerateQrCode" 
                wire:confirm="Are you sure you want to regenerate the QR code? The old code will no longer work."
                class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-red-600 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 w-full transition-colors duration-200">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            Regenerate QR Code
        </button>
    </div> --}}
</div>

<!-- JavaScript for Enhanced Functionality -->
<script>
function copyToClipboard(text) {
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(() => {
            showToast('Copied to clipboard!', 'success');
        }).catch(() => {
            fallbackCopy(text);
        });
    } else {
        fallbackCopy(text);
    }
}

function fallbackCopy(text) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    textArea.style.top = '-999999px';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    try {
        document.execCommand('copy');
        showToast('Copied to clipboard!', 'success');
    } catch (err) {
        showToast('Failed to copy. Please copy manually.', 'error');
    }
    document.body.removeChild(textArea);
}

function printQrCode() {
    const printWindow = window.open('', '_blank');
    const qrCodeHtml = document.getElementById('qr-code-display').innerHTML;
    const title = '{{ $questionnaire->title }}';
    const code = '{{ $questionnaire->qr_code }}';
    
    printWindow.document.write(`
        <html>
            <head>
                <title>QR Code - ${title}</title>
                <style>
                    body { 
                        font-family: Arial, sans-serif; 
                        text-align: center; 
                        padding: 20px;
                        margin: 0;
                    }
                    .qr-container { 
                        margin: 20px auto;
                        display: inline-block;
                        padding: 20px;
                        border: 2px solid #e5e7eb;
                        border-radius: 8px;
                    }
                    .title { 
                        font-size: 24px; 
                        font-weight: bold; 
                        margin-bottom: 10px;
                        color: #1f2937;
                    }
                    .code { 
                        font-family: monospace; 
                        font-size: 18px; 
                        color: #1f2937;
                        margin: 15px 0;
                        font-weight: bold;
                    }
                    .instructions { 
                        font-size: 14px; 
                        color: #6b7280;
                        margin-top: 15px;
                        line-height: 1.4;
                    }
                    @media print {
                        body { margin: 0; }
                    }
                </style>
            </head>
            <body>
                <div class="title">${title}</div>
                <div class="qr-container">
                    ${qrCodeHtml}
                </div>
                <div class="code">Code: ${code}</div>
                <div class="instructions">
                    Scan this QR code or enter the code above to access the quiz
                </div>
            </body>
        </html>
    `);
    
    printWindow.document.close();
    printWindow.onload = function() {
        printWindow.print();
        printWindow.close();
    };
}

function shareQrCode(title, code) {
    const shareText = `Join the quiz "${title}" using code: ${code}`;
    
    if (navigator.share) {
        navigator.share({
            title: `Quiz: ${title}`,
            text: shareText
        }).then(() => {
            showToast('Shared successfully!', 'success');
        }).catch((err) => {
            if (err.name !== 'AbortError') {
                copyToClipboard(shareText);
            }
        });
    } else {
        copyToClipboard(shareText);
    }
}

function showToast(message, type = 'info') {
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 px-4 py-2 rounded-lg text-white text-sm z-50 transform transition-all duration-300 translate-x-full ${
        type === 'success' ? 'bg-green-500' : 
        type === 'error' ? 'bg-red-500' : 'bg-blue-500'
    }`;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    // Animate in
    setTimeout(() => {
        toast.classList.remove('translate-x-full');
    }, 100);
    
    // Animate out and remove
    setTimeout(() => {
        toast.classList.add('translate-x-full');
        setTimeout(() => {
            document.body.removeChild(toast);
        }, 300);
    }, 3000);
}
</script>