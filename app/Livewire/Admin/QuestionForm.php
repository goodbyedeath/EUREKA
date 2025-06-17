<?php
// app/Livewire/Admin/QuestionForm.php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Questionnaire;
use App\Models\Question;
use Livewire\Attributes\Validate;
use Illuminate\Validation\Rule;

class QuestionForm extends Component
{
    public Questionnaire $questionnaire;

    public array $newQuestion = [
        'question' => '',
        'type' => 'text',
        'options' => ['', '', '', ''],
        'correct_answer' => '',
        'points' => 1
    ];

    public function mount(Questionnaire $questionnaire)
    {
        $this->questionnaire = $questionnaire;
        $this->resetNewQuestion();
    }

    protected function newQuestionRules(): array
    {
        $rules = [
            'newQuestion.question' => 'required|string|max:1000',
            'newQuestion.type' => ['required', Rule::in(['text', 'multiple_choice', 'true_false'])],
            'newQuestion.points' => 'required|integer|min:1',
        ];

        // Type-specific validation
        switch ($this->newQuestion['type']) {
            case 'multiple_choice':
                $rules = array_merge($rules, $this->getMultipleChoiceRules());
                break;
            case 'true_false':
                $rules['newQuestion.correct_answer'] = ['required', Rule::in(['true', 'false'])];
                break;
            default:
                $rules['newQuestion.correct_answer'] = 'required|string|max:255';
                break;
        }

        return $rules;
    }

    protected function getMultipleChoiceRules(): array
    {
        $filteredOptions = $this->getFilteredOptions();
        
        return [
            'newQuestion.options' => [
                'required',
                'array',
                function ($attribute, $value, $fail) use ($filteredOptions) {
                    if (count($filteredOptions) < 2) {
                        $fail('At least two non-empty options are required for multiple choice questions.');
                    }
                }
            ],
            'newQuestion.options.*' => 'nullable|string|max:255',
            'newQuestion.correct_answer' => [
                'required',
                function ($attribute, $value, $fail) use ($filteredOptions) {
                    if (!in_array($value, $filteredOptions)) {
                        $fail('The correct answer must be one of the provided non-empty options.');
                    }
                }
            ]
        ];
    }

    protected function getFilteredOptions(): array
    {
        return array_values(array_filter(
            $this->newQuestion['options'], 
            fn($opt) => !empty(trim($opt))
        ));
    }

    protected function newQuestionMessages(): array
    {
        return [
            'newQuestion.question.required' => 'The question field is required.',
            'newQuestion.question.max' => 'The question may not be greater than 1000 characters.',
            'newQuestion.points.required' => 'Points are required.',
            'newQuestion.points.min' => 'Points must be at least 1.',
            'newQuestion.correct_answer.required' => 'The correct answer is required.',
            'newQuestion.type.in' => 'Invalid question type selected.',
        ];
    }

    public function addQuestion()
    {
        $this->validate($this->newQuestionRules(), $this->newQuestionMessages());

        try {
            $questionData = $this->prepareQuestionData();
            Question::create($questionData);

            $this->resetNewQuestion();
            session()->flash('questions_message', 'Question added successfully!');
            $this->dispatch('question-added');
            
        } catch (\Exception $e) {
            session()->flash('questions_error', 'Failed to add question. Please try again.');
            // Log the error for debugging
            \Log::error('Failed to add question: ' . $e->getMessage());
        }
    }

    protected function prepareQuestionData(): array
    {
        $questionData = $this->newQuestion;
        $questionData['questionnaire_id'] = $this->questionnaire->id;
        $questionData['order'] = $this->getNextQuestionOrder();

        // Handle options based on question type
        if ($questionData['type'] === 'multiple_choice') {
            $questionData['options'] = $this->getFilteredOptions();
        } else {
            $questionData['options'] = null;
        }

        return $questionData;
    }

    protected function getNextQuestionOrder(): int
    {
        return $this->questionnaire->questions()->count() + 1;
    }

    public function resetNewQuestion()
    {
        $this->reset('newQuestion');
        $this->newQuestion = [
            'question' => '',
            'type' => 'text',
            'options' => ['', '', '', ''],
            'correct_answer' => '',
            'points' => 1
        ];
    }

    public function updatedNewQuestionType()
    {
        // Clear correct answer when changing type
        $this->newQuestion['correct_answer'] = '';
        
        // Reset options for non-multiple-choice types
        if ($this->newQuestion['type'] !== 'multiple_choice') {
            $this->newQuestion['options'] = ['', '', '', ''];
        }
        
        // Clear related validation errors
        $this->resetValidation([
            'newQuestion.correct_answer',
            'newQuestion.options',
            'newQuestion.options.0',
            'newQuestion.options.1',
            'newQuestion.options.2',
            'newQuestion.options.3'
        ]);
    }

    public function addOption()
    {
        if ($this->newQuestion['type'] === 'multiple_choice') {
            $this->newQuestion['options'][] = '';
        }
    }

    public function removeOption($index)
    {
        if ($this->newQuestion['type'] === 'multiple_choice' && count($this->newQuestion['options']) > 2) {
            // Check if the option being removed is the correct answer
            $removedOption = $this->newQuestion['options'][$index] ?? '';
            if ($this->newQuestion['correct_answer'] === $removedOption) {
                $this->newQuestion['correct_answer'] = '';
            }
            
            unset($this->newQuestion['options'][$index]);
            $this->newQuestion['options'] = array_values($this->newQuestion['options']);
        }
    }

    public function getAvailableOptionsProperty()
    {
        if ($this->newQuestion['type'] !== 'multiple_choice') {
            return [];
        }

        return array_filter($this->newQuestion['options'], function($option) {
            return !empty(trim($option));
        });
    }

    public function updatedNewQuestionOptions()
    {
        // Reset correct answer if it's no longer in the available options
        if ($this->newQuestion['type'] === 'multiple_choice') {
            $availableOptions = $this->getAvailableOptionsProperty();
            if (!empty($this->newQuestion['correct_answer']) && 
                !in_array($this->newQuestion['correct_answer'], $availableOptions)) {
                $this->newQuestion['correct_answer'] = '';
            }
        }
    }

    public function render()
    {
        return view('livewire.admin.question-form');
    }
}