import './bootstrap';

// Import and expose QR Scanner for global use
import QrScanner from 'qr-scanner';

// Set the worker path for QR Scanner (auto-detect from build)
// The worker file is automatically bundled by Vite

// Make QrScanner available globally for Livewire components
window.QrScanner = QrScanner;

