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
        'points',
        'initial_points',
        'created_by'
    ];

    protected function casts(): array
    {
        return [
            'points' => 'decimal:2',
            'initial_points' => 'decimal:2',
        ];
    }

    public function members()
    {
        return $this->hasMany(TeamMember::class);
    }

    public function users()
    {
        return $this->hasMany(User::class, 'team_id');
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

    /**
     * Add points to the team
     */
    public function addPoints($amount, $reason = null)
    {
        $this->increment('points', $amount);
        
        // Log the transaction if logging is needed
        // This can be expanded later for audit trail
        return $this;
    }

    /**
     * Deduct points from the team
     */
    public function deductPoints($amount, $reason = null)
    {
        $this->decrement('points', $amount);
        
        // Ensure points don't go below 0
        if ($this->points < 0) {
            $this->update(['points' => 0]);
        }
        
        return $this;
    }

    /**
     * Set initial points for the team
     */
    public function setInitialPoints($amount)
    {
        $this->update([
            'initial_points' => $amount,
            'points' => $amount
        ]);
        
        return $this;
    }

    /**
     * Reset points to initial amount
     */
    public function resetPoints()
    {
        $this->update(['points' => $this->initial_points]);
        
        return $this;
    }
}