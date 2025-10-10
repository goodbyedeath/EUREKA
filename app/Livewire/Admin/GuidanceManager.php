<?php

namespace App\Livewire\Admin;

use App\Models\Guidance;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

class GuidanceManager extends Component
{
    use WithPagination, WithFileUploads;

    public $showModal = false;
    public $editMode = false;
    public $guidanceId;

    // Form properties
    public $title = '';
    public $description = '';
    public $is_active = true;
    public $sort_order = 0;
    public $images = [];
    public $newImages = [];
    public $removedImages = [];
    
    // User targeting properties
    public $target_type = 'all_users';
    public $target_user_id = null;
    
    // Search and filter properties
    public $search = '';
    public $filterStatus = 'all';
    public $filterTarget = 'all';
    public $selectedGuidance = [];
    public $selectAll = false;

    protected $rules = [
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'is_active' => 'nullable|boolean',
        'sort_order' => 'nullable|integer|min:0',
        'newImages.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        'target_type' => 'required|in:all_users,specific_user',
        'target_user_id' => 'nullable|exists:users,id|required_if:target_type,specific_user',
    ];

    public function render()
    {
        try {
            $query = Guidance::with(['targetUser', 'creator']);
            
            // Apply search filter
            if (!empty($this->search)) {
                $searchTerm = trim($this->search);
                $query->where(function($q) use ($searchTerm) {
                    $q->where('title', 'like', '%' . $searchTerm . '%')
                      ->orWhere('description', 'like', '%' . $searchTerm . '%');
                });
            }
            
            // Apply status filter
            if ($this->filterStatus !== 'all') {
                $isActive = $this->filterStatus === 'active';
                $query->where('is_active', $isActive);
            }
            
            // Apply target filter
            if ($this->filterTarget !== 'all') {
                $query->where('target_type', $this->filterTarget);
            }
            
            $users = User::orderBy('name')->get();
            
            return view('livewire.admin.guidance-manager', [
                'guidances' => $query->ordered()->paginate(10),
                'users' => $users
            ])->layout(null);
        } catch (\Exception $e) {
            \Log::error('GuidanceManager render error: ' . $e->getMessage());
            session()->flash('error', 'Failed to load guidance data. Please try again.');
            
            return view('livewire.admin.guidance-manager', [
                'guidances' => collect()->paginate(10),
                'users' => collect()
            ])->layout(null);
        }
    }
    
    public function updatingSearch()
    {
        $this->resetPage();
    }
    
    public function updatingFilterStatus()
    {
        $this->resetPage();
    }
    
    public function updatingFilterTarget()
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
        $guidance = Guidance::findOrFail($id);
        
        $this->guidanceId = $guidance->id;
        $this->title = $guidance->title;
        $this->description = $guidance->description;
        $this->is_active = $guidance->is_active;
        $this->sort_order = $guidance->sort_order;
        $this->images = $guidance->images ?? [];
        $this->target_type = $guidance->target_type;
        $this->target_user_id = $guidance->target_user_id;
        $this->newImages = [];
        $this->removedImages = [];

        $this->editMode = true;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        $allImages = $this->images ?? [];
        
        // Handle new image uploads
        if (!empty($this->newImages)) {
            foreach ($this->newImages as $newImage) {
                try {
                    $imagePath = $newImage->store('guidance/images', 'public');
                    $allImages[] = $imagePath;
                } catch (\Exception $e) {
                    session()->flash('error', 'Failed to upload image: ' . $e->getMessage());
                    return;
                }
            }
        }

        // Remove deleted images from array
        if (!empty($this->removedImages)) {
            foreach ($this->removedImages as $removedImage) {
                $allImages = array_values(array_filter($allImages, function($img) use ($removedImage) {
                    return $img !== $removedImage;
                }));
                // Delete file from storage
                try {
                    if (Storage::disk('public')->exists($removedImage)) {
                        Storage::disk('public')->delete($removedImage);
                    }
                } catch (\Exception $e) {
                    // Log but don't stop the process
                    \Log::error('Failed to delete image: ' . $e->getMessage());
                }
            }
        }

        $data = [
            'title' => $this->title,
            'description' => $this->description,
            'images' => $allImages,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'target_type' => $this->target_type,
            'target_user_id' => $this->target_type === 'specific_user' ? $this->target_user_id : null,
        ];

        try {
            if ($this->editMode) {
                $guidance = Guidance::findOrFail($this->guidanceId);
                $guidance->update($data);
                session()->flash('message', 'Guidance updated successfully.');
            } else {
                $data['created_by'] = auth()->id();
                Guidance::create($data);
                session()->flash('message', 'Guidance created successfully.');
            }

            $this->closeModal();
            $this->resetPage();
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to save guidance: ' . $e->getMessage());
        }
    }

    public function removeImage($imagePath)
    {
        if ($this->editMode) {
            $this->removedImages[] = $imagePath;
        }
        
        $this->images = array_values(array_filter($this->images, function($img) use ($imagePath) {
            return $img !== $imagePath;
        }));
    }

    public function delete($id)
    {
        try {
            $guidance = Guidance::findOrFail($id);
            
            // Delete associated images
            if ($guidance->images && is_array($guidance->images)) {
                foreach ($guidance->images as $imagePath) {
                    try {
                        if (Storage::disk('public')->exists($imagePath)) {
                            Storage::disk('public')->delete($imagePath);
                        }
                    } catch (\Exception $imageError) {
                        \Log::error('Failed to delete guidance image: ' . $imageError->getMessage());
                    }
                }
            }
            
            $guidance->delete();
            session()->flash('message', 'Guidance deleted successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete guidance: ' . $e->getMessage());
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->title = '';
        $this->description = '';
        $this->is_active = true;
        $this->sort_order = 0;
        $this->images = [];
        $this->newImages = [];
        $this->removedImages = [];
        $this->target_type = 'all_users';
        $this->target_user_id = null;
        $this->guidanceId = null;
        $this->resetErrorBag();
    }
    
    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedGuidance = Guidance::pluck('id')->toArray();
        } else {
            $this->selectedGuidance = [];
        }
    }
    
    public function bulkActivate()
    {
        if (empty($this->selectedGuidance)) {
            session()->flash('error', 'Please select at least one guidance.');
            return;
        }
        
        try {
            Guidance::whereIn('id', $this->selectedGuidance)->update(['is_active' => true]);
            session()->flash('message', count($this->selectedGuidance) . ' guidance(s) activated successfully.');
            $this->selectedGuidance = [];
            $this->selectAll = false;
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to activate guidance: ' . $e->getMessage());
        }
    }
    
    public function bulkDeactivate()
    {
        if (empty($this->selectedGuidance)) {
            session()->flash('error', 'Please select at least one guidance.');
            return;
        }
        
        try {
            Guidance::whereIn('id', $this->selectedGuidance)->update(['is_active' => false]);
            session()->flash('message', count($this->selectedGuidance) . ' guidance(s) deactivated successfully.');
            $this->selectedGuidance = [];
            $this->selectAll = false;
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to deactivate guidance: ' . $e->getMessage());
        }
    }
    
    public function bulkDelete()
    {
        if (empty($this->selectedGuidance)) {
            session()->flash('error', 'Please select at least one guidance.');
            return;
        }
        
        try {
            $guidances = Guidance::whereIn('id', $this->selectedGuidance)->get();
            
            foreach ($guidances as $guidance) {
                // Delete associated images
                if ($guidance->images && is_array($guidance->images)) {
                    foreach ($guidance->images as $imagePath) {
                        try {
                            if (Storage::disk('public')->exists($imagePath)) {
                                Storage::disk('public')->delete($imagePath);
                            }
                        } catch (\Exception $imageError) {
                            \Log::error('Failed to delete guidance image in bulk: ' . $imageError->getMessage());
                        }
                    }
                }
                $guidance->delete();
            }
            
            session()->flash('message', count($this->selectedGuidance) . ' guidance(s) deleted successfully.');
            $this->selectedGuidance = [];
            $this->selectAll = false;
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete guidance: ' . $e->getMessage());
        }
    }
}