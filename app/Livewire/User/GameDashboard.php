<?php

namespace App\Livewire\User;

use App\Models\GameLocation;
use App\Models\Hotspot;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;

class GameDashboard extends Component
{
    use WithPagination;

    public $selectedGame = null;
    public $showModal = false;
    
    // Panorama default view coordinates (for future use)
    public $default_pitch = 0;
    public $default_yaw = 0;

    #[On('panorama-view-updated')]

    public function mount()
    {
        // Initialize any default values if needed
        $this->default_pitch = 0;
        $this->default_yaw = 0;
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
            
            // Reset coordinates to game's default view
            $this->default_pitch = $this->selectedGame->default_pitch ?? 0;
            $this->default_yaw = $this->selectedGame->default_yaw ?? 0;
            
            // Dispatch event to frontend if needed
            $this->dispatch('game-selected', [
                'gameId' => $gameId,
                'defaultView' => [
                    'pitch' => $this->default_pitch,
                    'yaw' => $this->default_yaw
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
        $this->default_pitch = 0;
        $this->default_yaw = 0;
        
        // Dispatch cleanup event to frontend without causing listener conflicts
        $this->dispatch('game-modal-cleanup');
    }

    public function updatePanoramaView($pitch, $yaw)
    {
        // Validate panorama coordinates
        $pitch = max(-90, min(90, (float) $pitch));
        $yaw = (float) $yaw;
        
        $this->default_pitch = $pitch;
        $this->default_yaw = $yaw;
        
        // Emit event for JavaScript if needed
        $this->dispatch('panorama-view-updated', [
            'pitch' => $this->default_pitch, 
            'yaw' => $this->default_yaw
        ]);
    }

    public function handlePanoramaUpdate($data)
    {
        if (isset($data['pitch']) && isset($data['yaw'])) {
            $this->updatePanoramaView($data['pitch'], $data['yaw']);
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
    public function updatingDefaultPitch($value)
    {
        return max(-90, min(90, (float) $value));
    }

    public function updatingDefaultYaw($value)
    {
        return (float) $value;
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

    // Get hotspots for a game location with tour features
    public function getHotspots($gameLocationId)
    {
        try {
            $game = GameLocation::with('activeHotspots')->find($gameLocationId);
            
            if (!$game) {
                return [];
            }

            // Use the official toPannellumConfig method for consistency with UserPanoramaController
            return $game->activeHotspots->map(function ($hotspot) {
                // Get the official Pannellum configuration
                $pannellumConfig = $hotspot->toPannellumConfig();
                
                // Add additional data needed for user interface
                $hotspotData = [
                    'id' => $hotspot->id,
                    'pitch' => (float) $hotspot->pitch, // Raw radians for coordinate processing
                    'yaw' => (float) $hotspot->yaw,     // Raw radians for coordinate processing
                    'type' => $hotspot->type,
                    'title' => $hotspot->title,
                    'description' => $hotspot->description,
                    
                    // Tour feature data from Pannellum config
                    'hotspot_type' => $hotspot->getHotspotType(),
                    'content' => $hotspot->getContent(),
                    
                    // Include Pannellum-ready data
                    'pannellum_config' => $pannellumConfig
                ];
                
                // Add navigation data
                if ($hotspot->isNavigationHotspot()) {
                    $hotspotData['target_location_id'] = $hotspot->getTargetLocationId();
                }
                
                // Add quiz data
                if ($hotspot->isQuizHotspot()) {
                    $hotspotData['quiz_data'] = $hotspot->getQuizData();
                }
                
                // Add image data for info hotspots
                if ($hotspot->isInfoHotspot() && $hotspot->hasImage()) {
                    $hotspotData['has_image'] = true;
                    $hotspotData['image_url'] = $hotspot->getImageUrl();
                    $hotspotData['image_path'] = $hotspot->getImagePath();
                } else {
                    $hotspotData['has_image'] = false;
                }
                
                return $hotspotData;
            })->toArray();
        } catch (\Exception $e) {
            \Log::error('Failed to load user hotspots with tour features', [
                'gameLocationId' => $gameLocationId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    // New method to handle hotspot clicks for users
    public function handleHotspotClick($hotspotId, $action = null)
    {
        try {
            $hotspot = Hotspot::find($hotspotId);
            
            if (!$hotspot) {
                $this->dispatch('hotspot-error', ['message' => 'Hotspot not found']);
                return;
            }
            
            // Log user interaction for analytics
            \Log::info('User hotspot interaction', [
                'user_id' => auth()->id(),
                'hotspot_id' => $hotspotId,
                'hotspot_type' => $hotspot->getHotspotType(),
                'action' => $action
            ]);
            
            // Dispatch different events based on hotspot type
            if ($hotspot->isNavigationHotspot()) {
                $this->dispatch('hotspot-navigation', [
                    'hotspot_id' => $hotspotId,
                    'target_location_id' => $hotspot->getTargetLocationId(),
                    'title' => $hotspot->title
                ]);
            } elseif ($hotspot->isQuizHotspot()) {
                $this->dispatch('hotspot-quiz', [
                    'hotspot_id' => $hotspotId,
                    'quiz_data' => $hotspot->getQuizData(),
                    'title' => $hotspot->title
                ]);
            } elseif ($hotspot->isInfoHotspot()) {
                $this->dispatch('hotspot-info', [
                    'hotspot_id' => $hotspotId,
                    'title' => $hotspot->title,
                    'content' => $hotspot->getContent(),
                    'image_url' => $hotspot->hasImage() ? $hotspot->getImageUrl() : null
                ]);
            }
            
        } catch (\Exception $e) {
            \Log::error('Error handling hotspot click', [
                'hotspot_id' => $hotspotId,
                'error' => $e->getMessage()
            ]);
            
            $this->dispatch('hotspot-error', [
                'message' => 'Error loading hotspot content'
            ]);
        }
    }
}