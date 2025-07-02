<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class FeatureSetting extends Model
{
    protected $fillable = [
        'feature_key',
        'feature_name',
        'description',
        'is_enabled',
        'sort_order',
        'metadata'
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'metadata' => 'array',
        'sort_order' => 'integer'
    ];

    // Cache duration in seconds (1 hour)
    const CACHE_DURATION = 3600;

    /**
     * Get all enabled features
     */
    public static function getEnabledFeatures()
    {
        return Cache::remember('enabled_features', self::CACHE_DURATION, function () {
            return self::where('is_enabled', true)
                ->orderBy('sort_order')
                ->get()
                ->keyBy('feature_key');
        });
    }

    /**
     * Check if a specific feature is enabled
     */
    public static function isEnabled(string $featureKey): bool
    {
        $enabledFeatures = self::getEnabledFeatures();
        return $enabledFeatures->has($featureKey);
    }

    /**
     * Get feature metadata
     */
    public static function getFeatureMetadata(string $featureKey): ?array
    {
        $enabledFeatures = self::getEnabledFeatures();
        $feature = $enabledFeatures->get($featureKey);
        return $feature ? $feature->metadata : null;
    }

    /**
     * Toggle feature status
     */
    public function toggle(): bool
    {
        $this->is_enabled = !$this->is_enabled;
        $saved = $this->save();
        
        if ($saved) {
            $this->clearCache();
        }
        
        return $saved;
    }

    /**
     * Enable feature
     */
    public function enable(): bool
    {
        $this->is_enabled = true;
        $saved = $this->save();
        
        if ($saved) {
            $this->clearCache();
        }
        
        return $saved;
    }

    /**
     * Disable feature
     */
    public function disable(): bool
    {
        $this->is_enabled = false;
        $saved = $this->save();
        
        if ($saved) {
            $this->clearCache();
        }
        
        return $saved;
    }

    /**
     * Clear feature cache
     */
    public static function clearCache(): void
    {
        Cache::forget('enabled_features');
    }

    /**
     * Scope for enabled features
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Scope for ordering
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Get feature icon from metadata
     */
    public function getIconAttribute(): ?string
    {
        return $this->metadata['icon'] ?? null;
    }

    /**
     * Get feature color from metadata
     */
    public function getColorAttribute(): ?string
    {
        return $this->metadata['color'] ?? null;
    }

    /**
     * Get feature route from metadata
     */
    public function getRouteAttribute(): ?string
    {
        return $this->metadata['route'] ?? null;
    }

    /**
     * Boot method to clear cache on model events
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function () {
            self::clearCache();
        });

        static::deleted(function () {
            self::clearCache();
        });
    }
}