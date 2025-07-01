<?php

namespace App\Livewire\Admin;

use App\Models\GameLocation;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

class GameManager extends Component
{
    use WithPagination, WithFileUploads;

    public $showModal = false;
    public $editMode = false;
    public $gameId;

    // Form properties
    public $name = '';
    public $description = '';
    public $what_to_do = '';
    public $radius = 50;
    public $is_active = true;
    public $map_image = null;  // Map image
    public $coordinate_x = '';
    public $coordinate_y = '';
    public $existing_map_image_path = null;
    
    // Additional missing properties
    public $quest_points = 10;
    public $max_check_ins_per_user = 1;
    public $image = null;  // Regular image
    public $existing_image_path = null;
    
    // Image removal tracking
    public $pending_remove_image = false;
    public $pending_remove_map_image = false;
    public $original_coordinate_x = '';
    public $original_coordinate_y = '';
    
    // Search and filter properties
    public $search = '';
    public $filterStatus = 'all'; // all, active, inactive
    public $selectedGames = [];
    public $selectAll = false;

    protected $rules = [
        'name' => 'required|string|max:255|unique:game_locations,name',
        'description' => 'required|string|min:10',
        'what_to_do' => 'required|string|min:10',
        'radius' => 'required|integer|min:10|max:1000',
        'quest_points' => 'required|integer|min:0|max:1000',
        'max_check_ins_per_user' => 'required|integer|min:1|max:100',
        'is_active' => 'boolean',
        'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        'map_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        'coordinate_x' => 'nullable|integer|min:0',
        'coordinate_y' => 'nullable|integer|min:0',
    ];

    protected function messages()
    {
        return [
            'name.unique' => __('games.name_unique'),
            'description.min' => __('games.description_min'),
            'what_to_do.min' => __('games.what_to_do_min'),
            'radius.min' => __('games.radius_min'),
            'radius.max' => __('games.radius_max'),
            'quest_points.required' => __('games.quest_points_required'),
            'quest_points.min' => __('games.quest_points_min'),
            'quest_points.max' => __('games.quest_points_max'),
            'max_check_ins_per_user.min' => __('games.max_check_ins_min'),
            'max_check_ins_per_user.max' => __('games.max_check_ins_max'),
            'image.image' => __('validation.image', ['attribute' => __('games.regular_image')]),
            'image.max' => __('games.image_max'),
            'image.mimes' => __('games.image_mimes'),
            'map_image.image' => __('validation.image', ['attribute' => __('games.map_image')]),
            'map_image.max' => __('games.map_image_max'),
            'map_image.mimes' => __('games.image_mimes'),
            'coordinate_x.min' => __('games.coordinate_positive'),
            'coordinate_y.min' => __('games.coordinate_positive'),
        ];
    }

    public function render()
    {
        $query = GameLocation::query();
        
        // Apply search filter
        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }
        
        // Apply status filter
        if ($this->filterStatus !== 'all') {
            $isActive = $this->filterStatus === 'active';
            $query->where('is_active', $isActive);
        }
        
        return view('livewire.admin.game-manager', [
            'games' => $query->latest()->paginate(10)
        ]);
    }
    
    public function updatingSearch()
    {
        $this->resetPage();
    }
    
    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    public function create()
    {
        $this->resetForm();
        $this->editMode = false;
        $this->showModal = true;
    }

    public function edit($id)
    {
        $game = GameLocation::findOrFail($id);
        
        $this->gameId = $game->id;
        $this->name = $game->name;
        $this->description = $game->description;
        $this->what_to_do = $game->what_to_do;
        $this->radius = $game->radius;
        $this->quest_points = $game->quest_points ?? 10;
        $this->max_check_ins_per_user = $game->max_check_ins_per_user ?? 1;
        $this->is_active = $game->is_active;
        $this->coordinate_x = $game->coordinate_x;
        $this->coordinate_y = $game->coordinate_y;
        $this->existing_map_image_path = $game->map_image_path;
        $this->existing_image_path = $game->image_path;
        $this->pending_remove_image = false;
        $this->pending_remove_map_image = false;
        $this->original_coordinate_x = '';
        $this->original_coordinate_y = '';

        $this->editMode = true;
        $this->showModal = true;
    }

    public function save()
    {
        // Adjust validation rules for edit mode
        $rules = $this->rules;
        if ($this->editMode) {
            $rules['name'] = 'required|string|max:255|unique:game_locations,name,' . $this->gameId;
        }
        
        $this->validate($rules);

        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'what_to_do' => $this->what_to_do,
            'radius' => $this->radius,
            'quest_points' => $this->quest_points,
            'max_check_ins_per_user' => $this->max_check_ins_per_user,
            'is_active' => $this->is_active,
            'coordinate_x' => $this->coordinate_x ?: null,
            'coordinate_y' => $this->coordinate_y ?: null,
        ];

        // Handle regular image upload
        if ($this->image) {
            try {
                // Delete old regular image if exists
                if ($this->editMode && $this->existing_image_path) {
                    Storage::disk('public')->delete($this->existing_image_path);
                }
                
                // Store new regular image
                $imagePath = $this->image->store('games/images', 'public');
                $data['image_path'] = $imagePath;
                $this->existing_image_path = $imagePath; // Update component property
                $this->image = null; // Clear uploaded file
            } catch (\Exception $e) {
                session()->flash('error', __('games.upload_failed', ['error' => $e->getMessage()]));
                return;
            }
        }

        // Handle pending regular image removal
        if ($this->editMode && $this->pending_remove_image && $this->existing_image_path) {
            try {
                Storage::disk('public')->delete($this->existing_image_path);
                $data['image_path'] = null;
            } catch (\Exception $e) {
                // Log error but don't stop the save process
                logger()->error('Failed to remove regular image: ' . $e->getMessage());
            }
        }

        // Handle map image upload
        if ($this->map_image) {
            try {
                // Delete old map image if exists
                if ($this->editMode && $this->existing_map_image_path) {
                    Storage::disk('public')->delete($this->existing_map_image_path);
                }
                
                // Store new map image with proper naming
                $mapImagePath = $this->map_image->store('games/map-images', 'public');
                $data['map_image_path'] = $mapImagePath;
                $this->existing_map_image_path = $mapImagePath; // Update component property
                $this->map_image = null; // Clear uploaded file
            } catch (\Exception $e) {
                session()->flash('error', __('games.upload_failed', ['error' => $e->getMessage()]));
                return;
            }
        }

        // Handle pending map image removal
        if ($this->editMode && $this->pending_remove_map_image && $this->existing_map_image_path) {
            try {
                Storage::disk('public')->delete($this->existing_map_image_path);
                $data['map_image_path'] = null;
                // Reset coordinates when map image is removed
                $data['coordinate_x'] = null;
                $data['coordinate_y'] = null;
            } catch (\Exception $e) {
                // Log error but don't stop the save process
                logger()->error('Failed to remove map image: ' . $e->getMessage());
            }
        }

        try {
            if ($this->editMode) {
                $game = GameLocation::findOrFail($this->gameId);
                $game->update($data);
                session()->flash('message', __('games.location_updated'));
            } else {
                $data['created_by'] = auth()->id();
                GameLocation::create($data);
                session()->flash('message', __('games.location_created'));
            }

            $this->closeModal();
            $this->resetPage(); // Reset pagination to show new/updated item
        } catch (\Exception $e) {
            session()->flash('error', __('games.save_failed', ['error' => $e->getMessage()]));
        }
    }

    public function delete($id)
    {
        try {
            $game = GameLocation::findOrFail($id);
            
            // Delete associated images if they exist
            if ($game->image_path) {
                Storage::disk('public')->delete($game->image_path);
            }
            if ($game->map_image_path) {
                Storage::disk('public')->delete($game->map_image_path);
            }
            
            $game->delete();
            session()->flash('message', __('games.location_deleted'));
        } catch (\Exception $e) {
            session()->flash('error', __('games.delete_failed', ['error' => $e->getMessage()]));
        }
    }

    public function removeImage($type = 'regular')
    {
        if (!$this->editMode) return;

        if ($type === 'regular' && $this->existing_image_path) {
            $this->pending_remove_image = true;
            session()->flash('message', __('games.regular_image_marked_for_removal'));
        } elseif ($type === 'map' && $this->existing_map_image_path) {
            // Backup current coordinates before marking for removal
            $this->original_coordinate_x = $this->coordinate_x;
            $this->original_coordinate_y = $this->coordinate_y;
            
            // Reset coordinates when map image is marked for removal
            $this->coordinate_x = '';
            $this->coordinate_y = '';
            
            $this->pending_remove_map_image = true;
            session()->flash('message', __('games.map_image_marked_for_removal'));
        }
    }

    public function undoRemoveImage($type = 'regular')
    {
        if ($type === 'regular') {
            $this->pending_remove_image = false;
            session()->flash('message', __('games.regular_image_removal_cancelled'));
        } elseif ($type === 'map') {
            // Restore original coordinates when undoing map image removal
            $this->coordinate_x = $this->original_coordinate_x;
            $this->coordinate_y = $this->original_coordinate_y;
            
            $this->pending_remove_map_image = false;
            session()->flash('message', __('games.map_image_removal_cancelled'));
        }
    }


    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }
    
    public function initializeMapInteraction()
    {
        // This method is called from JavaScript to trigger re-initialization
        $this->dispatch('map-interaction-ready');
    }

    private function resetForm()
    {
        $this->name = '';
        $this->description = '';
        $this->what_to_do = '';
        $this->radius = 50;
        $this->quest_points = 10;
        $this->max_check_ins_per_user = 1;
        $this->is_active = true;
        $this->image = null;
        $this->map_image = null;
        $this->coordinate_x = '';
        $this->coordinate_y = '';
        $this->existing_image_path = null;
        $this->existing_map_image_path = null;
        $this->pending_remove_image = false;
        $this->pending_remove_map_image = false;
        $this->original_coordinate_x = '';
        $this->original_coordinate_y = '';
        $this->gameId = null;
        $this->resetErrorBag();
    }
    
    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedGames = GameLocation::pluck('id')->toArray();
        } else {
            $this->selectedGames = [];
        }
    }
    
    public function bulkActivate()
    {
        if (empty($this->selectedGames)) {
            session()->flash('error', __('games.select_at_least_one'));
            return;
        }
        
        try {
            GameLocation::whereIn('id', $this->selectedGames)->update(['is_active' => true]);
            session()->flash('message', __('games.locations_activated', ['count' => count($this->selectedGames)]));
            $this->selectedGames = [];
            $this->selectAll = false;
        } catch (\Exception $e) {
            session()->flash('error', __('games.activate_failed', ['error' => $e->getMessage()]));
        }
    }
    
    public function bulkDeactivate()
    {
        if (empty($this->selectedGames)) {
            session()->flash('error', __('games.select_at_least_one'));
            return;
        }
        
        try {
            GameLocation::whereIn('id', $this->selectedGames)->update(['is_active' => false]);
            session()->flash('message', __('games.locations_deactivated', ['count' => count($this->selectedGames)]));
            $this->selectedGames = [];
            $this->selectAll = false;
        } catch (\Exception $e) {
            session()->flash('error', __('games.deactivate_failed', ['error' => $e->getMessage()]));
        }
    }
    
    public function bulkDelete()
    {
        if (empty($this->selectedGames)) {
            session()->flash('error', __('games.select_at_least_one'));
            return;
        }
        
        try {
            $games = GameLocation::whereIn('id', $this->selectedGames)->get();
            
            foreach ($games as $game) {
                // Delete associated images
                if ($game->image_path) {
                    Storage::disk('public')->delete($game->image_path);
                }
                if ($game->map_image_path) {
                    Storage::disk('public')->delete($game->map_image_path);
                }
                $game->delete();
            }
            
            session()->flash('message', __('games.locations_deleted', ['count' => count($this->selectedGames)]));
            $this->selectedGames = [];
            $this->selectAll = false;
        } catch (\Exception $e) {
            session()->flash('error', __('games.bulk_delete_failed', ['error' => $e->getMessage()]));
        }
    }
}