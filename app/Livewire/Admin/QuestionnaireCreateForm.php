<?php
// app/Livewire/Admin/QuestionnaireCreateForm.php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Questionnaire;
use Illuminate\Support\Str;
use Carbon\Carbon;

class QuestionnaireCreateForm extends Component
{
    // No #[Validate] attributes here any more: they ran on every keystroke with rules that disagreed
    // with the ones used on save — duration capped at 300 instead of 1440, the start date required
    // though it is optional, and "Tak terbatas" for attempts flagged as missing. rules() below is the
    // one set, used both while typing and when saving.
    public string $title = '';

    public string $description = '';

    // No photo field (operator, 22 Sep). Only the retired web participant view ever showed one and the
    // app never receives it, so it was a field that changed nothing a player could see. photo_path
    // stays on the model for the rows that already have one.

    /** Untyped on purpose: a cleared number input sends '', which an int property silently turned back into 30. */
    public $time_limit = 30;

    public string $start_date = '';

    public string $end_date = '';

    /** '' = unlimited. */
    public string $max_attempts = '1';

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

    /** The one set of rules — used while typing (updated()) and on save. */
    protected function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            // The same range as the edit form, so what one accepts the other does too.
            'time_limit' => 'required|integer|min:1|max:1440',
            // Optional: a questionnaire with no window is simply open until switched off.
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            // '' is "Tak terbatas"; nullable lets it through instead of flagging it as missing.
            'max_attempts' => 'nullable|integer|min:1|max:50',
            'is_active' => 'boolean',
            'counts_toward_finish' => 'boolean',
            'venue_mode' => 'nullable|in:outdoor,indoor',
            'quest_location_id' => 'nullable|required_if:venue_mode,outdoor|exists:quest_locations,id',
            'game_location_id' => 'nullable|required_if:venue_mode,indoor|exists:game_locations,id',
        ];
    }

    protected function messages(): array
    {
        return [
            'time_limit.required' => 'Durasi wajib diisi, dalam menit.',
            'time_limit.integer' => 'Durasi harus berupa angka menit.',
            'time_limit.min' => 'Durasi minimal 1 menit.',
            'time_limit.max' => 'Durasi maksimal 1440 menit (24 jam).',
            'quest_location_id.required_if' => 'Pilih pos outdoor untuk kuesioner ini.',
            'game_location_id.required_if' => 'Pilih pos indoor untuk kuesioner ini.',
            'end_date.after_or_equal' => 'Tanggal berakhir tidak boleh sebelum tanggal mulai.',
        ];
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
                // The system's one generator, shared with duplication and the QR Code Manager.
                'qr_code' => \App\Services\QuestionnaireService::generateUniqueQrCode(),
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
        $this->validateOnly($propertyName);
        
        // A start date no longer fills in an end date a week later. An empty end means open-ended,
        // and an end before the start is a validation message, not something to fix up silently.
    }

    

    public function render()
    {
        return view('livewire.admin.questionnaire-create-form', [
            'questLocations' => \App\Models\QuestLocation::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'gameLocations' => \App\Models\GameLocation::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }
}