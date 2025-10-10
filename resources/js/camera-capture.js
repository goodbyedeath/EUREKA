// Camera capture utility for quiz submission verification
class CameraCapture {
    constructor() {
        this.stream = null;
        this.video = null;
        this.canvas = null;
        this.isCapturing = false;
    }

    /**
     * Initialize camera and capture photo
     */
    async capturePhoto() {
        try {
            // Check if running on HTTPS or localhost (required for camera)
            if (location.protocol !== 'https:' && location.hostname !== 'localhost') {
                throw new Error('Camera access requires HTTPS connection');
            }

            // Check if getUserMedia is available
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                throw new Error('Camera access not supported by this browser');
            }
            
            // Create video element for camera preview
            this.video = document.createElement('video');
            this.video.style.position = 'fixed';
            this.video.style.top = '-9999px'; // Hide video element
            this.video.setAttribute('playsinline', true);
            this.video.setAttribute('autoplay', true);
            this.video.setAttribute('muted', true);
            document.body.appendChild(this.video);

            // Request camera access (front camera preferred)
            const constraints = {
                video: {
                    facingMode: 'user', // Front camera
                    width: { ideal: 640 },
                    height: { ideal: 480 }
                },
                audio: false
            };

            try {
                this.stream = await navigator.mediaDevices.getUserMedia(constraints);
            } catch (cameraError) {
                // Front camera failed, trying any available camera
                // Fallback: try any available camera
                const fallbackConstraints = {
                    video: {
                        width: { ideal: 640 },
                        height: { ideal: 480 }
                    },
                    audio: false
                };
                this.stream = await navigator.mediaDevices.getUserMedia(fallbackConstraints);
            }
            
            // Set video source to camera stream
            this.video.srcObject = this.stream;
            
            // Wait for video to be ready
            await new Promise((resolve) => {
                this.video.addEventListener('loadedmetadata', resolve);
            });

            // Create canvas for photo capture
            this.canvas = document.createElement('canvas');
            this.canvas.width = this.video.videoWidth;
            this.canvas.height = this.video.videoHeight;
            
            const context = this.canvas.getContext('2d');
            
            // Draw current video frame to canvas
            context.drawImage(this.video, 0, 0, this.canvas.width, this.canvas.height);
            
            // Convert canvas to base64 image
            const photoDataUrl = this.canvas.toDataURL('image/jpeg', 0.8);
            
            // Clean up
            this.cleanup();
            
            return photoDataUrl;
            
        } catch (error) {
            this.cleanup();
            
            // Provide user-friendly error messages
            let userMessage = 'Camera access failed';
            if (error.name === 'NotAllowedError') {
                userMessage = 'Camera permission denied. Please allow camera access and try again.';
            } else if (error.name === 'NotFoundError') {
                userMessage = 'No camera found on this device.';
            } else if (error.name === 'NotSupportedError') {
                userMessage = 'Camera not supported by this browser.';
            } else if (error.message.includes('HTTPS')) {
                userMessage = 'Camera requires secure connection (HTTPS).';
            }
            
            // Return error info for fallback handling
            return {
                error: true,
                message: userMessage,
                code: error.name,
                originalError: error.message
            };
        }
    }

    /**
     * Clean up camera resources
     */
    cleanup() {
        try {
            // Stop camera stream
            if (this.stream) {
                this.stream.getTracks().forEach(track => {
                    track.stop();
                });
                this.stream = null;
            }

            // Remove video element
            if (this.video && this.video.parentNode) {
                this.video.parentNode.removeChild(this.video);
                this.video = null;
            }

            // Clean up canvas
            if (this.canvas) {
                this.canvas = null;
            }

            // Camera resources cleaned up
        } catch (error) {
            // Cleanup error
        }
    }

    /**
     * Check if camera is supported
     */
    static isSupported() {
        return !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
    }

    /**
     * Show camera permission modal
     */
    static showCameraPermissionModal() {
        return new Promise((resolve) => {
            // Create modal HTML
            const modalHTML = `
                <div id="camera-permission-modal" class="fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-50">
                    <div class="flex items-center justify-center min-h-screen px-4">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
                            <div class="text-center">
                                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900 mb-4">
                                    <i class="fas fa-camera text-blue-600 dark:text-blue-400 text-xl"></i>
                                </div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                                    Camera Permission Required
                                </h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                                    We need to take a verification photo before submitting your quiz. Please allow camera access when prompted.
                                </p>
                                <div class="flex space-x-3">
                                    <button id="allow-camera" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium transition-colors">
                                        Allow Camera
                                    </button>
                                    <button id="skip-camera" class="flex-1 bg-gray-300 dark:bg-gray-600 hover:bg-gray-400 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-md font-medium transition-colors">
                                        Skip Photo
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Add modal to page
            document.body.insertAdjacentHTML('beforeend', modalHTML);

            // Handle button clicks
            document.getElementById('allow-camera').addEventListener('click', () => {
                document.getElementById('camera-permission-modal').remove();
                resolve(true);
            });

            document.getElementById('skip-camera').addEventListener('click', () => {
                document.getElementById('camera-permission-modal').remove();
                resolve(false);
            });
        });
    }

    /**
     * Show photo capture progress modal
     */
    static showCaptureProgressModal() {
        const modalHTML = `
            <div id="capture-progress-modal" class="fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-50">
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
                        <div class="text-center">
                            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900 mb-4">
                                <i class="fas fa-camera text-blue-600 dark:text-blue-400 text-xl animate-pulse"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                                Taking Photo...
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Please wait while we capture your verification photo.
                            </p>
                            <div class="mt-4">
                                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
    }

    /**
     * Hide photo capture progress modal
     */
    static hideCaptureProgressModal() {
        const modal = document.getElementById('capture-progress-modal');
        if (modal) {
            modal.remove();
        }
    }

    /**
     * Show photo capture success modal
     */
    static showCaptureSuccessModal() {
        const modalHTML = `
            <div id="capture-success-modal" class="fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-50">
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
                        <div class="text-center">
                            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 dark:bg-green-900 mb-4">
                                <i class="fas fa-check text-green-600 dark:text-green-400 text-xl"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                                Photo Captured Successfully
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                Your verification photo has been taken. Submitting quiz...
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);

        // Auto-hide after 2 seconds
        setTimeout(() => {
            const modal = document.getElementById('capture-success-modal');
            if (modal) {
                modal.remove();
            }
        }, 2000);
    }
}

// Global function to handle quiz submission with photo capture
window.capturePhotoAndSubmit = async function(livewireComponent, method = 'submitQuiz', ...args) {
    try {
        // Check if camera is supported
        if (!CameraCapture.isSupported()) {
            console.warn('Camera not supported, submitting without photo');
            return livewireComponent.call(method, ...args);
        }

        // Show permission modal
        const allowCamera = await CameraCapture.showCameraPermissionModal();
        
        if (!allowCamera) {
            return livewireComponent.call(method, ...args);
        }

        // Show capture progress
        CameraCapture.showCaptureProgressModal();

        // Capture photo
        const camera = new CameraCapture();
        const photoResult = await camera.capturePhoto();

        // Hide progress modal
        CameraCapture.hideCaptureProgressModal();

        if (photoResult.error) {
            // Show error message and continue without photo
            alert(`Camera error: ${photoResult.message}. Continuing without photo.`);
            return livewireComponent.call(method, ...args);
        }

        // Show success modal
        CameraCapture.showCaptureSuccessModal();

        // Submit quiz with photo data
        return livewireComponent.call(method, photoResult, ...args);

    } catch (error) {
        // Hide any open modals
        CameraCapture.hideCaptureProgressModal();
        
        // Continue with normal submission
        alert('Photo capture failed. Submitting quiz without photo.');
        return livewireComponent.call(method, ...args);
    }
};

// Export for use in other files
window.CameraCapture = CameraCapture;