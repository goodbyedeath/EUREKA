/**
 * QR Scanner Alpine.js Component
 * Replaces the Livewire QRScannerModal component
 */

export default function (Alpine) {
    Alpine.data('qrScanner', () => ({
        // State
        showModal: false,
        scannedCode: '',
        questionnaire: null,
        questionnaireId: null,
        error: '',
        isScanning: false,

        // QR Scanner instance
        qrScannerInstance: null,

        /**
         * Initialize component
         */
        init() {
            // Listen for open-qr-scanner event
            window.addEventListener('open-qr-scanner', () => {
                this.openModal();
            });

            // Cleanup on page unload
            window.addEventListener('beforeunload', () => {
                this.cleanup();
            });

            // Cleanup when tab becomes hidden
            document.addEventListener('visibilitychange', () => {
                if (document.hidden && this.qrScannerInstance) {
                    this.cleanup();
                }
            });
        },

        /**
         * Open modal
         */
        openModal() {
            this.showModal = true;
            this.error = '';
            this.questionnaire = null;
            this.questionnaireId = null;
            this.scannedCode = '';
            this.isScanning = false;

            console.log('QR Scanner modal opened');
        },

        /**
         * Close modal
         */
        closeModal() {
            this.showModal = false;
            this.isScanning = false;
            this.scannedCode = '';
            this.questionnaire = null;
            this.questionnaireId = null;
            this.error = '';
            this.cleanup();
        },

        /**
         * Start scanning
         */
        async startScanning() {
            this.isScanning = true;
            this.error = '';

            // Wait for DOM update
            await this.$nextTick();

            // Initialize scanner after short delay
            setTimeout(() => {
                this.initializeQRScanner();
            }, 300);
        },

        /**
         * Stop scanning
         */
        stopScanning() {
            this.isScanning = false;
            this.cleanup();
        },

        /**
         * Initialize QR Scanner
         */
        async initializeQRScanner() {
            if (this.qrScannerInstance) {
                try {
                    await this.qrScannerInstance.stop();
                    await this.qrScannerInstance.destroy();
                } catch (e) {
                    // Ignore cleanup errors
                }
                this.qrScannerInstance = null;
            }

            try {
                // Check camera support
                const cameraSupported = await this.checkCameraSupport();
                if (!cameraSupported) {
                    throw new Error('Camera not supported or not available');
                }

                // Request camera permission
                const permissionGranted = await this.requestCameraPermission();
                if (!permissionGranted) {
                    throw new Error('Camera permission denied');
                }

                // Load QR Scanner library
                let QrScanner;
                if (window.QrScanner) {
                    QrScanner = window.QrScanner;
                } else {
                    const module = await import('qr-scanner');
                    QrScanner = module.default;
                    window.QrScanner = QrScanner;
                }

                const video = document.getElementById('qr-video');
                if (!video) {
                    this.error = 'Video element not found. Please try again.';
                    this.isScanning = false;
                    return;
                }

                // Initialize QR Scanner
                this.qrScannerInstance = new QrScanner(
                    video,
                    (result) => {
                        this.handleQRScanned(result.data);
                    },
                    {
                        returnDetailedScanResult: true,
                        highlightScanRegion: true,
                        highlightCodeOutline: true,
                        maxScansPerSecond: 3,
                        preferredCamera: 'environment'
                    }
                );

                await this.qrScannerInstance.start();

                // Check if video is working
                setTimeout(() => {
                    if (video.videoWidth === 0 || video.videoHeight === 0) {
                        this.error = 'Camera feed not detected. Please check camera permissions and try again.';
                        this.isScanning = false;
                    }
                }, 2000);

            } catch (error) {
                this.error = this.getCameraErrorMessage(error);
                this.isScanning = false;
            }
        },

        /**
         * Check camera support
         */
        async checkCameraSupport() {
            try {
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    throw new Error('Camera not supported in this browser');
                }

                if (location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
                    throw new Error('Camera access requires HTTPS');
                }

                const devices = await navigator.mediaDevices.enumerateDevices();
                const cameras = devices.filter(device => device.kind === 'videoinput');

                if (cameras.length === 0) {
                    throw new Error('No cameras found');
                }

                return true;
            } catch (error) {
                return false;
            }
        },

        /**
         * Request camera permission
         */
        async requestCameraPermission() {
            try {
                const constraints = {
                    video: {
                        facingMode: { ideal: 'environment' },
                        width: { ideal: 640 },
                        height: { ideal: 480 }
                    }
                };

                const stream = await navigator.mediaDevices.getUserMedia(constraints);
                stream.getTracks().forEach(track => track.stop());

                return true;
            } catch (error) {
                return false;
            }
        },

        /**
         * Get camera error message
         */
        getCameraErrorMessage(error) {
            if (error.message.includes('HTTPS')) {
                return 'Camera access requires HTTPS. Please use a secure connection.';
            } else if (error.name === 'NotAllowedError' || error.message.includes('permission denied')) {
                return 'Camera access denied. Please allow camera access in your browser settings and try again.';
            } else if (error.name === 'NotFoundError' || error.message.includes('No cameras found')) {
                return 'No camera found. Please connect a camera and try again.';
            } else if (error.name === 'NotReadableError') {
                return 'Camera is already in use by another application. Please close other apps using the camera.';
            } else if (error.name === 'NotSupportedError' || error.message.includes('not supported')) {
                return 'QR scanner is not supported on this device/browser. Please try a different browser.';
            } else if (error.name === 'AbortError') {
                return 'Camera access was interrupted. Please try again.';
            } else {
                return `Camera initialization failed: ${error.message}`;
            }
        },

        /**
         * Handle QR code scanned
         */
        async handleQRScanned(qrCode) {
            // Sanitize input
            qrCode = typeof qrCode === 'string' ? qrCode.trim() : '';

            if (!qrCode || qrCode.length > 255) {
                this.error = 'Invalid QR code detected.';
                return;
            }

            this.scannedCode = qrCode;
            this.isScanning = false;
            this.cleanup();

            await this.lookupQuestionnaire(qrCode);
        },

        /**
         * Submit manual code
         */
        async submitManualCode() {
            const code = this.scannedCode.trim();

            if (!code) {
                this.error = 'Please enter a QR code.';
                return;
            }

            if (code.length > 255) {
                this.error = 'QR code is too long. Please enter a valid code.';
                return;
            }

            // Validate format
            if (!/^[a-zA-Z0-9\-_]+$/.test(code)) {
                this.error = 'QR code contains invalid characters. Please check the code and try again.';
                return;
            }

            this.error = '';
            await this.lookupQuestionnaire(code);
        },

        /**
         * Lookup questionnaire
         */
        async lookupQuestionnaire(code) {
            try {
                const response = await fetch('/api/qr-scanner/lookup', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ qr_code: code })
                });

                // Check if response is HTML (login page redirect)
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('text/html')) {
                    console.error('Received HTML response instead of JSON - likely redirected to login');
                    this.error = 'Session expired. Please refresh the page and log in again.';
                    this.questionnaire = null;
                    this.questionnaireId = null;

                    // Reload page after 2 seconds
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                    return;
                }

                const result = await response.json();

                // Handle authentication errors
                if (response.status === 401 || result.error === 'unauthenticated') {
                    this.error = 'Session expired. Please refresh the page and log in again.';
                    this.questionnaire = null;
                    this.questionnaireId = null;

                    // Reload page after 2 seconds
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                    return;
                }

                // The START code is not a quiz. It comes back as its own type with a
                // redirect, and the branch below assumes result.questionnaire exists — so
                // without this, scanning START at the start line threw a TypeError and the
                // scanner simply died with no message.
                if (result.success && result.type === 'race_start' && result.redirect) {
                    this.error = '';
                    this.closeModal();
                    window.location.href = result.redirect;
                    return;
                }

                if (result.success) {
                    this.questionnaire = result.questionnaire;
                    this.questionnaireId = result.questionnaire.id;
                    this.error = '';

                    console.log('QR Code validated, redirecting to quiz:', this.questionnaireId);

                    // Dispatch event for dashboard
                    window.dispatchEvent(new CustomEvent('qr-code-scanned', {
                        detail: { qr_code: code }
                    }));

                    // Save questionnaire ID before closing modal (closeModal() sets it to null)
                    const savedQuestionnaireId = this.questionnaireId;

                    // Redirect directly to quiz (skip preview)
                    this.closeModal();

                    const redirectUrl = `/quiz/take/${savedQuestionnaireId}`;
                    console.log('Redirecting to:', redirectUrl);
                    window.location.href = redirectUrl;
                } else {
                    this.error = result.message || 'Questionnaire not found or inactive';
                    this.questionnaire = null;
                    this.questionnaireId = null;
                }
            } catch (error) {
                console.error('QR Scanner error:', error);

                // Check if error is JSON parsing error (HTML response)
                if (error.message && error.message.includes('JSON')) {
                    this.error = 'Session expired. Please refresh the page and log in again.';

                    // Reload page after 2 seconds
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    this.error = 'An error occurred while processing the QR code. Please try again.';
                }

                this.questionnaire = null;
                this.questionnaireId = null;
            }
        },

        /**
         * Start quiz
         */
        startQuiz() {
            if (this.questionnaireId) {
                this.closeModal();
                window.location.href = `/quiz/start/${this.questionnaireId}`;
            } else {
                this.error = 'No questionnaire selected. Please scan a QR code first.';
            }
        },

        /**
         * Cleanup scanner
         */
        async cleanup() {
            if (this.qrScannerInstance) {
                try {
                    await this.qrScannerInstance.stop();
                    await this.qrScannerInstance.destroy();
                } catch (error) {
                    // Ignore cleanup errors
                }
                this.qrScannerInstance = null;
            }
        },

        /**
         * Set scanned code (for test buttons)
         */
        setCode(code) {
            this.scannedCode = code;
        }
    }));
}
