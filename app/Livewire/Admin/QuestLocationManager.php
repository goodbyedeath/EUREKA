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
    public $latitude = '';
    public $longitude = '';
    public $radius = 50;
    public $is_active = true;
    public $max_check_ins_per_user = null;
    public $quest_points = 0;
    public $image = null;
    public $existing_image_path = null;

    // Location detection properties
    public $adminLatitude = null;
    public $adminLongitude = null;
    public $locationAccuracy = null;
    public $locationError = null;
    public $locationDetected = false;
    public $lastLocationUpdate = null;

    protected $rules = [
        'name' => 'required|string|max:255',
        'description' => 'required|string',
        'what_to_do' => 'required|string',
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
        ])->layout(null);
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

    public function updateAdminLocation($latitude, $longitude, $accuracy = null)
    {
        $this->adminLatitude = $latitude;
        $this->adminLongitude = $longitude;
        $this->locationAccuracy = $accuracy;
        $this->locationDetected = true;
        $this->locationError = null;
        $this->lastLocationUpdate = now()->format('H:i:s');
    }

    public function setLocationError($error)
    {
        $this->locationError = $error;
        $this->locationDetected = false;
    }

    public function useCurrentLocation()
    {
        if ($this->locationDetected) {
            $this->latitude = $this->adminLatitude;
            $this->longitude = $this->adminLongitude;
            
            session()->flash('message', 'Current location has been applied to the form!');
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

}