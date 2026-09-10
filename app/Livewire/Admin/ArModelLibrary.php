<?php

namespace App\Livewire\Admin;

use App\Models\ArModel;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * The shared 3D asset library.
 *
 * Models live here rather than on a location, so the same asset can be reused
 * everywhere without a second upload or a second offline download.
 */
class ArModelLibrary extends Component
{
    use WithFileUploads, WithPagination;

    public $upload;
    public $uploadName = '';
    public $search = '';

    /** Inline rename */
    public $editingId = null;
    public $editingName = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function store()
    {
        $this->validate([
            'upload' => 'required|file|max:30720',
            'uploadName' => 'nullable|string|max:120',
        ], [
            'upload.max' => 'The model must be 30 MB or smaller.',
        ]);

        // .glb/.gltf have no dependable server MIME, so validate by extension.
        $extension = strtolower($this->upload->getClientOriginalExtension());

        if (! in_array($extension, ['glb', 'gltf'], true)) {
            $this->addError('upload', 'Only .glb or .gltf models are supported.');
            return;
        }

        $path = $this->upload->store('games/ar-models', 'public');

        ArModel::create([
            'name' => trim($this->uploadName) ?: pathinfo($this->upload->getClientOriginalName(), PATHINFO_FILENAME),
            'path' => $path,
            'size' => Storage::disk('public')->size($path),
            'created_by' => auth()->id(),
        ]);

        $this->reset(['upload', 'uploadName']);
        session()->flash('library_message', 'Model added to the library.');
    }

    public function startRename($id)
    {
        $model = ArModel::findOrFail($id);
        $this->editingId = $model->id;
        $this->editingName = $model->name;
    }

    public function saveRename()
    {
        $this->validate(['editingName' => 'required|string|max:120']);

        ArModel::findOrFail($this->editingId)->update(['name' => trim($this->editingName)]);
        $this->reset(['editingId', 'editingName']);
        session()->flash('library_message', 'Renamed.');
    }

    public function cancelRename()
    {
        $this->reset(['editingId', 'editingName']);
    }

    /**
     * Deleting an asset that a location or object still points at would blank that
     * outpost, so it is refused rather than cascaded.
     */
    public function destroy($id)
    {
        $model = ArModel::findOrFail($id);

        if ($model->isInUse()) {
            session()->flash('library_error',
                '"' . $model->name . '" is still used by ' . $model->gameLocations()->count()
                . ' location(s) and ' . $model->hotspots()->count() . ' object(s).');
            return;
        }

        Storage::disk('public')->delete($model->path);
        $model->delete();
        session()->flash('library_message', 'Model deleted.');
    }

    public function render()
    {
        $models = ArModel::withCount(['gameLocations', 'hotspots'])
            ->when($this->search, fn ($q) => $q->where('name', 'like', '%' . $this->search . '%'))
            ->orderBy('name')
            ->paginate(8);

        return view('livewire.admin.ar-model-library', [
            'models' => $models,
            'totalSize' => ArModel::sum('size'),
        ]);
    }
}
