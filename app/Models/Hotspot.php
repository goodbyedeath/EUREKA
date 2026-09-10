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
 * - Degree helpers exist for any viewer that wants them
 * - Use getPitchDegrees() and getYawDegrees() where degrees are needed
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
        'is_active',
        'ar_model_path',
        'ar_model_id',
        // Interaction payload: media_type is null (message), 'link' or 'image'.
        'content',
        'media_type',
        'media_path',
        'ar_distance',
        'ar_scale',
        // Were missing, so points/type/order set on create were silently dropped.
        'points_value',
        'hotspot_type',
        'tour_order',
        'is_required',
        'ar_rotation_x',
        'ar_rotation_y',
        'ar_rotation_z',
        // Idle motion. A list, because motions compose — see
        // ArExperienceController::ANIMATIONS for the formulas and the rates.
        'ar_motions',
    ];

    protected $casts = [
        'ar_motions' => 'array',
        'pitch' => 'float',
        'yaw' => 'float',
        'extra_data' => 'array',
        'is_active' => 'boolean',
        'ar_distance' => 'float',
        'ar_scale' => 'float',
        'ar_rotation_x' => 'float',
        'ar_rotation_y' => 'float',
        'ar_rotation_z' => 'float',
    ];

    public function arModel(): BelongsTo
    {
        return $this->belongsTo(ArModel::class, 'ar_model_id');
    }

    /**
     * This object's own model, if it overrides the location default.
     */
    public function modelUrl(): ?string
    {
        if ($this->arModel) {
            return $this->arModel->url();
        }

        return $this->ar_model_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->ar_model_path) : null;
    }

    public function gameLocation(): BelongsTo
    {
        return $this->belongsTo(GameLocation::class);
    }

    /**
     * Get pitch in degrees
     * Converts from radians (database) to degrees (frontend)
     */
    public function getPitchDegreesAttribute(): float
    {
        return $this->pitch * (180 / pi());
    }

    /**
     * Get yaw in degrees
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
