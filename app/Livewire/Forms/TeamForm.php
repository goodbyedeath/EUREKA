<?php
// app/Livewire/TeamForm.php
namespace App\Livewire\Forms;

use App\Models\Team;
use App\Models\TeamMember;
use Livewire\Component;
use Livewire\Attributes\Validate;

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

    public function mount()
    {
        // Check if user has already registered a team
        $existingTeam = Team::where('created_by', auth()->id())->first();
        if ($existingTeam) {
            return redirect()->route('user.dashboard')
                ->with('info', 'Anda sudah terdaftar dalam tim: ' . $existingTeam->name);
        }

        // Inisialisasi dengan 1 anggota kosong (minimum)
        $this->initializeMembers();
    }

    public function initializeMembers()
    {
        $this->members = [];
        // Start with 1 member minimum
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
            // Jika yang dihapus adalah leader, unset leader status
            if ($this->members[$index]['is_leader']) {
                $this->members[$index]['is_leader'] = false;
            }
            unset($this->members[$index]);
            $this->members = array_values($this->members);
        }
    }

    public function setLeader($index)
    {
        // Reset semua leader status
        foreach ($this->members as $key => $member) {
            $this->members[$key]['is_leader'] = false;
        }
        // Set leader yang dipilih
        $this->members[$index]['is_leader'] = true;
    }

    public function rules()
    {
        $rules = [
            'teamName' => 'required|string|min:3|max:100',
            'teamDescription' => 'nullable|string|max:500',
            'department' => 'nullable|string|max:100',
        ];

        // Validasi untuk setiap member
        foreach ($this->members as $index => $member) {
            $rules["members.{$index}.name"] = 'required|string|min:2|max:100';
            $rules["members.{$index}.email"] = 'required|email|max:100';
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
            'teamDescription.max' => 'Deskripsi maksimal 500 karakter',
            'department.max' => 'Departemen maksimal 100 karakter',
        ];

        // Pesan validasi untuk setiap member
        foreach ($this->members as $index => $member) {
            $memberNumber = $index + 1;
            $messages["members.{$index}.name.required"] = "Nama anggota #{$memberNumber} wajib diisi";
            $messages["members.{$index}.name.min"] = "Nama anggota #{$memberNumber} minimal 2 karakter";
            $messages["members.{$index}.name.max"] = "Nama anggota #{$memberNumber} maksimal 100 karakter";
            $messages["members.{$index}.email.required"] = "Email anggota #{$memberNumber} wajib diisi";
            $messages["members.{$index}.email.email"] = "Format email anggota #{$memberNumber} tidak valid";
            $messages["members.{$index}.email.max"] = "Email anggota #{$memberNumber} maksimal 100 karakter";
            $messages["members.{$index}.phone.max"] = "Nomor telepon anggota #{$memberNumber} maksimal 20 karakter";
            $messages["members.{$index}.position.max"] = "Posisi anggota #{$memberNumber} maksimal 100 karakter";
        }

        return $messages;
    }

    public function save()
{
    $this->validate();

    // Validasi khusus: harus ada minimal satu leader
    $hasLeader = collect($this->members)->contains('is_leader', true);
    if (!$hasLeader) {
        $this->addError('leader', 'Pilih minimal satu ketua tim');
        return;
    }

    // Validasi email unik dalam tim
    $emails = collect($this->members)->pluck('email')->filter();
    if ($emails->count() !== $emails->unique()->count()) {
        $this->addError('email_unique', 'Email anggota tim harus unik');
        return;
    }

    // Check if user has already registered a team
    if (Team::where('created_by', auth()->id())->exists()) {
        $this->addError('save', 'Anda sudah terdaftar dalam tim. Tidak dapat mendaftarkan tim baru.');
        return;
    }

    try {
        // Simpan tim
        $team = Team::create([
            'name' => $this->teamName,
            'description' => $this->teamDescription,
            'department' => $this->department,
            'points' => 1000.00, // Default 1000 points
            'initial_points' => 1000.00, // Default initial points
            'created_by' => auth()->id(),
        ]);

        // Simpan anggota
        foreach ($this->members as $memberData) {
            TeamMember::create([
                'team_id' => $team->id,
                'name' => $memberData['name'],
                'email' => $memberData['email'],
                'phone' => $memberData['phone'],
                'position' => $memberData['position'],
                'is_leader' => $memberData['is_leader'],
            ]);
        }

        // 🔥 Perbarui team_id user yang membuat tim
        $user = auth()->user();
        $user->team_id = $team->id;
        $user->save();

        // Opsional: sinkronkan dengan team_members jika diperlukan
        $user->syncWithTeamMembers();

        // Dispatch event to refresh team data across components
        $this->dispatch('teamUpdated');

        // Flash & redirect
        session()->flash('success', 'Tim berhasil didaftarkan! Selamat datang di dashboard.');
        return redirect()->to(route('user.dashboard'));

    } catch (\Exception $e) {
        $this->addError('save', 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage());
    }
}


    public function render()
    {
        return view('livewire.forms.team-form');
    }
}