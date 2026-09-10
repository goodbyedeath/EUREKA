<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * One entry in the reusable 3D asset library.
 */
class ArModel extends Model
{
    protected $table = 'ar_models';

    protected $fillable = ['name', 'path', 'size', 'created_by'];

    protected $casts = ['size' => 'integer'];

    public function gameLocations(): HasMany
    {
        return $this->hasMany(GameLocation::class, 'ar_model_id');
    }

    public function hotspots(): HasMany
    {
        return $this->hasMany(Hotspot::class, 'ar_model_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function url(): string
    {
        // disk('public'), not the default disk: Storage::url() resolves against
        // `filesystems.default` (local), which has no `url` and returns a relative
        // path. A browser resolves that fine; a native client cannot.
        return Storage::disk('public')->url($this->path);
    }

    /**
     * Refuse to delete an asset something still points at — otherwise an outpost
     * silently loses its object mid-event.
     */
    public function isInUse(): bool
    {
        return $this->gameLocations()->exists() || $this->hotspots()->exists();
    }

    public function getSizeForHumansAttribute(): string
    {
        return $this->size >= 1048576
            ? round($this->size / 1048576, 1) . ' MB'
            : round($this->size / 1024) . ' KB';
    }
}
