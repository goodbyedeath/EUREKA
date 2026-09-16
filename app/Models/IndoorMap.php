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

    /**
     * The floor plan this player should see.
     *
     * An indoor event runs several teams through different plans at once, so the plan belongs to
     * the team (operator, 16 Sep). Without an assignment it is the plan on the START code they
     * scanned — the behaviour every team had before — and failing that, the first active plan.
     */
    /** The plan the crew assigned this player's team, if it is still active. */
    public static function assignedTo(?\App\Models\User $user): ?self
    {
        $assigned = $user?->team?->indoor_map_id;

        return $assigned ? static::where('is_active', true)->find($assigned) : null;
    }

    public static function forUser(?\App\Models\User $user): ?self
    {
        if ($map = static::assignedTo($user)) {
            return $map;
        }

        // No assignment: the plan this team actually started on. A venue can have several START
        // codes, so the newest one is not necessarily the code they scanned.
        $fromSession = $user ? \App\Models\RaceSession::where('user_id', $user->id)
            ->whereNotNull('indoor_map_id')
            ->orderByDesc('id')
            ->value('indoor_map_id') : null;

        if ($fromSession && $map = static::where('is_active', true)->find($fromSession)) {
            return $map;
        }

        $fromStart = \App\Models\RaceStart::where('is_active', true)
            ->whereNotNull('indoor_map_id')
            ->orderByDesc('id')
            ->value('indoor_map_id');

        if ($fromStart && $map = static::where('is_active', true)->find($fromStart)) {
            return $map;
        }

        return static::where('is_active', true)->orderBy('name')->first();
    }

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
