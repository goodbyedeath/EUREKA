<?php
// app/Livewire/Admin/QrCodeManager.php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Questionnaire;
use App\Services\QuestionnaireService;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Str;

class QRCodeManager extends Component
{
    public Questionnaire $questionnaire;

     public function mount(Questionnaire $questionnaire)
    {
        // Load the questionnaire with questions count
        $this->questionnaire = $questionnaire->loadCount('questions');
        
        // Ensure the questionnaire has a QR code
        QuestionnaireService::ensureQrCode($this->questionnaire);
        // Refresh the model to get the updated qr_code while preserving the count
        $this->questionnaire->refresh();
        $this->questionnaire->loadCount('questions');
    }

    public function generateQrCode($size = 200)
    {
        return QrCode::size($size)
                    ->margin(2)
                    ->backgroundColor(255, 255, 255)
                    ->color(0, 0, 0)
                    ->generate($this->getQrCodeContent());
    }

    public function getQrCodeContent()
    {
        // Return only the unique code, not the URL
        return $this->questionnaire->qr_code;
    }

    public function downloadQrCode($format = 'png', $size = 512)
    {
        try {
            $qrCode = QrCode::format($format)
                           ->size($size)
                           ->margin(4)
                           ->backgroundColor(255, 255, 255)
                           ->color(0, 0, 0)
                           ->errorCorrection('M')
                           ->generate($this->getQrCodeContent());

            $filename = 'qr-code-' . Str::slug($this->questionnaire->title) . '.' . $format;

            return response()->streamDownload(function () use ($qrCode) {
                echo $qrCode;
            }, $filename, ['Content-Type' => 'image/' . $format]);
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to download QR code: ' . $e->getMessage());
            return null;
        }
    }

    public function regenerateQrCode()
    {
        $this->questionnaire->update([
            'qr_code' => QuestionnaireService::generateUniqueQrCode()
        ]);
        $this->questionnaire->refresh();
        session()->flash('message', 'QR Code regenerated successfully!');
    }

    public function copyQrCode()
    {
        session()->flash('message', 'QR Code copied to clipboard!');
    }

    public function printQrCode()
    {
        $this->dispatch('print-qr-code');
    }

    public function shareQrCode()
    {
        $this->dispatch('share-qr-code', [
            'title' => $this->questionnaire->title,
            'code' => $this->questionnaire->qr_code
        ]);
    }

    public function getQuestionsCountProperty()
    {
        // Fallback for questions count if not loaded via withCount
        return $this->questionnaire->questions_count ?? $this->questionnaire->questions()->count();
    }


    public function render()
    {
        return view('livewire.admin.q-r-code-manager');
    }
}
    