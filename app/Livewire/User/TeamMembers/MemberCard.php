<?php
namespace App\Livewire\User\TeamMembers;

use Livewire\Component;
use App\Models\TeamMember;

class MemberCard extends Component
{
    public TeamMember $member;
    public $isLeader = false;

    public function mount(TeamMember $member, $isLeader = false)
    {
        $this->member = $member;
        $this->isLeader = $isLeader;
    }

    public function render()
    {
        return view('livewire.user.team-members.member-card');
    }
}