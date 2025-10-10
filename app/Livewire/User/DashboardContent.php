<?php
// app/Livewire/User/DashboardContent.php

namespace App\Livewire\User;

use App\Traits\HasFeatureAccess;
use Livewire\Component;
use App\Services\WorkflowTimerService;
use App\Models\FeatureSetting;


class DashboardContent extends Component
{
    use HasFeatureAccess;
    public $activeTab = 'dashboard';
    public $scannedqr_code = null;
    public $questionnaireId = null;
    public $team = null;

    protected $listeners = [
        'active-tab-changed' => 'handleTabChange',
        'qr-code-scanned' => 'handleqr_codeScanned',
        'dashboard-refreshed' => 'refreshData',
        'echo:session-expired,SessionExpired' => 'handleSessionExpired'
    ];

    public $sessionTimeRemaining = null;
    public $sessionTimeout = null;
    private ?WorkflowTimerService $workflowTimerService = null;

    public function mount()
    {
        try {
            $this->workflowTimerService = app(WorkflowTimerService::class);
        } catch (\Exception $e) {
            \Log::error("Failed to initialize WorkflowTimerService in DashboardContent: " . $e->getMessage());
            $this->workflowTimerService = null;
        }
        
        $this->loadUserData();
        
        // Check if we should switch to a specific tab (e.g., after completing a quiz)
        if (session()->has('active_tab')) {
            $this->activeTab = session('active_tab');
            session()->forget('active_tab');
        }
        
        // Initialize session timer data if workflow timers are enabled
        if ($this->isWorkflowTimersEnabled()) {
            $this->updateSessionTimer();
        }
    }

    public function loadUserData()
    {
        // Load user's team
        $user = auth()->user();
        $this->team = $user->createdTeam;
        
        // Check for active questionnaire
        $this->questionnaireId = session('active_questionnaire_id');
        
        // Check for scanned QR code
        $this->scannedqr_code = session('scanned_qr_code');
    }

    public function handleTabChange($tab)
{
    $tabValue = is_array($tab) ? $tab['tab'] : $tab;

    if (in_array($tabValue, ['dashboard', 'quizzes', 'members', 'quests', 'games'])) {
        $this->activeTab = $tabValue;
    }
}


    public function handleqr_codeScanned($qr_code)
    {
        $this->scannedqr_code = $qr_code;
        $this->activeTab = 'quizzes';
    }

    public function refreshData()
    {
        $this->loadUserData();
    }

    public function switchToQuizzes()
    {
        $this->activeTab = 'quizzes';
        $this->dispatch('tab-switched', tab: 'quizzes');
    }

    public function switchToMembers()
    {
        $this->activeTab = 'members';
        $this->dispatch('tab-switched', tab: 'members');
    }

    public function switchToGames()
    {
        $this->activeTab = 'games';
        $this->dispatch('tab-switched', tab: 'games');
    }
    
    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->dispatch('tab-switched', tab: $tab);
    }

    public function openQRScanner()
    {
        $this->dispatch('open-qr-scanner');
    }

    /**
     * Handle session expired event from workflow
     */
    public function handleSessionExpired($event)
    {
        session()->flash('error', 'Your session has expired due to inactivity.');
        $this->dispatch('forceLogout');
    }

    /**
     * Update session timer data for display
     */
    public function updateSessionTimer()
    {
        if (!$this->isWorkflowTimersEnabled() || !$this->workflowTimerService) {
            return;
        }

        try {
            $user = auth()->user();
            if ($user->role !== 'user') {
                return;
            }

            $timerData = $this->workflowTimerService->getSessionRemainingTime($user);
            if ($timerData) {
                $this->sessionTimeRemaining = $timerData['remaining_seconds'];
                $this->sessionTimeout = $timerData['total_seconds'];
            }
        } catch (\Exception $e) {
            \Log::error("Failed to update session timer: " . $e->getMessage());
            // Don't set timer data if there's an error
        }
    }

    /**
     * Get formatted session time remaining for display
     */
    public function getFormattedSessionTime()
    {
        if (!$this->sessionTimeRemaining) {
            return '--:--';
        }

        $minutes = floor($this->sessionTimeRemaining / 60);
        $seconds = $this->sessionTimeRemaining % 60;
        return sprintf('%d:%02d', $minutes, $seconds);
    }

    /**
     * Check if workflow timers are enabled
     */
    public function isWorkflowTimersEnabled(): bool
    {
        try {
            return FeatureSetting::isEnabled('workflow_timers');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get session timer status for UI styling
     */
    public function getSessionTimerStatus(): string
    {
        if (!$this->sessionTimeRemaining || !$this->sessionTimeout) {
            return 'normal';
        }

        $percentage = ($this->sessionTimeRemaining / $this->sessionTimeout) * 100;
        
        if ($percentage <= 10) { // Less than 10% remaining
            return 'critical';
        } elseif ($percentage <= 25) { // Less than 25% remaining
            return 'warning';
        }

        return 'normal';
    }

    public function render()
    {
        // Update session timer data on each render if workflow timers are enabled
        if ($this->isWorkflowTimersEnabled()) {
            $this->updateSessionTimer();
        }
        
        return view('livewire.user.dashboard-content');
    }
}