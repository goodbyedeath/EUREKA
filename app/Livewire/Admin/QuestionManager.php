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
        'question-deleted' => 'refreshQuestions'
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

    public function deleteQuestion(int $questionId)
    {
        Question::findOrFail($questionId)->delete();
        $this->refreshQuestions();
        session()->flash('questions_message', 'Question deleted successfully!');
    }

    public function render()
    {
        return view('livewire.admin.question-manager');
    }
}