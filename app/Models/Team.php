<?php
// app/Models/Team.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'department',
        'created_by'
    ];

    public function members()
    {
        return $this->hasMany(TeamMember::class);
    }

    public function leader()
    {
        return $this->hasOne(TeamMember::class)->where('is_leader', true);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function makeLeader($memberId)
    {
        \DB::transaction(function () use ($memberId) {
            $newLeader = $this->members()->findOrFail($memberId);

            // Demote current leader
            $this->members()->where('is_leader', true)->update(['is_leader' => false]);

            // Promote new leader
            $newLeader->update(['is_leader' => true]);
        });
    }
}