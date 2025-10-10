<?php

namespace App\Livewire\User;

use Livewire\Component;
use App\Models\GameAssessment;
use App\Models\QuizAttempt;
use App\Models\Question;
use App\Models\Team;

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
            $this->deposit = (int)($user->team->initial_points ?? 1000);
        } else {
            $this->deposit = 1000; // Default fallback
        }

        // Load existing values if already assessed
        if ($this->assessment->is_assessed) {
            $this->penalty = (int)($this->assessment->penalty ?? 0);
            $this->additionalPoints = (int)($this->assessment->additional_points ?? 0);
            $this->notes = $this->assessment->notes ?? '';
        } else {
            // Set additional points to questionnaire total points for new assessments
            $questionnaireTotalPoints = (int)($this->attempt->questionnaire->questions()->sum('points') ?? 0);
            $this->additionalPoints = $questionnaireTotalPoints;
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

        // Update the assessment
        $this->assessment->update([
            'deposit' => $this->deposit,
            'penalty' => $this->penalty,
            'additional_points' => $this->additionalPoints,
            'notes' => $this->notes,
            'total_deposit' => $this->totalDeposit,
            'is_assessed' => true,
            'assessed_by' => auth()->id(),
            'assessed_at' => now()
        ]);

        // Update team points based on assessment
        $user = auth()->user();
        if ($user->team_id && $this->totalDeposit != 0) {
            $team = Team::find($user->team_id);
            if ($team) {
                if ($this->totalDeposit > 0) {
                    $team->addPoints($this->totalDeposit, "Game assessment: {$this->question->game_name}");
                } else {
                    $team->deductPoints(abs($this->totalDeposit), "Game assessment penalty: {$this->question->game_name}");
                }
            }
        }

        session()->flash('success', 'Assessment saved successfully! Team points updated.');
        
        // Check if there are more questions in the quiz
        return $this->handlePostAssessmentNavigation();
    }

    public function skipAssessment()
    {
        // Mark as assessed with zero values
        $this->assessment->update([
            'deposit' => $this->deposit, // Keep team's initial points
            'penalty' => 0,
            'additional_points' => 0,
            'notes' => 'Assessment skipped by user',
            'total_deposit' => $this->deposit, // Only team's initial points
            'is_assessed' => true,
            'assessed_by' => auth()->id(),
            'assessed_at' => now()
        ]);

        // No team points change when skipped

        session()->flash('info', 'Assessment skipped. No points awarded.');
        
        // Check if there are more questions in the quiz
        return $this->handlePostAssessmentNavigation();
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
                'total_time_seconds' => $this->attempt->started_at ? 
                    now()->diffInSeconds($this->attempt->started_at) : 0
            ]);
            
            return $this->redirect(route('quiz.results', ['attemptId' => $this->attempt->id]), navigate: true);
        }
    }

    public function render()
    {
        return view('livewire.user.game-assessment-form');
    }
}