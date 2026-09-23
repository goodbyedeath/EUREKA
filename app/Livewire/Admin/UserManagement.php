<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Models\Team;
use App\Models\Questionnaire;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
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

    /**
     * An account the database will not let go of yet, waiting for the admin to say what to do.
     *
     * @var array{id: int, name: string, questionnaires: int}|null
     */
    public ?array $pendingDelete = null;

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

        // questionnaires.created_by is ON DELETE RESTRICT, so the database refuses to remove whoever
        // authored one — and the refusal used to arrive as an unhandled SQL error, which the admin saw
        // as a button that did nothing (operator, 23 Sep). Ask first instead.
        $authored = Questionnaire::where('created_by', $user->id)->count();
        if ($authored > 0) {
            $this->pendingDelete = ['id' => $user->id, 'name' => $user->name ?: $user->email, 'questionnaires' => $authored];

            return;
        }

        $this->remove($user);
    }

    /**
     * Hand the account's questionnaires to the admin doing the deleting, then remove it.
     *
     * `created_by` is bookkeeping — who first saved the questionnaire — and grants nothing an admin
     * does not already have, so moving it keeps the questionnaires and their questions untouched.
     */
    public function deleteWithQuestionnaires()
    {
        if (! $this->pendingDelete) {
            return;
        }

        $user = User::findOrFail($this->pendingDelete['id']);

        if ($user->id === auth()->id()) {
            $this->pendingDelete = null;
            session()->flash('error', 'You cannot delete your own account.');

            return;
        }

        $moved = Questionnaire::where('created_by', $user->id)->update(['created_by' => auth()->id()]);
        $this->pendingDelete = null;

        $this->remove($user, " {$moved} kuesionernya kini tercatat atas nama Anda.");
    }

    public function cancelDelete()
    {
        $this->pendingDelete = null;
    }

    /** The delete itself, with the database's own refusals turned into a sentence. */
    private function remove(User $user, string $extra = ''): void
    {
        try {
            $user->delete();
            session()->flash('message', 'Akun dihapus.'.$extra);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Failed to delete user', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            session()->flash('error', 'Akun ini masih terhubung ke data lain, jadi belum bisa dihapus. '
                .'Nonaktifkan akunnya, atau hubungi yang mengelola data itu.');
        }
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
            
            // Setting a timeout IS the "grant access again" action: it clears any
            // spent window so the next login starts a fresh clock.
            $user->grantAccessWindow($timeoutValue);

            session()->flash('message', $timeoutValue === null
                ? 'Timeout removed — this team now has unlimited access.'
                : 'Access granted. The clock starts at their next login.');
            
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