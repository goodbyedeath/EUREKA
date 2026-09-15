<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A participant from the FEKDI x IFSE website — see App\Services\FekdiIntegration. */
class FekdiParticipant extends Model
{
    protected $fillable = [
        'google_id', 'name', 'email', 'avatar', 'joined_at', 'imported_at',
        'team_id', 'team_name', 'is_leader',
        'points', 'points_synced', 'lifetime_sent', 'sync_state', 'sync_error', 'last_synced_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'imported_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'is_leader' => 'boolean',
        'points' => 'integer',
        'points_synced' => 'integer',
        'lifetime_sent' => 'integer',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /** "am***@gmail.com" — enough for a team to recognise a colleague, not enough to harvest a list. */
    public function maskedEmail(): ?string
    {
        if (! $this->email || ! str_contains($this->email, '@')) {
            return null;
        }
        [$local, $domain] = explode('@', $this->email, 2);

        return mb_substr($local, 0, 2).'***@'.$domain;
    }
}
