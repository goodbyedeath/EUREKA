<?php

namespace App\Livewire\Admin;

use App\Models\QuestLocation;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

class QuestLocationManager extends Component
{
    use WithPagination, WithFileUploads;

    public $showModal = false;
    public $editMode = false;
    public $questLocationId;

    // Form properties
    public $name = '';
    public $description = '';
    public $what_to_do = '';
    public $google_map_embed_url = '';
    public $latitude = '';
    public $longitude = '';
    public $radius = 50;
    public $is_active = true;
    public $max_check_ins_per_user = null;
    public $quest_points = 0;
    public $image = null;
    public $existing_image_path = null;

    protected $rules = [
        'name' => 'required|string|max:255',
        'description' => 'required|string',
        'what_to_do' => 'required|string',
        'google_map_embed_url' => 'nullable|url',
        'latitude' => 'required|numeric|between:-90,90',
        'longitude' => 'required|numeric|between:-180,180',
        'radius' => 'required|integer|min:10|max:1000',
        'is_active' => 'boolean',
        'max_check_ins_per_user' => 'nullable|integer|min:1',
        'quest_points' => 'required|integer|min:0',
        'image' => 'nullable|image|max:2048', // 2MB max
    ];

    protected $messages = [
        'image.image' => 'The file must be an image.',
        'image.max' => 'The image may not be greater than 2MB.',
        'max_check_ins_per_user.min' => 'Maximum check-ins must be at least 1.',
        'quest_points.min' => 'Quest points cannot be negative.',
    ];

    public function render()
    {
        return view('livewire.admin.quest-location-manager', [
            'questLocations' => QuestLocation::latest()->paginate(10)
        ]);
    }

    public function create()
    {
        $this->resetForm();
        $this->editMode = false;
        $this->showModal = true;
    }

    public function edit($id)
    {
        $questLocation = QuestLocation::findOrFail($id);
        
        $this->questLocationId = $questLocation->id;
        $this->name = $questLocation->name;
        $this->description = $questLocation->description;
        $this->what_to_do = $questLocation->what_to_do;
        $this->google_map_embed_url = $questLocation->google_map_embed_url;
        $this->latitude = $questLocation->latitude;
        $this->longitude = $questLocation->longitude;
        $this->radius = $questLocation->radius;
        $this->is_active = $questLocation->is_active;
        $this->max_check_ins_per_user = $questLocation->max_check_ins_per_user;
        $this->quest_points = $questLocation->quest_points;
        $this->existing_image_path = $questLocation->image_path;

        $this->editMode = true;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'what_to_do' => $this->what_to_do,
            'google_map_embed_url' => $this->google_map_embed_url,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'radius' => $this->radius,
            'is_active' => $this->is_active,
            'max_check_ins_per_user' => $this->max_check_ins_per_user,
            'quest_points' => $this->quest_points,
        ];

        // Handle image upload
        if ($this->image) {
            // Delete old image if updating
            if ($this->editMode && $this->existing_image_path) {
                Storage::delete($this->existing_image_path);
            }
            
            $imagePath = $this->image->store('quest-locations', 'public');
            $data['image_path'] = $imagePath;
        }

        if ($this->editMode) {
            // Add created_by only if it's not already set (for new records)
            $questLocation = QuestLocation::findOrFail($this->questLocationId);
            $questLocation->update($data);
            session()->flash('message', 'Quest Location berhasil diupdate!');
        } else {
            // Add created_by for new records
            $data['created_by'] = auth()->id();
            QuestLocation::create($data);
            session()->flash('message', 'Quest Location berhasil ditambahkan!');
        }

        $this->closeModal();
    }

    public function delete($id)
    {
        $questLocation = QuestLocation::findOrFail($id);
        
        // Delete associated image if exists
        if ($questLocation->image_path) {
            Storage::delete($questLocation->image_path);
        }
        
        $questLocation->delete();
        session()->flash('message', 'Quest Location berhasil dihapus!');
    }

    public function removeImage()
    {
        if ($this->editMode && $this->existing_image_path) {
            $questLocation = QuestLocation::findOrFail($this->questLocationId);
            
            // Delete the file
            Storage::delete($this->existing_image_path);
            
            // Update database
            $questLocation->update(['image_path' => null]);
            
            // Reset component state
            $this->existing_image_path = null;
            
            session()->flash('message', 'Image berhasil dihapus!');
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->name = '';
        $this->description = '';
        $this->what_to_do = '';
        $this->google_map_embed_url = '';
        $this->latitude = '';
        $this->longitude = '';
        $this->radius = 50;
        $this->is_active = true;
        $this->max_check_ins_per_user = null;
        $this->quest_points = 0;
        $this->image = null;
        $this->existing_image_path = null;
        $this->questLocationId = null;
        $this->resetErrorBag();
    }

    // Helper method to extract coordinates from Google Maps URL
    public function extractCoordinates()
    {
        if (empty($this->google_map_embed_url)) {
            return;
        }

        $input = trim($this->google_map_embed_url);
        $url = '';

        // Check if it's a full iframe code
        if (strpos($input, '<iframe') !== false) {
            // Extract src attribute from iframe
            preg_match('/src="([^"]*)"/', $input, $matches);
            if (isset($matches[1])) {
                $url = $matches[1];
                // Update the field with just the URL
                $this->google_map_embed_url = $url;
            }
        } else {
            // It's already a URL
            $url = $input;
        }

        if (empty($url)) {
            return;
        }

        // Extract coordinates from different Google Maps URL formats
        $latitude = null;
        $longitude = null;

        // Method 1: Extract from pb parameter (embed URLs)
        if (preg_match('/!3d(-?\d+\.?\d*)!4d(-?\d+\.?\d*)/', $url, $matches)) {
            $latitude = (float) $matches[1];
            $longitude = (float) $matches[2];
        }
        // Method 2: Extract from q parameter
        elseif (preg_match('/[?&]q=(-?\d+\.?\d*),(-?\d+\.?\d*)/', $url, $matches)) {
            $latitude = (float) $matches[1];
            $longitude = (float) $matches[2];
        }
        // Method 3: Extract from ll parameter
        elseif (preg_match('/[?&]ll=(-?\d+\.?\d*),(-?\d+\.?\d*)/', $url, $matches)) {
            $latitude = (float) $matches[1];
            $longitude = (float) $matches[2];
        }
        // Method 4: Extract from center parameter
        elseif (preg_match('/[?&]center=(-?\d+\.?\d*),(-?\d+\.?\d*)/', $url, $matches)) {
            $latitude = (float) $matches[1];
            $longitude = (float) $matches[2];
        }
        // Method 5: Extract from @ parameter (new Google Maps URLs)
        elseif (preg_match('/@(-?\d+\.?\d*),(-?\d+\.?\d*)/', $url, $matches)) {
            $latitude = (float) $matches[1];
            $longitude = (float) $matches[2];
        }

        // Update the coordinates if found
        if ($latitude !== null && $longitude !== null) {
            $this->latitude = $latitude;
            $this->longitude = $longitude;
            
            // Optional: Show success message
            session()->flash('coordinate_extracted', 'Coordinates extracted successfully!');
        } else {
            // Optional: Show error message
            session()->flash('coordinate_error', 'Could not extract coordinates from the URL. Please enter them manually.');
        }
    }
}