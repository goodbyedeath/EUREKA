<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class GameLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'created_by',
        'target_type',
        'target_user_id',
        // experience_type is 'ar' once a model is uploaded; 'panorama' is the legacy
        // default meaning "not set up yet" (the 360° viewer itself was removed).
        'experience_type',
        'ar_model_id',
        'ar_model_path',
        'ar_sky_path',
        // Where the outpost physically is. Nullable — a location is bound the first
        // time an admin authors at it; until then the viewer behaves as it always did.
        'latitude',
        'longitude',
        'radius',
        // The post this outpost belongs to. When set it owns the coordinates.
        'quest_location_id',
        // 'geofence' (outdoor, radius decides) or 'manual' (indoor, an admin decides).
        'access_mode',
    ];

    public const EXPERIENCE_PANORAMA = 'panorama';
    public const EXPERIENCE_AR = 'ar';

    /**
     * An AR location needs a model. Without one the outpost has nothing to show, so
     * callers must send the team elsewhere rather than open an empty camera view.
     */
    /**
     * The model shown for this location's objects unless an object overrides it.
     * Falls back to the legacy per-location path from before the asset library.
     */
    public function modelUrl(): ?string
    {
        if ($this->arModel) {
            return $this->arModel->url();
        }

        return $this->ar_model_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->ar_model_path) : null;
    }

    public function arModel(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ArModel::class, 'ar_model_id');
    }

    public function usesAr(): bool
    {
        return $this->experience_type === self::EXPERIENCE_AR && $this->modelUrl() !== null;
    }

    public const ACCESS_GEOFENCE = 'geofence';
    public const ACCESS_MANUAL = 'manual';

    public function unlocks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(GameLocationUnlock::class);
    }

    /**
     * Indoor outposts are opened by a person, not by a radius. GPS does not work through
     * a roof, so requiring both gates would make an indoor post unreachable.
     */
    public function isManualAccess(): bool
    {
        return $this->access_mode === self::ACCESS_MANUAL;
    }

    /**
     * May this team open the 3D camera here?
     *
     * Returns [allowed, reason]. Distance is left to the caller, which has the team's
     * position; this decides only what kind of gate applies.
     */
    public function accessGrantedTo(?User $user): bool
    {
        if (! $this->isManualAccess()) {
            return true;                       // geofence handles it elsewhere
        }

        if (! $user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;                       // admins inspect from anywhere
        }

        return $this->unlocks()
            ->where('user_id', $user->id)
            ->whereNotNull('granted_at')
            ->whereNull('revoked_at')
            ->exists();
    }

    /**
     * The post this outpost belongs to, if any.
     */
    public function questLocation(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(QuestLocation::class);
    }

    /**
     * Where the coordinates come from: the linked post, this row, or nowhere.
     *
     * The post wins deliberately. An admin sets a coordinate once per post in Quest
     * Locations, and a second point stored here could drift away from it without
     * anyone noticing which was right.
     */
    public function coordinateSource(): string
    {
        if ($this->questLocation && $this->questLocation->latitude !== null) {
            return 'quest_location';
        }

        return ($this->latitude !== null && $this->longitude !== null) ? 'own' : 'none';
    }

    public function resolvedLatitude(): ?float
    {
        return match ($this->coordinateSource()) {
            'quest_location' => (float) $this->questLocation->latitude,
            'own' => (float) $this->latitude,
            default => null,
        };
    }

    public function resolvedLongitude(): ?float
    {
        return match ($this->coordinateSource()) {
            'quest_location' => (float) $this->questLocation->longitude,
            'own' => (float) $this->longitude,
            default => null,
        };
    }

    public function resolvedRadius(): int
    {
        return (int) ($this->coordinateSource() === 'quest_location'
            ? ($this->questLocation->radius ?: 50)
            : ($this->radius ?: 50));
    }

    /**
     * Has this outpost been bound to a physical place yet, from either source?
     */
    public function hasCoordinates(): bool
    {
        return $this->coordinateSource() !== 'none';
    }

    /**
     * Metres between the outpost and a reported position. Same Haversine as
     * QuestLocation::calculateDistance, so both geofences agree.
     */
    public function calculateDistance(float $userLat, float $userLng): float
    {
        $earthRadius = 6371000; // metres

        $lat = $this->resolvedLatitude();
        $lng = $this->resolvedLongitude();

        $latDelta = deg2rad($lat - $userLat);
        $lngDelta = deg2rad($lng - $userLng);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($userLat)) * cos(deg2rad($lat)) *
             sin($lngDelta / 2) * sin($lngDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }

    /**
     * An unbound location has no geofence to fail, so it stays open — otherwise
     * adding this feature would have blacked out every outpost authored before it.
     */
    public function isWithinRadius(float $userLat, float $userLng): bool
    {
        if (! $this->hasCoordinates()) {
            return true;
        }

        return $this->calculateDistance($userLat, $userLng) <= $this->resolvedRadius();
    }

    protected $casts = [
        'is_active' => 'boolean',
        'radius' => 'integer',
        'max_check_ins_per_user' => 'integer',
        'quest_points' => 'integer',
        'coordinate_x' => 'decimal:2',
        'coordinate_y' => 'decimal:2',
        'target_user_id' => 'integer'
    ];

    // Check if coordinate is within game map bounds
    public function isCoordinateValid(int $x, int $y): bool
    {
        return $x >= 0 && $y >= 0 && $this->coordinate_x >= 0 && $this->coordinate_y >= 0;
    }

    // Scope for active locations
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Format game map coordinates for display
    public function getFormattedCoordinatesAttribute(): string
    {
        if (!$this->coordinate_x || !$this->coordinate_y) {
            return 'Not set';
        }

        return "X: {$this->coordinate_x}, Y: {$this->coordinate_y}";
    }

    // Relationship with hotspots
    public function hotspots(): HasMany
    {
        return $this->hasMany(Hotspot::class);
    }

    // Get active hotspots only
    public function activeHotspots(): HasMany
    {
        return $this->hasMany(Hotspot::class)->where('is_active', true);
    }
}
