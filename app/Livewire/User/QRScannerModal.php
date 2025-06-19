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
        'qr-scanned' => 'handleQRScanned',
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

            // Check if user can scan this questionnaire (using new QrCodeScan model)
            if (!QrCodeScan::canUserScanQuestionnaire(auth()->id(), $this->questionnaire)) {
                // Only set session flash if one doesn't already exist to prevent duplicates
                if (!session()->has('error')) {
                    session()->flash('error', 'You have reached the maximum number of attempts for this questionnaire');
                }
                $this->error = 'You have reached the maximum number of attempts for this questionnaire';
                
                // Set questionnaire to null to prevent showing the start quiz button
                $this->questionnaire = null;
                return;
            }

            // Record the QR code scan
            QrCodeScan::recordScan(auth()->id(), $this->questionnaire->id, $code);

            // Success - questionnaire found and user can access it
            if (!session()->has('success') && !session()->has('error')) {
                session()->flash('success', 'Questionnaire unlocked successfully!');
            }
            
            // Dispatch event to show the questionnaire in available quizzes
            $this->dispatch('qr-code-scanned', qr_code: $code);
            
            // Close the modal after successful scan
            $this->closeModal();
            
        } catch (\Exception $e) {
            $this->error = 'Error looking up questionnaire: ' . $e->getMessage();
        }
    }

    public function manualEntry($code)
    {
        if (!empty($code)) {
            $this->handleQRScanned($code);
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