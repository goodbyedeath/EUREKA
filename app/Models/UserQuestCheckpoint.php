<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserQuestCheckpoint extends Model
{
    protected $fillable = [
        'user_id',
        'quest_location_id',
        'user_latitude',
        'user_longitude',
        'checked_at'
    ];

    protected $casts = [
        'user_latitude' => 'decimal:8',
        'user_longitude' => 'decimal:8',
        'checked_at' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function questLocation(): BelongsTo
    {
        return $this->belongsTo(QuestLocation::class);
    }
}