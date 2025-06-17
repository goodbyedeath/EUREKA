<?php
namespace App\Livewire\User;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Questionnaire;
use App\Models\QuizAttempt;
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
        \Log::info('AvailableQuest: Received QR code scanned event', ['qr_code' => $qr_code]);
        
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
        $this->recentAttempts = QuizAttempt::where('user_id', Auth::id())->get();
        
        if ($this->scannedQr_code) {
            \Log::info('=== DETAILED QR CODE DEBUGGING ===');
            \Log::info('Scanned QR Code: "' . $this->scannedQr_code . '"');
            \Log::info('Current time: ' . now()->toDateTimeString());
            
            // Step 1: Raw database check
            $questionnairesWithQr = Questionnaire::where('qr_code', $this->scannedQr_code)->get();
            \Log::info('Step 1 - Raw questionnaires with QR: ' . $questionnairesWithQr->count());
            
            foreach ($questionnairesWithQr as $q) {
                \Log::info("Raw Questionnaire: ID {$q->id}, Title: '{$q->title}'");
                \Log::info("  - is_active: " . ($q->is_active ? 'true' : 'false'));
                \Log::info("  - start_date: " . ($q->start_date ? $q->start_date->toDateTimeString() : 'null'));
                \Log::info("  - end_date: " . ($q->end_date ? $q->end_date->toDateTimeString() : 'null'));
                \Log::info("  - questions_count: " . $q->questions()->count());
                
                // Test isAvailable step by step
                $now = now();
                $isActiveCheck = $q->is_active;
                $startDateCheck = !$q->start_date || $now->gte($q->start_date);
                $endDateCheck = !$q->end_date || $now->lte($q->end_date);
                $finalAvailable = $q->isAvailable();
                
                \Log::info("  - isActive check: " . ($isActiveCheck ? 'PASS' : 'FAIL'));
                \Log::info("  - startDate check: " . ($startDateCheck ? 'PASS' : 'FAIL'));
                \Log::info("  - endDate check: " . ($endDateCheck ? 'PASS' : 'FAIL'));
                \Log::info("  - isAvailable() result: " . ($finalAvailable ? 'PASS' : 'FAIL'));
            }
            
            if ($questionnairesWithQr->count() == 0) {
                // No questionnaire found - check what QR codes exist
                $allQrs = Questionnaire::select('id', 'title', 'qr_code')->get();
                \Log::info('All QR codes in database:');
                foreach ($allQrs as $q) {
                    \Log::info("  QR: '{$q->qr_code}' (ID: {$q->id}, Title: {$q->title})");
                }
            }
            
            // Step 2: Active filter
            $activeQuestionnaires = Questionnaire::where('qr_code', $this->scannedQr_code)
                ->active()
                ->get();
            \Log::info('Step 2 - After active() filter: ' . $activeQuestionnaires->count());
            
            // Step 3: WithQuestions filter
            $questionnairesWithQuestions = Questionnaire::where('qr_code', $this->scannedQr_code)
                ->active()
                ->withQuestions()
                ->withCount('questions')
                ->get();
            \Log::info('Step 3 - After withQuestions() filter: ' . $questionnairesWithQuestions->count());
            
            // Step 4: isAvailable filter
            $availableOnes = $questionnairesWithQuestions->filter(function ($questionnaire) {
                return $questionnaire->isAvailable();
            });
            \Log::info('Step 4 - After isAvailable() filter: ' . $availableOnes->count());
            
            $this->availableQuestionnaires = $availableOnes->values();
            $this->showNoQuizMessage = $this->availableQuestionnaires->isEmpty();
            
            \Log::info('Final result: ' . ($this->showNoQuizMessage ? 'SHOWING NO QUIZ MESSAGE' : 'QUESTIONNAIRES AVAILABLE'));
            \Log::info('=== END DETAILED DEBUGGING ===');
        } else {
            // Load all available questionnaires when no QR code is scanned
            $this->availableQuestionnaires = Questionnaire::active()
                ->withQuestions()
                ->withCount('questions')
                ->get()
                ->filter(function ($questionnaire) {
                    return $questionnaire->isAvailable();
                })
                ->values();
            
            $this->showNoQuizMessage = false;
        }
    }

    public function startQuiz($questionnaireId)
{
    \Log::info('=== START QUIZ DEBUG ===');
    \Log::info('Received questionnaireId: ' . $questionnaireId);
    
    $questionnaire = Questionnaire::find($questionnaireId);
    
    if (!$questionnaire) {
        \Log::error('Questionnaire not found with ID: ' . $questionnaireId);
        session()->flash('error', 'Questionnaire not found.');
        return;
    }

    \Log::info('Questionnaire found: ' . $questionnaire->title);

    if (!$questionnaire->isAvailable() || !$questionnaire->hasQuestions()) {
        session()->flash('error', 'Questionnaire not found, inactive, or has no questions.');
        return;
    }

    if ($this->scannedQr_code && $questionnaire->qr_code !== $this->scannedQr_code) {
        session()->flash('error', 'This questionnaire does not match the scanned code.');
        return;
    }

    if (!$questionnaire->canUserAttempt(Auth::id())) {
        session()->flash('error', 'You have reached the maximum number of attempts for this quiz.');
        return;
    }

    $existingAttempt = $questionnaire->getUserInProgressAttempt(Auth::id());
    if ($existingAttempt) {
        \Log::info('Found existing attempt, redirecting to continue...');
        return redirect()->route('quiz.continue', $existingAttempt->id);
    }

    \Log::info('Starting new quiz attempt, redirecting to quiz.take...');
    
    // Direct redirect to quiz.take route with questionnaireId
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
        return $this->recentAttempts
            ->where('questionnaire_id', $questionnaireId)
            ->where('status', QuizAttempt::STATUS_COMPLETED)
            ->isNotEmpty();
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

    public function render()
    {
        return view('livewire.user.available-quest');
    }
}