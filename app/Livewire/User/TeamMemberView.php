<?php

namespace App\Livewire\User;

use App\Models\TeamMember;
use App\Models\Team;
use Livewire\Component;
use Livewire\Attributes\On;

class TeamMemberView extends Component
{
    public $showModal = false;
    public $showDeleteModal = false;
    public $showDetailModal = false;
    public $modalTitle = '';
    public $editingMember = null;
    public $deletingMember = null;
    public $viewingMember = null;
    
    // Form fields
    public $name = '';
    public $email = '';
    public $phone = '';
    public $position = '';
    public $is_leader = false;

    protected $rules = [
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255',
        'phone' => 'nullable|string|max:20',
        'position' => 'required|string|max:255',
        'is_leader' => 'boolean'
    ];

    public function render()
    {
        $user = auth()->user();
        $teamMembers = collect();
        $userTeam = null;
        
        if ($user->team_id) {
            $teamMembers = TeamMember::where('team_id', $user->team_id)
                ->orderBy('is_leader', 'desc')
                ->orderBy('name')
                ->get();
            
            $userTeam = $user->team;
        }

        return view('livewire.user.team-member-view', [
            'teamMembers' => $teamMembers,
            'userTeam' => $userTeam
        ]);
    }

    public function addMember()
    {
        $this->resetForm();
        $this->modalTitle = 'Add Team Member';
        $this->editingMember = null;
        $this->showModal = true;
    }

    public function editMember($memberId)
    {
        $member = TeamMember::findOrFail($memberId);
        $this->editingMember = $member;
        $this->name = $member->name;
        $this->email = $member->email;
        $this->phone = $member->phone;
        $this->position = $member->position;
        $this->is_leader = $member->is_leader;
        $this->modalTitle = 'Edit Team Member';
        $this->showModal = true;
    }

    public function saveMember()
    {
        $this->validate();

        $user = auth()->user();
        
        if (!$user->team_id) {
            session()->flash('error', 'You must be assigned to a team to manage members.');
            return;
        }

        $data = [
            'team_id' => $user->team_id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'position' => $this->position,
            'is_leader' => $this->is_leader
        ];

        if ($this->editingMember) {
            $this->editingMember->update($data);
            session()->flash('message', 'Team member updated successfully!');
        } else {
            TeamMember::create($data);
            session()->flash('message', 'Team member added successfully!');
        }

        $this->closeModal();
    }

    public function confirmDelete($memberId)
    {
        $this->deletingMember = TeamMember::findOrFail($memberId);
        $this->showDeleteModal = true;
    }

    public function deleteMember()
    {
        if ($this->deletingMember) {
            $this->deletingMember->delete();
            session()->flash('message', 'Team member deleted successfully!');
            $this->closeDeleteModal();
        }
    }

    public function viewMember($memberId)
    {
        $this->viewingMember = TeamMember::with('team')->findOrFail($memberId);
        $this->showDetailModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->deletingMember = null;
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->viewingMember = null;
    }

    private function resetForm()
    {
        $this->name = '';
        $this->email = '';
        $this->phone = '';
        $this->position = '';
        $this->is_leader = false;
        $this->editingMember = null;
    }
}