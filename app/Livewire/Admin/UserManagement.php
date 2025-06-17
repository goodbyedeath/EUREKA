<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Models\Team;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $selectedRole = '';
    public $selectedTeam = '';
    public $showModal = false;
    public $editMode = false;
    public $userId;

    // Form properties
    public $name = '';
    public $email = '';
    public $password = '';
    public $role = 'user';
    public $team_id = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'selectedRole' => ['except' => ''],
        'selectedTeam' => ['except' => ''],
    ];

    public function rules()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users')->ignore($this->userId),
            ],
            'role' => 'required|in:admin,user',
            'team_id' => 'nullable|exists:teams,id',
        ];

        if (!$this->editMode) {
            $rules['password'] = 'required|string|min:8';
        } else {
            $rules['password'] = 'nullable|string|min:8';
        }

        return $rules;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSelectedRole()
    {
        $this->resetPage();
    }

    public function updatingSelectedTeam()
    {
        $this->resetPage();
    }

    public function openModal()
    {
        $this->resetForm();
        $this->editMode = false;
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
        $this->resetValidation();
    }

    public function resetForm()
    {
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->role = 'user';
        $this->team_id = '';
        $this->userId = null;
    }

    public function save()
    {
        $this->validate();

        $userData = [
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'team_id' => $this->team_id ?: null,
        ];

        if (!$this->editMode || $this->password) {
            $userData['password'] = Hash::make($this->password);
        }

        if ($this->editMode) {
            $user = User::findOrFail($this->userId);
            $user->update($userData);
            $message = 'User berhasil diperbarui!';
        } else {
            User::create($userData);
            $message = 'User berhasil ditambahkan!';
        }

        $this->closeModal();
        session()->flash('message', $message);
    }

    public function edit($userId)
    {
        $user = User::findOrFail($userId);
        
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role;
        $this->team_id = $user->team_id;
        $this->password = '';
        
        $this->editMode = true;
        $this->showModal = true;
    }

    public function delete($userId)
    {
        User::findOrFail($userId)->delete();
        session()->flash('message', 'User berhasil dihapus!');
    }

    public function getUsers()
    {
        $query = User::with(['team', 'createdTeam'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->selectedRole, function ($query) {
                $query->where('role', $this->selectedRole);
            })
            ->when($this->selectedTeam, function ($query) {
                $query->where('team_id', $this->selectedTeam);
            })
            ->orderBy('created_at', 'desc');

        return $query->paginate(10);
    }

    public function render()
    {
        return view('livewire.admin.user-management', [
            'users' => $this->getUsers(),
            'teams' => Team::orderBy('name')->get(),
            'totalUsers' => User::count(),
            'totalAdmins' => User::where('role', 'admin')->count(),
            'totalRegularUsers' => User::where('role', 'user')->count(),
        ]);
    }
}