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
    use \App\Livewire\Concerns\GuardsFileUploads;

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

    /** The photo the spot has now, so the edit form can show it — it used to show nothing at all. */
    public ?string $spotImagePath = null;

    /** Take the photo off on save. */
    public bool $spotRemoveImage = false;
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
            $previous = $map->image_path;
            $map->update($data);
            if (isset($data['image_path']) && $previous) {
                $this->deleteIfUnused($previous);
            }
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

    /**
     * Copy this plan for another team — markers, posts, 3D objects, questionnaires and their
     * questions (operator, 20 Sep). Each team needs its own of all four, and doing that by hand
     * across four pages is where the broken links came from.
     */
    public function duplicateMap(int $id): void
    {
        $source = IndoorMap::find($id);

        if (! $source) {
            session()->flash('error', 'Denah itu tidak ada.');

            return;
        }

        $result = app(\App\Services\IndoorMapDuplicator::class)->duplicate($source, null, auth()->id());

        $this->mapId = $result['map']->id;

        session()->flash('success', "Denah \"{$result['map']->name}\" dibuat: {$result['spots']} penanda, "
            ."{$result['posts']} pos, {$result['questionnaires']} kuesioner, {$result['questions']} pertanyaan. "
            .'QR kuesionernya baru, jadi perlu dicetak ulang. Tetapkan denah ini ke sebuah tim di Team Management.');
    }

    public function deleteMap(int $id): void
    {
        $map = IndoorMap::find($id);
        if (! $map) {
            return;
        }

        $files = collect([$map->image_path])
            ->merge(IndoorMapSpot::where('indoor_map_id', $map->id)->pluck('image_path'))
            ->filter()->unique()->all();

        $map->delete();   // spots cascade; their photos are handled below, which they never were

        foreach ($files as $path) {
            $this->deleteIfUnused($path);
        }
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
        $this->spotImagePath = $spot->image_path;
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
        } elseif ($this->spotRemoveImage) {
            $data['image_path'] = null;
        }

        if ($this->spotId) {
            $spot = IndoorMapSpot::findOrFail($this->spotId);
            $previous = $spot->image_path;
            $spot->update($data);

            // A replaced or removed photo leaves the disk — unless a duplicated plan's spot still
            // shows it, which replicate() makes likely.
            if ($previous && array_key_exists('image_path', $data) && $data['image_path'] !== $previous) {
                $this->deleteIfUnused($previous);
            }
        } else {
            IndoorMapSpot::create($data);
        }

        $this->showSpotModal = false;
        $this->resetSpotForm();
        session()->flash('indoor_msg', __('Spot saved.'));
    }

    /**
     * A photo is checked the moment it is chosen: the form previews it straight away, and Livewire
     * cannot preview anything that is not an image — a wrong file would take the modal down.
     */
    public function updatedSpotImageUpload(): void
    {
        if (! $this->spotImageUpload) {
            return;
        }

        $this->resetErrorBag('spotImageUpload');
        try {
            $this->validateOnly('spotImageUpload', ['spotImageUpload' => 'image|mimes:jpeg,jpg,png,webp|max:5120'], [
                'spotImageUpload.image' => 'File ini bukan gambar.',
                'spotImageUpload.mimes' => 'Pakai JPG, PNG atau WebP.',
                'spotImageUpload.max' => 'Maksimal 5 MB.',
            ]);
            $this->spotRemoveImage = false;
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->spotImageUpload = null;
            throw $e;
        }
    }

    public function removeSpotImage(): void
    {
        $this->spotImageUpload = null;
        $this->spotRemoveImage = true;
    }

    public function keepSpotImage(): void
    {
        $this->spotRemoveImage = false;
    }

    public function discardSpotImageUpload(): void
    {
        $this->spotImageUpload = null;
    }

    /**
     * Delete a plan or spot picture only when nothing else shows it.
     *
     * "Duplikat denah" copies spots with replicate(), so the copy points at the original's files.
     * Deleting unconditionally meant that removing a spot from the copy blanked the photo on the
     * original plan too.
     */
    private function deleteIfUnused(string $path): void
    {
        $inUse = IndoorMapSpot::where('image_path', $path)->exists()
            || IndoorMap::where('image_path', $path)->exists();

        if (! $inUse) {
            Storage::disk('public')->delete($path);
        }
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

        $photo = $spot->image_path;
        $spot->delete();
        if ($photo) {
            $this->deleteIfUnused($photo);
        }
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
        $this->spotImagePath = null;
        $this->spotRemoveImage = false;
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
