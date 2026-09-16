<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A pin an admin dropped on a tracker route. Promote it and it becomes a quest location. */
class MapRouteMarker extends Model
{
    protected $fillable = [
        'map_route_id',
        'source_key',
        'title',
        'description',
        'icon',
        'color',
        'latitude',
        'longitude',
        'position',
        'quest_location_id',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'position' => 'integer',
    ];

    public function route(): BelongsTo
    {
        return $this->belongsTo(MapRoute::class, 'map_route_id');
    }

    public function questLocation(): BelongsTo
    {
        return $this->belongsTo(QuestLocation::class);
    }

    /** Stable across syncs: the tracker has no id of its own for a marker. */
    public static function keyFor(array $marker): string
    {
        return sha1(implode('|', [
            trim((string) ($marker['title'] ?? '')),
            round((float) ($marker['lat'] ?? 0), 6),
            round((float) ($marker['lng'] ?? 0), 6),
        ]));
    }
}
