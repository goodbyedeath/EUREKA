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

            // Record the QR code scan
            QrCodeScan::recordScan(auth()->id(), $this->questionnaire->id, $code);

            // Dispatch event to show the questionnaire in available quizzes
            $this->dispatch('qr-code-scanned', qr_code: $code);
            
            // Close the modal after successful scan
            $this->closeModal();
            
        } catch (\Exception $e) {
            \Log::error('QR Scanner error: ' . $e->getMessage());
            $this->error = 'An error occurred while processing the QR code. Please try again.';
        }
    }

    public function manualEntry($code)
    {
        $code = is_string($code) ? trim($code) : '';
        if (!empty($code) && strlen($code) <= 255) {
            $this->handleQRScanned($code);
        } else {
            $this->error = 'Please enter a valid QR code.';
        }
    }

    public function openModal()
    {
        $this->showModal = true;
        $this->reset(['error', 'questionnaire', 'scannedCode', 'isScanning']);
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->isScanning = false;
        $this->scannedCode = '';
        $this->questionnaire = null;
        $this->error = '';
        $this->dispatch('cleanup-qr-scanner');
    }

    public function startQuiz()
    {
        if ($this->questionnaire) {
            return redirect()->route('quiz.start', ['questionnaireId' => $this->questionnaire->id]);
        }
    }

    public function render()
    {
        return view('livewire.user.q-r-scanner-modal');
    }
}