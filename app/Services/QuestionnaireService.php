<?php
// app/Services/QuestionnaireService.php

namespace App\Services;

use App\Models\Questionnaire;
use Illuminate\Support\Str;

class QuestionnaireService
{
    /**
     * The one generator of questionnaire QR codes — creating, duplicating a floor plan and "new QR"
     * in the QR Code Manager all come through here.
     *
     * A UUID, as the create form always made. This used to be eight random characters while the
     * create form made UUIDs, so a code's strength depended on how the questionnaire came to exist.
     * It matters now: the printed code is also the key that seals the questionnaire's offline copy
     * (OfflineQuestionnaireSeal), so it has to be out of guessing range — and it has to be the same
     * code, not a second one, so nothing already printed changes.
     */
    public static function generateUniqueQrCode(): string
    {
        do {
            $code = (string) Str::uuid();
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