// QR Scanner utilities - loaded on demand
import QrScanner from 'qr-scanner';

// Override HTMLCanvasElement.getContext for QR scanning optimization
const originalGetContext = HTMLCanvasElement.prototype.getContext;
HTMLCanvasElement.prototype.getContext = function(contextType, contextAttributes = {}) {
    if (contextType === '2d' && !contextAttributes.hasOwnProperty('willReadFrequently')) {
        contextAttributes.willReadFrequently = true;
    }
    return originalGetContext.call(this, contextType, contextAttributes);
};

// Make QrScanner available globally
window.QrScanner = QrScanner;

export default QrScanner;