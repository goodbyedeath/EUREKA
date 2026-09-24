<?php

namespace App\Livewire\Admin;

use App\Models\AppSetting;
use App\Support\BellChoice;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

/**
 * What the time-up bell sounds like.
 *
 * The bell rings on the Outpost Access desk when a team's race clock runs out, and a desk beside a
 * PA speaker needs a different noise from one in a quiet room — so the crew picks the tone, the
 * volume and how many times it repeats (operator, 24 Sep). The built-in tones are synthesised in the
 * browser, so there is no audio file to ship, cache or lose; an uploaded sound is for a crew that
 * wants its own.
 *
 * Kept out of OutpostAccessPanel on purpose: that component polls every ten seconds, and a file
 * upload inside a polling component is a fight nobody needs.
 */
class BellSettings extends Component
{
    use \App\Livewire\Concerns\GuardsFileUploads;

    /** The tones live in BellChoice: the phones are offered the same list. */
    public const TONES = BellChoice::TONES;

    public string $tone = 'bel';
    public int $volume = 70;
    public int $repeat = 1;
    public $upload;

    public bool $open = false;

    public function mount(): void
    {
        $this->tone = self::tone();
        $this->volume = self::volume();
        $this->repeat = self::repeat();
    }

    public static function tone(): string
    {
        return BellChoice::tone();
    }

    public static function volume(): int
    {
        return BellChoice::volume();
    }

    public static function repeat(): int
    {
        return BellChoice::repeat();
    }

    public static function customPath(): ?string
    {
        return BellChoice::customPath();
    }

    public static function customUrl(): ?string
    {
        return BellChoice::customUrl();
    }

    public function updated(string $field): void
    {
        if (in_array($field, ['tone', 'volume', 'repeat'], true)) {
            $this->save();
        }
    }

    public function save(): void
    {
        $this->validate([
            'tone' => 'required|string|in:'.implode(',', array_merge(array_keys(self::TONES), ['custom'])),
            'volume' => 'required|integer|min:0|max:100',
            'repeat' => 'required|integer|min:1|max:5',
        ]);

        if ($this->tone === 'custom' && ! self::customPath()) {
            $this->addError('tone', 'Unggah dulu suaranya sebelum memilih "suara sendiri".');
            $this->tone = self::tone();

            return;
        }

        AppSetting::put('bell_tone', $this->tone, Auth::id());
        AppSetting::put('bell_volume', (string) $this->volume, Auth::id());
        AppSetting::put('bell_repeat', (string) $this->repeat, Auth::id());
    }

    public function saveUpload(): void
    {
        // m4a arrives as audio/mp4 or video/mp4 depending on the phone that recorded it, so the
        // extension list is the gate and the mimetype list has to allow both.
        $this->validate([
            'upload' => 'required|file|mimes:mp3,wav,ogg,m4a,aac|max:1024',
        ], [
            'upload.mimes' => 'Pakai MP3, WAV, OGG, M4A atau AAC.',
            'upload.max' => 'Maksimal 1 MB — ini bel, bukan lagu.',
        ]);

        $old = AppSetting::row('bell_custom_path')?->value;

        AppSetting::put('bell_custom_path', $this->upload->store('bell', 'public'), Auth::id());
        AppSetting::put('bell_tone', 'custom', Auth::id());
        $this->tone = 'custom';
        $this->reset('upload');

        // Only after the new path is stored: a failed upload must never leave the desk pointing at
        // a file that is already gone.
        if ($old) {
            Storage::disk('public')->delete($old);
        }

        session()->flash('bell_msg', 'Suara sendiri dipakai. Coba "Tes bel".');
    }

    public function removeUpload(): void
    {
        $old = AppSetting::row('bell_custom_path')?->value;

        AppSetting::put('bell_custom_path', null, Auth::id());
        if (AppSetting::row('bell_tone')?->value === 'custom') {
            AppSetting::put('bell_tone', 'bel', Auth::id());
            $this->tone = 'bel';
        }

        if ($old) {
            Storage::disk('public')->delete($old);
        }

        session()->flash('bell_msg', 'Suara sendiri dihapus, kembali ke nada bawaan.');
    }

    public function render()
    {
        return view('livewire.admin.bell-settings', [
            'tones' => self::TONES,
            'customUrl' => self::customUrl(),
        ]);
    }
}
