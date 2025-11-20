<?php

namespace App\Livewire\User;

use App\Models\GameLocation;
use App\Models\Hotspot;
use Livewire\Component;
use Livewire\WithPagination;

class GameDashboard extends Component
{
    use WithPagination;

    public function render()
    {
        return view('livewire.user.game-dashboard', [
            'games' => GameLocation::where('is_active', true)
                ->orderBy('created_at', 'desc')
                ->paginate(12)
        ]);
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