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
        'what_to_do',
        'radius',
        'is_active',
        'created_by',
        'max_check_ins_per_user',
        'quest_points',
        'image_path',
        'map_image_path',
        'coordinate_x',
        'coordinate_y'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'radius' => 'integer',
        'max_check_ins_per_user' => 'integer',
        'quest_points' => 'integer',
        'coordinate_x' => 'integer',
        'coordinate_y' => 'integer'
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
}
