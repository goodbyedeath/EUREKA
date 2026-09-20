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

    /**
     * When the race was last stopped, for a client to compare against its own cache.
     *
     * A stop leaves the server consistent — no clocks, no opened posts, every gate shut — but a client
     * that cached a list at Sync keeps painting the old world until something tells it not to. The
     * operator saw exactly that on 20 Sep: the 3D camera still listed posts as open while entry was
     * correctly refused. This is the something.
     */
    public static function lastAt(): ?\Illuminate\Support\Carbon
    {
        return static::query()->max('reset_at')
            ? \Illuminate\Support\Carbon::parse(static::query()->max('reset_at'))
            : null;
    }

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
