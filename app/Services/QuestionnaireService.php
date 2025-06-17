<?php
// app/Services/QuestionnaireService.php

namespace App\Services;

use App\Models\Questionnaire;
use Illuminate\Support\Str;

class QuestionnaireService
{
    /**
     * Generate a unique QR code for a questionnaire
     */
    public static function generateUniqueQrCode(): string
    {
        do {
            // Generate a random 8-character code
            $code = strtoupper(Str::random(8));
        } while (Questionnaire::where('qr_code', $code)->exists());

        return $code;
    }

    /**
     * Ensure questionnaire has a QR code
     */
    public static function ensureQrCode(Questionnaire $questionnaire): string
    {
        if (empty($questionnaire->qr_code)) {
            $questionnaire->update([
                'qr_code' => self::generateUniqueQrCode()
            ]);
        }

        return $questionnaire->qr_code;
    }
}