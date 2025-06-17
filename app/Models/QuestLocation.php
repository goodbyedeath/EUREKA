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
        'google_map_embed_url',
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

    // Clean and validate Google Maps embed URL
    public function setGoogleMapEmbedUrlAttribute(?string $value): void
    {
        if (empty($value)) {
            $this->attributes['google_map_embed_url'] = null;
            return;
        }

        // Extract URL from iframe if provided
        if (str_contains($value, '<iframe')) {
            preg_match('/src="([^"]*)"/', $value, $matches);
            $value = $matches[1] ?? $value;
        }

        // Ensure it's a valid Google Maps embed URL
        if (str_contains($value, 'google.com/maps/embed')) {
            $this->attributes['google_map_embed_url'] = $value;
        } else {
            $this->attributes['google_map_embed_url'] = null;
        }
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

    // Auto-extract coordinates from Google Maps URL
    public function extractCoordinatesFromUrl(): bool
    {
        if (empty($this->google_map_embed_url)) {
            return false;
        }

        $url = $this->google_map_embed_url;
        $patterns = [
            '/!3d(-?\d+\.?\d*)!4d(-?\d+\.?\d*)/',           // pb parameter
            '/[?&]q=(-?\d+\.?\d*),(-?\d+\.?\d*)/',          // q parameter  
            '/[?&]ll=(-?\d+\.?\d*),(-?\d+\.?\d*)/',         // ll parameter
            '/[?&]center=(-?\d+\.?\d*),(-?\d+\.?\d*)/',     // center parameter
            '/@(-?\d+\.?\d*),(-?\d+\.?\d*)/',               // @ parameter
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                $this->latitude = (float) $matches[1];
                $this->longitude = (float) $matches[2];
                return true;
            }
        }

        return false;
    }

    // Format coordinates for display
    public function getFormattedCoordinatesAttribute(): string
    {
        if (!$this->latitude || !$this->longitude) {
            return 'Not set';
        }

        return number_format($this->latitude, 6) . ', ' . number_format($this->longitude, 6);
    }

    // Get Google Maps direct link (for opening in Google Maps app)
    public function getGoogleMapsLinkAttribute(): string
    {
        if (!$this->latitude || !$this->longitude) {
            return '#';
        }

        return "https://www.google.com/maps/dir/?api=1&destination={$this->latitude},{$this->longitude}";
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