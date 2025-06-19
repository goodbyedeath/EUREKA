<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HeroSlide extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'subtitle',
        'primary_button_text',
        'primary_button_url',
        'secondary_button_text',
        'secondary_button_url',
        'background_gradient',
        'background_image',
        'text_color',
        'button_color',
        'order',
        'is_active',
        'icon_svg',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Scope to get only active slides
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order slides by their order field
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }

    /**
     * Get active slides in order
     */
    public static function getActiveSlides()
    {
        return self::active()->ordered()->get();
    }

    /**
     * Get the background image URL
     */
    public function getBackgroundImageUrlAttribute()
    {
        if ($this->background_image) {
            return asset('storage/' . $this->background_image);
        }
        return null;
    }

    /**
     * Check if slide has background image
     */
    public function hasBackgroundImage()
    {
        return !empty($this->background_image) && file_exists(storage_path('app/public/' . $this->background_image));
    }

    /**
     * Get background style for the slide
     */
    public function getBackgroundStyle()
    {
        if ($this->hasBackgroundImage()) {
            return "background-image: url('" . $this->background_image_url . "'); background-size: cover; background-position: center; background-repeat: no-repeat;";
        }
        return '';
    }
}