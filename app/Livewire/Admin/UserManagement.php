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
    public $session_timeout = '3600'; // Default 1 hour (as string)

    protected $queryString = [
        'search' => ['except' => ''],
        'selectedRole' => ['except' => ''],
        'selectedTeam' => ['except' => ''],
    ];

    public function rules()
    {
        $rules = [
            'name' => 'required|string|max:255|min:2',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users')->ignore($this->userId),
            ],
            'role' => 'required|in:admin,user',
            'team_id' => 'nullable|exists:teams,id',
            'session_timeout' => 'nullable|string', // Allow empty string for "No timeout"
        ];

        if (!$this->editMode) {
            $rules['password'] = 'required|string|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$/';
        } else {
            $rules['password'] = 'nullable|string|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$/';
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
        $this->session_timeout = '3600'; // Default 1 hour (as string)
        $this->userId = null;
    }

    public function save()
    {
        $this->validate();
        
        // Prevent current user from demoting themselves from admin
        if ($this->editMode && $this->userId == auth()->id()) {
            $currentUser = User::find($this->userId);
            if ($currentUser->role === 'admin' && $this->role !== 'admin') {
                session()->flash('error', 'You cannot change your own admin role.');
                return;
            }
        }
        
        // Prevent changing the last admin to user role
        if ($this->editMode && $this->role === 'user') {
            $user = User::find($this->userId);
            if ($user && $user->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
                session()->flash('error', 'Cannot change the last admin to user role.');
                return;
            }
        }

        $userData = [
            'name' => trim($this->name),
            'email' => strtolower(trim($this->email)),
            'role' => $this->role,
            'team_id' => $this->team_id ?: null,
        ];

        // Add session_timeout to userData directly - no need for separate method calls
        if ($this->role === 'user') {
            // Convert empty string to null for "No timeout" option
            $userData['session_timeout'] = ($this->session_timeout === '' || $this->session_timeout === null) ? null : (int) $this->session_timeout;
        } else {
            // Admin users should have no session timeout
            $userData['session_timeout'] = null;
        }

        if (!$this->editMode || $this->password) {
            $userData['password'] = Hash::make($this->password);
        }

        if ($this->editMode) {
            $user = User::findOrFail($this->userId);
            $user->update($userData);
            $message = 'User berhasil diperbarui!';
        } else {
            $user = User::create($userData);
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
        
        // Handle session timeout safely - convert to string for form
        try {
            $timeout = $user->getSessionTimeout();
            $this->session_timeout = $timeout === null ? '' : (string) $timeout;
        } catch (\Exception $e) {
            $this->session_timeout = '3600'; // Default 1 hour
        }
        
        $this->password = '';
        
        $this->editMode = true;
        $this->showModal = true;
    }

    public function delete($userId)
    {
        $user = User::findOrFail($userId);
        
        // Prevent self-deletion
        if ($user->id === auth()->id()) {
            session()->flash('error', 'You cannot delete your own account.');
            return;
        }
        
        // Prevent deleting the last admin
        if ($user->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
            session()->flash('error', 'Cannot delete the last admin user.');
            return;
        }
        
        $user->delete();
        session()->flash('message', 'User berhasil dihapus!');
    }

    public function setSessionTimeout($userId, $timeout)
    {
        try {
            $user = User::findOrFail($userId);
            
            if ($user->role !== 'user') {
                session()->flash('error', 'Session timeout can only be set for regular users.');
                return;
            }
            
            // Convert empty string or 'null' string to actual null
            $timeoutValue = ($timeout === '' || $timeout === 'null' || $timeout === null) ? null : (int) $timeout;
            
            // Update the user directly instead of using the model method
            $user->update(['session_timeout' => $timeoutValue]);
            
            session()->flash('message', 'Session timeout updated successfully!');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to update session timeout: ' . $e->getMessage());
        }
    }

    public function getSessionTimeoutOptions()
    {
        return [
            300 => '5 minutes',
            900 => '15 minutes',
            1800 => '30 minutes',
            3600 => '1 hour',
            7200 => '2 hours',
            14400 => '4 hours',
            28800 => '8 hours',
            '' => 'No timeout'  // Use empty string instead of null
        ];
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
            'sessionTimeoutOptions' => $this->getSessionTimeoutOptions(),
            'totalUsers' => User::count(),
            'totalAdmins' => User::where('role', 'admin')->count(),
            'totalRegularUsers' => User::where('role', 'user')->count(),
        ])->layout(null);
    }
}