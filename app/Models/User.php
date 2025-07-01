<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'team_id',
        'last_login_at',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isUser()
    {
        return $this->role === 'user';
    }

    public function questionnaires()
    {
        return $this->hasMany(Questionnaire::class, 'created_by');
    }

    public function quizAttempts()
    {
        return $this->hasMany(QuizAttempt::class);
    }

    // Teams created by this user
    public function createdTeams()
    {
        return $this->hasMany(Team::class, 'created_by');
    }

    // Primary team relationship - Direct team_id foreign key
    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    // Alternative: Get teams where this user's email matches a team member (fallback)
    public function teamsByEmail()
    {
        return $this->hasManyThrough(
            Team::class,
            TeamMember::class,
            'email', // Foreign key on team_members table
            'id',    // Foreign key on teams table
            'email', // Local key on users table
            'team_id' // Local key on team_members table
        );
    }

    // Check if user is a team leader
    public function isTeamLeader($teamId = null)
    {
        // First check via direct team relationship
        if ($this->team_id && (!$teamId || $this->team_id == $teamId)) {
            return $this->team && $this->team->leader && $this->team->leader->email === $this->email;
        }
        
        // Fallback to email-based check
        $query = TeamMember::where('email', $this->email)
                          ->where('is_leader', true);
        
        if ($teamId) {
            $query->where('team_id', $teamId);
        }
        
        return $query->exists();
    }

    // Get teams where user is a leader
    public function ledTeams()
    {
        return Team::whereHas('members', function($query) {
            $query->where('email', $this->email)
                  ->where('is_leader', true);
        });
    }

    // Sync user with team_members table (useful for maintaining both systems)
    public function syncWithTeamMembers()
    {
        if ($this->team_id) {
            TeamMember::updateOrCreate(
                ['email' => $this->email],
                [
                    'team_id' => $this->team_id,
                    'user_id' => $this->id,  // Add this
                    'name' => $this->name,
                    'is_leader' => $this->isTeamLeader($this->team_id)
                ]
            );
        }
    }

    public function createdTeam()
    {
        return $this->hasOne(Team::class, 'created_by');
    }

    /**
     * Check if user account is active
     */
    public function isActive(): bool
    {
        return $this->is_active ?? true;
    }

    /**
     * Update last login timestamp
     */
    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    /**
     * Check if user has verified email
     */
    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * Get user's role display name
     */
    public function getRoleDisplayAttribute(): string
    {
        return ucfirst($this->role);
    }

    /**
     * Scope for active users only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for users by role
     */
    public function scopeRole($query, string $role)
    {
        return $query->where('role', $role);
    }

}