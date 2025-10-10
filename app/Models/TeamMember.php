<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'user_id',  
        'name',
        'email',
        'phone',
        'position',
        'is_leader'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    protected $casts = [
        'is_leader' => 'boolean'
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    
    // Scope to get leaders only
    public function scopeLeaders($query)
    {
        return $query->where('is_leader', true);
    }

    // Scope to get members only (non-leaders)
    public function scopeMembers($query)
    {
        return $query->where('is_leader', false);
    }
}