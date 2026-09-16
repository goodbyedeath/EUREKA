<?php

namespace App\Livewire\User;

use Livewire\Component;
use App\Models\GameAssessment;
use App\Models\QuizAttempt;
use App\Models\Question;

class GameAssessmentForm extends Component
{
    public $assessmentId;
    public $assessment;
    public $question;
    public $attempt;
    
    public $deposit = 0; // Read-only - team's initial points
    public $penalty = 0;
    public $additionalPoints = 0; // Additional points from questionnaire total
    public $notes = '';
    public $totalDeposit = 0;
    public $facilitatorPhoto = null;

    protected $rules = [
        'penalty' => 'required|integer|min:0|max:999999',
        'additionalPoints' => 'required|integer|min:0|max:999999', 
        'notes' => 'nullable|string|max:1000'
    ];

    protected $messages = [
        'penalty.required' => 'Penalty amount is required.',
        'penalty.integer' => 'Penalty must be a whole number.',
        'penalty.min' => 'Penalty cannot be negative.',
        'additionalPoints.required' => 'Additional points amount is required.',
        'additionalPoints.integer' => 'Additional points must be a whole number.',
        'additionalPoints.min' => 'Additional points cannot be negative.',
        'notes.max' => 'Notes cannot exceed 1000 characters.'
    ];

    public function mount($assessmentId)
    {
        $this->assessmentId = $assessmentId;
        
        // Load the assessment record
        $this->assessment = GameAssessment::with(['question', 'quizAttempt.questionnaire', 'user.team'])
            ->where('id', $assessmentId)
            ->where('user_id', auth()->id())
            ->firstOrFail();
        
        // Ensure the assessment has proper relationships
        if (!$this->assessment->question || !$this->assessment->quizAttempt || !$this->assessment->user) {
            abort(404, 'Assessment data incomplete.');
        }
        
        $this->question = $this->assessment->question;
        $this->attempt = $this->assessment->quizAttempt;
        
        // Set deposit to team's initial points (always read-only)
        $user = auth()->user();
        if ($user->team) {
            $this->deposit = (int)($user->team->initial_points ?? 0);
        } else {
            $this->deposit = 1000; // Default fallback
        }

        // Load existing values if already assessed
        if ($this->assessment->is_assessed) {
            $this->penalty = (int)($this->assessment->penalty ?? 0);
            $this->additionalPoints = (int)($this->assessment->additional_points ?? 0);
            $this->notes = $this->assessment->notes ?? '';
        } else {
            // Default to this game's own points — the most a facilitator may award for it.
            $this->additionalPoints = (int) ($this->question->points ?? 0);
        }
        
        $this->calculateTotalDeposit();
    }

    public function updatedPenalty()
    {
        $this->calculateTotalDeposit();
    }

    public function updatedAdditionalPoints()
    {
        $this->calculateTotalDeposit();
    }

    public function calculateTotalDeposit()
    {
        $deposit = (int)($this->deposit ?? 0);
        $additionalPoints = (int)($this->additionalPoints ?? 0);
        $penalty = (int)($this->penalty ?? 0);
        $this->totalDeposit = $deposit + $additionalPoints - $penalty;
    }

    public function saveAssessment()
    {
        $this->validate();

        return $this->persist((int) $this->additionalPoints, (int) $this->penalty, $this->notes, 'Assessment saved successfully! Team points updated.');
    }

    public function skipAssessment()
    {
        return $this->persist(0, 0, 'Assessment skipped by user', 'Assessment skipped. No points awarded.');
    }

    /**
     * Retired. Scoring now happens in the app, behind the facilitator PIN and a photo. This form
     * has neither, so letting it save would be the way around both.
     */
    protected function persist(int $additional, int $penalty, ?string $notes, string $flash)
    {
        $this->addError('additionalPoints', 'Penilaian game dilakukan di aplikasi, dengan PIN fasilitator.');

        return null;
    }

    protected function handlePostAssessmentNavigation()
    {
        // Safety checks
        if (!$this->attempt || !$this->attempt->questionnaire || !$this->question) {
            session()->flash('error', 'Invalid assessment data. Redirecting to dashboard.');
            return $this->redirect(route('user.dashboard'), navigate: true);
        }
        
        // Get all questions in the quiz
        $allQuestions = $this->attempt->questionnaire->questions()->orderBy('order')->get();
        
        if ($allQuestions->isEmpty()) {
            // No questions found, go to results
            return $this->redirect(route('quiz.results', ['attemptId' => $this->attempt->id]), navigate: true);
        }
        
        // Find current question position
        $currentQuestionIndex = $allQuestions->search(function($q) {
            return $q->id === $this->question->id;
        });
        
        // If question not found in list, assume it's the last one
        if ($currentQuestionIndex === false) {
            return $this->redirect(route('quiz.results', ['attemptId' => $this->attempt->id]), navigate: true);
        }
        
        // Check if there are more questions after this one
        $hasMoreQuestions = $currentQuestionIndex < $allQuestions->count() - 1;
        
        if ($hasMoreQuestions) {
            // Continue to the quiz (next question)
            return $this->redirect(route('quiz.continue', ['attemptId' => $this->attempt->id]), navigate: true);
        } else {
            // This was the last question, mark quiz as completed and go to results
            $this->attempt->update([
                'status' => 'completed',
                'completed_at' => now(),
                'total_time_seconds' => (int) max(0, $this->attempt->started_at?->diffInSeconds(now()) ?? 0),
            ]);
            
            return $this->redirect(route('quiz.results', ['attemptId' => $this->attempt->id]), navigate: true);
        }
    }

    public function render()
    {
        return view('livewire.user.game-assessment-form');
    }
}