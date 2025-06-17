<?php
// app/Livewire/TeamMembers/Index.php
namespace App\Livewire\User\TeamMembers;

use Livewire\Component;
use App\Models\TeamMember;
use App\Models\Team;
use Illuminate\Support\Facades\Auth;

class Index extends Component
{
    public $showCreateModal = false;
    public $showEditModal = false;
    public $showDetailModal = false;
    public $showDeleteModal = false;
    
    public $selectedMember = null;
    public $userTeam = null;
    public $isLeader = false;

    public function mount()
    {
        $this->userTeam = Auth::user()->team;
        $this->isLeader = Auth::user()->isTeamLeader();
        
        if (!$this->userTeam) {
            session()->flash('error', 'You are not assigned to any team.');
        }
    }

    public function openCreateModal()
    {
        if (!$this->isLeader) {
            session()->flash('error', 'Only team leaders can add new members.');
            return;
        }
        $this->showCreateModal = true;
    }

    public function openEditModal($memberId)
    {
        if (!$this->isLeader) {
            session()->flash('error', 'Only team leaders can edit members.');
            return;
        }
        $this->selectedMember = TeamMember::find($memberId);
        $this->showEditModal = true;
    }

    public function openDetailModal($memberId)
    {
        $this->selectedMember = TeamMember::find($memberId);
        $this->showDetailModal = true;
    }

    public function openDeleteModal($memberId)
    {
        if (!$this->isLeader) {
            session()->flash('error', 'Only team leaders can delete members.');
            return;
        }
        $this->selectedMember = TeamMember::find($memberId);
        $this->showDeleteModal = true;
    }

    public function deleteMember()
    {
        if (!$this->isLeader || !$this->selectedMember) {
            return;
        }

        $this->selectedMember->delete();
        $this->showDeleteModal = false;
        $this->selectedMember = null;
        session()->flash('success', 'Member deleted successfully.');
    }

    public function makeLeader($memberId)
    {
        if (!$this->isLeader) {
            session()->flash('error', 'Only team leaders can assign leadership.');
            return;
        }

        $this->userTeam->makeLeader($memberId);
        $this->isLeader = Auth::user()->isTeamLeader(); // Refresh leadership status
        session()->flash('success', 'Leadership transferred successfully.');
    }

    public function closeModals()
    {
        $this->showCreateModal = false;
        $this->showEditModal = false;
        $this->showDetailModal = false;
        $this->showDeleteModal = false;
        $this->selectedMember = null;
    }

    public function render()
    {
        $members = $this->userTeam ? $this->userTeam->members()->get() : collect();
        
        return view('livewire.user.team-members.index', [
            'members' => $members
        ]);
    }
}