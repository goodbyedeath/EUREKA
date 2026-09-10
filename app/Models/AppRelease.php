<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppRelease extends Model
{
    protected $fillable = ['version_code', 'version_name', 'download_url', 'notes'];

    protected $casts = ['version_code' => 'integer'];

    /** The highest build the client has reported, or null if it never has. */
    public static function latest(): ?self
    {
        return static::orderByDesc('version_code')->first();
    }
}
