<?php
// app/Livewire/User/DashboardTabs.php

namespace App\Livewire\User;

use Livewire\Component;
use Livewire\Attributes\On;

class DashboardTabs extends Component
{
    public $activeTab = 'dashboard';
    public $scannedqr_code = null;

    // Remove the old listeners array and use attributes instead
    
    public function mount()
    {
        // Check if there's a scanned QR code in session
        $this->scannedqr_code = session('scanned_qr_code');
        
        // If there's a scanned QR code, switch to quizzes tab
        if ($this->scannedqr_code) {
            $this->activeTab = 'quizzes';
        }
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        
        // Emit event to update content
        $this->dispatch('active-tab-changed', tab: $tab);
    }

    #[On('qr-code-scanned')]
    public function handleqr_codeScanned($qr_code)
    {
        $this->scannedqr_code = $qr_code;
        
        // Store in session for persistence
        session(['scanned_qr_code' => $qr_code]);
        
        // Switch to quizzes tab when QR code is scanned
        $this->switchTab('quizzes');
        
        // Dispatch to the AvailableQuest component
        $this->dispatch('qr-code-scanned', qr_code: $qr_code);
    }

    #[On('tab-switched')]
    public function handleTabSwitch($tab)
    {
        $this->switchTab($tab);
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