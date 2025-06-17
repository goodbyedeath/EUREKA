<?php
// app/Livewire/TeamMembers/EditForm.php
namespace App\Livewire\User\TeamMembers;

use Livewire\Component;
use App\Models\TeamMember;

class EditForm extends Component
{
    public TeamMember $member;
    public $name = '';
    public $email = '';
    public $phone = '';
    public $position = '';
    public $is_leader = false;

    protected $rules = [
        'name' => 'required|string|max:255',
        'email' => 'required|email',
        'phone' => 'nullable|string|max:20',
        'position' => 'required|string|max:255',
        'is_leader' => 'boolean'
    ];

    public function mount(TeamMember $member)
    {
        $this->member = $member;
        $this->name = $member->name;
        $this->email = $member->email;
        $this->phone = $member->phone;
        $this->position = $member->position;
        $this->is_leader = $member->is_leader;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:team_members,email,' . $this->member->id,
            'phone' => 'nullable|string|max:20',
            'position' => 'required|string|max:255',
            'is_leader' => 'boolean'
        ]);

        $this->member->update([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'position' => $this->position,
            'is_leader' => $this->is_leader
        ]);

        $this->dispatch('member-updated');
        session()->flash('success', 'Member updated successfully.');
    }

    public function render()
    {
        return view('livewire.user.team-members.edit-form');
    }
}