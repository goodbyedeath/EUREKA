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
        'checked_at',
        'accuracy',
        'distance_from_center',
        'device_info'
    ];

    protected $casts = [
        'user_latitude' => 'decimal:8',
        'user_longitude' => 'decimal:8',
        'checked_at' => 'datetime',
        'accuracy' => 'float',
        'distance_from_center' => 'float'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function questLocation(): BelongsTo
    {
        return $this->belongsTo(QuestLocation::class);
    }
}