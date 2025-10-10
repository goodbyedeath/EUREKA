<?php

namespace App\Livewire\User;

use App\Models\Guidance;
use Livewire\Component;
use Livewire\WithPagination;

class GuidanceView extends Component
{
    use WithPagination;

    public $selectedGuidance = null;
    public $showModal = false;
    public $currentImageIndex = 0;
    public $search = '';

    public function render()
    {
        try {
            $userId = auth()->id();
            
            $query = Guidance::active()
                ->forUser($userId)
                ->with(['creator']);
                
            // Apply search filter
            if (!empty($this->search)) {
                $query->where(function($q) {
                    $q->where('title', 'like', '%' . $this->search . '%')
                      ->orWhere('description', 'like', '%' . $this->search . '%');
                });
            }
            
            return view('livewire.user.guidance-view', [
                'guidances' => $query->ordered()->paginate(12)
            ]);
        } catch (\Exception $e) {
            // Fallback when database is not available
            $emptyCollection = collect([]);
            $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
                $emptyCollection,
                0,
                12,
                1,
                ['path' => request()->url()]
            );
            
            session()->flash('error', 'Database connection error. Please ensure your database server is running.');
            
            return view('livewire.user.guidance-view', [
                'guidances' => $paginator
            ]);
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function viewGuidance($guidanceId)
    {
        try {
            $userId = auth()->id();
            $this->selectedGuidance = Guidance::active()
                ->forUser($userId)
                ->findOrFail($guidanceId);
            
            $this->currentImageIndex = 0;
            $this->showModal = true;
        } catch (\Exception $e) {
            session()->flash('error', 'Guidance not found or not accessible.');
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->selectedGuidance = null;
        $this->currentImageIndex = 0;
    }

    public function nextImage()
    {
        if ($this->selectedGuidance && $this->selectedGuidance->images) {
            $imageCount = count($this->selectedGuidance->images);
            $this->currentImageIndex = ($this->currentImageIndex + 1) % $imageCount;
        }
    }

    public function prevImage()
    {
        if ($this->selectedGuidance && $this->selectedGuidance->images) {
            $imageCount = count($this->selectedGuidance->images);
            $this->currentImageIndex = ($this->currentImageIndex - 1 + $imageCount) % $imageCount;
        }
    }

    public function goToImage($index)
    {
        if ($this->selectedGuidance && $this->selectedGuidance->images) {
            $imageCount = count($this->selectedGuidance->images);
            if ($index >= 0 && $index < $imageCount) {
                $this->currentImageIndex = $index;
            }
        }
    }

    public function refreshGuidances()
    {
        $this->resetPage();
    }
}