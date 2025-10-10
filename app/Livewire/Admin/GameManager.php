<?php

namespace App\Livewire\Admin;

use App\Models\GameLocation;
use App\Models\Hotspot;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Storage;

class GameManager extends Component
{
    use WithFileUploads, WithPagination;

    public $name = '';
    public $description = '';
    public $map_image_path = '';
    
    // Panorama default view coordinates
    public $default_pitch = 0;  // Vertical view angle (-90 to 90)
    public $default_yaw = 0;    // Horizontal view angle (-180 to 180)
    public $is_active = true;
    public $target_type = 'all_users';
    public $target_user_id = null;
    
    public $mapImageUpload;
    public $editingGameId = null;
    public $showModal = false;
    public $showPanoramaModal = false;
    public $selectedGame = null;

    protected $rules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        // Panorama default view coordinates
        'default_pitch' => 'nullable|numeric|between:-90,90',
        'default_yaw' => 'nullable|numeric|between:-180,180',
        'is_active' => 'boolean',
        'target_type' => 'required|in:all_users,specific_user',
        'target_user_id' => 'nullable|exists:users,id',
        'mapImageUpload' => 'nullable|image|max:10240' // 10MB max, temporarily removed ratio validation
    ];

    protected $messages = [
        'mapImageUpload.image' => 'Please upload a valid image file.',
        'mapImageUpload.max' => 'The image size must not exceed 10MB.',
    ];

    public function render()
    {
        return view('livewire.admin.game-manager', [
            'games' => GameLocation::with('activeHotspots')->orderBy('created_at', 'desc')->paginate(10)
        ]);
    }

    public function openModal($gameId = null)
    {
        $this->resetForm();
        
        if ($gameId) {
            $this->editingGameId = $gameId;
            $game = GameLocation::findOrFail($gameId);
            
            $this->name = $game->name;
            $this->description = $game->description;
            $this->map_image_path = $game->map_image_path;
            $this->default_pitch = $game->default_pitch ?? 0;
            $this->default_yaw = $game->default_yaw ?? 0;
            $this->is_active = $game->is_active;
            $this->target_type = $game->target_type ?? 'all_users';
            $this->target_user_id = $game->target_user_id;
        }
        
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function save()
    {
        try {
            \Log::info('GameManager save method called', [
                'editingGameId' => $this->editingGameId,
                'name' => $this->name,
                'hasMapImageUpload' => !!$this->mapImageUpload,
                'mapImageUploadInfo' => $this->mapImageUpload ? [
                    'originalName' => $this->mapImageUpload->getClientOriginalName(),
                    'size' => $this->mapImageUpload->getSize(),
                    'mimeType' => $this->mapImageUpload->getMimeType()
                ] : null
            ]);

            $this->validate();

        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'what_to_do' => $this->description, // Use description as what_to_do for now
            // Panorama default view coordinates
            'default_pitch' => $this->default_pitch ?: null,
            'default_yaw' => $this->default_yaw ?: null,
            'is_active' => $this->is_active,
            'target_type' => $this->target_type,
            'target_user_id' => $this->target_user_id,
            'created_by' => 1, // Default admin user ID
            'quest_points' => 10, // Default points
            'radius' => 50, // Default radius
            'max_check_ins_per_user' => 1, // Default max check-ins
        ];

        // Handle image upload
        if ($this->mapImageUpload) {
            try {
                \Log::info('Processing image upload', [
                    'file' => $this->mapImageUpload->getClientOriginalName(),
                    'size' => $this->mapImageUpload->getSize(),
                    'mime' => $this->mapImageUpload->getMimeType()
                ]);

                // Delete old image if updating
                if ($this->editingGameId && $this->map_image_path) {
                    \Log::info('Deleting old image: ' . $this->map_image_path);
                    Storage::disk('public')->delete($this->map_image_path);
                }
                
                $path = $this->mapImageUpload->store('games/map-images', 'public');
                \Log::info('Image stored successfully at: ' . $path);
                $data['map_image_path'] = $path;
            } catch (\Exception $e) {
                \Log::error('Image upload failed: ' . $e->getMessage());
                session()->flash('error', 'Image upload failed: ' . $e->getMessage());
                return;
            }
        }

        if ($this->editingGameId) {
            GameLocation::findOrFail($this->editingGameId)->update($data);
            session()->flash('success', 'Game location updated successfully!');
        } else {
            GameLocation::create($data);
            session()->flash('success', 'Game location created successfully!');
        }

        $this->closeModal();
        } catch (\Exception $e) {
            \Log::error('GameManager save failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            session()->flash('error', 'Save failed: ' . $e->getMessage());
        }
    }

    public function delete($gameId)
    {
        $game = GameLocation::findOrFail($gameId);
        
        // Delete associated image
        if ($game->map_image_path) {
            Storage::disk('public')->delete($game->map_image_path);
        }
        
        $game->delete();
        session()->flash('success', 'Game location deleted successfully!');
    }

    public function toggleActive($gameId)
    {
        $game = GameLocation::findOrFail($gameId);
        $game->update(['is_active' => !$game->is_active]);
        
        session()->flash('success', 'Game location status updated!');
    }

    public function viewPanorama($gameId)
    {
        $this->selectedGame = GameLocation::with('activeHotspots')->findOrFail($gameId);
        $this->showPanoramaModal = true;
        
        // Emit event to initialize Panellum viewer with existing hotspots
        $this->dispatch('panorama-modal-opened', [
            'gameId' => $gameId,
            'imagePath' => $this->selectedGame->map_image_path,
            'hotspots' => $this->selectedGame->activeHotspots->map(function ($hotspot) {
                return [
                    'id' => $hotspot->id,
                    'pitch' => (float) $hotspot->pitch,
                    'yaw' => (float) $hotspot->yaw,
                    'type' => $hotspot->type,
                    'text' => $hotspot->title,
                    'description' => $hotspot->description,
                    'cssClass' => $hotspot->css_class ?: 'custom-admin-hotspot'
                ];
            })->toArray()
        ]);
    }

    public function closePanoramaModal()
    {
        $this->showPanoramaModal = false;
        $this->selectedGame = null;
        
        // Emit cleanup event
        $this->dispatch('panorama-modal-closed');
    }

    public function updatePanoramaView($pitch, $yaw)
    {
        $this->default_pitch = (float) $pitch;
        $this->default_yaw = (float) $yaw;
    }

    // Hotspot Management Methods
    public function saveHotspot($gameLocationId, $pitch, $yaw, $title, $description = null, $type = 'info', $cssClass = 'custom-admin-hotspot')
    {
        try {
            // Validate inputs
            if (empty($gameLocationId)) {
                throw new \InvalidArgumentException('Game location ID is required');
            }
            
            if (empty($title)) {
                throw new \InvalidArgumentException('Hotspot title is required');
            }

            // Check if game location exists
            $gameLocation = GameLocation::find($gameLocationId);
            if (!$gameLocation) {
                throw new \InvalidArgumentException('Game location not found');
            }

            // Validate coordinates
            $pitch = (float) $pitch;
            $yaw = (float) $yaw;
            
            if ($pitch < -90 || $pitch > 90) {
                throw new \InvalidArgumentException('Pitch must be between -90 and 90 degrees');
            }
            
            if ($yaw < -180 || $yaw > 180) {
                throw new \InvalidArgumentException('Yaw must be between -180 and 180 degrees');
            }

            $hotspot = Hotspot::create([
                'game_location_id' => $gameLocationId,
                'title' => $title,
                'description' => $description,
                'pitch' => $pitch,
                'yaw' => $yaw,
                'type' => $type ?: 'info',
                'css_class' => $cssClass ?: 'custom-admin-hotspot',
                'is_active' => true
            ]);

            $this->dispatch('hotspot-saved', [
                'id' => $hotspot->id,
                'pitch' => (float) $hotspot->pitch,
                'yaw' => (float) $hotspot->yaw,
                'type' => $hotspot->type,
                'text' => $hotspot->title,
                'description' => $hotspot->description,
                'cssClass' => $hotspot->css_class
            ]);

            session()->flash('success', 'Hotspot saved successfully!');
            
            return $hotspot->id;
        } catch (\Exception $e) {
            \Log::error('Failed to save hotspot', [
                'gameLocationId' => $gameLocationId,
                'pitch' => $pitch,
                'yaw' => $yaw,
                'title' => $title,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            session()->flash('error', 'Failed to save hotspot: ' . $e->getMessage());
            return false;
        }
    }

    public function deleteHotspot($hotspotId)
    {
        try {
            $hotspot = Hotspot::findOrFail($hotspotId);
            $hotspot->delete();
            
            $this->dispatch('hotspot-deleted', ['id' => $hotspotId]);
            session()->flash('success', 'Hotspot deleted successfully!');
            
            return true;
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete hotspot: ' . $e->getMessage());
            return false;
        }
    }

    public function clearAllHotspots($gameLocationId)
    {
        try {
            $deletedCount = Hotspot::where('game_location_id', $gameLocationId)->delete();
            
            $this->dispatch('all-hotspots-cleared');
            session()->flash('success', "Cleared {$deletedCount} hotspots successfully!");
            
            return $deletedCount;
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to clear hotspots: ' . $e->getMessage());
            return false;
        }
    }

    public function getHotspots($gameLocationId)
    {
        try {
            $hotspots = Hotspot::where('game_location_id', $gameLocationId)
                ->where('is_active', true)
                ->get()
                ->map(function ($hotspot) {
                    return [
                        'id' => $hotspot->id,
                        'pitch' => (float) $hotspot->pitch,
                        'yaw' => (float) $hotspot->yaw,
                        'type' => $hotspot->type,
                        'text' => $hotspot->title,
                        'description' => $hotspot->description,
                        'cssClass' => $hotspot->css_class ?: 'custom-admin-hotspot'
                    ];
                })
                ->toArray();

            return $hotspots;
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to load hotspots: ' . $e->getMessage());
            return [];
        }
    }

    public function exportHotspots($gameLocationId)
    {
        try {
            $gameLocation = GameLocation::findOrFail($gameLocationId);
            $hotspots = $this->getHotspots($gameLocationId);
            
            $data = [
                'location_name' => $gameLocation->name,
                'location_id' => $gameLocationId,
                'exported_at' => now()->toISOString(),
                'hotspots' => $hotspots
            ];

            $filename = 'hotspots-' . \Str::slug($gameLocation->name) . '-' . now()->format('Y-m-d-H-i-s') . '.json';
            
            $this->dispatch('download-hotspots', [
                'data' => $data,
                'filename' => $filename
            ]);

            return $data;
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to export hotspots: ' . $e->getMessage());
            return false;
        }
    }

    public function importHotspots($gameLocationId, $hotspotsData)
    {
        try {
            $imported = 0;
            
            foreach ($hotspotsData as $hotspotData) {
                $this->saveHotspot(
                    $gameLocationId,
                    $hotspotData['pitch'] ?? 0,
                    $hotspotData['yaw'] ?? 0,
                    $hotspotData['text'] ?? $hotspotData['title'] ?? 'Imported Hotspot',
                    $hotspotData['description'] ?? null,
                    $hotspotData['type'] ?? 'info',
                    $hotspotData['cssClass'] ?? 'custom-admin-hotspot'
                );
                $imported++;
            }

            session()->flash('success', "Successfully imported {$imported} hotspots!");
            return $imported;
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to import hotspots: ' . $e->getMessage());
            return false;
        }
    }

    private function resetForm()
    {
        $this->editingGameId = null;
        $this->name = '';
        $this->description = '';
        $this->map_image_path = '';
        $this->default_pitch = 0;
        $this->default_yaw = 0;
        $this->is_active = true;
        $this->target_type = 'all_users';
        $this->target_user_id = null;
        $this->mapImageUpload = null;
        $this->resetValidation();
    }
}