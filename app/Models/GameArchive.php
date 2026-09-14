<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameArchive extends Model
{
    protected $fillable = [
        'name', 'notes', 'snapshot', 'team_count', 'account_count',
        'winner_name', 'winner_score', 'storage_dir', 'archived_by', 'archived_at',
    ];

    protected $casts = [
        'snapshot' => 'array',
        'archived_at' => 'datetime',
    ];

    public function credentials(): HasMany
    {
        return $this->hasMany(GameArchiveCredential::class);
    }
}
