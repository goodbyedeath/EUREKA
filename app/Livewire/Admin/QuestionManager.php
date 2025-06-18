<?php
// app/Livewire/Admin/QuestionManager.php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Questionnaire;
use App\Models\Question;

class QuestionManager extends Component
{
    public Questionnaire $questionnaire;
    public array $questions = [];

    public function mount(Questionnaire $questionnaire)
    {
        $this->questionnaire = $questionnaire;
        $this->loadQuestions();
    }

    protected $listeners = [
        'question-added' => 'refreshQuestions',
        'question-deleted' => 'refreshQuestions',
        'question-updated' => 'refreshQuestions'
    ];

    public function loadQuestions()
    {
        $this->questions = $this->questionnaire
            ->questions()
            ->orderBy('order', 'asc')
            ->get()
            ->toArray();
    }

    public function refreshQuestions()
    {
        $this->questionnaire = $this->questionnaire->fresh();
        $this->loadQuestions();
    }

    public function editQuestion(int $questionId)
    {
        $this->dispatch('edit-question', questionId: $questionId);
    }

    public function deleteQuestion(int $questionId)
    {
        // Find the question and ensure it belongs to the current questionnaire
        $question = Question::where('id', $questionId)
            ->where('questionnaire_id', $this->questionnaire->id)
            ->firstOrFail();

        // Additional authorization check - ensure user can modify this questionnaire
        if ($this->questionnaire->created_by !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized to delete questions from this questionnaire.');
        }

        // Check if there are any user answers for this question
        $hasAnswers = $question->userAnswers()->exists();
        
        if ($hasAnswers) {
            session()->flash('questions_error', 'Cannot delete question - it has existing user answers. Consider disabling the questionnaire instead.');
            return;
        }

        $question->delete();
        $this->refreshQuestions();
        session()->flash('questions_message', 'Question deleted successfully!');
    }

    public function render()
    {
        return view('livewire.admin.question-manager');
    }
}