<?php
// app/Livewire/TeamMembers/CreateForm.php
namespace App\Livewire\User\TeamMembers;

use Livewire\Component;
use App\Models\TeamMember;
use Illuminate\Support\Facades\Auth;

class CreateForm extends Component
{
    public $name = '';
    public $email = '';
    public $phone = '';
    public $position = '';
    public $is_leader = false;

    protected $rules = [
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:team_members,email',
        'phone' => 'nullable|string|max:20',
        'position' => 'required|string|max:255',
        'is_leader' => 'boolean'
    ];

    public function save()
    {
        $this->validate();

        $userTeam = Auth::user()->team;
        if (!$userTeam) {
            session()->flash('error', 'No team found.');
            return;
        }

        TeamMember::create([
            'team_id' => $userTeam->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'position' => $this->position,
            'is_leader' => $this->is_leader
        ]);

        $this->reset();
        $this->dispatch('member-created');
        session()->flash('success', 'Member added successfully.');
    }

    public function render()
    {
        return view('livewire.user.team-members.create-form');
    }
}