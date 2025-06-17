<?php

namespace App\Livewire\User\Components;

use App\Models\TeamMember;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Locked;
use Illuminate\Support\Facades\Gate;

class TeamMemberCard extends Component
{
    #[Locked]
    public TeamMember $member;
    
    #[Locked]
    public bool $canEdit = false;
    
    #[Locked]
    public bool $canDelete = false;
    
    #[Locked]
    public bool $canMakeLeader = false;
    
    #[Locked]
    public int $totalMembers = 1;

    public bool $isLoading = false;

    protected $listeners = [
        'refreshMember' => 'handleRefresh',
        'memberUpdated' => 'handleMemberUpdated'
    ];

    public function mount(TeamMember $member, array $permissions = [], int $totalMembers = 1)
    {
        $this->member = $member;
        $this->totalMembers = $totalMembers;
        
        // Set permissions with proper authorization checks
        $this->canEdit = $permissions['edit'] ?? $this->authorizeAction('update');
        $this->canDelete = $permissions['delete'] ?? $this->authorizeAction('delete');
        $this->canMakeLeader = $permissions['makeLeader'] ?? $this->authorizeAction('makeLeader');
    }

    public function boot()
    {
        // Ensure member exists and is valid
        if (!$this->member || !$this->member->exists) {
            throw new \InvalidArgumentException('Invalid team member provided');
        }
    }

    #[On('member-updated')]
    public function handleMemberUpdated($memberId): void
    {
        if ($this->member->id === $memberId) {
            $this->refreshMemberData();
        }
    }

    public function handleRefresh(): void
    {
        $this->refreshMemberData();
    }

    private function refreshMemberData(): void
    {
        try {
            $this->isLoading = true;
            
            $freshMember = $this->member->fresh();
            if ($freshMember) {
                $this->member = $freshMember;
            }
        } catch (\Exception $e) {
            $this->handleError('Failed to refresh member data', $e);
        } finally {
            $this->isLoading = false;
        }
    }

    public function editMember(): void
    {
        if (!$this->canEdit) {
            $this->showErrorToast('You do not have permission to edit this member');
            return;
        }

        $this->dispatchToParent('editMember', memberId: $this->member->id);
    }

    public function makeLeader(): void
    {
        if (!$this->canMakeLeader) {
            $this->showErrorToast('You do not have permission to make this member a leader');
            return;
        }

        if ($this->member->is_leader) {
            $this->showErrorToast('This member is already a leader');
            return;
        }

        $this->dispatchToParent('makeLeader', memberId: $this->member->id);
    }

    public function confirmDeleteMember(): void
    {
        if (!$this->canDelete) {
            $this->showErrorToast('You do not have permission to delete this member');
            return;
        }

        // Prevent deleting the last member
        if ($this->totalMembers <= 1) {
            $this->showErrorToast('Cannot delete the last team member');
            return;
        }

        $this->dispatchToParent('confirmDeleteMember', memberId: $this->member->id);
    }

    public function showMemberDetail(): void
    {
        $this->dispatchToParent('show-member-detail', memberId: $this->member->id);
    }

    #[On('tab-switched')]
    public function handleTabSwitch(array $data): void
    {
        if (($data['newTab'] ?? '') === 'members') {
            $this->refreshMemberData();
        }
    }

    private function authorizeAction(string $action): bool
    {
        try {
            return Gate::allows($action, $this->member);
        } catch (\Exception $e) {
            $this->handleError("Authorization check failed for action: {$action}", $e);
            return false;
        }
    }

    private function dispatchToParent(string $event, ...$params): void
    {
        try {
            $this->dispatch($event, ...$params)->to('user.team-member-view');
        } catch (\Exception $e) {
            $this->handleError("Failed to dispatch event: {$event}", $e);
        }
    }

    private function showErrorToast(string $message): void
    {
        $this->dispatch('showToast', [
            'type' => 'error',
            'message' => $message
        ]);
    }

    private function handleError(string $message, \Exception $e): void
    {
        logger()->error($message, [
            'exception' => $e->getMessage(),
            'member_id' => $this->member->id ?? 'unknown',
            'component' => self::class
        ]);
        
        $this->showErrorToast('An error occurred. Please try again.');
    }

    public function render()
    {
        return view('livewire.user.components.team-member-card', [
            'member' => $this->member,
            'canEdit' => $this->canEdit,
            'canDelete' => $this->canDelete,
            'canMakeLeader' => $this->canMakeLeader,
            'isLoading' => $this->isLoading
        ]);
    }
}