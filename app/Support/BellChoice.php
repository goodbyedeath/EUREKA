<?php

namespace App\Support;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Storage;

/**
 * The time-up bell: one choice, made by the crew, read by every screen that rings.
 *
 * The admin picks it on Outpost Access (App\Livewire\Admin\BellSettings); the desk reads it from a
 * data attribute and the phones read it from the manifest and from /race/status, so a crew that
 * changes the tone changes it everywhere rather than in four places.
 *
 * The built-in tones are *named*, not shipped: each end draws its own from the name, so there is no
 * audio file to download before a bell can ring, and a phone in a dead spot still has one.
 */
class BellChoice
{
    /** Every built-in tone, with the words the crew chooses by. The ids are the wire format. */
    public const TONES = [
        'bel' => 'Bel — tiga nada (bawaan)',
        'lonceng' => 'Lonceng — dua pukulan panjang',
        'sirine' => 'Sirine — sapuan naik-turun',
        'bip' => 'Bip — lima nada pendek, paling menusuk',
    ];

    /**
     * The settings rows this choice is made of. Named here so the offline content version can watch
     * them: a crew that changes the tone has changed the manifest, and every synced phone has to
     * learn about it (SCALING-PLAN.md §3c).
     */
    public const SETTING_KEYS = ['bell_tone', 'bell_volume', 'bell_repeat', 'bell_custom_path'];

    public static function tone(): string
    {
        $tone = AppSetting::row('bell_tone')?->value;

        // 'custom' survives only while the file does, so a deleted upload falls back to the bell
        // rather than ringing nothing.
        if ($tone === 'custom') {
            return self::customPath() ? 'custom' : 'bel';
        }

        return array_key_exists((string) $tone, self::TONES) ? (string) $tone : 'bel';
    }

    public static function volume(): int
    {
        return max(0, min(100, (int) (AppSetting::row('bell_volume')?->value ?? 70)));
    }

    public static function repeat(): int
    {
        return max(1, min(5, (int) (AppSetting::row('bell_repeat')?->value ?? 1)));
    }

    public static function customPath(): ?string
    {
        $path = AppSetting::row('bell_custom_path')?->value;

        return $path && Storage::disk('public')->exists($path) ? $path : null;
    }

    /** The URL a browser or a phone plays, or null when no sound was uploaded. */
    public static function customUrl(): ?string
    {
        $path = self::customPath();

        return $path ? Storage::disk('public')->url($path) : null;
    }

    /**
     * What the API sends. `tone` is one of the built-in ids or 'custom'; `sound_url` is filled only
     * for 'custom', and a client that cannot fetch or play it falls back to the built-in bell —
     * a silent phone at the deadline is the one outcome nobody wants.
     */
    public static function payload(): array
    {
        return [
            'tone' => self::tone(),
            'volume' => self::volume(),
            'repeat' => self::repeat(),
            'sound_url' => self::customUrl(),
        ];
    }
}
