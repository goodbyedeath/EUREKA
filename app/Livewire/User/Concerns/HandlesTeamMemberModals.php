<?php

namespace App\Livewire\User\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait HandlesTeamMemberModals
{
    public $showEditModal = false;
    public $editForm = [];
    public $editingMember;

    protected function initializeModalProperties()
    {
        $this->showEditModal = false;

        $this->editForm = [
            'name' => '',
            'email' => '',
            'phone' => '',
            'position' => '',
            'is_leader' => false,
        ];

        $this->editingMember = null;
    }

    public function openEditModal($memberId)
    {
        if (!$this->authorize('edit')) return;

        try {
            $member = $this->team->members()->findOrFail($memberId);

            $this->editingMember = $member;
            $this->editForm = [
                'name' => $member->name,
                'email' => $member->email,
                'phone' => $member->phone ?? '',
                'position' => $member->position ?? '',
                'is_leader' => (bool)$member->is_leader
            ];

            $this->resetErrorBag();
            $this->showEditModal = true;

        } catch (\Exception $e) {
            $this->dispatch('showToast', [
                'type' => 'error',
                'message' => 'Anggota tidak ditemukan.'
            ]);
        }
    }

    public function closeEditModal()
    {
        $this->initializeModalProperties();
        $this->resetErrorBag();
    }

    public function updateMember()
    {
        if (!$this->authorize('edit') || !$this->editingMember) return;

        $this->validate($this->getModalRules(), $this->getModalMessages());

        try {
            DB::beginTransaction();

            // If making this member leader, demote others
            if ($this->editForm['is_leader'] && !$this->editingMember->is_leader) {
                $this->team->members()->update(['is_leader' => false]);
            }

            $this->editingMember->update([
                'name' => $this->editForm['name'],
                'email' => $this->editForm['email'],
                'phone' => $this->editForm['phone'] ?: null,
                'position' => $this->editForm['position'] ?: null,
                'is_leader' => $this->editForm['is_leader']
            ]);

            DB::commit();

            $this->closeEditModal();

            $this->dispatch('memberUpdated');
            $this->dispatch('showToast', [
                'type' => 'success',
                'message' => 'Data anggota berhasil diperbarui.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating team member: ' . $e->getMessage());

            $this->dispatch('showToast', [
                'type' => 'error',
                'message' => 'Gagal memperbarui data anggota.'
            ]);
        }
    }

    public function openDeleteConfirmation($memberId)
    {
        if (!$this->authorize('delete')) return;

        try {
            $member = $this->team->members()->findOrFail($memberId);

            $this->dispatch('confirm-delete', [
                'memberId' => $memberId,
                'title' => 'Hapus Anggota Tim',
                'message' => "Apakah Anda yakin ingin menghapus {$member->name} dari tim? Tindakan ini tidak dapat dibatalkan.",
                'confirmText' => 'Ya, Hapus',
                'cancelText' => 'Batal',
                'type' => 'danger'
            ]);

        } catch (\Exception $e) {
            $this->dispatch('showToast', [
                'type' => 'error',
                'message' => 'Anggota tidak ditemukan.'
            ]);
        }
    }

    public function showMemberDetailModal($memberId)
    {
        try {
            $member = $this->team->members()->findOrFail($memberId);

            $this->dispatch('show-member-detail-modal', [
                'member' => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'phone' => $member->phone,
                    'position' => $member->position,
                    'is_leader' => (bool)$member->is_leader,
                    'joined_date' => $member->created_at->format('d M Y')
                ]
            ]);

        } catch (\Exception $e) {
            $this->dispatch('showToast', [
                'type' => 'error',
                'message' => 'Anggota tidak ditemukan.'
            ]);
        }
    }

    protected function getModalRules(): array
    {
        return [
            'editForm.name' => 'required|string|max:255',
            'editForm.email' => 'required|email|max:255',
            'editForm.phone' => 'nullable|string|max:20',
            'editForm.position' => 'nullable|string|max:100',
            'editForm.is_leader' => 'boolean',
        ];
    }

    protected function getModalMessages(): array
    {
        return [
            'editForm.name.required' => 'Nama wajib diisi.',
            'editForm.email.required' => 'Email wajib diisi.',
            'editForm.email.email' => 'Email tidak valid.',
        ];
    }
}
        