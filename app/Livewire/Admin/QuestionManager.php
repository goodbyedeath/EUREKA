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

    /**
     * A question the teams have already answered, waiting for the admin to say "yes, with its answers".
     *
     * Deleting one used to be refused with a flash message this component never rendered, so the
     * button simply did nothing (operator, 22 Sep, on a foto bersama that one test team had answered).
     * Now the refusal says how many answers are in the way and offers to take them too.
     *
     * @var array{id: int, question: string, answers: int}|null
     */
    public ?array $pendingDelete = null;

    public function deleteQuestion(int $questionId)
    {
        $question = $this->ownedQuestion($questionId);

        $answers = $question->userAnswers()->count();
        if ($answers > 0) {
            $this->pendingDelete = ['id' => $question->id, 'question' => (string) $question->question, 'answers' => $answers];

            return;
        }

        $this->destroy($question);
        session()->flash('questions_message', 'Soal dihapus.');
    }

    /**
     * Delete a question and every answer to it.
     *
     * The rows go by cascade — user_answers and game_assessments both reference the question ON DELETE
     * CASCADE — and team scores are computed from what remains, so a deleted answer's points leave the
     * totals on their own. Files do not cascade, so destroy() removes them.
     */
    public function deleteQuestionWithAnswers()
    {
        if (! $this->pendingDelete) {
            return;
        }

        $question = $this->ownedQuestion((int) $this->pendingDelete['id']);
        $count = $question->userAnswers()->count();

        $this->destroy($question);
        $this->pendingDelete = null;
        session()->flash('questions_message', "Soal dihapus beserta {$count} jawaban. Poin dari jawaban itu sudah keluar dari skor tim.");
    }

    public function cancelDelete()
    {
        $this->pendingDelete = null;
    }

    private function ownedQuestion(int $questionId): Question
    {
        $question = Question::where('id', $questionId)
            ->where('questionnaire_id', $this->questionnaire->id)
            ->firstOrFail();

        if ($this->questionnaire->created_by !== auth()->id() && ! auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized to delete questions from this questionnaire.');
        }

        return $question;
    }

    /**
     * Remove the question, its answers, and the files that belonged only to them.
     *
     * A picture or frame is kept when another question still uses the same path — a duplicated floor
     * plan can share them — so deleting a copy never blanks the original.
     */
    private function destroy(Question $question): void
    {
        $public = \Illuminate\Support\Facades\Storage::disk('public');
        $local = \Illuminate\Support\Facades\Storage::disk('local');

        $ownFiles = array_filter(array_merge([(string) $question->frame_path], (array) ($question->images ?? [])),
            fn ($p) => is_string($p) && $p !== '' && ! preg_match('#^https?://#i', $p));

        // The answers' own files: a foto bersama answer is the photo's path; a facilitator's photo
        // sits on the private disk beside the game assessment.
        $answerPhotos = $question->type === \App\Enums\QuestionType::GROUP_PHOTO->value
            ? $question->userAnswers()->pluck('answer')->filter(fn ($p) => str_starts_with((string) $p, 'group-photos/'))->all()
            : [];
        $facilitatorPhotos = \App\Models\GameAssessment::where('question_id', $question->id)->pluck('facilitator_photo')
            ->filter(fn ($p) => str_starts_with((string) $p, 'facilitator-photos/'))->all();

        \Illuminate\Support\Facades\DB::transaction(fn () => $question->delete());

        // Compared in PHP, not with LIKE: `images` is stored as JSON with escaped slashes
        // ("games\/question-images\/x.png"), which a LIKE on the plain path never matches.
        if ($ownFiles) {
            $inUse = Question::whereNotNull('frame_path')->pluck('frame_path')
                ->merge(Question::whereNotNull('images')->get(['images'])->flatMap(fn ($q) => (array) $q->images))
                ->filter(fn ($p) => is_string($p))
                ->flip();

            foreach ($ownFiles as $path) {
                if (! $inUse->has($path)) {
                    $public->delete($path);
                }
            }
        }
        if ($answerPhotos) {
            $public->delete($answerPhotos);
        }
        if ($facilitatorPhotos) {
            $local->delete($facilitatorPhotos);
        }

        $this->refreshQuestions();
    }

    public function render()
    {
        return view('livewire.admin.question-manager');
    }
}