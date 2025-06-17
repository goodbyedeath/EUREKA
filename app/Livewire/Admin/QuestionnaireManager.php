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
    public bool $showQuestionsModal = false;
    public bool $showQrModal = false;
    public bool $showDeleteModal = false;
    public ?Questionnaire $selectedQuestionnaire = null;
    public ?Questionnaire $qrQuestionnaire = null;
    public ?Questionnaire $deleteQuestionnaire = null;

    protected $listeners = [
        'questionnaire-created' => '$refresh',
        'questionnaire-updated' => '$refresh',
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
            ->get();

        return view('livewire.admin.questionnaire-manager', [
            'questionnaires' => $questionnaires
        ]);
    }
}