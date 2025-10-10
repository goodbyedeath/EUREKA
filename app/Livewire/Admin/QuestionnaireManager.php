<?php
// app/Livewire/Admin/QuestionnaireManager.php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Questionnaire;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QuestionnaireManager extends Component
{
    public bool $showCreateModal = false;
    public bool $showEditModal = false;
    public bool $showQuestionsModal = false;
    public bool $showQrModal = false;
    public bool $showDeleteModal = false;
    public ?Questionnaire $selectedQuestionnaire = null;
    public ?Questionnaire $editQuestionnaire = null;
    public ?Questionnaire $qrQuestionnaire = null;
    public ?Questionnaire $deleteQuestionnaire = null;
    
    // Edit form properties
    public string $editTitle = '';
    public string $editDescription = '';
    public ?int $editTimeLimit = null;
    public ?int $editMaxAttempts = null;
    public ?int $editPassPercentage = null;
    public string $editQrCode = '';
    public bool $editIsActive = true;

    protected $listeners = [
        'questionnaire-created' => '$refresh',
        'questionnaire-updated' => '$refresh',
        'close-create-modal' => 'closeCreateModal',
        'open-edit-modal' => 'openEditModal',
        'close-edit-modal' => 'closeEditModal',
        'open-questions-modal' => 'openQuestionsModal',
        'close-questions-modal' => 'closeQuestionsModal',
        'open-qr-modal' => 'openQrModal',
        'close-qr-modal' => 'closeQrModal'
    ];

    public function openCreateModal()
    {
        $this->showCreateModal = true;
    }

    public function closeCreateModal()
    {
        $this->showCreateModal = false;
    }

    public function openEditModal(int $questionnaireId)
    {
        try {
            $this->editQuestionnaire = Questionnaire::findOrFail($questionnaireId);
            
            // Populate form fields with current values
            $this->editTitle = $this->editQuestionnaire->title;
            $this->editDescription = $this->editQuestionnaire->description ?? '';
            $this->editTimeLimit = $this->editQuestionnaire->time_limit;
            $this->editMaxAttempts = $this->editQuestionnaire->max_attempts;
            $this->editPassPercentage = $this->editQuestionnaire->pass_percentage;
            $this->editQrCode = $this->editQuestionnaire->qr_code ?? '';
            $this->editIsActive = $this->editQuestionnaire->is_active;
            
            $this->showEditModal = true;
        } catch (\Exception $e) {
            session()->flash('error', 'Questionnaire not found.');
            Log::error('Error opening edit modal: ' . $e->getMessage());
        }
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->editQuestionnaire = null;
        $this->resetEditForm();
    }

    private function resetEditForm()
    {
        $this->editTitle = '';
        $this->editDescription = '';
        $this->editTimeLimit = null;
        $this->editMaxAttempts = null;
        $this->editPassPercentage = null;
        $this->editQrCode = '';
        $this->editIsActive = true;
    }

    public function updateQuestionnaire()
    {
        if (!$this->editQuestionnaire) {
            session()->flash('error', 'No questionnaire selected for editing.');
            return;
        }

        $this->validate([
            'editTitle' => 'required|string|max:255',
            'editDescription' => 'nullable|string|max:1000',
            'editTimeLimit' => 'nullable|integer|min:1|max:1440', // Max 24 hours
            'editMaxAttempts' => 'nullable|integer|min:1|max:10',
            'editPassPercentage' => 'nullable|integer|min:1|max:100',
            'editQrCode' => 'nullable|string|max:255',
        ], [
            'editTitle.required' => 'Title is required.',
            'editTitle.max' => 'Title cannot exceed 255 characters.',
            'editDescription.max' => 'Description cannot exceed 1000 characters.',
            'editTimeLimit.min' => 'Time limit must be at least 1 minute.',
            'editTimeLimit.max' => 'Time limit cannot exceed 1440 minutes (24 hours).',
            'editMaxAttempts.min' => 'Maximum attempts must be at least 1.',
            'editMaxAttempts.max' => 'Maximum attempts cannot exceed 10.',
            'editPassPercentage.min' => 'Pass percentage must be at least 1%.',
            'editPassPercentage.max' => 'Pass percentage cannot exceed 100%.',
        ]);

        try {
            DB::transaction(function () {
                $this->editQuestionnaire->update([
                    'title' => $this->editTitle,
                    'description' => $this->editDescription ?: null,
                    'time_limit' => $this->editTimeLimit,
                    'max_attempts' => $this->editMaxAttempts,
                    'pass_percentage' => $this->editPassPercentage,
                    'qr_code' => $this->editQrCode ?: null,
                    'is_active' => $this->editIsActive,
                    'updated_at' => now()
                ]);
                
                Log::info("Questionnaire updated: ID {$this->editQuestionnaire->id}, Title: {$this->editTitle}");
            });
            
            session()->flash('message', "Questionnaire '{$this->editTitle}' has been updated successfully.");
            $this->dispatch('questionnaire-updated');
            $this->closeEditModal();
            
        } catch (\Exception $e) {
            Log::error('Failed to update questionnaire: ' . $e->getMessage());
            session()->flash('error', 'Failed to update questionnaire: ' . $e->getMessage());
        }
    }

    public function openQuestionsModal(int $questionnaireId)
    {
        $this->selectedQuestionnaire = Questionnaire::with('questions')->findOrFail($questionnaireId);
        $this->showQuestionsModal = true;
    }

    public function closeQuestionsModal()
    {
        $this->showQuestionsModal = false;
        $this->selectedQuestionnaire = null;
    }

    public function openQrModal($questionnaireId)
    {
        $this->qrQuestionnaire = Questionnaire::findOrFail($questionnaireId);
        $this->showQrModal = true;
    }

    public function closeQrModal()
    {
        $this->showQrModal = false;
        $this->qrQuestionnaire = null;
    }

    public function openDeleteModal($questionnaireId)
    {
        try {
            $this->deleteQuestionnaire = Questionnaire::withCount('questions')->findOrFail($questionnaireId);
            $this->showDeleteModal = true;
        } catch (\Exception $e) {
            session()->flash('error', 'Questionnaire not found.');
            Log::error('Error opening delete modal: ' . $e->getMessage());
        }
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->deleteQuestionnaire = null;
    }

    public function confirmDelete()
    {
        if (!$this->deleteQuestionnaire) {
            session()->flash('error', 'No questionnaire selected for deletion.');
            $this->closeDeleteModal();
            return;
        }

        try {
            DB::transaction(function () {
                $questionnaireName = $this->deleteQuestionnaire->title;
                $questionnaireId = $this->deleteQuestionnaire->id;
                
                // Delete all related records first
                $this->deleteQuestionnaire->questions()->delete();
                $this->deleteQuestionnaire->quizAttempts()->delete();
                
                // Delete the questionnaire
                $this->deleteQuestionnaire->delete();
                
                Log::info("Questionnaire deleted: ID {$questionnaireId}, Title: {$questionnaireName}");
                session()->flash('message', "Questionnaire '{$questionnaireName}' and all its questions have been deleted successfully.");
            });
        } catch (\Exception $e) {
            Log::error('Failed to delete questionnaire: ' . $e->getMessage());
            session()->flash('error', 'Failed to delete questionnaire: ' . $e->getMessage());
        } finally {
            $this->closeDeleteModal();
        }
    }

    // Keep the old method for backward compatibility but redirect to confirmDelete
    public function deleteQuestionnaire()
    {
        $this->confirmDelete();
    }

    public function toggleActive(int $questionnaireId)
    {
        try {
            $questionnaire = Questionnaire::findOrFail($questionnaireId);
            $questionnaire->update(['is_active' => !$questionnaire->is_active]);
            
            $status = $questionnaire->is_active ? 'activated' : 'deactivated';
            session()->flash('message', "Questionnaire has been {$status}.");
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to update questionnaire status.');
            Log::error('Error toggling questionnaire status: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $questionnaires = Questionnaire::withCount('questions')
            ->with('creator')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($questionnaire) {
                // Add attempt supervision data
                if ($questionnaire->max_attempts) {
                    $questionnaire->users_at_max_attempts = DB::table('quiz_attempts')
                        ->select('user_id')
                        ->where('questionnaire_id', $questionnaire->id)
                        ->where('status', 'completed')
                        ->groupBy('user_id')
                        ->havingRaw('COUNT(*) >= ?', [$questionnaire->max_attempts])
                        ->count();
                } else {
                    $questionnaire->users_at_max_attempts = 0;
                }
                return $questionnaire;
            });

        return view('livewire.admin.questionnaire-manager', [
            'questionnaires' => $questionnaires
        ])->layout(null);
    }
}