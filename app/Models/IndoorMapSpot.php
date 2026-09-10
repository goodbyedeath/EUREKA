<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * One marked outpost on an indoor plan.
 *
 * x and y are percentages of the image, so a spot lands in the same place whatever width
 * the plan is drawn at. Storing pixels would move every marker the moment a phone rotated.
 */
class IndoorMapSpot extends Model
{
    use HasFactory;

    public const SHAPES = ['circle', 'square', 'diamond', 'pin'];

    protected $fillable = [
        'indoor_map_id',
        'name',
        'x',
        'y',
        'shape',
        'color',
        'size',
        'content',
        'image_path',
        'game_location_id',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'x' => 'float',
        'y' => 'float',
        'size' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function indoorMap(): BelongsTo
    {
        return $this->belongsTo(IndoorMap::class);
    }

    /** The AR outpost this spot stands for, when one is linked. */
    public function gameLocation(): BelongsTo
    {
        return $this->belongsTo(GameLocation::class);
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    /** A spot with neither text nor picture is just a marker — worth knowing before rendering. */
    public function hasContent(): bool
    {
        return filled($this->content) || filled($this->image_path);
    }
}
