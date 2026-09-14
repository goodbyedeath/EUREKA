<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameArchiveCredential extends Model
{
    protected $fillable = ['game_archive_id', 'kind', 'value'];
}
