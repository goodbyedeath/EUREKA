<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\GameAssessment as GameAssessmentModel;
use App\Models\QuizAttempt;
use App\Models\Question;
use App\Models\User;
use App\Exceptions\QuizRuleException;
use App\Services\GameAssessmentService;

class GameAssessment extends Component
{
    public $selectedAttempt = null;
    public $assessments = [];
    public $searchTerm = '';
    public $statusFilter = 'all'; // all, pending, assessed
    public $attempts = [];
    
    // Assessment form data
    public $editingAssessment = null;
    public $deposit = 0; // team.initial_points, shown for reference only
    public $additionalPoints = 0;
    public $penalty = 0;
    public $notes = '';

    public function mount()
    {
        $this->loadAttempts();
    }

    public function loadAttempts()
    {
        $query = QuizAttempt::with(['user', 'questionnaire', 'gameAssessments.question'])
            ->whereHas('gameAssessments')
            ->where('status', QuizAttempt::STATUS_COMPLETED);

        if ($this->searchTerm) {
            $query->whereHas('user', function($q) {
                $q->where('name', 'like', '%' . $this->searchTerm . '%')
                  ->orWhere('email', 'like', '%' . $this->searchTerm . '%');
            });
        }

        $this->attempts = $query->orderBy('completed_at', 'desc')->get();
    }

    public function selectAttempt($attemptId)
    {
        $this->selectedAttempt = QuizAttempt::with([
            'user', 
            'questionnaire',
            'gameAssessments' => function($query) {
                $query->with(['question', 'assessedBy']);
            }
        ])->findOrFail($attemptId);

        $this->assessments = $this->selectedAttempt->gameAssessments;
    }

    public function editAssessment($assessmentId)
    {
        $this->editingAssessment = GameAssessmentModel::findOrFail($assessmentId);
        $this->deposit = $this->editingAssessment->deposit;
        $this->additionalPoints = (int) $this->editingAssessment->additional_points;
        $this->penalty = $this->editingAssessment->penalty;
        $this->notes = $this->editingAssessment->notes ?? '';
    }

    public function saveAssessment()
    {
        $this->validate([
            'additionalPoints' => 'required|integer|min:0',
            'penalty' => 'required|integer|min:0',
            'notes' => 'nullable|string|max:1000'
        ]);

        // Corrections go through the same service as the team phone, so Team.points moves by
        // the difference from what this assessment already paid.
        try {
            app(GameAssessmentService::class)->record(
                $this->editingAssessment, (int) $this->additionalPoints, (int) $this->penalty,
                $this->notes, auth()->id(), allowReassess: true,
            );
        } catch (QuizRuleException $e) {
            $this->addError($e->errorKey === 'penalty_out_of_range' ? 'penalty' : 'additionalPoints', $e->getMessage());
            return;
        }

        $this->editingAssessment = null;
        $this->reset(['deposit', 'additionalPoints', 'penalty', 'notes']);
        $this->selectAttempt($this->selectedAttempt->id); // Refresh data
        
        session()->flash('success', 'Assessment saved successfully!');
    }

    public function cancelEdit()
    {
        $this->editingAssessment = null;
        $this->reset(['deposit', 'additionalPoints', 'penalty', 'notes']);
    }

    public function updatedSearchTerm()
    {
        $this->loadAttempts();
    }

    public function updatedStatusFilter()
    {
        $this->loadAttempts();
    }

    public function getTotalDeposit()
    {
        return (int) $this->deposit + (int) $this->additionalPoints - (int) $this->penalty;
    }

    public function render()
    {
        return view('livewire.admin.game-assessment', [
            'totalDeposit' => $this->getTotalDeposit()
        ])->layout(null);
    }
}