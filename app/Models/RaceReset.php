<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/** An emergency race stop — see App\Services\RaceEmergencyStop. */
class RaceReset extends Model
{
    public const MESSAGE = 'Race dihentikan oleh panitia. Semua poin dan progres direset — scan QR START untuk memulai lagi.';

    protected $fillable = ['reset_by', 'reset_at', 'summary'];

    protected $casts = [
        'reset_at' => 'datetime',
        'summary' => 'array',
    ];

    /** The stop that deleted this attempt or assessment, if one did. Auto-increment ids are never reused. */
    public static function forRef(string $kind, int $id): ?self
    {
        $resetId = DB::table('race_reset_refs')->where('kind', $kind)->where('ref_id', $id)->value('race_reset_id');

        return $resetId ? static::find($resetId) : null;
    }

    public static function response(self $reset): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => 'race_reset',
            'race_reset' => true,
            'message' => self::MESSAGE,
            'reset_at' => $reset->reset_at?->toIso8601String(),
        ], 409);
    }
}
