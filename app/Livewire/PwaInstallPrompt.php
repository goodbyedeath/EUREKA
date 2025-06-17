<?php
// app/Livewire/PwaInstallPrompt.php

namespace App\Livewire;

use Livewire\Component;

class PwaInstallPrompt extends Component
{
    public $showPrompt = false;
    
    public function mount()
    {
        // Show prompt by default, will be hidden by JavaScript if PWA is already installed
        $this->showPrompt = true;
    }
    
    public function hidePrompt()
    {
        $this->showPrompt = false;
    }
    
    public function render()
    {
        return view('livewire.pwa-install-prompt');
    }
}