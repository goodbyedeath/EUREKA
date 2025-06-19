<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrCodeScan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'questionnaire_id',
        'qr_code',
        'scanned_at',
        'is_active'
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
        'is_active' => 'boolean'
    ];

    /**
     * Get the user who scanned the QR code.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the questionnaire associated with the scan.
     */
    public function questionnaire(): BelongsTo
    {
        return $this->belongsTo(Questionnaire::class);
    }

    /**
     * Check if user has already scanned this QR code for a questionnaire with max attempts.
     */
    public static function hasUserScannedQuestionnaire(int $userId, int $questionnaireId): bool
    {
        return self::where('user_id', $userId)
            ->where('questionnaire_id', $questionnaireId)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Record a QR code scan for a user and questionnaire.
     */
    public static function recordScan(int $userId, int $questionnaireId, string $qrCode): self
    {
        // Check if there's already an active scan for this user and questionnaire
        $existingScan = self::where('user_id', $userId)
            ->where('questionnaire_id', $questionnaireId)
            ->where('is_active', true)
            ->first();

        if ($existingScan) {
            // Update the existing scan timestamp
            $existingScan->update(['scanned_at' => now()]);
            return $existingScan;
        }

        return self::create([
            'user_id' => $userId,
            'questionnaire_id' => $questionnaireId,
            'qr_code' => $qrCode,
            'scanned_at' => now(),
            'is_active' => true
        ]);
    }

    /**
     * Check if user can scan this questionnaire based on max attempts and existing scans.
     */
    public static function canUserScanQuestionnaire(int $userId, Questionnaire $questionnaire): bool
    {
        // If questionnaire has no max attempts limit, user can always scan
        if (!$questionnaire->max_attempts) {
            return true;
        }

        // Check actual completed attempts first
        $completedAttempts = QuizAttempt::where('user_id', $userId)
            ->where('questionnaire_id', $questionnaire->id)
            ->where('status', QuizAttempt::STATUS_COMPLETED)
            ->count();

        // If user has already reached the max attempts, they cannot scan
        if ($completedAttempts >= $questionnaire->max_attempts) {
            // Auto-deactivate any active scans for completed questionnaires
            self::deactivateScan($userId, $questionnaire->id);
            return false;
        }

        // For questionnaires with max attempts = 1, allow scanning if:
        // 1. User hasn't completed any attempts yet, OR
        // 2. User hasn't scanned yet (first time)
        if ($questionnaire->max_attempts === 1) {
            // If no completed attempts, allow scanning (even if they scanned before but didn't complete)
            if ($completedAttempts === 0) {
                return true;
            }
            // If they have completed attempts, don't allow more scans
            return false;
        }

        return true;
    }

    /**
     * Deactivate scan when user completes or abandons the questionnaire.
     */
    public static function deactivateScan(int $userId, int $questionnaireId): void
    {
        self::where('user_id', $userId)
            ->where('questionnaire_id', $questionnaireId)
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }
}