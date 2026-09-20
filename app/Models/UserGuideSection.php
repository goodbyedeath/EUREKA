<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One step of the player's how-to. See the migration for why this is not a `guidance`.
 */
class UserGuideSection extends Model
{
    protected $fillable = ['icon', 'title', 'body', 'sort_order', 'is_active', 'updated_by'];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /** The body is plain sentences, one per line: no markup for the crew to learn, or the app to parse. */
    public function lines(): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $this->body))
            ->map(fn ($l) => trim($l))
            ->filter()
            ->values()
            ->all();
    }

    public function toApiPayload(): array
    {
        return [
            'id' => $this->id,
            'icon' => $this->icon,
            'title' => $this->title,
            'lines' => $this->lines(),
            'sort_order' => (int) $this->sort_order,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
