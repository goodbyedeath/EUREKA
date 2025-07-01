<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Team;
use App\Models\User;
use App\Models\TeamMember;
use Livewire\WithPagination;

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
        $team = Team::findOrFail($teamId);
        
        $this->dispatch('open-team-modal', [
            'teamId' => $team->id,
            'name' => $team->name,
            'department' => $team->department ?? '',
            'description' => $team->description ?? '',
            'initial_points' => $team->initial_points,
            'points' => $team->points
        ]);
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
            // Create new team
            Team::create([
                'name' => $teamData['name'],
                'description' => $teamData['description'],
                'department' => $teamData['department'],
                'points' => $teamData['points'],
                'initial_points' => $teamData['initial_points'],
                'created_by' => auth()->id(),
            ]);
            
            session()->flash('success', 'Team created successfully!');
        }

        $this->dispatch('close-team-modal');
        $this->resetForm();
    }

    public function deleteTeam($teamId)
    {
        $team = Team::findOrFail($teamId);
        
        // Check if team has members
        if ($team->members()->count() > 0) {
            session()->flash('error', 'Cannot delete team with existing members. Please remove all members first.');
            return;
        }
        
        $team->delete();
        session()->flash('success', 'Team deleted successfully!');
    }

    public function managePoints($teamId)
    {
        $team = Team::findOrFail($teamId);
        
        $this->dispatch('open-points-modal', [
            'id' => $team->id,
            'name' => $team->name,
            'points' => number_format($team->points, 2),
            'pointsAction' => 'set',
            'pointsAmount' => 0,
            'pointsReason' => ''
        ]);
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
        }

        session()->flash('success', $message . " - {$reason}");
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

        $members = $team->members->map(function($member) {
            $user = $member->user;

            // Fallback manually if user is null and user_id is missing
            if (!$user && $member->email) {
                $user = User::where('email', $member->email)->first();
            }

            return [
                'id' => $user?->id,
                'name' => $user?->name ?? $member->name,
                'email' => $user?->email ?? $member->email,
                'is_leader' => $member->is_leader,
                'joined_date' => $member->created_at->format('M d, Y'),
            ];
        });

        $this->dispatch('open-members-modal', [
            'id' => $team->id,
            'name' => $team->name,
            'members' => $members->toArray()
        ]);
        
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
        $query = Team::with(['members', 'creator']);

        if ($this->searchTerm) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->searchTerm . '%')
                  ->orWhere('department', 'like', '%' . $this->searchTerm . '%')
                  ->orWhere('description', 'like', '%' . $this->searchTerm . '%');
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate(10);
    }

    public function render()
    {
        return view('livewire.admin.team-manager');
    }
}