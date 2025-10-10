{{-- resources/views/livewire/q-r-scanner-modal.blade.php --}}
<div>
    @if($showModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 shadow-lg rounded-md bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">{{ __('quiz.scan_qr_code') }}</h3>
                    
                    @if($isScanning)
                        <div wire:ignore>
                            <div id="qr-scanner-container" class="relative">
                                <video id="qr-video" class="w-full max-w-md mx-auto rounded-lg bg-black" playsinline autoplay muted></video>
                                <div id="qr-scanner-overlay" class="absolute inset-0 border-2 border-blue-500 rounded-lg pointer-events-none"></div>
                                <div class="absolute bottom-2 left-2 right-2 text-center">
                                    <p class="text-white text-sm bg-black bg-opacity-50 rounded px-2 py-1">
                                        {{ __('quiz.point_camera_at_qr') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 text-center">
                            <button wire:click="stopScanning" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 transition-colors">
                                {{ __('quiz.stop_scanning') }}
                            </button>
                        </div>
                    @else
                        <div class="text-center">
                            <button wire:click="startScanning" class="bg-blue-500 text-white px-4 py-2 rounded mb-4 hover:bg-blue-600 transition-colors">
                                {{ __('quiz.start_scanning') }}
                            </button>
                            
                            <!-- Info Section -->
                            <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded text-sm text-blue-800 dark:text-blue-200">
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-blue-500 mt-0.5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                    </svg>
                                    <div>
                                        <p class="font-medium text-blue-800 dark:text-blue-200">{{ __('quiz.scanning_tips') }}</p>
                                        <ul class="list-disc list-inside mt-1 space-y-1 text-left text-blue-700 dark:text-blue-300">
                                            <li>{{ __('quiz.allow_camera_access') }}</li>
                                            <li>{{ __('quiz.ensure_good_lighting') }}</li>
                                            <li>{{ __('quiz.hold_qr_steady') }}</li>
                                            <li>{{ __('quiz.use_manual_entry') }}</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Manual Entry -->
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Manual Entry</label>
                        <div class="flex mt-1">
                            <input 
                                type="text" 
                                wire:model.live="scannedCode" 
                                wire:keydown.enter="submitManualCode"
                                class="flex-1 border border-gray-300 dark:border-gray-600 rounded px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400" 
                                placeholder="Enter QR code manually"
                                autocomplete="off"
                                spellcheck="false">
                            <button 
                                wire:click="submitManualCode" 
                                class="ml-2 bg-green-500 hover:bg-green-600 dark:bg-green-600 dark:hover:bg-green-700 text-white px-4 py-2 rounded transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                Submit
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Enter the QR code text manually if scanning doesn't work
                            @if(!empty($scannedCode))
                                <span class="text-green-600 dark:text-green-400">• Code entered: {{ strlen($scannedCode) }} characters</span>
                            @endif
                        </p>
                        
                        <!-- Test Helper (only in development/testing) -->
                        @if(config('app.debug'))
                            <div class="mt-2 p-2 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded text-xs">
                                <p class="font-medium text-yellow-800 dark:text-yellow-200">Test QR Codes:</p>
                                <div class="flex flex-wrap gap-1 mt-1">
                                    <button wire:click="$set('scannedCode', '91ccdded-291a-43cc-a626-077cea5bc3b5')" 
                                            class="px-2 py-1 bg-yellow-100 dark:bg-yellow-800 text-yellow-800 dark:text-yellow-200 rounded text-xs hover:bg-yellow-200 dark:hover:bg-yellow-700">
                                        Test 1
                                    </button>
                                    <button wire:click="$set('scannedCode', '55f2d997-fba1-47eb-8e0e-29b56a70c0fd')" 
                                            class="px-2 py-1 bg-yellow-100 dark:bg-yellow-800 text-yellow-800 dark:text-yellow-200 rounded text-xs hover:bg-yellow-200 dark:hover:bg-yellow-700">
                                        Pos 1
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Results -->
                    @if($error)
                        <div class="mt-4 p-4 bg-red-100 dark:bg-red-900/20 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-300 rounded">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-red-500 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                </svg>
                                <div>
                                    <h4 class="font-semibold text-red-700 dark:text-red-300">Camera Access Error</h4>
                                    <p class="mt-1 text-red-700 dark:text-red-300">{{ $error }}</p>
                                    
                                    @if(str_contains($error, 'denied') || str_contains($error, 'permission'))
                                        <div class="mt-3 text-sm">
                                            <p class="font-medium text-red-700 dark:text-red-300">To fix this:</p>
                                            <ol class="list-decimal list-inside mt-1 space-y-1 text-red-700 dark:text-red-300">
                                                <li>Click the camera icon in your browser's address bar</li>
                                                <li>Select "Allow" for camera access</li>
                                                <li>Refresh the page and try again</li>
                                            </ol>
                                        </div>
                                    @elseif(str_contains($error, 'HTTPS'))
                                        <div class="mt-3 text-sm">
                                            <p class="font-medium text-red-700 dark:text-red-300">Camera access requires a secure connection (HTTPS).</p>
                                            <p class="text-red-700 dark:text-red-300">Please access this site via HTTPS or use localhost for testing.</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($questionnaire)
                        <div class="mt-4 p-4 bg-green-100 dark:bg-green-900/20 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-300 rounded">
                            <h4 class="font-semibold text-lg text-green-700 dark:text-green-300">{{ $questionnaire->title }}</h4>
                            <p class="mt-2 text-green-700 dark:text-green-300">{{ $questionnaire->description }}</p>
                            @if($questionnaire->time_limit)
                                <p class="mt-2 text-sm text-green-600 dark:text-green-400">
                                    <i class="fas fa-clock"></i> Time Limit: {{ $questionnaire->time_limit }} minutes
                                </p>
                            @endif
                            <div class="mt-4 flex gap-2">
                                <button wire:click="startQuiz" class="bg-green-600 hover:bg-green-700 dark:bg-green-700 dark:hover:bg-green-800 text-white px-6 py-2 rounded transition-colors flex-1">
                                    Start Quiz
                                </button>
                                @if(config('app.debug'))
                                    <button wire:click="refreshQuestionnaire" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded transition-colors text-xs">
                                        🔄
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endif

                    <div class="mt-6 text-center">
                        <button wire:click="closeModal" class="bg-gray-500 hover:bg-gray-600 dark:bg-gray-600 dark:hover:bg-gray-700 text-white px-4 py-2 rounded transition-colors">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@script
<script>
let qrScanner = null;
let isInitialized = false;

// Check if camera is supported
async function checkCameraSupport() {
    try {
        // Check if getUserMedia is supported
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            throw new Error('Camera not supported in this browser');
        }

        // Check if we're on HTTPS (required for camera access)
        if (location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
            throw new Error('Camera access requires HTTPS');
        }

        // Check if cameras are available
        const devices = await navigator.mediaDevices.enumerateDevices();
        const cameras = devices.filter(device => device.kind === 'videoinput');
        
        if (cameras.length === 0) {
            throw new Error('No cameras found');
        }

        return true;
    } catch (error) {
        // Camera support check failed
        return false;
    }
}

async function requestCameraPermission() {
    try {
        // Request camera permission with constraints
        const constraints = {
            video: { 
                facingMode: { ideal: 'environment' },
                width: { ideal: 640 },
                height: { ideal: 480 }
            }
        };

        const stream = await navigator.mediaDevices.getUserMedia(constraints);
        
        // Stop the stream immediately - we just wanted to check permission
        stream.getTracks().forEach(track => track.stop());
        
        return true;
    } catch (error) {
        return false;
    }
}

async function initializeQRScanner() {
    if (qrScanner) {
        try {
            await qrScanner.stop();
            await qrScanner.destroy();
        } catch (e) {
            // Ignore cleanup errors
        }
        qrScanner = null;
    }

    try {
        // Check camera support first
        const cameraSupported = await checkCameraSupport();
        if (!cameraSupported) {
            throw new Error('Camera not supported or not available');
        }

        // Request camera permission
        const permissionGranted = await requestCameraPermission();
        if (!permissionGranted) {
            throw new Error('Camera permission denied');
        }

        // Use new lazy loading approach for QR Scanner
        let QrScanner;
        try {
            console.log('Attempting to load QR Scanner...');
            
            // Try the global lazy loader function
            if (typeof window.loadScannerUtils === 'function') {
                console.log('Using global scanner loader...');
                QrScanner = await window.loadScannerUtils();
            } 
            // Check if already loaded globally
            else if (window.QrScanner) {
                console.log('Using already loaded QrScanner...');
                QrScanner = window.QrScanner;
            } 
            // Final fallback - direct import
            else {
                console.log('Fallback: Loading QrScanner directly...');
                const module = await import('qr-scanner');
                QrScanner = module.default;
                
                // Apply canvas optimization inline
                if (!window.canvasOptimized) {
                    const originalGetContext = HTMLCanvasElement.prototype.getContext;
                    HTMLCanvasElement.prototype.getContext = function(contextType, contextAttributes = {}) {
                        if (contextType === '2d' && !contextAttributes.hasOwnProperty('willReadFrequently')) {
                            contextAttributes.willReadFrequently = true;
                        }
                        return originalGetContext.call(this, contextType, contextAttributes);
                    };
                    window.canvasOptimized = true;
                }
                
                window.QrScanner = QrScanner;
            }
            
            if (!QrScanner) {
                throw new Error('QrScanner not loaded after all attempts');
            }
            
            console.log('QrScanner loaded successfully:', typeof QrScanner, !!QrScanner);
        } catch (importError) {
            console.error('QrScanner loading failed:', importError);
            throw new Error('QR Scanner library not available. Please refresh the page and try again.');
        }
        const video = document.getElementById('qr-video');
        
        if (!video) {
            if (typeof $wire !== 'undefined' && $wire && $wire.set) {
                $wire.set('error', 'Video element not found. Please try again.');
                $wire.set('isScanning', false);
            }
            return;
        }

        // Initialize QR Scanner
        qrScanner = new QrScanner(
            video,
            result => {
                if (typeof $wire !== 'undefined' && $wire && $wire.handleQRScanned) {
                    $wire.handleQRScanned(result.data);
                }
            },
            {
                returnDetailedScanResult: true,
                highlightScanRegion: true,
                highlightCodeOutline: true,
                maxScansPerSecond: 3,
                preferredCamera: 'environment'
            }
        );

        await qrScanner.start();
        
        // Check if video is working after brief delay
        setTimeout(() => {
            if (video.videoWidth === 0 || video.videoHeight === 0) {
                if (typeof $wire !== 'undefined' && $wire && $wire.set) {
                    $wire.set('error', 'Camera feed not detected. Please check camera permissions and try again.');
                    $wire.set('isScanning', false);
                }
            }
        }, 2000);
        
        isInitialized = true;
        
    } catch (error) {
        let errorMessage = 'Camera access failed';
        
        if (error.message.includes('HTTPS')) {
            errorMessage = 'Camera access requires HTTPS. Please use a secure connection.';
        } else if (error.name === 'NotAllowedError' || error.message.includes('permission denied')) {
            errorMessage = 'Camera access denied. Please allow camera access in your browser settings and try again.';
        } else if (error.name === 'NotFoundError' || error.message.includes('No cameras found')) {
            errorMessage = 'No camera found. Please connect a camera and try again.';
        } else if (error.name === 'NotReadableError') {
            errorMessage = 'Camera is already in use by another application. Please close other apps using the camera.';
        } else if (error.name === 'NotSupportedError' || error.message.includes('not supported')) {
            errorMessage = 'QR scanner is not supported on this device/browser. Please try a different browser.';
        } else if (error.name === 'AbortError') {
            errorMessage = 'Camera access was interrupted. Please try again.';
        } else {
            errorMessage = `Camera initialization failed: ${error.message}`;
        }
        
        if (typeof $wire !== 'undefined' && $wire && $wire.set) {
            $wire.set('error', errorMessage);
            $wire.set('isScanning', false);
        }
    }
}

async function cleanupQRScanner() {
    if (qrScanner) {
        try {
            await qrScanner.stop();
            await qrScanner.destroy();
        } catch (error) {
            // Ignore cleanup errors
        }
        qrScanner = null;
    }
    isInitialized = false;
}

$wire.on('start-qr-scanner', () => {
    if (typeof $wire !== 'undefined' && $wire) {
        setTimeout(initializeQRScanner, 300);
    }
});

$wire.on('stop-qr-scanner', () => {
    if (typeof $wire !== 'undefined' && $wire) {
        cleanupQRScanner();
    }
});

$wire.on('cleanup-qr-scanner', () => {
    if (typeof $wire !== 'undefined' && $wire) {
        cleanupQRScanner();
    }
});

document.addEventListener('livewire:navigating', () => {
    cleanupQRScanner();
});

window.addEventListener('beforeunload', () => {
    cleanupQRScanner();
});

document.addEventListener('visibilitychange', () => {
    if (document.hidden && qrScanner) {
        cleanupQRScanner();
    }
});

</script>
@endscript