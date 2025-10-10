<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Hotspot Model
 * 
 * Represents interactive hotspots within panoramic images.
 * 
 * Coordinate System:
 * - Database stores pitch/yaw in RADIANS
 * - Frontend (Pannellum) expects coordinates in DEGREES
 * - Use getPitchDegrees() and getYawDegrees() for Pannellum compatibility
 * 
 * @property float $pitch Vertical angle in radians (-π/2 to π/2)
 * @property float $yaw Horizontal angle in radians (-π to π)
 * @property string $title Hotspot title/name
 * @property string $description Optional description text
 * @property string $type Hotspot type ('info', 'scene', 'custom')
 * @property string $css_class CSS class for styling
 * @property array $extra_data Additional JSON data
 * @property bool $is_active Whether hotspot is active/visible
 */
class Hotspot extends Model
{
    protected $fillable = [
        'game_location_id',
        'title',
        'description',
        'pitch',
        'yaw',
        'type',
        'css_class',
        'extra_data',
        'is_active'
    ];

    protected $casts = [
        'pitch' => 'float',
        'yaw' => 'float',
        'extra_data' => 'array',
        'is_active' => 'boolean'
    ];

    public function gameLocation(): BelongsTo
    {
        return $this->belongsTo(GameLocation::class);
    }

    /**
     * Get pitch in degrees (for Pannellum)
     * Converts from radians (database) to degrees (frontend)
     */
    public function getPitchDegreesAttribute(): float
    {
        return $this->pitch * (180 / pi());
    }

    /**
     * Get yaw in degrees (for Pannellum)
     * Converts from radians (database) to degrees (frontend)
     */
    public function getYawDegreesAttribute(): float
    {
        return $this->yaw * (180 / pi());
    }

    /**
     * Set pitch from degrees (convert to radians for database)
     */
    public function setPitchFromDegrees(float $degrees): void
    {
        $this->pitch = $degrees * (pi() / 180);
    }

    /**
     * Set yaw from degrees (convert to radians for database)
     */
    public function setYawFromDegrees(float $degrees): void
    {
        $this->yaw = $degrees * (pi() / 180);
    }

    /**
     * Get hotspot configuration for Pannellum viewer
     */
    public function toPannellumConfig(): array
    {
        $config = [
            'id' => 'hotspot-' . $this->id,
            'pitch' => $this->pitch_degrees,        // Converted to degrees
            'yaw' => $this->yaw_degrees,            // Converted to degrees
            'type' => $this->type ?? 'info',
            'text' => $this->title,
            'cssClass' => $this->css_class ?? 'admin-hotspot-marker',
            'scale' => true,
            'description' => $this->description
        ];
        
        // Add tour-specific configuration
        if ($this->isNavigationHotspot()) {
            $config['clickHandlerFunc'] = 'navigateToScene';
            $config['targetLocationId'] = $this->getTargetLocationId();
        } elseif ($this->isQuizHotspot()) {
            $config['clickHandlerFunc'] = 'openQuiz';
            $config['quizData'] = $this->getQuizData();
        } elseif ($this->hasInteractiveContent()) {
            $config['clickHandlerFunc'] = 'showInteractiveContent';
            $config['content'] = $this->getContent();
            $config['mediaType'] = $this->getMediaType();
            $config['mediaPath'] = $this->getMediaPath();
            
            // Add image data for info hotspots
            if ($this->hasImage()) {
                $config['hasImage'] = true;
                $config['imageUrl'] = $this->getImageUrl();
            }
        }
        
        return $config;
    }
    
    // Tour feature methods
    public function getHotspotType(): string
    {
        return $this->extra_data['hotspot_type'] ?? 'info';
    }
    
    public function setHotspotType(string $type): void
    {
        $extraData = $this->extra_data ?? [];
        $extraData['hotspot_type'] = $type;
        $this->extra_data = $extraData;
    }
    
    public function isNavigationHotspot(): bool
    {
        return $this->getHotspotType() === 'navigation';
    }
    
    public function isQuizHotspot(): bool
    {
        return $this->getHotspotType() === 'quiz';
    }
    
    public function isInfoHotspot(): bool
    {
        return $this->getHotspotType() === 'info';
    }
    
    // Navigation hotspot methods
    public function getTargetLocationId(): ?int
    {
        return $this->extra_data['target_location_id'] ?? null;
    }
    
    public function setTargetLocationId(?int $locationId): void
    {
        $extraData = $this->extra_data ?? [];
        $extraData['target_location_id'] = $locationId;
        $this->extra_data = $extraData;
    }
    
    public function targetLocation(): BelongsTo
    {
        return $this->belongsTo(GameLocation::class, 'extra_data->target_location_id');
    }
    
    // Interactive content methods
    public function hasInteractiveContent(): bool
    {
        return !empty($this->getContent()) || !empty($this->getMediaPath());
    }
    
    public function getContent(): ?string
    {
        return $this->extra_data['content'] ?? null;
    }
    
    public function setContent(?string $content): void
    {
        $extraData = $this->extra_data ?? [];
        $extraData['content'] = $content;
        $this->extra_data = $extraData;
    }
    
    public function getMediaType(): ?string
    {
        return $this->extra_data['media_type'] ?? null;
    }
    
    public function setMediaType(?string $mediaType): void
    {
        $extraData = $this->extra_data ?? [];
        $extraData['media_type'] = $mediaType;
        $this->extra_data = $extraData;
    }
    
    public function getMediaPath(): ?string
    {
        return $this->extra_data['media_path'] ?? $this->media_path ?? null;
    }
    
    public function setMediaPath(?string $mediaPath): void
    {
        $extraData = $this->extra_data ?? [];
        $extraData['media_path'] = $mediaPath;
        $this->extra_data = $extraData;
    }
    
    // Image-specific methods for info hotspots
    public function hasImage(): bool
    {
        return !empty($this->getImagePath());
    }
    
    public function getImagePath(): ?string
    {
        return $this->extra_data['image_path'] ?? null;
    }
    
    public function getImageUrl(): ?string
    {
        $path = $this->getImagePath();
        return $path ? asset('storage/' . $path) : null;
    }
    
    public function setImagePath(?string $imagePath): void
    {
        $extraData = $this->extra_data ?? [];
        $extraData['image_path'] = $imagePath;
        $this->extra_data = $extraData;
    }
    
    // Quiz methods
    public function getQuizData(): ?array
    {
        return $this->extra_data['quiz_data'] ?? null;
    }
    
    public function setQuizData(?array $quizData): void
    {
        $extraData = $this->extra_data ?? [];
        $extraData['quiz_data'] = $quizData;
        $this->extra_data = $extraData;
    }
    
    public function getPointsValue(): int
    {
        return $this->extra_data['points_value'] ?? 0;
    }
    
    public function setPointsValue(int $points): void
    {
        $extraData = $this->extra_data ?? [];
        $extraData['points_value'] = $points;
        $this->extra_data = $extraData;
    }
    
    public function isRequired(): bool
    {
        return $this->extra_data['is_required'] ?? false;
    }
    
    public function setRequired(bool $required): void
    {
        $extraData = $this->extra_data ?? [];
        $extraData['is_required'] = $required;
        $this->extra_data = $extraData;
    }
    
    public function getTourOrder(): ?int
    {
        return $this->extra_data['tour_order'] ?? null;
    }
    
    public function setTourOrder(?int $order): void
    {
        $extraData = $this->extra_data ?? [];
        $extraData['tour_order'] = $order;
        $this->extra_data = $extraData;
    }
}
