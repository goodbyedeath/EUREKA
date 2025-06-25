<?php
// app/Livewire/Admin/QuestionForm.php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Questionnaire;
use App\Models\Question;
use Livewire\Attributes\Validate;
use Illuminate\Validation\Rule;
use App\Enums\QuestionType;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

class QuestionForm extends Component
{
    use WithFileUploads;
    public Questionnaire $questionnaire;
    public ?int $editingQuestionId = null;
    public bool $isEditing = false;

    public array $newQuestion = [
        'question' => '',
        'type' => 'text',
        'options' => ['', '', '', ''],
        'correct_answer' => '',
        'points' => 1,
        'game_name' => '',
        'description' => '',
        'images' => []
    ];

    public array $uploadedImages = [];
    public $newImage;

    protected $listeners = [
        'edit-question' => 'loadQuestionForEdit'
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
            'newQuestion.type' => ['required', Rule::in(QuestionType::values())],
            'newQuestion.points' => 'required|integer|min:1',
        ];

        // Type-specific validation
        switch ($this->newQuestion['type']) {
            case 'multiple_choice':
                $rules = array_merge($rules, [
                    'newQuestion.options' => 'required|array|min:2|max:8',
                    'newQuestion.options.*' => 'nullable|string|max:200',
                    'newQuestion.correct_answer' => 'required|string',
                ]);
                break;
            case 'true_false':
                $rules['newQuestion.correct_answer'] = 'required|in:true,false';
                break;
            case 'text':
                $rules['newQuestion.correct_answer'] = 'required|string|max:1000';
                break;
            case 'fun_game':
                $rules = array_merge($rules, [
                    'newQuestion.game_name' => 'required|string|max:200',
                    'newQuestion.description' => 'required|string|max:2000',
                    'uploadedImages' => 'nullable|array|max:10',
                    'uploadedImages.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
                    'newImage' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
                ]);
                break;
        }

        return $rules;
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

        // Additional validation for data integrity
        if ($this->newQuestion['type'] === 'multiple_choice') {
            $validationErrors = $this->validateMultipleChoiceData();
            if (!empty($validationErrors)) {
                session()->flash('questions_error', implode(' ', $validationErrors));
                return;
            }
        }

        try {
            if ($this->isEditing) {
                $this->updateQuestion();
            } else {
                $this->createQuestion();
            }
        } catch (\Exception $e) {
            session()->flash('questions_error', 'Failed to save question. Please try again.');
            \Log::error('Failed to save question: ' . $e->getMessage());
        }
    }

    protected function createQuestion()
    {
        // Authorization check - ensure user can modify this questionnaire
        if ($this->questionnaire->created_by !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized to add questions to this questionnaire.');
        }

        $questionData = $this->prepareQuestionData();
        Question::create($questionData);

        $this->resetNewQuestion();
        session()->flash('questions_message', 'Question added successfully!');
        $this->dispatch('question-added');
    }

    protected function updateQuestion()
    {
        // Find question and ensure it belongs to the current questionnaire
        $question = Question::where('id', $this->editingQuestionId)
            ->where('questionnaire_id', $this->questionnaire->id)
            ->firstOrFail();

        // Authorization check - ensure user can modify this questionnaire
        if ($this->questionnaire->created_by !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized to update questions in this questionnaire.');
        }

        // Check if question has existing answers - restrict certain updates
        $hasAnswers = $question->userAnswers()->exists();
        if ($hasAnswers) {
            // Don't allow changing question type or correct answer if there are existing answers
            $originalType = $question->type;
            $originalCorrectAnswer = $question->correct_answer;
        }

        $questionData = $this->prepareQuestionData();
        unset($questionData['questionnaire_id']); // Don't update questionnaire_id
        unset($questionData['order']); // Don't update order during edit
        
        // Prevent type/answer changes if there are existing answers
        if ($hasAnswers) {
            $questionData['type'] = $originalType;
            $questionData['correct_answer'] = $originalCorrectAnswer;
            
            if ($originalType !== $this->newQuestion['type']) {
                session()->flash('questions_warning', 'Question type cannot be changed as it has existing answers.');
            }
        }
        
        $question->update($questionData);

        $this->resetNewQuestion();
        session()->flash('questions_message', 'Question updated successfully!');
        $this->dispatch('question-updated');
    }

    public function loadQuestionForEdit($questionId)
    {
        // Find question and ensure it belongs to the current questionnaire
        $question = Question::where('id', $questionId)
            ->where('questionnaire_id', $this->questionnaire->id)
            ->firstOrFail();

        // Authorization check - ensure user can modify this questionnaire
        if ($this->questionnaire->created_by !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized to edit questions in this questionnaire.');
        }
        
        $this->editingQuestionId = $question->id;
        $this->isEditing = true;
        
        $this->newQuestion = [
            'question' => $question->question,
            'type' => $question->type,
            'options' => $question->options ?: ['', '', '', ''],
            'correct_answer' => $question->correct_answer,
            'points' => $question->points,
            'game_name' => $question->game_name ?? '',
            'description' => $question->description ?? '',
            'images' => $question->images ?? []
        ];

        // Ensure we have at least 2 options for multiple choice
        if ($question->type === 'multiple_choice' && count($this->newQuestion['options']) < 2) {
            while (count($this->newQuestion['options']) < 4) {
                $this->newQuestion['options'][] = '';
            }
        }
    }

    public function cancelEdit()
    {
        $this->resetNewQuestion();
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

        // Handle image uploads for fun_game type
        if ($questionData['type'] === 'fun_game') {
            $questionData['images'] = $this->storeUploadedImages();
        }

        return $questionData;
    }

    protected function getNextQuestionOrder(): int
    {
        return $this->questionnaire->questions()->count() + 1;
    }

    public function resetNewQuestion()
    {
        $this->reset(['newQuestion', 'editingQuestionId', 'isEditing', 'uploadedImages', 'newImage']);
        $this->newQuestion = [
            'question' => '',
            'type' => 'text',
            'options' => ['', '', '', ''],
            'correct_answer' => '',
            'points' => 1,
            'game_name' => '',
            'description' => '',
            'images' => []
        ];
        $this->uploadedImages = [];
        $this->newImage = null;
        $this->editingQuestionId = null;
        $this->isEditing = false;
    }

    public function updatedNewQuestionType()
    {
        // Clear correct answer when changing type
        $this->newQuestion['correct_answer'] = '';
        
        // Reset options for non-multiple-choice types
        if ($this->newQuestion['type'] !== 'multiple_choice') {
            $this->newQuestion['options'] = ['', '', '', ''];
        }
        
        // Reset fun game fields for non-fun-game types
        if ($this->newQuestion['type'] !== 'fun_game') {
            $this->newQuestion['game_name'] = '';
            $this->newQuestion['description'] = '';
            $this->newQuestion['images'] = [];
            $this->uploadedImages = [];
            $this->newImage = null;
        }
        
        // Clear related validation errors
        $this->resetValidation([
            'newQuestion.correct_answer',
            'newQuestion.options',
            'newQuestion.options.0',
            'newQuestion.options.1',
            'newQuestion.options.2',
            'newQuestion.options.3',
            'newQuestion.game_name',
            'newQuestion.description',
            'newQuestion.images'
        ]);
    }

    public function addOption()
    {
        if ($this->newQuestion['type'] === 'multiple_choice' && count($this->newQuestion['options']) < 8) {
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

    protected function validateMultipleChoiceData(): array
    {
        $errors = [];
        
        // Get filtered options (non-empty)
        $filteredOptions = $this->getFilteredOptions();
        
        // Check minimum options
        if (count($filteredOptions) < 2) {
            $errors[] = 'Please provide at least 2 non-empty options.';
        }
        
        // Check if correct answer exists in options
        if (!empty($this->newQuestion['correct_answer']) && 
            !in_array($this->newQuestion['correct_answer'], $filteredOptions)) {
            $errors[] = 'The correct answer must be one of the provided options.';
        }
        
        // Check for duplicate options
        if (count($filteredOptions) !== count(array_unique($filteredOptions))) {
            $errors[] = 'All options must be unique.';
        }
        
        return $errors;
    }

    public function updatedNewImage()
    {
        if ($this->newImage && $this->newQuestion['type'] === 'fun_game' && count($this->uploadedImages) < 10) {
            $this->uploadedImages[] = $this->newImage;
            $this->newImage = null;
        }
    }

    public function removeUploadedImage($index)
    {
        if ($this->newQuestion['type'] === 'fun_game' && isset($this->uploadedImages[$index])) {
            unset($this->uploadedImages[$index]);
            $this->uploadedImages = array_values($this->uploadedImages);
        }
    }

    public function removeExistingImage($index)
    {
        if ($this->newQuestion['type'] === 'fun_game' && isset($this->newQuestion['images'][$index])) {
            // Delete the file from storage if it exists
            $imagePath = $this->newQuestion['images'][$index];
            if ($imagePath && Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }
            
            unset($this->newQuestion['images'][$index]);
            $this->newQuestion['images'] = array_values($this->newQuestion['images']);
        }
    }

    protected function storeUploadedImages(): array
    {
        $storedImagePaths = [];
        
        // Keep existing images (for editing mode)
        if (!empty($this->newQuestion['images'])) {
            $storedImagePaths = $this->newQuestion['images'];
        }
        
        // Store new uploaded images
        if (!empty($this->uploadedImages)) {
            foreach ($this->uploadedImages as $uploadedImage) {
                if ($uploadedImage) {
                    $path = $uploadedImage->store('games/question-images', 'public');
                    $storedImagePaths[] = $path;
                }
            }
        }
        
        return $storedImagePaths;
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