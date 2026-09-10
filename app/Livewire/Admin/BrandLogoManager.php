<?php

namespace App\Livewire\Admin;

use App\Models\BrandSetting;
use App\Services\BrandIconService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Upload the event's logo.
 *
 * Lives on the Hero Slides page because that is where the rest of the front-of-house look
 * is set, and an admin dressing the app for a client changes both in the same sitting.
 */
class BrandLogoManager extends Component
{
    use WithFileUploads;

    public $horizontalUpload;
    public $iconUpload;

    public string $appName = '';
    public string $tagline = '';

    public function mount(): void
    {
        $brand = BrandSetting::current();
        // Seeded with the effective name rather than the stored one, so the field shows
        // what the app is actually called today instead of an empty box.
        $this->appName = BrandSetting::appName();
        $this->tagline = (string) $brand->tagline;
    }

    public function saveName(): void
    {
        $this->validate([
            'appName' => 'required|string|max:60',
            'tagline' => 'nullable|string|max:120',
        ], [
            'appName.required' => 'The event needs a name.',
            'appName.max' => 'Keep the name under 60 characters — it has to fit a browser tab.',
        ]);

        BrandSetting::current()->update([
            'app_name' => trim($this->appName),
            'tagline' => trim($this->tagline) ?: null,
            'updated_by' => Auth::id(),
        ]);
        BrandSetting::forget();

        session()->flash('brand_msg', __('Name updated everywhere.'));
    }

    public function rules(): array
    {
        return [
            // PNG first in the hint because a logo almost always wants transparency; SVG is
            // not accepted, since it is markup and would be served from our own origin.
            'horizontalUpload' => 'nullable|image|mimes:png,jpeg,jpg,webp|max:2048',
            'iconUpload' => 'nullable|image|mimes:png,jpeg,jpg,webp|max:1024',
        ];
    }

    protected array $messages = [
        'horizontalUpload.max' => 'The wide logo must be 2 MB or smaller.',
        'iconUpload.max' => 'The square icon must be 1 MB or smaller.',
    ];

    public function saveHorizontal(): void
    {
        $this->validate(['horizontalUpload' => 'required|image|mimes:png,jpeg,jpg,webp|max:2048']);
        $this->store('horizontal_path', $this->horizontalUpload);
        $this->reset('horizontalUpload');
        session()->flash('brand_msg', __('Wide logo updated everywhere.'));
    }

    public function saveIcon(): void
    {
        $this->validate(['iconUpload' => 'required|image|mimes:png,jpeg,jpg,webp|max:1024']);
        $this->store('icon_path', $this->iconUpload);
        $this->reset('iconUpload');
        session()->flash('brand_msg', __('Square icon updated everywhere.'));
    }

    /** Put the bundled logo back. The uploaded file is removed with it. */
    public function resetHorizontal(): void
    {
        $this->clear('horizontal_path');
        session()->flash('brand_msg', __('Wide logo restored to the default.'));
    }

    public function resetIcon(): void
    {
        $this->clear('icon_path');
        session()->flash('brand_msg', __('Square icon restored to the default.'));
    }

    private function store(string $column, $file): void
    {
        $brand = BrandSetting::current();
        $old = $brand->{$column};

        $brand->update([
            $column => $file->store('branding', 'public'),
            'updated_by' => Auth::id(),
        ]);

        // Only after the new path is committed: a failed upload must never leave the app
        // pointing at a file that has already been deleted.
        if ($old) {
            Storage::disk('public')->delete($old);
        }

        BrandSetting::forget();

        // The generated squares are drawn from the icon, so they must go with it.
        if ($column === 'icon_path') {
            BrandIconService::flush();
        }
    }

    private function clear(string $column): void
    {
        $brand = BrandSetting::current();
        $old = $brand->{$column};

        $brand->update([$column => null, 'updated_by' => Auth::id()]);

        if ($old) {
            Storage::disk('public')->delete($old);
        }

        BrandSetting::forget();

        if ($column === 'icon_path') {
            BrandIconService::flush();
        }
    }

    public function render()
    {
        return view('livewire.admin.brand-logo-manager', [
            'brand' => BrandSetting::current(),
            'iconSize' => BrandIconService::sourceDimensions(),
        ]);
    }
}
