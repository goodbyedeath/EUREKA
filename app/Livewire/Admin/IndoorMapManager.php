<?php

namespace App\Livewire\Admin;

use App\Models\GameLocation;
use App\Models\IndoorMap;
use App\Models\IndoorMapSpot;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Authoring for indoor plans: upload the picture, then click it to mark outposts.
 *
 * Placement is done by clicking the image rather than typing coordinates — the admin is
 * looking at the plan, and asking them to convert what they see into numbers is the kind
 * of step that gets a marker put in the wrong room.
 */
class IndoorMapManager extends Component
{
    use WithFileUploads;

    public ?int $mapId = null;

    // --- map form ---
    public string $name = '';
    public string $description = '';
    public $imageUpload;
    public string $clueQuestion = '';
    public string $clueAnswer = '';
    public bool $showMapModal = false;
    public ?int $editingMapId = null;

    // --- spot form ---
    public ?int $spotId = null;
    public string $spotName = '';
    public float $spotX = 50;
    public float $spotY = 50;
    public string $spotShape = 'circle';
    public string $spotColor = '#ef4444';
    public int $spotSize = 28;
    public string $spotContent = '';
    public $spotImageUpload;
    public ?int $spotGameLocationId = null;
    public bool $showSpotModal = false;

    public function mount(): void
    {
        $this->mapId ??= IndoorMap::orderBy('name')->value('id');
    }

    // ---------------------------------------------------------------- maps

    public function openMapModal(?int $id = null): void
    {
        $this->resetMapForm();

        if ($id && $map = IndoorMap::find($id)) {
            $this->editingMapId = $map->id;
            $this->name = $map->name;
            $this->description = (string) $map->description;
            $this->clueQuestion = (string) $map->clue_question;
            $this->clueAnswer = (string) $map->clue_answer;
        }

        $this->showMapModal = true;
    }

    public function saveMap(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'clueQuestion' => 'nullable|string|max:2000',
            'clueAnswer' => 'nullable|string|max:255|required_with:clueQuestion',
            // Required only when creating: editing a map without re-uploading keeps the plan.
            'imageUpload' => ($this->editingMapId ? 'nullable' : 'required') . '|image|mimes:jpeg,jpg,png,webp|max:8192',
        ], [
            'imageUpload.max' => 'The plan must be 8 MB or smaller.',
        ]);

        $data = [
            'name' => $this->name,
            'description' => $this->description ?: null,
            'clue_question' => $this->clueQuestion ?: null,
            'clue_answer' => $this->clueAnswer ?: null,
        ];

        if ($this->imageUpload) {
            $data['image_path'] = $this->imageUpload->store('indoor-maps', 'public');
        }

        if ($this->editingMapId) {
            $map = IndoorMap::findOrFail($this->editingMapId);
            // Replacing the plan removes the old file; spots keep their percentages, which
            // still land correctly as long as the new plan frames the same area.
            if (isset($data['image_path']) && $map->image_path) {
                Storage::disk('public')->delete($map->image_path);
            }
            $map->update($data);
        } else {
            $data['created_by'] = Auth::id();
            $map = IndoorMap::create($data);
            $this->mapId = $map->id;
        }

        $this->showMapModal = false;
        $this->resetMapForm();
        session()->flash('indoor_msg', __('Indoor map saved.'));
    }

    public function selectMap(int $id): void
    {
        $this->mapId = $id;
    }

    public function deleteMap(int $id): void
    {
        $map = IndoorMap::find($id);
        if (! $map) {
            return;
        }

        if ($map->image_path) {
            Storage::disk('public')->delete($map->image_path);
        }

        $map->delete();   // spots cascade
        $this->mapId = IndoorMap::orderBy('name')->value('id');
        session()->flash('indoor_msg', __('Indoor map deleted.'));
    }

    // --------------------------------------------------------------- spots

    /**
     * Called from the image click handler with percentage coordinates.
     */
    public function placeSpot(float $x, float $y): void
    {
        if (! $this->mapId) {
            return;
        }

        $this->resetSpotForm();
        $this->spotX = round(max(0, min(100, $x)), 3);
        $this->spotY = round(max(0, min(100, $y)), 3);
        $this->showSpotModal = true;
    }

    public function editSpot(int $id): void
    {
        $spot = IndoorMapSpot::find($id);
        if (! $spot) {
            return;
        }

        $this->resetSpotForm();
        $this->spotId = $spot->id;
        $this->spotName = $spot->name;
        $this->spotX = $spot->x;
        $this->spotY = $spot->y;
        $this->spotShape = $spot->shape;
        $this->spotColor = $spot->color;
        $this->spotSize = $spot->size;
        $this->spotContent = (string) $spot->content;
        $this->spotGameLocationId = $spot->game_location_id;
        $this->showSpotModal = true;
    }

    public function saveSpot(): void
    {
        $this->validate([
            'spotName' => 'required|string|max:255',
            'spotX' => 'required|numeric|between:0,100',
            'spotY' => 'required|numeric|between:0,100',
            'spotShape' => 'required|in:' . implode(',', IndoorMapSpot::SHAPES),
            'spotColor' => 'required|string|regex:/^#[0-9a-fA-F]{6}$/',
            'spotSize' => 'required|integer|min:12|max:72',
            'spotContent' => 'nullable|string|max:2000',
            'spotImageUpload' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120',
            'spotGameLocationId' => 'nullable|exists:game_locations,id',
        ]);

        $data = [
            'indoor_map_id' => $this->mapId,
            'name' => $this->spotName,
            'x' => $this->spotX,
            'y' => $this->spotY,
            'shape' => $this->spotShape,
            'color' => $this->spotColor,
            'size' => $this->spotSize,
            'content' => $this->spotContent ?: null,
            'game_location_id' => $this->spotGameLocationId ?: null,
        ];

        if ($this->spotImageUpload) {
            $data['image_path'] = $this->spotImageUpload->store('indoor-spots', 'public');
        }

        if ($this->spotId) {
            IndoorMapSpot::findOrFail($this->spotId)->update($data);
        } else {
            IndoorMapSpot::create($data);
        }

        $this->showSpotModal = false;
        $this->resetSpotForm();
        session()->flash('indoor_msg', __('Spot saved.'));
    }

    /** Dragging a marker only moves it; everything else about the spot is left alone. */
    public function moveSpot(int $id, float $x, float $y): void
    {
        // The browser has already put the marker where it was dropped, so re-rendering
        // would ship the whole workspace back — plan, every marker, the table — only to
        // paint the same picture. Write the two numbers and say nothing.
        $this->skipRender();

        $spot = IndoorMapSpot::find($id);
        if (! $spot) {
            return;
        }

        $spot->update([
            'x' => round(max(0, min(100, $x)), 3),
            'y' => round(max(0, min(100, $y)), 3),
        ]);
    }

    public function deleteSpot(int $id): void
    {
        $spot = IndoorMapSpot::find($id);
        if (! $spot) {
            return;
        }

        if ($spot->image_path) {
            Storage::disk('public')->delete($spot->image_path);
        }

        $spot->delete();
        $this->showSpotModal = false;
        session()->flash('indoor_msg', __('Spot removed.'));
    }

    public function toggleSpot(int $id): void
    {
        $spot = IndoorMapSpot::find($id);
        $spot?->update(['is_active' => ! $spot->is_active]);
    }

    // --------------------------------------------------------------- misc

    private function resetMapForm(): void
    {
        $this->editingMapId = null;
        $this->name = '';
        $this->description = '';
        $this->imageUpload = null;
        $this->clueQuestion = '';
        $this->clueAnswer = '';
        $this->resetValidation();
    }

    private function resetSpotForm(): void
    {
        $this->spotId = null;
        $this->spotName = '';
        $this->spotX = 50;
        $this->spotY = 50;
        $this->spotShape = 'circle';
        $this->spotColor = '#ef4444';
        $this->spotSize = 28;
        $this->spotContent = '';
        $this->spotImageUpload = null;
        $this->spotGameLocationId = null;
        $this->resetValidation();
    }

    public function render()
    {
        $map = $this->mapId ? IndoorMap::with('spots.gameLocation')->find($this->mapId) : null;

        return view('livewire.admin.indoor-map-manager', [
            'map' => $map,
            'maps' => IndoorMap::orderBy('name')->get(['id', 'name', 'image_path', 'is_active']),
            'gameLocations' => GameLocation::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
