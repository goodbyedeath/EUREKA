<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

/** A printed QR login card for one team account — see App\Services\LoginCardService. */
class LoginCard extends Model
{
    protected $fillable = ['user_id', 'code_hash', 'code_encrypted', 'password_encrypted', 'batch', 'revoked_at', 'last_used_at', 'created_by'];

    protected $hidden = ['code_hash', 'code_encrypted', 'password_encrypted'];

    protected $casts = [
        'revoked_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plainCode(): string
    {
        return Crypt::decryptString($this->code_encrypted);
    }

    public function plainPassword(): string
    {
        return Crypt::decryptString($this->password_encrypted);
    }
}
