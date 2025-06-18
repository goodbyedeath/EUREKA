<?php
// app/Livewire/User/DashboardContent.php

namespace App\Livewire\User;

use Livewire\Component;


class DashboardContent extends Component
{
    public $activeTab = 'dashboard';
    public $scannedqr_code = null;
    public $questionnaireId = null;
    public $team = null;

    protected $listeners = [
        'active-tab-changed' => 'handleTabChange',
        'qr-code-scanned' => 'handleqr_codeScanned',
        'dashboard-refreshed' => 'refreshData'
    ];

    public function mount()
    {
        $this->loadUserData();
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

    if (in_array($tabValue, ['dashboard', 'quizzes', 'members'])) {
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
        $this->dispatch('tab-switched', ['tab' => 'quizzes']);
    }

    public function switchToMembers()
    {
        $this->activeTab = 'members';
        $this->dispatch('tab-switched', ['tab' => 'members']);
    }

    public function render()
    {
        return view('livewire.user.dashboard-content');
    }
}