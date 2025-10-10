<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class QuestLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'what_to_do',
        'latitude',
        'longitude',
        'radius',
        'is_active',
        'created_by',
        'max_check_ins_per_user',
        'quest_points',
        'image_path'
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_active' => 'boolean',
        'radius' => 'integer',
        'max_check_ins_per_user' => 'integer',
        'quest_points' => 'integer'
    ];

    // Relationships
    public function checkpoints(): HasMany
    {
        return $this->hasMany(UserQuestCheckpoint::class);
    }

    public function userCheckpoints(): HasMany
    {
        return $this->hasMany(UserQuestCheckpoint::class);
    }

    // Calculate distance using Haversine formula
    public function calculateDistance(float $userLat, float $userLng): float
    {
        $earthRadius = 6371000; // Earth's radius in meters

        $latDelta = deg2rad($this->latitude - $userLat);
        $lngDelta = deg2rad($this->longitude - $userLng);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($userLat)) * cos(deg2rad($this->latitude)) *
             sin($lngDelta / 2) * sin($lngDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }

    // Check if user is within the quest location radius
    public function isWithinRadius(float $userLat, float $userLng): bool
    {
        $distance = $this->calculateDistance($userLat, $userLng);
        return $distance <= $this->radius;
    }

    // Check if user has already checked in
    public function isUserCheckedIn(int $userId): bool
    {
        return $this->checkpoints()
            ->where('user_id', $userId)
            ->exists();
    }

    // Get user's check-in count for this location
    public function getUserCheckInCount(int $userId): int
    {
        return $this->checkpoints()
            ->where('user_id', $userId)
            ->count();
    }

    // Check if user can check in (considering max check-ins limit)
    public function canUserCheckIn(int $userId): bool
    {
        if ($this->max_check_ins_per_user === null) {
            return !$this->isUserCheckedIn($userId);
        }

        return $this->getUserCheckInCount($userId) < $this->max_check_ins_per_user;
    }

    // Get total check-ins for this location
    public function getTotalCheckInsAttribute(): int
    {
        return Cache::remember(
            "quest_location_{$this->id}_total_checkins",
            3600, // Cache for 1 hour
            fn() => $this->checkpoints()->count()
        );
    }

    // Get unique users who checked in
    public function getUniqueUsersCountAttribute(): int
    {
        return Cache::remember(
            "quest_location_{$this->id}_unique_users",
            3600,
            fn() => $this->checkpoints()->distinct('user_id')->count()
        );
    }


    // Scope for active locations
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope for locations within a certain distance from user
    public function scopeNearUser($query, float $userLat, float $userLng, int $maxDistance = 10000)
    {
        // Using approximate distance calculation for database query
        $latRange = $maxDistance / 111320; // Degrees latitude per meter
        $lngRange = $maxDistance / (111320 * cos(deg2rad($userLat))); // Degrees longitude per meter

        return $query->whereBetween('latitude', [$userLat - $latRange, $userLat + $latRange])
                    ->whereBetween('longitude', [$userLng - $lngRange, $userLng + $lngRange]);
    }


    // Format coordinates for display
    public function getFormattedCoordinatesAttribute(): string
    {
        if (!$this->latitude || !$this->longitude) {
            return 'Not set';
        }

        return number_format($this->latitude, 6) . ', ' . number_format($this->longitude, 6);
    }

    // Get coordinates in MapLibre format [longitude, latitude]
    public function getMapLibreCoordinatesAttribute(): ?array
    {
        if (!$this->latitude || !$this->longitude) {
            return null;
        }

        return [(float) $this->longitude, (float) $this->latitude];
    }

    // Check if location has valid coordinates for mapping
    public function getHasValidCoordinatesAttribute(): bool
    {
        // Convert to float for proper comparison
        $lat = (float) $this->latitude;
        $lng = (float) $this->longitude;
        
        return $this->latitude !== null && 
               $this->longitude !== null && 
               $lat !== 0.0 && 
               $lng !== 0.0 &&
               $lat >= -90.0 && 
               $lat <= 90.0 &&
               $lng >= -180.0 && 
               $lng <= 180.0;
    }



    // Clear related caches when model is updated
    protected static function booted(): void
    {
        static::updated(function (QuestLocation $location) {
            Cache::forget("quest_location_{$location->id}_total_checkins");
            Cache::forget("quest_location_{$location->id}_unique_users");
        });

        static::deleted(function (QuestLocation $location) {
            Cache::forget("quest_location_{$location->id}_total_checkins");
            Cache::forget("quest_location_{$location->id}_unique_users");
        });
    }
}