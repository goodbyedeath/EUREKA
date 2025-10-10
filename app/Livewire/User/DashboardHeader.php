<?php
// app/Livewire/User/DashboardHeader.php

namespace App\Livewire\User;

use Livewire\Component;
use Livewire\Attributes\On;

class DashboardHeader extends Component
{
    public $isRefreshing = false;

    #[On('refresh-dashboard')]

    public function refreshData()
    {
        $this->isRefreshing = true;
        
        // Emit event to refresh other components
        $this->dispatch('dashboard-refreshed');
        
        // Simulate refresh delay
        sleep(1);
        
        $this->isRefreshing = false;
        
        session()->flash('message', 'Dashboard refreshed successfully!');
    }

    public function openScanner()
    {
        $this->dispatch('open-qr-scanner');
    }

    public function render()
    {
        return view('livewire.user.dashboard-header');
    }
}