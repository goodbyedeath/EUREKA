<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

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
        return !empty($this->background_image) && Storage::disk('public')->exists($this->background_image);
    }

    /**
     * Get background style for the slide
     */
    public function getBackgroundStyle()
    {
        if ($this->hasBackgroundImage()) {
            return "background-image: url('" . $this->background_image_url . "'); background-size: cover; background-position: center; background-repeat: no-repeat;";
        }
        return "background: linear-gradient(to bottom right, " . $this->getGradientColors() . ");";
    }

    /**
     * Convert Tailwind gradient to CSS colors
     */
    private function getGradientColors()
    {
        $gradientMap = [
            'from-blue-600 via-purple-600 to-indigo-800' => '#2563eb, #9333ea, #3730a3',
            'from-green-500 via-teal-500 to-blue-600' => '#10b981, #14b8a6, #2563eb',
            'from-purple-600 via-pink-500 to-red-500' => '#9333ea, #ec4899, #ef4444',
            'from-gray-900 via-gray-800 to-black' => '#111827, #1f2937, #000000',
            'from-orange-500 via-yellow-500 to-red-500' => '#f97316, #eab308, #ef4444',
        ];

        return $gradientMap[$this->background_gradient] ?? '#2563eb, #9333ea, #3730a3';
    }

    /**
     * Get the full background image path
     */
    public function getBackgroundImagePathAttribute()
    {
        if ($this->background_image) {
            return storage_path('app/public/' . $this->background_image);
        }
        return null;
    }

    /**
     * Delete the background image file
     */
    public function deleteBackgroundImage()
    {
        if ($this->background_image && Storage::disk('public')->exists($this->background_image)) {
            Storage::disk('public')->delete($this->background_image);
            $this->update(['background_image' => null]);
            return true;
        }
        return false;
    }

    /**
     * Get next order number for new slides
     */
    public static function getNextOrder()
    {
        return (self::max('order') ?? -1) + 1;
    }

    /**
     * Reorder slides after deletion
     */
    public static function reorderSlides()
    {
        $slides = self::orderBy('order')->get();
        foreach ($slides as $index => $slide) {
            $slide->update(['order' => $index]);
        }
    }

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($slide) {
            $slide->deleteBackgroundImage();
        });
    }
}