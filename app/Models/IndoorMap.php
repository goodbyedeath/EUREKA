<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * A top-view plan of an indoor venue, with the outposts marked on it.
 *
 * The indoor counterpart of the GPS map: teams cannot be located by satellite under a
 * roof, so they navigate by looking at a picture of the building instead.
 */
class IndoorMap extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'image_path',
        // The clue that guards this plan. The START code lives on race_starts now:
        // a start line belongs to the event, and outdoor events have one too.
        'clue_question',
        'clue_answer',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function spots(): HasMany
    {
        return $this->hasMany(IndoorMapSpot::class)->orderBy('sort_order')->orderBy('id');
    }

    public function activeSpots(): HasMany
    {
        return $this->spots()->where('is_active', true);
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }
}
