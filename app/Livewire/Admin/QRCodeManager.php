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
        $this->questionnaire = $questionnaire;
        // Ensure the questionnaire has a QR code
        QuestionnaireService::ensureQrCode($this->questionnaire);
        // Refresh the model to get the updated qr_code
        $this->questionnaire->refresh();
    }

    public function generateQrCode()
    {
        return QrCode::size(150)->generate($this->getQrCodeContent());
    }

    public function getQrCodeContent()
    {
        // Return only the unique code, not the URL
        return $this->questionnaire->qr_code;
    }

    public function downloadQrCode()
    {
        try {
            $qrCode = QrCode::format('png')
                           ->size(300)
                           ->margin(2)
                           ->generate($this->getQrCodeContent());

            $filename = 'qr-code-' . Str::slug($this->questionnaire->title) . '.png';

            return response()->streamDownload(function () use ($qrCode) {
                echo $qrCode;
            }, $filename, ['Content-Type' => 'image/png']);
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

    public function render()
    {
        return view('livewire.admin.q-r-code-manager');
    }
}
    