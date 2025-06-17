{{-- resources/views/livewire/q-r-scanner-modal.blade.php --}}
<div>
    @if($showModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">QR Code Scanner</h3>
                    
                    @if($isScanning)
                        <div wire:ignore>
                            <div id="qr-scanner-container" class="relative">
                                <video id="qr-video" class="w-full max-w-md mx-auto rounded-lg bg-black" playsinline></video>
                                <div id="qr-scanner-overlay" class="absolute inset-0 border-2 border-blue-500 rounded-lg pointer-events-none"></div>
                                <div class="absolute bottom-2 left-2 right-2 text-center">
                                    <p class="text-white text-sm bg-black bg-opacity-50 rounded px-2 py-1">
                                        Point your camera at a QR code
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 text-center">
                            <button wire:click="stopScanning" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 transition-colors">
                                Stop Scanning
                            </button>
                        </div>
                    @else
                        <div class="text-center">
                            <button wire:click="startScanning" class="bg-blue-500 text-white px-4 py-2 rounded mb-4 hover:bg-blue-600 transition-colors">
                                Start Camera Scanner
                            </button>
                            
                            <!-- Info Section -->
                            <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded text-sm text-blue-800">
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-blue-500 mt-0.5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                    </svg>
                                    <div>
                                        <p class="font-medium">Tips for successful scanning:</p>
                                        <ul class="list-disc list-inside mt-1 space-y-1 text-left">
                                            <li>Allow camera access when prompted</li>
                                            <li>Ensure good lighting</li>
                                            <li>Hold the QR code steady and close to camera</li>
                                            <li>Use manual entry if camera isn't working</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Manual Entry -->
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700">Manual Entry</label>
                        <div class="flex mt-1">
                            <input type="text" wire:model="scannedCode" class="flex-1 border rounded px-3 py-2" placeholder="Enter QR code manually">
                            <button wire:click="manualEntry($wire.scannedCode)" class="ml-2 bg-green-500 text-white px-4 py-2 rounded">
                                Submit
                            </button>
                        </div>
                    </div>

                    <!-- Results -->
                    @if($error)
                        <div class="mt-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-red-500 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                </svg>
                                <div>
                                    <h4 class="font-semibold">Camera Access Error</h4>
                                    <p class="mt-1">{{ $error }}</p>
                                    
                                    @if(str_contains($error, 'denied') || str_contains($error, 'permission'))
                                        <div class="mt-3 text-sm">
                                            <p class="font-medium">To fix this:</p>
                                            <ol class="list-decimal list-inside mt-1 space-y-1">
                                                <li>Click the camera icon in your browser's address bar</li>
                                                <li>Select "Allow" for camera access</li>
                                                <li>Refresh the page and try again</li>
                                            </ol>
                                        </div>
                                    @elseif(str_contains($error, 'HTTPS'))
                                        <div class="mt-3 text-sm">
                                            <p class="font-medium">Camera access requires a secure connection (HTTPS).</p>
                                            <p>Please access this site via HTTPS or use localhost for testing.</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($questionnaire)
                        <div class="mt-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                            <h4 class="font-semibold text-lg">{{ $questionnaire->title }}</h4>
                            <p class="mt-2">{{ $questionnaire->description }}</p>
                            @if($questionnaire->time_limit)
                                <p class="mt-2 text-sm text-green-600">
                                    <i class="fas fa-clock"></i> Time Limit: {{ $questionnaire->time_limit }} minutes
                                </p>
                            @endif
                            <div class="mt-4">
                                <button wire:click="startQuiz" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 transition-colors">
                                    Start Quiz
                                </button>
                            </div>
                        </div>
                    @endif

                    <div class="mt-6 text-center">
                        <button wire:click="closeModal" class="bg-gray-500 text-white px-4 py-2 rounded">
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
        console.error('Camera support check failed:', error);
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
        console.error('Camera permission request failed:', error);
        return false;
    }
}

async function initializeQRScanner() {
    if (qrScanner) {
        try {
            await qrScanner.stop();
            await qrScanner.destroy();
        } catch (e) {
            console.warn('Error cleaning up previous scanner:', e);
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

        // Use globally available QR Scanner with fallback
        let QrScanner;
        if (window.QrScanner) {
            QrScanner = window.QrScanner;
        } else {
            // Fallback: try to import directly (for development)
            try {
                const module = await import('qr-scanner');
                QrScanner = module.default;
            } catch (importError) {
                throw new Error('QR Scanner library not available. Please refresh the page.');
            }
        }
        const video = document.getElementById('qr-video');
        
        if (!video) {
            console.error('Video element not found');
            $wire.set('error', 'Video element not found');
            $wire.set('isScanning', false);
            return;
        }

        // Initialize QR Scanner
        qrScanner = new QrScanner(
            video,
            result => {
                console.log('QR Code detected:', result.data);
                $wire.call('handleQRScanned', result.data);
            },
            {
                returnDetailedScanResult: true,
                highlightScanRegion: true,
                highlightCodeOutline: true,
                maxScansPerSecond: 3,
                preferredCamera: 'environment'
            }
        );

        // Start scanner
        await qrScanner.start();
        isInitialized = true;
        console.log('QR Scanner initialized successfully');
        
    } catch (error) {
        console.error('QR Scanner initialization failed:', error);
        
        // Provide detailed error messages
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
        }
        
        $wire.set('error', errorMessage);
        $wire.set('isScanning', false);
    }
}

async function cleanupQRScanner() {
    if (qrScanner) {
        try {
            await qrScanner.stop();
            await qrScanner.destroy();
            console.log('QR Scanner cleaned up successfully');
        } catch (error) {
            console.error('Error cleaning up QR Scanner:', error);
        }
        qrScanner = null;
    }
    isInitialized = false;
}

// Listen for Livewire events
$wire.on('start-qr-scanner', () => {
    console.log('Starting QR Scanner...');
    console.log('QrScanner available:', !!window.QrScanner);
    setTimeout(initializeQRScanner, 300); // Small delay to ensure DOM is ready
});

$wire.on('stop-qr-scanner', () => {
    console.log('Stopping QR Scanner...');
    cleanupQRScanner();
});

$wire.on('cleanup-qr-scanner', () => {
    console.log('Cleaning up QR Scanner...');
    cleanupQRScanner();
});

// Cleanup on navigation
document.addEventListener('livewire:navigating', () => {
    console.log('Navigating - cleaning up QR Scanner');
    cleanupQRScanner();
});

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    cleanupQRScanner();
});

// Handle visibility changes (mobile browser backgrounding)
document.addEventListener('visibilitychange', () => {
    if (document.hidden && qrScanner) {
        cleanupQRScanner();
    }
});
</script>
@endscript