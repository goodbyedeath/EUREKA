<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $fillable = ['key', 'value', 'updated_by'];

    public static function row(string $key): ?self
    {
        return static::where('key', $key)->first();
    }

    public static function put(string $key, ?string $value, ?int $userId = null): self
    {
        return static::updateOrCreate(['key' => $key], ['value' => $value, 'updated_by' => $userId]);
    }
}
