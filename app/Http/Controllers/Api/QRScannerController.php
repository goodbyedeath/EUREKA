<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Questionnaire;
use App\Models\QrCodeScan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class QRScannerController extends Controller
{
    /**
     * Lookup questionnaire by QR code
     */
    public function lookup(Request $request)
    {
        $request->validate([
            'qr_code' => 'required|string|max:255'
        ]);

        $qrCode = trim($request->qr_code);

        try {
            $questionnaire = Questionnaire::where('qr_code', $qrCode)
                ->where('is_active', true)
                ->first();

            if (!$questionnaire) {
                return response()->json([
                    'success' => false,
                    'message' => 'Questionnaire not found or inactive'
                ]);
            }

            // Check if questionnaire is available (date range, etc.)
            if (!$questionnaire->isAvailable()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This questionnaire is not currently available'
                ]);
            }

            // Check if user can scan this questionnaire
            if (!QrCodeScan::canUserScanQuestionnaire(Auth::id(), $questionnaire)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have reached the maximum number of attempts for this questionnaire'
                ]);
            }

            // Record the QR code scan
            QrCodeScan::recordScan(Auth::id(), $questionnaire->id, $qrCode);

            Log::info('QR code scanned successfully', [
                'user_id' => Auth::id(),
                'questionnaire_id' => $questionnaire->id,
                'qr_code' => $qrCode
            ]);

            return response()->json([
                'success' => true,
                'questionnaire' => [
                    'id' => $questionnaire->id,
                    'title' => $questionnaire->title,
                    'description' => $questionnaire->description,
                    'time_limit' => $questionnaire->time_limit,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('QR Scanner lookup error', [
                'user_id' => Auth::id(),
                'qr_code' => $qrCode,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the QR code. Please try again.'
            ], 500);
        }
    }
}
