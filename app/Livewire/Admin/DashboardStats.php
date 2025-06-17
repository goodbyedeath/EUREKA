<?php
// app/Livewire/Admin/DashboardStats.php

namespace App\Livewire\Admin;

use Livewire\Component;

class DashboardStats extends Component
{
    public array $stats;

    public function mount(array $stats)
    {
        $this->stats = $stats;
    }

    public function render()
    {
        return view('livewire.admin.dashboard-stats');
    }
}