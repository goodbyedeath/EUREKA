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
    
    // Default view coordinates (legacy; AR calibrates from the QR instead)
    public $is_active = true;
    public $target_type = 'all_users';
    public $target_user_id = null;
    /** The post this outpost sits at; its coordinates gate the AR scene. */
    public $quest_location_id = null;
    /** 'geofence' = outdoor radius, 'manual' = indoor, opened by crew. */
    public $access_mode = 'geofence';
    

    /** 3D model upload for the AR experience (see ArExperienceController). */
    public $arModelUpload;
    public $editingGameId = null;
    public $showModal = false;

    protected $rules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        // Default view coordinates (legacy; AR calibrates from the QR instead)
        'is_active' => 'boolean',
        'target_type' => 'required|in:all_users,specific_user',
        'target_user_id' => 'nullable|exists:users,id',
        'quest_location_id' => 'nullable|exists:quest_locations,id',
        'access_mode' => 'required|in:geofence,manual',
    ];

    protected $messages = [
    ];

    public function render()
    {
        return view('livewire.admin.game-manager', [
            'arModels' => \App\Models\ArModel::orderBy('name')->get(),
            'questLocations' => \App\Models\QuestLocation::where('is_active', true)->orderBy('name')->get(),
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
            $this->is_active = $game->is_active;
            $this->target_type = $game->target_type ?? 'all_users';
            $this->target_user_id = $game->target_user_id;
            $this->quest_location_id = $game->quest_location_id;
            $this->access_mode = $game->access_mode ?? 'geofence';
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
            ]);

            $this->validate();

        $data = [
            'name' => $this->name,
            'description' => $this->description,
            // Default view coordinates (legacy; AR calibrates from the QR instead)
            'is_active' => $this->is_active,
            'target_type' => $this->target_type,
            'target_user_id' => $this->target_user_id,
            'quest_location_id' => $this->quest_location_id ?: null,
            'access_mode' => $this->access_mode,
            'created_by' => 1, // Default admin user ID
        ];

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
        
        // Remove the location's 3D model, if it has one.
        if ($game->ar_model_path) {
            Storage::disk('public')->delete($game->ar_model_path);
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
        $this->is_active = true;
        $this->target_type = 'all_users';
        $this->target_user_id = null;
        $this->quest_location_id = null;
        $this->access_mode = 'geofence';
        $this->resetValidation();
    }
    /**
     * Add a model to the shared library and point this location at it.
     *
     * The file lands in the library, not on the location, so the same asset can be
     * reused everywhere without a second upload or a second offline download.
     */
    public function uploadArModel($gameId)
    {
        $this->validate([
            'arModelUpload' => 'required|file|max:30720',
        ], [
            'arModelUpload.max' => 'The model must be 30 MB or smaller.',
        ]);

        $extension = strtolower($this->arModelUpload->getClientOriginalExtension());

        if (! in_array($extension, ['glb', 'gltf'], true)) {
            $this->addError('arModelUpload', 'Only .glb or .gltf models are supported.');
            return;
        }

        try {
            $game = GameLocation::findOrFail($gameId);
            $path = $this->arModelUpload->store('games/ar-models', 'public');

            $model = \App\Models\ArModel::create([
                'name' => pathinfo($this->arModelUpload->getClientOriginalName(), PATHINFO_FILENAME),
                'path' => $path,
                'size' => Storage::disk('public')->size($path),
                'created_by' => auth()->id(),
            ]);

            $game->update([
                'ar_model_id' => $model->id,
                'ar_model_path' => null,          // superseded by the library reference
                'experience_type' => GameLocation::EXPERIENCE_AR,
            ]);

            $this->arModelUpload = null;
            session()->flash('message', 'Added "' . $model->name . '" to the library and applied it here.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to upload the model. Please try again.');
        }
    }

    /**
     * Point a location at a model that is already in the library.
     */
    public function setArModel($gameId, $modelId)
    {
        $game = GameLocation::findOrFail($gameId);

        if (! $modelId) {
            $game->update(['ar_model_id' => null, 'experience_type' => GameLocation::EXPERIENCE_PANORAMA]);
            session()->flash('message', 'Model cleared — this location has no 3D experience yet.');
            return;
        }

        $game->update([
            'ar_model_id' => (int) $modelId,
            'ar_model_path' => null,
            'experience_type' => GameLocation::EXPERIENCE_AR,
        ]);

        session()->flash('message', 'Model updated.');
    }

    /**
     * Remove an asset from the library, but never one still in use.
     */
    public function deleteArModel($modelId)
    {
        $model = \App\Models\ArModel::findOrFail($modelId);

        if ($model->isInUse()) {
            session()->flash('error', 'That model is still used by a location or an object.');
            return;
        }

        Storage::disk('public')->delete($model->path);
        $model->delete();
        session()->flash('message', 'Model removed from the library.');
    }

    /**
     * Enable or disable the AR experience for a location.
     */
    public function setExperienceType($gameId, $type)
    {
        $game = GameLocation::findOrFail($gameId);

        // Ask the model where its file is, rather than checking one of the two columns.
        // uploadArModel() and setArModel() both write ar_model_path = null and record the
        // reference in ar_model_id, so an admin who picked a model from the library was
        // still told to "upload a 3D model first" and could never turn AR on.
        if ($type === GameLocation::EXPERIENCE_AR && $game->modelUrl() === null) {
            session()->flash('error', 'Upload a 3D model first — AR needs something to show.');
            return;
        }

        $game->update([
            'experience_type' => $type === GameLocation::EXPERIENCE_AR
                ? GameLocation::EXPERIENCE_AR
                : GameLocation::EXPERIENCE_PANORAMA,
        ]);

        session()->flash('message', $game->usesAr() ? 'Now using the 3D / AR experience.' : 'No 3D model yet — upload one to enable AR.');
    }
}