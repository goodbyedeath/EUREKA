<?php
namespace App\Livewire\Forms;

use App\Models\Team;
use App\Models\TeamMember;
use Livewire\Component;
use Livewire\Attributes\Validate;
use Illuminate\Support\Facades\DB;

class TeamForm extends Component
{
    #[Validate('required|string|min:3|max:100')]
    public $teamName = '';
    
    #[Validate('nullable|string|max:500')]
    public $teamDescription = '';
    
    #[Validate('nullable|string|max:100')]
    public $department = '';
    
    public $members = [];
    public $showSuccessMessage = false;
    public $savedTeam = null;

    public function mount()
    {
        try {
            $user = auth()->user();

            // Check if user already has a team
            if ($user->team_id || $user->createdTeams()->exists()) {
                // Use JavaScript redirect to avoid Livewire mount issues
                $this->dispatch('redirect-to-dashboard-immediate');
                return;
            }

            // Initialize form for users without teams
            $this->initializeMembers();
            $this->members[0] = [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => '',
                'position' => '',
                'is_leader' => true
            ];
        } catch (\Exception $e) {
            \Log::error('TeamForm mount error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'user_team_id' => auth()->user()->team_id ?? 'null',
                'trace' => $e->getTraceAsString()
            ]);
            $this->dispatch('redirect-to-dashboard-immediate');
        }
    }

    public function initializeMembers()
    {
        $this->members = [];
        $this->members[] = [
            'name' => '',
            'email' => '',
            'phone' => '',
            'position' => '',
            'is_leader' => false
        ];
    }

    public function addMember()
    {
        if (count($this->members) < 10) {
            $this->members[] = [
                'name' => '',
                'email' => '',
                'phone' => '',
                'position' => '',
                'is_leader' => false
            ];
        }
    }

    public function removeMember($index)
    {
        if (count($this->members) > 1) {
            if ($this->members[$index]['is_leader']) {
                $this->members[$index]['is_leader'] = false;
            }
            unset($this->members[$index]);
            $this->members = array_values($this->members);
        }
    }

    public function setLeader($index)
    {
        foreach ($this->members as $key => $member) {
            $this->members[$key]['is_leader'] = false;
        }
        $this->members[$index]['is_leader'] = true;
    }

    public function rules()
    {
        $rules = [
            'teamName' => 'required|string|min:3|max:100|unique:teams,name',
            'teamDescription' => 'nullable|string|max:500',
            'department' => 'nullable|string|max:100',
        ];

        foreach ($this->members as $index => $member) {
            $rules["members.{$index}.name"] = 'required|string|min:2|max:100';
            $rules["members.{$index}.email"] = 'required|email|max:100|unique:team_members,email';
            $rules["members.{$index}.phone"] = 'nullable|string|max:20';
            $rules["members.{$index}.position"] = 'nullable|string|max:100';
        }

        return $rules;
    }

    public function messages()
    {
        $messages = [
            'teamName.required' => 'Nama tim wajib diisi',
            'teamName.min' => 'Nama tim minimal 3 karakter',
            'teamName.max' => 'Nama tim maksimal 100 karakter',
            'teamName.unique' => 'Nama tim sudah digunakan, pilih nama yang berbeda',
            'teamDescription.max' => 'Deskripsi maksimal 500 karakter',
            'department.max' => 'Departemen maksimal 100 karakter',
        ];

        foreach ($this->members as $index => $member) {
            $memberNumber = $index + 1;
            $messages["members.{$index}.name.required"] = "Nama anggota #{$memberNumber} wajib diisi";
            $messages["members.{$index}.name.min"] = "Nama anggota #{$memberNumber} minimal 2 karakter";
            $messages["members.{$index}.name.max"] = "Nama anggota #{$memberNumber} maksimal 100 karakter";
            $messages["members.{$index}.email.required"] = "Email anggota #{$memberNumber} wajib diisi";
            $messages["members.{$index}.email.email"] = "Format email anggota #{$memberNumber} tidak valid";
            $messages["members.{$index}.email.max"] = "Email anggota #{$memberNumber} maksimal 100 karakter";
            $messages["members.{$index}.email.unique"] = "Email anggota #{$memberNumber} sudah terdaftar di tim lain";
            $messages["members.{$index}.phone.max"] = "Nomor telepon anggota #{$memberNumber} maksimal 20 karakter";
            $messages["members.{$index}.position.max"] = "Posisi anggota #{$memberNumber} maksimal 100 karakter";
        }

        return $messages;
    }

    public function save()
    {
        // Double-check: prevent users who already have teams from submitting
        $user = auth()->user();
        if ($user->team_id || $user->createdTeams()->exists()) {
            $this->addError('save', 'Anda sudah memiliki tim dan tidak dapat mendaftarkan tim baru.');
            return;
        }

        $this->validate();

        $hasLeader = collect($this->members)->contains('is_leader', true);
        if (!$hasLeader) {
            $this->addError('leader', 'Pilih minimal satu ketua tim');
            return;
        }

        $emails = collect($this->members)->pluck('email')->filter();
        if ($emails->count() !== $emails->unique()->count()) {
            $this->addError('email_unique', 'Email anggota tim harus unik');
            return;
        }

        // Check again if user has any team association
        if (Team::where('created_by', auth()->id())->exists() || $user->team_id) {
            $this->addError('save', 'Anda sudah terdaftar dalam tim. Tidak dapat mendaftarkan tim baru.');
            return;
        }

        try {
            DB::transaction(function () {
                $team = Team::create([
                    'name' => $this->teamName,
                    'description' => $this->teamDescription,
                    'department' => $this->department,
                    'points' => 1000,
                    'initial_points' => 1000,
                    'created_by' => auth()->id(),
                ]);

                foreach ($this->members as $memberData) {
                    $userId = ($memberData['email'] === auth()->user()->email) ? auth()->id() : null;
                    
                    TeamMember::create([
                        'team_id' => $team->id,
                        'user_id' => $userId,
                        'name' => $memberData['name'],
                        'email' => $memberData['email'],
                        'phone' => $memberData['phone'],
                        'position' => $memberData['position'],
                        'is_leader' => $memberData['is_leader'],
                    ]);
                }

                // Update user's team association
                $user = auth()->user();
                $user->team_id = $team->id;
                $user->save();

                // Ensure the current user is properly linked in team_members
                $currentUserMember = TeamMember::where('team_id', $team->id)
                    ->where('email', $user->email)
                    ->first();
                
                if ($currentUserMember && !$currentUserMember->user_id) {
                    $currentUserMember->user_id = $user->id;
                    $currentUserMember->save();
                }

                $this->savedTeam = $team;
            });

            $this->dispatch('teamUpdated');
            
            // Set success message and redirect
            $this->showSuccessMessage = true;
            $this->dispatch('success-message');
            
            // Use JavaScript redirect to avoid Livewire redirect issues
            $this->dispatch('redirect-to-dashboard');

        } catch (\Exception $e) {
            \Log::error('TeamForm save error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'team_name' => $this->teamName,
                'members_count' => count($this->members),
                'trace' => $e->getTraceAsString()
            ]);
            $this->addError('save', 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.forms.team-form')
            ->layout('components.layouts.app', ['title' => 'Team Registration']);
    }
}
