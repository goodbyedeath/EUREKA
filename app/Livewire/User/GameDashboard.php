<?php

namespace App\Livewire\User;

use App\Models\GameLocation;
use Livewire\Component;
use Livewire\WithPagination;

class GameDashboard extends Component
{
    use WithPagination;

    public $selectedGame = null;
    public $showModal = false;
    
    // Add these properties if you plan to use coordinate updates
    public $coordinate_x = 0;
    public $coordinate_y = 0;

    protected $listeners = [
        'coordinates-updated' => 'handleCoordinatesUpdate'
    ];

    public function mount()
    {
        // Initialize any default values if needed
        $this->coordinate_x = 0;
        $this->coordinate_y = 0;
    }

    public function render()
    {
        return view('livewire.user.game-dashboard', [
            'games' => GameLocation::where('is_active', true)
                ->orderBy('created_at', 'desc')
                ->paginate(12)
        ]);
    }

    public function viewGame($gameId)
    {
        try {
            $this->selectedGame = GameLocation::findOrFail($gameId);
            $this->showModal = true;
            
            // Reset coordinates to game's coordinates
            $this->coordinate_x = $this->selectedGame->coordinate_x ?? 0;
            $this->coordinate_y = $this->selectedGame->coordinate_y ?? 0;
            
            // Dispatch event to frontend if needed
            $this->dispatch('game-selected', [
                'gameId' => $gameId,
                'coordinates' => [
                    'x' => $this->coordinate_x,
                    'y' => $this->coordinate_y
                ]
            ]);
            
        } catch (\Exception $e) {
            session()->flash('error', 'Game not found.');
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->selectedGame = null;
        $this->coordinate_x = 0;
        $this->coordinate_y = 0;
        
        // Dispatch cleanup event to frontend without causing listener conflicts
        $this->dispatch('game-modal-cleanup');
    }

    public function updateCoordinates($x, $y)
    {
        // Validate coordinates
        $x = max(0, (float) $x);
        $y = max(0, (float) $y);
        
        $this->coordinate_x = $x;
        $this->coordinate_y = $y;
        
        // Emit event for JavaScript if needed
        $this->dispatch('coordinates-updated', [
            'x' => $this->coordinate_x, 
            'y' => $this->coordinate_y
        ]);
    }

    public function handleCoordinatesUpdate($data)
    {
        if (isset($data['x']) && isset($data['y'])) {
            $this->updateCoordinates($data['x'], $data['y']);
        }
    }

    // Optional: Add method to refresh games list
    public function refreshGames()
    {
        $this->resetPage();
    }

    // Optional: Add method to filter games (for future use)
    public function filterGames($filter = null)
    {
        $this->resetPage();
        // Add filtering logic here if needed
    }

    // Override Livewire's updating method for real-time validation
    public function updatingCoordinateX($value)
    {
        return max(0, (float) $value);
    }

    public function updatingCoordinateY($value)
    {
        return max(0, (float) $value);
    }

    // Optional: Add error handling for missing games
    public function getSelectedGameProperty()
    {
        if ($this->selectedGame && !$this->selectedGame->exists) {
            $this->closeModal();
            session()->flash('error', 'Selected game no longer exists.');
            return null;
        }
        
        return $this->selectedGame;
    }

    // Cleanup method that can be called from frontend
    public function cleanup()
    {
        $this->closeModal();
    }
}