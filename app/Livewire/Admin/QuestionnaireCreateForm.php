<?php
// app/Livewire/Admin/QuestionnaireCreateForm.php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Questionnaire;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Carbon\Carbon;

class QuestionnaireCreateForm extends Component
{
    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('nullable|string|max:1000')]
    public string $description = '';

    #[Validate('required|integer|min:1|max:300')]
    public int $time_limit = 30;

    #[Validate('required|date|after_or_equal:today')]
    public string $start_date = '';

    #[Validate('required|date|after:start_date')]
    public string $end_date = '';

    #[Validate('required|integer|min:1|max:10')]
    public int $max_attempts = 1;

    #[Validate('boolean')]
    public bool $is_active = false;

    public bool $isSubmitting = false;

    public bool $showCreateModal = true;
    public function mount()
    {
        $this->resetForm();
    }

    public function createQuestionnaire()
    {
        $this->isSubmitting = true;
        
        try {
            $this->validate();

            $questionnaire = Questionnaire::create([
                'title' => trim($this->title),
                'description' => trim($this->description),
                'time_limit' => $this->time_limit,
                'qr_code' => (string) Str::uuid(),
                'created_by' => auth()->id(),
                'start_date' => Carbon::parse($this->start_date),
                'end_date' => Carbon::parse($this->end_date),
                'max_attempts' => $this->max_attempts,
                'is_active' => $this->is_active,
            ]);

            $this->resetForm();
            
            session()->flash('message', 'Questionnaire "' . $questionnaire->title . '" created successfully!');
            session()->flash('message_type', 'success');
            
            $this->dispatch('questionnaire-created', questionnaireId: $questionnaire->id);
            $this->dispatch('close-create-modal');
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Re-throw validation exceptions to show field errors
            throw $e;
        } catch (\Exception $e) {
            session()->flash('message', 'An error occurred while creating the questionnaire. Please try again.');
            session()->flash('message_type', 'error');
        } finally {
            $this->isSubmitting = false;
        }
    }

    public function resetForm()
    {
        $this->reset([
            'title', 
            'description', 
            'time_limit', 
            'start_date', 
            'end_date', 
            'max_attempts', 
            'is_active'
        ]);
        
        // Set default values
        $this->time_limit = 30;
        $this->max_attempts = 1;
        $this->start_date = now()->format('Y-m-d');
        $this->end_date = now()->addDays(7)->format('Y-m-d');
        $this->is_active = false;
        $this->isSubmitting = false;
    }

    public function updated($propertyName)
    {
        // Real-time validation for better UX
        $this->validateOnly($propertyName);
        
        // Auto-adjust end date if start date changes
        if ($propertyName === 'start_date' && $this->start_date) {
            $startDate = Carbon::parse($this->start_date);
            $endDate = $this->end_date ? Carbon::parse($this->end_date) : null;
            
            if (!$endDate || $endDate->lte($startDate)) {
                $this->end_date = $startDate->addDays(7)->format('Y-m-d');
            }
        }
    }

    

    public function render()
    {
        return view('livewire.admin.questionnaire-create-form');
    }
}