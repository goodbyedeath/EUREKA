<?php
// app/Livewire/Admin/QuestionnaireCreateForm.php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Questionnaire;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Carbon\Carbon;

class QuestionnaireCreateForm extends Component
{
    use \App\Livewire\Concerns\GuardsFileUploads;
    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('nullable|string|max:1000')]
    public string $description = '';

    public $photo = null;

    #[Validate('required|integer|min:1|max:300')]
    public int $time_limit = 30;

    #[Validate('required|date|after_or_equal:today')]
    public string $start_date = '';

    #[Validate('nullable|date|after_or_equal:start_date')]
    public string $end_date = '';

    #[Validate('required|integer|min:1|max:10')]
    /** '' = unlimited. */
    public string $max_attempts = '1';

    #[Validate('boolean')]
    public bool $is_active = false;

    /**
     * The post a team checks in at before this questionnaire opens. Only the edit form had it, so a
     * new questionnaire was always created unplayable — the station gate refuses one with no post —
     * and had to be opened again to be finished (operator, 22 Sep: "ada yang miss").
     */
    public string $venue_mode = '';

    public $quest_location_id = '';

    public $game_location_id = '';

    /** Off for a bonus post: it still scores, but the race clock does not wait for it. */
    public bool $counts_toward_finish = true;

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
            $this->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                // The same range as the edit form, so what one accepts the other does too.
                'time_limit' => 'required|integer|min:1|max:1440',
                // Optional: a questionnaire with no window is simply open until switched off.
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'max_attempts' => 'nullable|integer|min:1|max:50',
                'is_active' => 'boolean',
                'counts_toward_finish' => 'boolean',
                'venue_mode' => 'nullable|in:outdoor,indoor',
                'quest_location_id' => 'nullable|required_if:venue_mode,outdoor|exists:quest_locations,id',
                'game_location_id' => 'nullable|required_if:venue_mode,indoor|exists:game_locations,id',
            ], [
                'time_limit.required' => 'Durasi wajib diisi, dalam menit.',
                'time_limit.min' => 'Durasi minimal 1 menit.',
                'time_limit.max' => 'Durasi maksimal 1440 menit (24 jam).',
                'quest_location_id.required_if' => 'Pilih pos outdoor untuk kuesioner ini.',
                'game_location_id.required_if' => 'Pilih pos indoor untuk kuesioner ini.',
                'end_date.after_or_equal' => 'Tanggal berakhir tidak boleh sebelum tanggal mulai.',
            ]);

            $photoPath = null;
            if ($this->photo) {
                try {
                    $photoPath = $this->photo->store('questionnaire-photos', 'public');
                } catch (\Exception $e) {
                    \Log::error('Photo upload failed: ' . $e->getMessage());
                    session()->flash('message', 'Photo upload failed, but questionnaire was created without photo.');
                    session()->flash('message_type', 'warning');
                }
            }

            $questionnaire = Questionnaire::create([
                'title' => trim($this->title),
                'description' => trim($this->description),
                'photo_path' => $photoPath,
                'time_limit' => $this->time_limit,
                'qr_code' => (string) Str::uuid(),
                'created_by' => auth()->id(),
                'start_date' => $this->start_date ? Carbon::parse($this->start_date) : null,
                'end_date' => $this->end_date ? Carbon::parse($this->end_date) : null,
                'max_attempts' => $this->max_attempts === '' ? null : (int) $this->max_attempts,
                'is_active' => $this->is_active,
                'counts_toward_finish' => $this->counts_toward_finish,
                'venue_mode' => $this->venue_mode ?: null,
                'quest_location_id' => $this->venue_mode === 'outdoor' ? (int) $this->quest_location_id : null,
                'game_location_id' => $this->venue_mode === 'indoor' ? (int) $this->game_location_id : null,
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
            'photo',
            'time_limit', 
            'start_date', 
            'end_date', 
            'max_attempts', 
            'is_active',
            'venue_mode',
            'quest_location_id',
            'game_location_id',
            'counts_toward_finish',
        ]);
        
        // Set default values
        $this->time_limit = 30;
        $this->max_attempts = '1';
        // No window by default. This used to pre-fill "today → +7 days" behind a red asterisk, so
        // every new questionnaire quietly closed a week later — the very way every active one on this
        // install once expired unnoticed. A window is still one click away when it is wanted.
        $this->start_date = '';
        $this->end_date = '';
        $this->counts_toward_finish = true;
        $this->is_active = false;
        $this->isSubmitting = false;
    }

    public function updated($propertyName)
    {
        // Validate photo separately with custom logic
        if ($propertyName === 'photo' && $this->photo) {
            if (!in_array($this->photo->getClientOriginalExtension(), ['jpg', 'jpeg', 'png', 'gif'])) {
                $this->addError('photo', 'The photo must be a valid image file (jpg, jpeg, png, gif).');
                $this->photo = null;
                return;
            }
            
            if ($this->photo->getSize() > 2048 * 1024) { // 2MB in bytes
                $this->addError('photo', 'The photo must not be larger than 2MB.');
                $this->photo = null;
                return;
            }
        }
        
        // Skip other validation for photo uploads due to temporary file issues
        if ($propertyName !== 'photo') {
            $this->validateOnly($propertyName);
        }
        
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
        return view('livewire.admin.questionnaire-create-form', [
            'questLocations' => \App\Models\QuestLocation::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'gameLocations' => \App\Models\GameLocation::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }
}