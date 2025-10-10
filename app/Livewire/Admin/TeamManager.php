<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Team;
use App\Models\User;
use App\Models\TeamMember;
use App\Models\QuizAttempt;
use App\Models\UserAnswer;
use App\Models\GameAssessment;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TeamManager extends Component
{
    use WithPagination;

    public $searchTerm = '';
    
    // Team form data
    public $teamId = null;
    public $name = '';
    public $description = '';
    public $department = '';
    public $points = 1000;
    public $initial_points = 1000;
    
    // Points management (for quick actions only)
    public $pointsAction = 'set'; // set, add, deduct
    public $pointsAmount = 0;
    public $pointsReason = '';
    
    // Quick actions
    public $quickActions = [
        ['label' => '+50 Points', 'action' => 'add', 'amount' => 50, 'reason' => 'Quick bonus'],
        ['label' => '+100 Points', 'action' => 'add', 'amount' => 100, 'reason' => 'Achievement bonus'],
        ['label' => '+200 Points', 'action' => 'add', 'amount' => 200, 'reason' => 'Major achievement'],
        ['label' => '-25 Points', 'action' => 'deduct', 'amount' => 25, 'reason' => 'Minor penalty'],
        ['label' => '-50 Points', 'action' => 'deduct', 'amount' => 50, 'reason' => 'Standard penalty'],
    ];
    
    // Bulk operations
    public $selectedTeams = [];

    protected $rules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string|max:1000',
        'department' => 'nullable|string|max:255',
        'points' => 'required|integer|min:0|max:999999',
        'initial_points' => 'required|integer|min:0|max:999999',
    ];

    protected $messages = [
        'name.required' => 'Team name is required.',
        'name.max' => 'Team name cannot exceed 255 characters.',
        'points.required' => 'Points are required.',
        'points.integer' => 'Points must be a whole number.',
        'points.min' => 'Points cannot be negative.',
        'initial_points.required' => 'Initial points are required.',
        'initial_points.integer' => 'Initial points must be a whole number.',
        'initial_points.min' => 'Initial points cannot be negative.',
    ];

    public function mount()
    {
        $this->resetForm();
    }

    public function updatingSearchTerm()
    {
        $this->resetPage();
    }

    public function createTeam()
    {
        $this->resetForm();
        $this->dispatch('open-team-modal', [
            'teamId' => null,
            'name' => '',
            'department' => '',
            'description' => '',
            'initial_points' => 1000,
            'points' => 1000
        ]);
    }

    public function editTeam($teamId)
    {
        try {
            $team = Team::findOrFail($teamId);
            
            $this->dispatch('open-team-modal', [
                'teamId' => $team->id,
                'name' => $team->name,
                'department' => $team->department ?? '',
                'description' => $team->description ?? '',
                'initial_points' => $team->initial_points,
                'points' => $team->points
            ]);
        } catch (\Exception $e) {
            session()->flash('error', 'Team not found or could not be loaded.');
        }
    }

    public function saveTeam($teamData)
    {
        // Validate the incoming data
        $validator = validator($teamData, [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'department' => 'nullable|string|max:255',
            'points' => 'required|integer|min:0|max:999999',
            'initial_points' => 'required|integer|min:0|max:999999',
        ]);
        
        if ($validator->fails()) {
            session()->flash('error', 'Validation failed: ' . $validator->errors()->first());
            return;
        }

        if ($teamData['teamId']) {
            // Update existing team
            $team = Team::findOrFail($teamData['teamId']);
            $team->update([
                'name' => $teamData['name'],
                'description' => $teamData['description'],
                'department' => $teamData['department'],
                'points' => $teamData['points'],
                'initial_points' => $teamData['initial_points'],
            ]);
            
            session()->flash('success', 'Team updated successfully!');
        } else {
            // Create new team (admin created teams don't automatically have members)
            $team = Team::create([
                'name' => $teamData['name'],
                'description' => $teamData['description'],
                'department' => $teamData['department'],
                'points' => $teamData['points'],
                'initial_points' => $teamData['initial_points'],
                'created_by' => auth()->id(),
            ]);
            
            session()->flash('success', 'Team created successfully! You can now add members to this team.');
        }

        $this->dispatch('close-team-modal');
        $this->resetForm();
    }

    public function forceDeleteTeam($teamId)
    {
        try {
            $team = Team::findOrFail($teamId);
            
            // Use database transaction for safe deletion
            DB::transaction(function () use ($team) {
                // Get user IDs associated with this team
                $userIds = User::where('team_id', $team->id)->pluck('id');
                
                if ($userIds->count() > 0) {
                    // Reset team_id for all users in this team
                    User::whereIn('id', $userIds)->update(['team_id' => null]);
                    
                    // Delete user answers for quiz attempts from these users
                    UserAnswer::whereHas('quizAttempt', function($query) use ($userIds) {
                        $query->whereIn('user_id', $userIds);
                    })->delete();
                    
                    // Delete game assessments for quiz attempts from these users
                    GameAssessment::whereHas('quizAttempt', function($query) use ($userIds) {
                        $query->whereIn('user_id', $userIds);
                    })->delete();
                    
                    // Delete quiz attempts from these users
                    QuizAttempt::whereIn('user_id', $userIds)->delete();
                }
                
                // Delete team members
                $team->members()->delete();
                
                // Finally delete the team
                $team->delete();
            });
            
            session()->flash('success', 'Team and all related data deleted successfully! All users have been removed from the team.');
        } catch (\Exception $e) {
            Log::error('Force team deletion error: ' . $e->getMessage(), [
                'team_id' => $teamId,
                'error' => $e->getTraceAsString()
            ]);
            session()->flash('error', 'Failed to delete team: ' . $e->getMessage());
        }
    }

    public function removeMemberFromTeam($teamId, $memberId)
    {
        try {
            $team = Team::findOrFail($teamId);
            $member = TeamMember::where('team_id', $teamId)->findOrFail($memberId);
            
            // Check if this is the last leader
            if ($member->is_leader) {
                $leaderCount = $team->members()->where('is_leader', true)->count();
                if ($leaderCount <= 1 && $team->members()->count() > 1) {
                    session()->flash('error', 'Cannot remove the last team leader. Please assign leadership to another member first.');
                    return;
                }
            }
            
            DB::transaction(function () use ($member) {
                // If this member is linked to a user, remove the team association
                if ($member->user_id) {
                    $user = User::find($member->user_id);
                    if ($user) {
                        $user->team_id = null;
                        $user->save();
                    }
                }
                
                // Delete the team member record
                $member->delete();
            });
            
            session()->flash('success', 'Team member removed successfully!');
        } catch (\Exception $e) {
            Log::error('Remove member error: ' . $e->getMessage(), [
                'team_id' => $teamId,
                'member_id' => $memberId,
                'error' => $e->getTraceAsString()
            ]);
            session()->flash('error', 'Failed to remove team member: ' . $e->getMessage());
        }
    }

    public function removeUserFromTeam($teamId, $userId)
    {
        try {
            $team = Team::findOrFail($teamId);
            $user = User::where('team_id', $teamId)->findOrFail($userId);
            
            DB::transaction(function () use ($user) {
                // Remove user from team
                $user->team_id = null;
                $user->save();
                
                // Also remove from team_members if they exist there
                TeamMember::where('user_id', $user->id)->delete();
            });
            
            session()->flash('success', 'User removed from team successfully!');
        } catch (\Exception $e) {
            Log::error('Remove user error: ' . $e->getMessage(), [
                'team_id' => $teamId,
                'user_id' => $userId,
                'error' => $e->getTraceAsString()
            ]);
            session()->flash('error', 'Failed to remove user from team: ' . $e->getMessage());
        }
    }

    public function deleteTeam($teamId)
    {
        try {
            $team = Team::findOrFail($teamId);
            
            // Check if team has users (via team_id foreign key)
            $usersInTeam = User::where('team_id', $team->id)->count();
            if ($usersInTeam > 0) {
                session()->flash('error', 'Cannot delete team with existing users. Please remove all users from this team first.');
                return;
            }
            
            // Check if team has members in team_members table
            $membersCount = $team->members()->count();
            if ($membersCount > 0) {
                session()->flash('error', 'Cannot delete team with existing members. Please remove all members first.');
                return;
            }
            
            // Use database transaction for safe deletion
            DB::transaction(function () use ($team) {
                // For empty teams, we still need to clean up any orphaned data
                // that might be referenced by team_id
                
                // Delete team members (should be empty but just in case)
                $team->members()->delete();
                
                // Finally delete the team
                $team->delete();
            });
            
            session()->flash('success', 'Team deleted successfully!');
        } catch (\Exception $e) {
            Log::error('Team deletion error: ' . $e->getMessage(), [
                'team_id' => $teamId,
                'error' => $e->getTraceAsString()
            ]);
            session()->flash('error', 'Failed to delete team: ' . $e->getMessage());
        }
    }

    public function managePoints($teamId)
    {
        try {
            $team = Team::findOrFail($teamId);
            
            $this->dispatch('open-points-modal', [
                'id' => $team->id,
                'name' => $team->name,
                'points' => number_format($team->points, 2),
                'pointsAction' => 'set',
                'pointsAmount' => 0,
                'pointsReason' => ''
            ]);
        } catch (\Exception $e) {
            session()->flash('error', 'Team not found or could not be loaded.');
        }
    }


    public function updatePointsFromModal($teamData)
    {
        $this->validate([
            'teamData.pointsAmount' => 'required|integer|min:0|max:999999',
            'teamData.pointsReason' => 'nullable|string|max:255',
        ], [], [
            'teamData.pointsAmount' => 'points amount',
            'teamData.pointsReason' => 'reason'
        ]);

        $team = Team::findOrFail($teamData['id']);

        switch ($teamData['pointsAction']) {
            case 'set':
                $team->update(['points' => $teamData['pointsAmount']]);
                $message = "Team points set to {$teamData['pointsAmount']}";
                break;
                
            case 'add':
                $team->addPoints($teamData['pointsAmount'], $teamData['pointsReason']);
                $message = "Added {$teamData['pointsAmount']} points to team";
                break;
                
            case 'deduct':
                $team->deductPoints($teamData['pointsAmount'], $teamData['pointsReason']);
                $message = "Deducted {$teamData['pointsAmount']} points from team";
                break;
        }

        session()->flash('success', $message . ($teamData['pointsReason'] ? " - {$teamData['pointsReason']}" : ''));
        $this->dispatch('close-points-modal');
    }

    public function quickPointsAction($teamId, $action, $amount, $reason)
    {
        try {
            $team = Team::findOrFail($teamId);
            
            switch ($action) {
                case 'add':
                    $team->addPoints($amount, $reason);
                    $message = "Added {$amount} points to {$team->name}";
                    break;
                    
                case 'deduct':
                    $team->deductPoints($amount, $reason);
                    $message = "Deducted {$amount} points from {$team->name}";
                    break;
                    
                default:
                    session()->flash('error', 'Invalid action specified.');
                    return;
            }

            session()->flash('success', $message . " - {$reason}");
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to update team points. Please try again.');
        }
    }

    public function resetTeamPoints($teamId)
    {
        $team = Team::findOrFail($teamId);
        $team->resetPoints();
        
        session()->flash('success', "Team points reset to initial amount ({$team->initial_points})");
    }


    
    public function viewMembers($teamId)
    {
        $team = Team::with(['members.user'])->findOrFail($teamId);
        
        Log::info('ViewMembers called', ['team_id' => $teamId, 'members_count' => $team->members->count()]);

        $members = $team->members->map(function($member) {
            $user = $member->user;

            // Fallback manually if user is null and we have email
            if (!$user && $member->email) {
                $user = User::where('email', $member->email)->first();
            }

            return [
                'id' => $member->id,
                'name' => $user?->name ?? $member->name ?? 'Unknown',
                'email' => $user?->email ?? $member->email ?? 'No email',
                'is_leader' => (bool)$member->is_leader,
                'joined_date' => $member->created_at?->format('M d, Y') ?? 'Unknown',
                'type' => 'member'
            ];
        });

        $modalData = [
            'id' => $team->id,
            'name' => $team->name,
            'members' => $members->toArray()
        ];
        
        Log::info('Dispatching members modal', $modalData);
        
        $this->dispatch('open-members-modal', $modalData);
        
    }

    public function viewTeamUsers($teamId)
    {
        $team = Team::findOrFail($teamId);
        $users = User::where('team_id', $teamId)->get();
        
        $usersList = $users->map(function($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_leader' => false, // Users don't have leader status in users table
                'joined_date' => $user->created_at?->format('M d, Y') ?? 'Unknown',
                'type' => 'user'
            ];
        });

        $modalData = [
            'id' => $team->id,
            'name' => $team->name,
            'members' => $usersList->toArray()
        ];
        
        $this->dispatch('open-users-modal', $modalData);
    }

    public function bulkPointsOperation()
    {
        if (empty($this->selectedTeams)) {
            session()->flash('error', 'Please select at least one team.');
            return;
        }
        
        $this->dispatch('open-bulk-modal', [
            'selectedCount' => count($this->selectedTeams),
            'bulkAction' => 'add',
            'bulkAmount' => 0,
            'bulkReason' => ''
        ]);
    }
    

    public function executeBulkPointsFromModal($bulkData)
    {
        $this->validate([
            'bulkData.bulkAmount' => 'required|integer|min:0|max:999999',
            'bulkData.bulkReason' => 'required|string|max:255',
        ], [], [
            'bulkData.bulkAmount' => 'points amount',
            'bulkData.bulkReason' => 'reason'
        ]);
        
        $teams = Team::whereIn('id', $this->selectedTeams)->get();
        $processedCount = 0;
        
        foreach ($teams as $team) {
            switch ($bulkData['bulkAction']) {
                case 'add':
                    $team->addPoints($bulkData['bulkAmount'], $bulkData['bulkReason']);
                    break;
                case 'deduct':
                    $team->deductPoints($bulkData['bulkAmount'], $bulkData['bulkReason']);
                    break;
                case 'set':
                    $team->update(['points' => $bulkData['bulkAmount']]);
                    break;
            }
            $processedCount++;
        }
        
        $actionText = $bulkData['bulkAction'] === 'add' ? 'Added' : ($bulkData['bulkAction'] === 'deduct' ? 'Deducted' : 'Set');
        session()->flash('success', "{$actionText} points for {$processedCount} teams - {$bulkData['bulkReason']}");
        
        $this->selectedTeams = [];
        $this->dispatch('close-bulk-modal');
    }


    public function resetForm()
    {
        $this->teamId = null;
        $this->name = '';
        $this->description = '';
        $this->department = '';
        $this->points = 1000;
        $this->initial_points = 1000;
        $this->pointsAction = 'set';
        $this->pointsAmount = 0;
        $this->pointsReason = '';
    }

    public function getTeamsProperty()
    {
        try {
            $query = Team::with(['members', 'creator']);

            if ($this->searchTerm) {
                $query->where(function($q) {
                    $q->where('name', 'like', '%' . $this->searchTerm . '%')
                      ->orWhere('department', 'like', '%' . $this->searchTerm . '%')
                      ->orWhere('description', 'like', '%' . $this->searchTerm . '%');
                });
            }

            $teams = $query->orderBy('created_at', 'desc')->paginate(10);
            
            // Calculate real total scores for each team and user counts
            foreach ($teams as $team) {
                try {
                    $team->calculated_total_score = $this->calculateTeamTotalScore($team);
                    $team->users_count = User::where('team_id', $team->id)->count();
                } catch (\Exception $e) {
                    Log::warning('Error calculating team data for team ' . $team->id . ': ' . $e->getMessage());
                    $team->calculated_total_score = $team->initial_points ?? 1000;
                    $team->users_count = 0;
                }
            }
            
            return $teams;
        } catch (\Exception $e) {
            Log::error('Error in getTeamsProperty: ' . $e->getMessage());
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10, 1);
        }
    }
    
    /**
     * Calculate team's total score using the correct logic:
     * Base Points + Bonus Points from all team members' completed questionnaires
     */
    private function calculateTeamTotalScore($team)
    {
        // Single base points for the team (not multiplied by member count)
        $basePoints = $team->initial_points ?? 1000;
        $totalBonusPoints = 0;

        // Get ALL team members directly from database
        $teamMembers = User::where('team_id', $team->id)->get();
        
        foreach ($teamMembers as $user) {
            // Get bonus points from this user's completed questionnaires
            $userBonusPoints = $this->calculateUserBonusPoints($user);
            $totalBonusPoints += $userBonusPoints;
        }
        
        // Team Total = Base Points + All Bonus Points from team members
        return $basePoints + $totalBonusPoints;
    }
    
    /**
     * Calculate bonus points for a specific user from completed questionnaires only
     * Uses the EXACT same logic as user-progress page (individual question points + assessment bonus)
     */
    private function calculateUserBonusPoints($user)
    {
        // Get all gained points from correct answers in completed attempts (same as user-progress)
        $userAnswers = \App\Models\UserAnswer::whereHas('quizAttempt', function($query) use ($user) {
            $query->where('user_id', $user->id)
                  ->where('status', 'completed');
        })->with(['question'])->where('is_correct', true)->get();
        
        $earnedPoints = 0;
        foreach ($userAnswers as $answer) {
            if ($answer->question) {
                $earnedPoints += $answer->question->points ?? 0;
            }
        }
        
        // Add assessment gains (bonus from fun games) - same as user-progress
        $assessmentGains = \App\Models\GameAssessment::whereHas('quizAttempt', function($query) use ($user) {
            $query->where('user_id', $user->id)
                  ->where('status', 'completed');
        })->where('is_assessed', true)->get();
        
        $assessmentBonus = 0;
        $basePoints = $user->team ? ($user->team->initial_points ?? 1000) : 1000;
        foreach ($assessmentGains as $assessment) {
            // Assessment gain = total_deposit - base_points_used
            $assessmentGain = ($assessment->total_deposit ?? 0) - $basePoints;
            $assessmentBonus += max(0, $assessmentGain);
        }
        
        return $earnedPoints + $assessmentBonus;
    }

    public function viewGameAssessments($teamId)
    {
        $team = Team::findOrFail($teamId);
        
        // Get team members
        $teamMembers = User::where('team_id', $team->id)->get();
        $memberIds = $teamMembers->pluck('id');
        
        // Get game assessments with verification photos for this team
        $gameAssessments = GameAssessment::with(['quizAttempt.user', 'quizAttempt.questionnaire'])
            ->whereHas('quizAttempt', function($query) use ($memberIds) {
                $query->whereIn('user_id', $memberIds)
                      ->whereNotNull('verification_photo')
                      ->where('verification_photo', '!=', '');
            })
            ->where('is_assessed', true)
            ->orderBy('created_at', 'desc')
            ->get();
        
        $assessmentsData = $gameAssessments->map(function($assessment) {
            $quizAttempt = $assessment->quizAttempt;
            return [
                'id' => $assessment->id,
                'user_name' => $quizAttempt->user->name ?? 'Unknown',
                'questionnaire_title' => $quizAttempt->questionnaire->title ?? 'Unknown Quiz',
                'total_deposit' => $assessment->total_deposit ?? 0,
                'completed_at' => $quizAttempt->completed_at?->format('M d, Y H:i'),
                'photo_captured_at' => $quizAttempt->photo_captured_at?->format('M d, Y H:i'),
                'verification_photo' => $quizAttempt->verification_photo
            ];
        });
        
        $modalData = [
            'team_id' => $team->id,
            'team_name' => $team->name,
            'assessments' => $assessmentsData->toArray()
        ];
        
        $this->dispatch('open-assessments-modal', $modalData);
    }

    public function render()
    {
        $teams = $this->getTeamsProperty();
        
        return view('livewire.admin.team-manager', [
            'teams' => $teams
        ])->layout(null);
    }
}