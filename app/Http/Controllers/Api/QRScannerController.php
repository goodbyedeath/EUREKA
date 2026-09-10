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
        // Check authentication first
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required. Please log in and try again.',
                'error' => 'unauthenticated'
            ], 401);
        }

        $request->validate([
            'qr_code' => 'required|string|max:255'
        ]);

        $qrCode = trim($request->qr_code);

        try {
            // A venue's START code opens the run, not a quiz. Checked first so a start
            // code can never be mistaken for a missing questionnaire.
            $startCode = \App\Models\RaceStart::where('code', $qrCode)
                ->where('is_active', true)
                ->first();

            if ($startCode) {
                return response()->json([
                    'success' => true,
                    'type' => 'race_start',
                    // The browser follows the redirect. A native client cannot — it posts
                    // the code back to /api/v1/race/start, which answers in the body.
                    'code' => $startCode->code,
                    'mode' => $startCode->isIndoor() ? 'indoor' : 'outdoor',
                    'redirect' => route('user.race.start', $qrCode),
                    'message' => 'Race start code.',
                ]);
            }

            // Looked up by code alone. Filtering is_active inside the query made a
            // switched-off questionnaire fall through to unknown_code, so a scanner could
            // not tell a misread QR from a facilitator who forgot to activate the station —
            // and on event day those need completely different responses from the crew.
            $questionnaire = Questionnaire::where('qr_code', $qrCode)->first();

            // Every other refusal on this endpoint carries an error key and a real status
            // code; these three answered 200 with only a sentence, so a client had to
            // string-match to tell "wrong code" from "out of attempts".
            if (!$questionnaire) {
                return response()->json([
                    'success' => false,
                    'error' => 'unknown_code',
                    'message' => 'This code does not belong to this event.'
                ], 404);
            }

            if (!$questionnaire->is_active) {
                return response()->json([
                    'success' => false,
                    'error' => 'not_available',
                    'reason' => 'inactive',
                    'message' => 'This station is switched off. Ask the crew to enable it.'
                ], 403);
            }

            // Check if questionnaire is available (date range, etc.)
            if (!$questionnaire->isAvailable()) {
                return response()->json([
                    'success' => false,
                    'error' => 'not_available',
                    'message' => 'This questionnaire is not currently available'
                ], 403);
            }

            // Check if user can scan this questionnaire
            if (!QrCodeScan::canUserScanQuestionnaire(Auth::id(), $questionnaire)) {
                return response()->json([
                    'success' => false,
                    'error' => 'max_attempts_reached',
                    'message' => 'You have reached the maximum number of attempts for this questionnaire'
                ], 403);
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
                // Race starts already answer with a type; questionnaires did not, so a client
                // had to infer the kind from which key happened to be present.
                'type' => 'questionnaire',
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
