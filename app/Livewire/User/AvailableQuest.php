<?php
namespace App\Livewire\User;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Questionnaire;
use App\Models\QuizAttempt;
use App\Models\QrCodeScan;
use Illuminate\Support\Facades\Auth;

class AvailableQuest extends Component
{
    public $availableQuestionnaires;
    public $recentAttempts;
    public $scannedQr_code = null;
    public $showNoQuizMessage = false;

    public function mount($qr_code = null)
    {
        $this->scannedQr_code = $qr_code;
        $this->loadQuizzes();
    }

    #[On('refresh-quizzes')]
    public function refreshQuizzes()
    {
        $this->loadQuizzes();
    }

    #[On('qr-code-scanned')]
    public function handleScannedCode($qr_code)
    {
        $this->scannedQr_code = $qr_code;
        $this->loadQuizzes();
        $this->dispatch('scanner-processed');
    }

    #[On('clear-scanned-qr')]
    public function clearScannedQuiz()
    {
        $this->scannedQr_code = null;
        $this->showNoQuizMessage = false;
        $this->loadQuizzes();
    }

    public function loadQuizzes()
    {
        // DEBUG: Add logging to see what's happening
        $userId = Auth::id();
        $attempts = QuizAttempt::where('user_id', $userId)->get();
        
        \Log::info('DEBUG loadQuizzes', [
            'userId' => $userId,
            'attemptsFound' => $attempts->count(),
            'attemptData' => $attempts->toArray()
        ]);
        
        $this->recentAttempts = $attempts;
        
        if ($this->scannedQr_code) {
            // Load questionnaires matching the scanned QR code
            $this->availableQuestionnaires = Questionnaire::where('qr_code', $this->scannedQr_code)
                ->active()
                ->withQuestions()
                ->withCount('questions')
                ->get()
                ->filter(function ($questionnaire) {
                    return $questionnaire->isAvailable();
                })
                ->values();
            
            $this->showNoQuizMessage = $this->availableQuestionnaires->isEmpty();
        } else {
            // Load all available questionnaires when no QR code is scanned
            $this->availableQuestionnaires = Questionnaire::active()
                ->withQuestions()
                ->withCount('questions')
                ->get()
                ->filter(function ($questionnaire) {
                    return $questionnaire->isAvailable() && $questionnaire->canUserAttempt(Auth::id());
                })
                ->values();
            
            $this->showNoQuizMessage = false;
        }
    }

    public function startQuiz($questionnaireId)
    {
        $questionnaire = Questionnaire::find($questionnaireId);
        
        if (!$questionnaire) {
            session()->flash('error', 'Questionnaire not found.');
            return;
        }

        if (!$questionnaire->isAvailable() || !$questionnaire->hasQuestions()) {
            session()->flash('error', 'Questionnaire not found, inactive, or has no questions.');
            return;
        }

        if ($this->scannedQr_code && $questionnaire->qr_code !== $this->scannedQr_code) {
            session()->flash('error', 'This questionnaire does not match the scanned code.');
            return;
        }

        // Use QrCodeScan model for more accurate validation
        if (!QrCodeScan::canUserScanQuestionnaire(Auth::id(), $questionnaire)) {
            // Only set session flash if one doesn't already exist to prevent duplicates
            if (!session()->has('error')) {
                session()->flash('error', 'You have reached the maximum number of attempts for this quiz.');
            }
            return;
        }

        $existingAttempt = $questionnaire->getUserInProgressAttempt(Auth::id());
        if ($existingAttempt) {
            return redirect()->route('quiz.continue', $existingAttempt->id);
        }

        return redirect()->route('quiz.take', ['questionnaireId' => $questionnaire->id]);
    }

    public function continueQuiz($attemptId)
    {
        $attempt = QuizAttempt::where('id', $attemptId)
            ->where('user_id', Auth::id())
            ->started() // Using the enum scope
            ->first();

        if ($attempt) {
            return redirect()->route('quiz.continue', $attempt->id);
        }

        session()->flash('error', 'Quiz attempt not found or already completed.');
        $this->loadQuizzes();
    }

    public function hasCompletedQuiz($questionnaireId)
    {
        $completedAttempts = $this->recentAttempts
            ->where('questionnaire_id', $questionnaireId)
            ->where('status', QuizAttempt::STATUS_COMPLETED);
            
            
        return $completedAttempts->isNotEmpty();
    }

    public function hasInProgressQuiz($questionnaireId)
    {
        return $this->recentAttempts
            ->where('questionnaire_id', $questionnaireId)
            ->where('status', QuizAttempt::STATUS_STARTED)
            ->first();
    }

    public function hasAbandonedQuiz($questionnaireId)
    {
        return $this->recentAttempts
            ->where('questionnaire_id', $questionnaireId)
            ->where('status', QuizAttempt::STATUS_ABANDONED)
            ->isNotEmpty();
    }

    public function canUserTakeQuiz($questionnaire)
    {
        return $questionnaire->canUserAttempt(Auth::id()) && 
               $questionnaire->isAvailable() && 
               !$this->hasInProgressQuiz($questionnaire->id);
    }

    public function getUserQuizStats($questionnaireId)
    {
        $questionnaire = Questionnaire::find($questionnaireId);
        if (!$questionnaire) {
            return null;
        }

        return [
            'attempts' => $questionnaire->getUserAttemptCount(Auth::id()),
            'completed' => $this->hasCompletedQuiz($questionnaireId),
            'best_score' => $questionnaire->getUserBestScore(Auth::id()),
            'average_score' => $questionnaire->getUserAverageScore(Auth::id()),
            'in_progress' => $this->hasInProgressQuiz($questionnaireId),
            'abandoned' => $this->hasAbandonedQuiz($questionnaireId),
        ];
    }

    public function debugQuizData($questionnaireId)
    {
        // Get fresh data from database
        $dbAttempts = QuizAttempt::where('user_id', Auth::id())
            ->where('questionnaire_id', $questionnaireId)
            ->get();
            
        $questionnaire = Questionnaire::find($questionnaireId);
        $methodCount = $questionnaire ? $questionnaire->getUserAttemptCount(Auth::id()) : 0;
        
        // Also check what recentAttempts contains for this questionnaire
        $recentForThisQuiz = $this->recentAttempts
            ->where('questionnaire_id', $questionnaireId);
            
        session()->flash('success', 
            "Debug Data for Q{$questionnaireId}: " .
            "DB completed attempts: " . $dbAttempts->where('status', 'completed')->count() . " | " .
            "recentAttempts total: " . $this->recentAttempts->count() . " | " .
            "recentAttempts for this quiz: " . $recentForThisQuiz->count() . " | " .
            "hasCompleted method: " . ($this->hasCompletedQuiz($questionnaireId) ? 'true' : 'false') . " | " .
            "getUserAttemptCount: " . $methodCount . " | " .
            "DB attempt statuses: " . $dbAttempts->pluck('status')->implode(',') . " | " .
            "recentAttempts statuses for this quiz: " . $recentForThisQuiz->pluck('status')->implode(',')
        );
    }


    public function render()
    {
        return view('livewire.user.available-quest');
    }
}