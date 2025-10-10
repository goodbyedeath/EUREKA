<?php

namespace App\Livewire\User;

use Livewire\Component;
use App\Models\Questionnaire;
use App\Models\QrCodeScan;

class QRScannerModal extends Component
{
    public $showModal = false;
    public $scannedCode = '';
    public $questionnaire = null;
    public $questionnaireId = null;
    public $error = '';
    public $isScanning = false;

    protected $listeners = [
        'open-qr-scanner' => 'openModal'
    ];

    public function startScanning()
    {
        $this->isScanning = true;
        $this->error = '';
        $this->dispatch('start-qr-scanner');
    }

    public function stopScanning()
    {
        $this->isScanning = false;
        $this->dispatch('stop-qr-scanner');
    }


    public function handleQRScanned($qrCode)
    {
        // Sanitize input
        $qrCode = is_string($qrCode) ? trim($qrCode) : '';
        
        if (empty($qrCode) || strlen($qrCode) > 255) {
            $this->error = 'Invalid QR code detected.';
            return;
        }
        
        $this->scannedCode = $qrCode;
        $this->isScanning = false;
        $this->lookupQuestionnaire($qrCode);
        $this->dispatch('stop-qr-scanner');
    }

    public function lookupQuestionnaire($code)
    {
        try {
            $this->questionnaire = Questionnaire::where('qr_code', $code)
                ->where('is_active', true)
                ->first();

            if (!$this->questionnaire) {
                $this->error = 'Questionnaire not found or inactive';
                return;
            }

            // Check if questionnaire is available (date range, etc.)
            if (!$this->questionnaire->isAvailable()) {
                $this->error = 'This questionnaire is not currently available';
                return;
            }

            // Check if user can scan this questionnaire
            if (!QrCodeScan::canUserScanQuestionnaire(auth()->id(), $this->questionnaire)) {
                $this->error = 'You have reached the maximum number of attempts for this questionnaire';
                $this->questionnaire = null;
                return;
            }

            // Store questionnaire ID separately for reliability
            $this->questionnaireId = $this->questionnaire->id;

            // Record the QR code scan
            QrCodeScan::recordScan(auth()->id(), $this->questionnaire->id, $code);

            // Dispatch event to show the questionnaire in available quizzes
            $this->dispatch('qr-code-scanned', qr_code: $code);
            
            // Don't close modal immediately - let user see questionnaire and click "Start Quiz"
            // Modal will close when user clicks "Start Quiz" or "Close" button
            
        } catch (\Exception $e) {
            \Log::error('QR Scanner error: ' . $e->getMessage());
            $this->error = 'An error occurred while processing the QR code. Please try again.';
        }
    }

    public function manualEntry($code = null)
    {
        // Use the scannedCode property if no parameter passed
        if ($code === null) {
            $code = $this->scannedCode;
        }
        
        $code = is_string($code) ? trim($code) : '';
        
        if (empty($code)) {
            $this->error = 'Please enter a QR code.';
            return;
        }
        
        if (strlen($code) > 255) {
            $this->error = 'QR code is too long. Please enter a valid code.';
            return;
        }
        
        $this->error = ''; // Clear any previous errors
        $this->handleQRScanned($code);
    }

    public function submitManualCode()
    {
        $code = trim($this->scannedCode);
        
        // Debug log
        \Log::info('Manual entry attempted', [
            'scannedCode' => $this->scannedCode,
            'trimmed_code' => $code,
            'user_id' => auth()->id()
        ]);
        
        if (empty($code)) {
            $this->error = 'Please enter a QR code.';
            return;
        }
        
        if (strlen($code) > 255) {
            $this->error = 'QR code is too long. Please enter a valid code.';
            return;
        }
        
        // Additional validation for QR code format (optional)
        if (!preg_match('/^[a-zA-Z0-9\-_]+$/', $code)) {
            $this->error = 'QR code contains invalid characters. Please check the code and try again.';
            return;
        }
        
        $this->error = ''; // Clear any previous errors
        $this->lookupQuestionnaire($code);
    }

    public function openModal()
    {
        $this->showModal = true;
        $this->reset(['error', 'questionnaire', 'questionnaireId', 'scannedCode', 'isScanning']);
        
        // Debug log
        \Log::info('QR Scanner modal opened', ['user_id' => auth()->id()]);
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->isScanning = false;
        $this->scannedCode = '';
        $this->questionnaire = null;
        $this->questionnaireId = null;
        $this->error = '';
        $this->dispatch('cleanup-qr-scanner');
    }

    // Method to refresh questionnaire if needed
    public function refreshQuestionnaire()
    {
        if ($this->scannedCode && (empty($this->questionnaire) || empty($this->questionnaireId))) {
            $this->lookupQuestionnaire($this->scannedCode);
        }
    }

    // Emergency fallback to get questionnaire ID from scanned code
    private function getQuestionnaireIdFromCode()
    {
        if (empty($this->scannedCode)) {
            return null;
        }

        try {
            $questionnaire = Questionnaire::where('qr_code', $this->scannedCode)
                ->where('is_active', true)
                ->first();
            
            return $questionnaire ? $questionnaire->id : null;
        } catch (\Exception $e) {
            \Log::error('Emergency questionnaire lookup failed', [
                'code' => $this->scannedCode,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function startQuiz()
    {
        // Get questionnaire ID from stored property or object
        $questionnaireId = null;
        
        if ($this->questionnaireId) {
            $questionnaireId = $this->questionnaireId;
        } elseif ($this->questionnaire && is_object($this->questionnaire) && property_exists($this->questionnaire, 'id')) {
            $questionnaireId = $this->questionnaire->id;
        } else {
            // Emergency fallback - try to get ID from scanned code
            $questionnaireId = $this->getQuestionnaireIdFromCode();
        }

        if ($questionnaireId) {
            \Log::info('Starting quiz from QR scanner', [
                'questionnaire_id' => $questionnaireId,
                'user_id' => auth()->id(),
                'has_questionnaire_object' => !empty($this->questionnaire),
                'stored_id' => $this->questionnaireId
            ]);
            
            $this->closeModal();
            return redirect()->route('quiz.start', ['questionnaireId' => $questionnaireId]);
        } else {
            $this->error = 'No questionnaire selected. Please scan a QR code first.';
            \Log::warning('Attempted to start quiz without valid questionnaire', [
                'questionnaire' => $this->questionnaire,
                'questionnaire_id' => $this->questionnaireId,
                'scanned_code' => $this->scannedCode,
                'user_id' => auth()->id()
            ]);
        }
    }

    public function render()
    {
        return view('livewire.user.q-r-scanner-modal');
    }
}