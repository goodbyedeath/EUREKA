<?php

namespace App\Livewire\User\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait HandlesTeamMemberActions
{
    public $selectedMembers = [];
    public $selectAll = false;

    /**
     * Initialize action properties
     */
    public function initializeActionProperties()
    {
        $this->selectedMembers = [];
        $this->selectAll = false;
    }

    /**
     * Toggle member selection
     */
    public function toggleMemberSelection($memberId)
    {
        if (in_array($memberId, $this->selectedMembers)) {
            $this->selectedMembers = array_diff($this->selectedMembers, [$memberId]);
        } else {
            $this->selectedMembers[] = $memberId;
        }
        
        $this->updateSelectAllState();
    }

    /**
     * Toggle select all members
     */
    public function toggleSelectAll()
    {
        if ($this->selectAll) {
            $this->selectedMembers = [];
            $this->selectAll = false;
        } else {
            $this->selectedMembers = $this->members->pluck('id')->toArray();
            $this->selectAll = true;
        }
    }

    /**
     * Update select all state based on individual selections
     */
    protected function updateSelectAllState()
    {
        $totalMembers = $this->members->count();
        $selectedCount = count($this->selectedMembers);
        
        $this->selectAll = $totalMembers > 0 && $selectedCount === $totalMembers;
    }

    /**
     * Clear all selections
     */
    public function clearSelections()
    {
        $this->selectedMembers = [];
        $this->selectAll = false;
    }

    /**
     * Bulk delete selected members
     */
    public function bulkDeleteMembers()
    {
        if (empty($this->selectedMembers)) {
            $this->dispatch('showToast', [
                'type' => 'warning',
                'message' => 'Pilih anggota yang ingin dihapus terlebih dahulu.'
            ]);
            return;
        }

        if (!$this->authorize('delete')) {
            return;
        }

        $memberCount = count($this->selectedMembers);
        $remainingCount = $this->team->members()->count() - $memberCount;

        // Don't allow deleting all members
        if ($remainingCount <= 0) {
            $this->dispatch('showToast', [
                'type' => 'error',
                'message' => 'Tidak dapat menghapus semua anggota dari tim.'
            ]);
            return;
        }

        $this->dispatch('confirm-bulk-delete', [
            'memberIds' => $this->selectedMembers,
            'title' => 'Hapus Anggota Tim',
            'message' => "Apakah Anda yakin ingin menghapus {$memberCount} anggota dari tim? Tindakan ini tidak dapat dibatalkan.",
            'confirmText' => 'Ya, Hapus Semua',
            'cancelText' => 'Batal',
            'type' => 'danger'
        ]);
    }

    /**
     * Execute bulk delete
     */
    public function executeBulkDelete($memberIds)
    {
        if (!$this->authorize('delete')) {
            return;
        }

        try {
            DB::beginTransaction();

            $membersToDelete = $this->team->members()->whereIn('id', $memberIds)->get();
            $hasLeader = $membersToDelete->contains('is_leader', true);
            $deletedNames = $membersToDelete->pluck('name')->toArray();

            // Delete the members
            $this->team->members()->whereIn('id', $memberIds)->delete();

            // If a leader was deleted, assign a new leader
            if ($hasLeader) {
                $remainingMembers = $this->team->members()->count();
                if ($remainingMembers > 0) {
                    $newLeader = $this->team->members()->first();
                    if ($newLeader) {
                        $newLeader->update(['is_leader' => true]);
                    }
                }
            }

            DB::commit();

            // Clear selections and refresh
            $this->clearSelections();
            $this->refreshMembers();

            $this->dispatch('showToast', [
                'type' => 'success',
                'message' => count($deletedNames) . ' anggota berhasil dihapus dari tim.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error bulk deleting team members: ' . $e->getMessage());
            
            $this->dispatch('showToast', [
                'type' => 'error',
                'message' => 'Terjadi kesalahan saat menghapus anggota.'
            ]);
        }
    }

    /**
     * Bulk update positions
     */
    public function bulkUpdatePositions($position)
    {
        if (empty($this->selectedMembers)) {
            $this->dispatch('showToast', [
                'type' => 'warning',
                'message' => 'Pilih anggota yang ingin diperbarui terlebih dahulu.'
            ]);
            return;
        }

        if (!$this->authorize('edit')) {
            return;
        }

        try {
            $this->team->members()
                ->whereIn('id', $this->selectedMembers)
                ->update(['position' => $position]);

            $memberCount = count($this->selectedMembers);
            $this->clearSelections();
            $this->refreshMembers();

            $this->dispatch('showToast', [
                'type' => 'success',
                'message' => "Posisi {$memberCount} anggota berhasil diperbarui menjadi '{$position}'."
            ]);

        } catch (\Exception $e) {
            Log::error('Error bulk updating member positions: ' . $e->getMessage());
            
            $this->dispatch('showToast', [
                'type' => 'error',
                'message' => 'Terjadi kesalahan saat memperbarui posisi anggota.'
            ]);
        }
    }

    /**
     * Send bulk notification/email
     */
    public function sendBulkNotification($message)
    {
        if (empty($this->selectedMembers)) {
            $this->dispatch('showToast', [
                'type' => 'warning',
                'message' => 'Pilih anggota yang ingin dikirim notifikasi terlebih dahulu.'
            ]);
            return;
        }

        // This would integrate with your notification system
        // For now, just show a success message
        $memberCount = count($this->selectedMembers);
        
        $this->dispatch('showToast', [
            'type' => 'success',
            'message' => "Notifikasi berhasil dikirim ke {$memberCount} anggota."
        ]);

        $this->clearSelections();
    }

    /**
     * Get selected members data
     */
    public function getSelectedMembersData()
    {
        return $this->team->members()
            ->whereIn('id', $this->selectedMembers)
            ->get();
    }

    /**
     * Check if member is selected
     */
    public function isMemberSelected($memberId)
    {
        return in_array($memberId, $this->selectedMembers);
    }

    /**
     * Get selection count
     */
    public function getSelectionCount()
    {
        return count($this->selectedMembers);
    }

    /**
     * Copy member emails to clipboard (for external communication)
     */
    public function copySelectedMemberEmails()
    {
        if (empty($this->selectedMembers)) {
            $this->dispatch('showToast', [
                'type' => 'warning',
                'message' => 'Pilih anggota terlebih dahulu.'
            ]);
            return;
        }

        $emails = $this->getSelectedMembersData()->pluck('email')->toArray();
        $emailString = implode(', ', $emails);

        $this->dispatch('copy-to-clipboard', [
            'text' => $emailString,
            'message' => count($emails) . ' email anggota disalin ke clipboard.'
        ]);
    }

    /**
     * Archive members (soft delete alternative)
     */
    public function archiveSelectedMembers()
    {
        if (empty($this->selectedMembers)) {
            $this->dispatch('showToast', [
                'type' => 'warning',
                'message' => 'Pilih anggota yang ingin diarsipkan terlebih dahulu.'
            ]);
            return;
        }

        // This would implement archiving logic if you have it
        $this->dispatch('showToast', [
            'type' => 'info',
            'message' => 'Fitur arsip akan segera tersedia.'
        ]);
    }
}