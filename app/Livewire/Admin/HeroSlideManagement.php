<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\HeroSlide;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class HeroSlideManagement extends Component
{
    use WithFileUploads;
    
    public $slides = [];
    public $editingSlide = null;
    public $showForm = false;
    
    // Form fields
    public $title = '';
    public $subtitle = '';
    public $primary_button_text = 'Get Started';
    public $primary_button_url = '/register';
    public $secondary_button_text = '';
    public $secondary_button_url = '';
    public $background_gradient = 'from-blue-600 via-purple-600 to-indigo-800';
    public $background_image;
    public $existing_background_image = '';
    public $text_color = 'text-white';
    public $button_color = 'text-blue-600 bg-white';
    public $order = 0;
    public $is_active = true;
    public $icon_svg = '';

    protected function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'subtitle' => 'required|string|max:1000',
            'primary_button_text' => 'required|string|max:50',
            'primary_button_url' => 'required|string|max:255',
            'secondary_button_text' => 'nullable|string|max:50',
            'secondary_button_url' => 'nullable|string|max:255',
            'background_gradient' => 'required|string|max:255',
            'background_image' => 'nullable|image|max:2048', // 2MB max
            'text_color' => 'required|string|max:50',
            'button_color' => 'required|string|max:100',
            'order' => 'required|integer|min:0',
            'is_active' => 'boolean',
            'icon_svg' => 'nullable|string|max:2000',
        ];
    }

    public function mount()
    {
        $this->loadSlides();
    }

    public function loadSlides()
    {
        $this->slides = HeroSlide::orderBy('order')->get();
    }

    public function openCreateForm()
    {
        $this->resetForm();
        $this->showForm = true;
        $this->editingSlide = null;
    }

    public function openEditForm($slideId)
    {
        $slide = HeroSlide::findOrFail($slideId);
        $this->editingSlide = $slide;
        
        $this->title = $slide->title;
        $this->subtitle = $slide->subtitle;
        $this->primary_button_text = $slide->primary_button_text;
        $this->primary_button_url = $slide->primary_button_url;
        $this->secondary_button_text = $slide->secondary_button_text ?? '';
        $this->secondary_button_url = $slide->secondary_button_url ?? '';
        $this->background_gradient = $slide->background_gradient;
        $this->existing_background_image = $slide->background_image ?? '';
        $this->background_image = null; // Reset file input
        $this->text_color = $slide->text_color;
        $this->button_color = $slide->button_color;
        $this->order = $slide->order;
        $this->is_active = $slide->is_active;
        $this->icon_svg = $slide->icon_svg ?? '';
        
        $this->showForm = true;
    }

    public function saveSlide()
    {
        $this->validate();

        $data = [
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'primary_button_text' => $this->primary_button_text,
            'primary_button_url' => $this->primary_button_url,
            'secondary_button_text' => $this->secondary_button_text ?: null,
            'secondary_button_url' => $this->secondary_button_url ?: null,
            'background_gradient' => $this->background_gradient,
            'text_color' => $this->text_color,
            'button_color' => $this->button_color,
            'order' => $this->order,
            'is_active' => $this->is_active,
            'icon_svg' => $this->icon_svg ?: null,
        ];

        // Handle background image upload
        if ($this->background_image) {
            // Delete old image if editing
            if ($this->editingSlide && $this->editingSlide->background_image) {
                Storage::disk('public')->delete($this->editingSlide->background_image);
            }
            
            $imagePath = $this->background_image->store('hero-slides', 'public');
            $data['background_image'] = $imagePath;
        } elseif ($this->editingSlide) {
            // Keep existing image if no new image uploaded
            $data['background_image'] = $this->editingSlide->background_image;
        }

        if ($this->editingSlide) {
            $this->editingSlide->update($data);
            session()->flash('success', 'Hero slide updated successfully!');
        } else {
            HeroSlide::create($data);
            session()->flash('success', 'Hero slide created successfully!');
        }

        $this->loadSlides();
        $this->closeForm();
    }

    public function deleteSlide($slideId)
    {
        $slide = HeroSlide::findOrFail($slideId);
        
        // Delete background image if exists
        if ($slide->background_image) {
            Storage::disk('public')->delete($slide->background_image);
        }
        
        $slide->delete();
        
        session()->flash('success', 'Hero slide deleted successfully!');
        $this->loadSlides();
    }

    public function toggleSlideStatus($slideId)
    {
        $slide = HeroSlide::findOrFail($slideId);
        $slide->update(['is_active' => !$slide->is_active]);
        
        $this->loadSlides();
        session()->flash('success', 'Slide status updated successfully!');
    }

    public function moveSlideUp($slideId)
    {
        $slide = HeroSlide::findOrFail($slideId);
        $previousSlide = HeroSlide::where('order', '<', $slide->order)
            ->orderBy('order', 'desc')
            ->first();

        if ($previousSlide) {
            $tempOrder = $slide->order;
            $slide->update(['order' => $previousSlide->order]);
            $previousSlide->update(['order' => $tempOrder]);
            
            $this->loadSlides();
            session()->flash('success', 'Slide order updated!');
        }
    }

    public function moveSlideDown($slideId)
    {
        $slide = HeroSlide::findOrFail($slideId);
        $nextSlide = HeroSlide::where('order', '>', $slide->order)
            ->orderBy('order', 'asc')
            ->first();

        if ($nextSlide) {
            $tempOrder = $slide->order;
            $slide->update(['order' => $nextSlide->order]);
            $nextSlide->update(['order' => $tempOrder]);
            
            $this->loadSlides();
            session()->flash('success', 'Slide order updated!');
        }
    }

    public function removeBackgroundImage()
    {
        if ($this->editingSlide && $this->editingSlide->background_image) {
            Storage::disk('public')->delete($this->editingSlide->background_image);
            $this->editingSlide->update(['background_image' => null]);
            $this->existing_background_image = '';
            $this->loadSlides();
            session()->flash('success', 'Background image removed successfully!');
        }
    }

    public function closeForm()
    {
        $this->showForm = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->title = '';
        $this->subtitle = '';
        $this->primary_button_text = 'Get Started';
        $this->primary_button_url = '/register';
        $this->secondary_button_text = '';
        $this->secondary_button_url = '';
        $this->background_gradient = 'from-blue-600 via-purple-600 to-indigo-800';
        $this->background_image = null;
        $this->existing_background_image = '';
        $this->text_color = 'text-white';
        $this->button_color = 'text-blue-600 bg-white';
        $this->order = HeroSlide::max('order') + 1 ?? 0;
        $this->is_active = true;
        $this->icon_svg = '';
        $this->editingSlide = null;
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.admin.hero-slide-management');
    }
}