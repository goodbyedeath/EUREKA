<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientFeedback extends Model
{
    protected $table = 'client_feedback';

    protected $fillable = [
        'kind', 'subject', 'detail', 'client_version', 'contract_sha', 'resolved',
    ];

    protected $casts = ['resolved' => 'boolean'];

    public const KINDS = ['mismatch', 'blocked', 'done', 'question', 'bug'];
}
