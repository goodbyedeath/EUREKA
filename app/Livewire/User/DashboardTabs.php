<?php
// app/Livewire/User/DashboardTabs.php

namespace App\Livewire\User;

use App\Traits\HasFeatureAccess;
use Livewire\Component;
use Livewire\Attributes\On;

class DashboardTabs extends Component
{
    use HasFeatureAccess;
    public $activeTab = 'dashboard';
    public $scannedqr_code = null;

    // Remove the old listeners array and use attributes instead
    
    public function mount()
    {
        // Always load scanned QR code from session
        $this->scannedqr_code = session('scanned_qr_code');
        
        // Check if there's a specific tab requested (e.g., after quiz completion)
        $requestedTab = session('active_tab');
        if ($requestedTab) {
            $this->activeTab = $requestedTab;
            session()->forget('active_tab');
        } elseif ($this->scannedqr_code) {
            // If there's a scanned QR code, switch to quizzes tab
            $this->activeTab = 'quizzes';
        }
        
        // Ensure the content component knows about the active tab
        $this->dispatch('active-tab-changed', tab: $this->activeTab);
    }

    public function switchTab($tab)
    {
        // Get enabled features
        $enabledTabs = $this->getEnabledTabs();
        
        // Validate tab and check if feature is enabled
        if (in_array($tab, $enabledTabs)) {
            $this->activeTab = $tab;
            
            // Emit event to update content
            $this->dispatch('active-tab-changed', tab: $tab);
        }
    }

    public function getEnabledTabs()
    {
        $tabs = ['dashboard']; // Dashboard is always enabled
        
        if ($this->featureEnabled('quiz_system')) {
            $tabs[] = 'quizzes';
        }
        
        if ($this->featureEnabled('team_management')) {
            $tabs[] = 'members';
        }
        
        if ($this->featureEnabled('quest_locations')) {
            $tabs[] = 'quests';
        }
        
        if ($this->featureEnabled('game_dashboard')) {
            $tabs[] = 'games';
        }
        
        return $tabs;
    }

    #[On('qr-code-scanned')]
    public function handleqr_codeScanned($qr_code)
    {
        $this->scannedqr_code = $qr_code;
        
        // Store in session for persistence
        session(['scanned_qr_code' => $qr_code]);
        
        // Switch to quizzes tab when QR code is scanned
        $this->switchTab('quizzes');
        
        // No need to re-dispatch - other components already listening to the original event
    }

    #[On('tab-switched')]
    public function handleTabSwitch($tab)
    {
        $this->switchTab($tab);
    }
    
    #[On('clear-qr-code')]
    public function handleClearqr_code()
    {
        $this->clearqr_code();
    }

    public function clearqr_code()
    {
        $this->scannedqr_code = null;
        session()->forget('scanned_qr_code');
        
        // Notify AvailableQuest component to clear the scanned code
        $this->dispatch('clear-scanned-qr');
    }

    public function render()
    {
        return view('livewire.user.dashboard-tabs');
    }
}