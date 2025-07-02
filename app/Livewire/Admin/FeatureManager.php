<?php

namespace App\Livewire\Admin;

use App\Models\FeatureSetting;
use Livewire\Component;
use Livewire\Attributes\Validate;

class FeatureManager extends Component
{
    public $features;
    public $showEditModal = false;
    public $editingFeature = null;
    
    #[Validate('required|string|max:255')]
    public $feature_name = '';
    
    #[Validate('nullable|string|max:1000')]
    public $description = '';
    
    #[Validate('required|integer|min:0')]
    public $sort_order = 0;
    
    #[Validate('nullable|array')]
    public $metadata = [];

    protected $listeners = [
        'featureUpdated' => 'refreshFeatures'
    ];

    public function mount()
    {
        $this->refreshFeatures();
    }

    public function render()
    {
        return view('livewire.admin.feature-manager');
    }

    public function refreshFeatures()
    {
        $this->features = FeatureSetting::orderBy('sort_order')->get();
        FeatureSetting::clearCache();
    }

    public function toggleFeature($featureId)
    {
        try {
            $feature = FeatureSetting::findOrFail($featureId);
            $feature->toggle();
            
            $status = $feature->is_enabled ? 'enabled' : 'disabled';
            
            $this->dispatch('showAlert', [
                'type' => 'success',
                'message' => "Feature '{$feature->feature_name}' has been {$status}."
            ]);
            
            $this->refreshFeatures();
            
        } catch (\Exception $e) {
            $this->dispatch('showAlert', [
                'type' => 'error',
                'message' => 'Failed to toggle feature: ' . $e->getMessage()
            ]);
        }
    }

    public function editFeature($featureId)
    {
        $this->editingFeature = FeatureSetting::findOrFail($featureId);
        $this->feature_name = $this->editingFeature->feature_name;
        $this->description = $this->editingFeature->description ?? '';
        $this->sort_order = $this->editingFeature->sort_order;
        $this->metadata = $this->editingFeature->metadata ?? [];
        $this->showEditModal = true;
    }

    public function updateFeature()
    {
        $this->validate();
        
        try {
            $this->editingFeature->update([
                'feature_name' => $this->feature_name,
                'description' => $this->description,
                'sort_order' => $this->sort_order,
                'metadata' => $this->metadata
            ]);
            
            $this->dispatch('showAlert', [
                'type' => 'success',
                'message' => 'Feature updated successfully.'
            ]);
            
            $this->closeEditModal();
            $this->refreshFeatures();
            
        } catch (\Exception $e) {
            $this->dispatch('showAlert', [
                'type' => 'error',
                'message' => 'Failed to update feature: ' . $e->getMessage()
            ]);
        }
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->editingFeature = null;
        $this->feature_name = '';
        $this->description = '';
        $this->sort_order = 0;
        $this->metadata = [];
        $this->resetErrorBag();
    }

    public function moveUp($featureId)
    {
        $this->reorderFeature($featureId, 'up');
    }

    public function moveDown($featureId)
    {
        $this->reorderFeature($featureId, 'down');
    }

    private function reorderFeature($featureId, $direction)
    {
        try {
            $feature = FeatureSetting::findOrFail($featureId);
            $currentOrder = $feature->sort_order;
            
            if ($direction === 'up') {
                $otherFeature = FeatureSetting::where('sort_order', '<', $currentOrder)
                    ->orderBy('sort_order', 'desc')
                    ->first();
            } else {
                $otherFeature = FeatureSetting::where('sort_order', '>', $currentOrder)
                    ->orderBy('sort_order', 'asc')
                    ->first();
            }
            
            if ($otherFeature) {
                // Swap sort orders
                $tempOrder = $feature->sort_order;
                $feature->sort_order = $otherFeature->sort_order;
                $otherFeature->sort_order = $tempOrder;
                
                $feature->save();
                $otherFeature->save();
                
                $this->refreshFeatures();
                
                $this->dispatch('showAlert', [
                    'type' => 'success',
                    'message' => 'Feature order updated.'
                ]);
            }
            
        } catch (\Exception $e) {
            $this->dispatch('showAlert', [
                'type' => 'error',
                'message' => 'Failed to reorder feature: ' . $e->getMessage()
            ]);
        }
    }

    public function bulkToggle($action)
    {
        try {
            $count = 0;
            
            if ($action === 'enable_all') {
                $count = FeatureSetting::where('is_enabled', false)->update(['is_enabled' => true]);
                $message = "Enabled {$count} features.";
            } else if ($action === 'disable_all') {
                $count = FeatureSetting::where('is_enabled', true)->update(['is_enabled' => false]);
                $message = "Disabled {$count} features.";
            }
            
            FeatureSetting::clearCache();
            $this->refreshFeatures();
            
            $this->dispatch('showAlert', [
                'type' => 'success',
                'message' => $message
            ]);
            
        } catch (\Exception $e) {
            $this->dispatch('showAlert', [
                'type' => 'error',
                'message' => 'Bulk operation failed: ' . $e->getMessage()
            ]);
        }
    }

    public function getColorClasses($color)
    {
        $colorMap = [
            'blue' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400',
            'green' => 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
            'purple' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-400',
            'indigo' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/20 dark:text-indigo-400',
            'yellow' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400',
            'red' => 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400',
            'gray' => 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400',
        ];
        
        return $colorMap[$color] ?? $colorMap['gray'];
    }

    public function addMetadataField()
    {
        $this->metadata[] = ['key' => '', 'value' => ''];
    }

    public function removeMetadataField($index)
    {
        unset($this->metadata[$index]);
        $this->metadata = array_values($this->metadata);
    }
}