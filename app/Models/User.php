<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'team_id',
        'last_login_at',
        'is_active',
        'session_timeout',
        'session_workflow_id',
        'last_activity_at',
        'session_expired_at',
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
            'last_activity_at' => 'datetime',
            'session_expired_at' => 'datetime',
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

    /**
     * Get session timeout in seconds
     */
    /**
     * Admin-granted access window.
     *
     * `session_timeout` is the length of the grant, in seconds. `session_expired_at`
     * is when the current grant runs out — it stays NULL until the user's first
     * login, so the clock starts when they actually begin, not when the admin sets
     * it. Once it passes, the account is locked out until an admin grants again
     * (which clears `session_expired_at` back to NULL).
     */
    public function hasAccessWindow(): bool
    {
        return $this->role === 'user' && $this->session_timeout !== null;
    }

    public function accessWindowExpired(): bool
    {
        return $this->hasAccessWindow()
            && $this->session_expired_at !== null
            && $this->session_expired_at->isPast();
    }

    /**
     * Start the clock on first login. Deliberately does nothing if the window is
     * already running, so logging out and back in cannot buy more time.
     */
    public function startAccessWindow(): void
    {
        if ($this->hasAccessWindow() && $this->session_expired_at === null) {
            $this->forceFill([
                'session_expired_at' => now()->addSeconds($this->session_timeout),
            ])->save();
        }
    }

    /**
     * Re-grant access: clears the expiry so the next login starts a fresh window.
     */
    public function grantAccessWindow(?int $seconds): void
    {
        $this->forceFill([
            'session_timeout' => $seconds,
            'session_expired_at' => null,
        ])->save();
    }

    /**
     * Short status for the admin list, so it is obvious who is locked out.
     */
    public function accessWindowStatus(): array
    {
        if (! $this->hasAccessWindow()) {
            return ['label' => 'Unlimited', 'tone' => 'green'];
        }

        if ($this->session_expired_at === null) {
            return ['label' => 'Not started', 'tone' => 'blue'];
        }

        if ($this->accessWindowExpired()) {
            return ['label' => 'Expired — locked', 'tone' => 'red'];
        }

        return ['label' => 'Ends ' . $this->session_expired_at->diffForHumans(), 'tone' => 'yellow'];
    }

    public function accessWindowEndsAt(): ?\Illuminate\Support\Carbon
    {
        return $this->hasAccessWindow() ? $this->session_expired_at : null;
    }
    public function getSessionTimeout(): ?int
    {
        return $this->session_timeout;
    }

    /**
     * Set session timeout in seconds (null for no timeout)
     */
    public function setSessionTimeout(?int $seconds): void
    {
        $this->update(['session_timeout' => $seconds]);
    }

    /**
     * Get formatted session timeout for display
     */
    public function getFormattedSessionTimeout(): string
    {
        $timeout = $this->getSessionTimeout();
        if (!$timeout) {
            return 'No timeout';
        }

        $minutes = intval($timeout / 60);
        $hours = intval($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($hours > 0) {
            return $hours . 'h ' . $remainingMinutes . 'm';
        }

        return $minutes . ' minutes';
    }


}