import './bootstrap';

// Import and expose QR Scanner for global use
import QrScanner from 'qr-scanner';

// Set the worker path for QR Scanner (auto-detect from build)
// The worker file is automatically bundled by Vite

// Override HTMLCanvasElement.getContext to automatically set willReadFrequently for QR scanning
const originalGetContext = HTMLCanvasElement.prototype.getContext;
HTMLCanvasElement.prototype.getContext = function(contextType, contextAttributes = {}) {
    // For 2D contexts used in QR scanning, automatically set willReadFrequently
    if (contextType === '2d' && !contextAttributes.hasOwnProperty('willReadFrequently')) {
        // Check if this canvas is likely being used for QR scanning (frequent getImageData calls)
        // We'll set it to true by default for all 2D contexts to prevent the warning
        contextAttributes.willReadFrequently = true;
    }
    return originalGetContext.call(this, contextType, contextAttributes);
};

// Make QrScanner available globally for Livewire components
window.QrScanner = QrScanner;

