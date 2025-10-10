<?php

namespace App\Rules;

use App\Models\TeamMember;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueTeamMemberEmail implements ValidationRule
{
    private $teamId;
    private $excludeMemberId;

    public function __construct($teamId, $excludeMemberId = null)
    {
        $this->teamId = $teamId;
        $this->excludeMemberId = $excludeMemberId;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Skip validation if email is empty (nullable)
        if (empty($value)) {
            return;
        }

        $query = TeamMember::where('team_id', $this->teamId)
                          ->where('email', $value);

        // Exclude specific member if provided (for editing)
        if ($this->excludeMemberId) {
            $query->where('id', '!=', $this->excludeMemberId);
        }

        if ($query->exists()) {
            $fail('This email is already used by another team member.');
        }
    }
}