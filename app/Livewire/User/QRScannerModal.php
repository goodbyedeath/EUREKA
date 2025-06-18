<?php

namespace App\Livewire\User;

use Livewire\Component;
use App\Models\Questionnaire;

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

            // Check if user can attempt this questionnaire
            if (!$this->questionnaire->canUserAttempt(auth()->id())) {
                $this->error = 'You have reached the maximum number of attempts for this questionnaire';
                return;
            }

            // Success - questionnaire found and user can access it
            session()->flash('success', 'Questionnaire unlocked successfully!');
            
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