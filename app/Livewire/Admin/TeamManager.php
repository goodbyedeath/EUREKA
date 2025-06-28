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
    public $selectedTeam = null;
    public $showEditModal = false;
    public $showMembersModal = false;
    
    // Team form data
    public $teamId = null;
    public $name = '';
    public $description = '';
    public $department = '';
    public $points = 1000;
    public $initial_points = 1000;
    
    // Points management
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
    public $showBulkModal = false;
    public $bulkAction = 'add';
    public $bulkAmount = 0;
    public $bulkReason = '';

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
        $this->showEditModal = true;
    }

    public function editTeam($teamId)
    {
        $team = Team::findOrFail($teamId);
        
        $this->teamId = $team->id;
        $this->name = $team->name;
        $this->description = $team->description ?? '';
        $this->department = $team->department ?? '';
        $this->points = $team->points;
        $this->initial_points = $team->initial_points;
        
        $this->showEditModal = true;
    }

    public function saveTeam()
    {
        $this->validate();

        if ($this->teamId) {
            // Update existing team
            $team = Team::findOrFail($this->teamId);
            $team->update([
                'name' => $this->name,
                'description' => $this->description,
                'department' => $this->department,
                'points' => $this->points,
                'initial_points' => $this->initial_points,
            ]);
            
            session()->flash('success', 'Team updated successfully!');
        } else {
            // Create new team
            Team::create([
                'name' => $this->name,
                'description' => $this->description,
                'department' => $this->department,
                'points' => $this->points,
                'initial_points' => $this->initial_points,
                'created_by' => auth()->id(),
            ]);
            
            session()->flash('success', 'Team created successfully!');
        }

        $this->closeModal();
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
        $this->selectedTeam = Team::with(['members.user'])->findOrFail($teamId);
        $this->pointsAction = 'set';
        $this->pointsAmount = $this->selectedTeam->points;
        $this->pointsReason = '';
    }

    public function updatePoints()
    {
        $this->validate([
            'pointsAmount' => 'required|integer|min:0|max:999999',
            'pointsReason' => 'nullable|string|max:255',
        ]);

        if (!$this->selectedTeam) {
            return;
        }

        switch ($this->pointsAction) {
            case 'set':
                $this->selectedTeam->update(['points' => $this->pointsAmount]);
                $message = "Team points set to {$this->pointsAmount}";
                break;
                
            case 'add':
                $this->selectedTeam->addPoints($this->pointsAmount, $this->pointsReason);
                $message = "Added {$this->pointsAmount} points to team";
                break;
                
            case 'deduct':
                $this->selectedTeam->deductPoints($this->pointsAmount, $this->pointsReason);
                $message = "Deducted {$this->pointsAmount} points from team";
                break;
        }

        session()->flash('success', $message . ($this->pointsReason ? " - {$this->pointsReason}" : ''));
        $this->selectedTeam = null;
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
        $this->selectedTeam = Team::with(['members.user'])->findOrFail($teamId);
        $this->showMembersModal = true;
    }

    public function bulkPointsOperation()
    {
        if (empty($this->selectedTeams)) {
            session()->flash('error', 'Please select at least one team.');
            return;
        }
        
        $this->showBulkModal = true;
    }
    
    public function executeBulkPoints()
    {
        $this->validate([
            'bulkAmount' => 'required|integer|min:0|max:999999',
            'bulkReason' => 'required|string|max:255',
        ]);
        
        $teams = Team::whereIn('id', $this->selectedTeams)->get();
        $processedCount = 0;
        
        foreach ($teams as $team) {
            switch ($this->bulkAction) {
                case 'add':
                    $team->addPoints($this->bulkAmount, $this->bulkReason);
                    break;
                case 'deduct':
                    $team->deductPoints($this->bulkAmount, $this->bulkReason);
                    break;
                case 'set':
                    $team->update(['points' => $this->bulkAmount]);
                    break;
            }
            $processedCount++;
        }
        
        $actionText = $this->bulkAction === 'add' ? 'Added' : ($this->bulkAction === 'deduct' ? 'Deducted' : 'Set');
        session()->flash('success', "{$actionText} points for {$processedCount} teams - {$this->bulkReason}");
        
        $this->selectedTeams = [];
        $this->showBulkModal = false;
        $this->bulkAmount = 0;
        $this->bulkReason = '';
    }

    public function closeModal()
    {
        $this->showEditModal = false;
        $this->showMembersModal = false;
        $this->showBulkModal = false;
        $this->selectedTeam = null;
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