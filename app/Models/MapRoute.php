<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A route recorded in the GPS Tracker app and copied into EUREKA (see the migration). */
class MapRoute extends Model
{
    protected $fillable = [
        'tracker_session_id',
        'name',
        'recorded_by',
        'color',
        'distance_m',
        'points',
        'point_count',
        'recorded_at',
        'is_active',
        'synced_at',
    ];

    protected $casts = [
        'points' => 'array',
        'is_active' => 'boolean',
        'recorded_at' => 'datetime',
        'synced_at' => 'datetime',
        'distance_m' => 'integer',
        'point_count' => 'integer',
    ];

    public function markers(): HasMany
    {
        return $this->hasMany(MapRouteMarker::class)->orderBy('position');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function distanceForHumans(): string
    {
        $m = (int) $this->distance_m;

        return $m >= 1000 ? number_format($m / 1000, 2).' km' : $m.' m';
    }

    /**
     * What a client draws: the line, and the pins that are not posts.
     *
     * A marker that has become a quest location is left out on purpose — the post already comes
     * from /quest-locations with its radius and check-in, and drawing both puts two pins on one
     * spot. `quest_location_id` is still reported so a client can highlight the post on the line.
     */
    public function toMapPayload(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'color' => $this->color ?: '#2563eb',
            'distance_m' => (int) $this->distance_m,
            'points' => $this->points ?: [],
            'markers' => $this->markers
                ->whereNull('quest_location_id')
                ->map(fn (MapRouteMarker $m) => [
                    'title' => $m->title,
                    'description' => $m->description,
                    'icon' => $m->icon,
                    'color' => $m->color,
                    'latitude' => (float) $m->latitude,
                    'longitude' => (float) $m->longitude,
                ])->values()->all(),
            'quest_location_ids' => $this->markers
                ->pluck('quest_location_id')->filter()->unique()->values()->all(),
        ];
    }
}
