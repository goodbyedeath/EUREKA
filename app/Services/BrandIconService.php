<?php

namespace App\Services;

use App\Models\BrandSetting;
use Illuminate\Support\Facades\Storage;

/**
 * Square, correctly-sized icons for a phone's home screen.
 *
 * A manifest that declares `"sizes": "512x512"` while serving a 447×558 file is telling
 * the phone something untrue. Chrome checks: an icon whose real dimensions do not match
 * what was declared is ignored, and with no usable 512px icon Android will not offer to
 * install the app at all, or falls back to a screenshot of the page. The logo an admin
 * uploads is whatever shape their designer gave them, so the app has to do this itself.
 *
 * Each size is drawn once and cached under a name that carries the source and the size,
 * so uploading a new logo produces new filenames and nothing stale is ever served.
 */
class BrandIconService
{
    /** What a phone actually asks for: 192 for the home screen, 512 for install and splash. */
    public const SIZES = [192, 512];

    private const DIR = 'branding/derived';

    /**
     * URL of the icon at this size, generating it the first time it is asked for.
     *
     * Falls back to the plain icon URL if anything goes wrong — a manifest with a
     * mis-sized icon is worse than the alternative, but a manifest with a broken link is
     * worse still.
     */
    public static function url(int $size): string
    {
        $path = self::ensure($size);

        return $path
            ? Storage::disk('public')->url($path) . '?v=' . BrandSetting::current()->updated_at?->timestamp
            : BrandSetting::iconUrl();
    }

    /** The stored path, or null if it could not be produced. */
    public static function ensure(int $size): ?string
    {
        $source = self::sourceFile();

        if (! $source || ! extension_loaded('gd')) {
            return null;
        }

        // The source's own modification time is in the name, so replacing the logo can
        // never hit a cached square drawn from the previous one.
        $target = self::DIR . '/icon-' . $size . '-' . substr(md5($source . filemtime($source)), 0, 12) . '.png';

        if (Storage::disk('public')->exists($target)) {
            return $target;
        }

        $png = self::square($source, $size);

        if ($png === null) {
            return null;
        }

        Storage::disk('public')->put($target, $png);

        return $target;
    }

    /**
     * Fit the logo inside a transparent square without cropping or stretching it.
     *
     * Contain, never cover: a logo is a shape someone drew, and cropping it to fill a
     * square cuts the ends off a wordmark. The empty space stays transparent, which is
     * what Android and iOS both expect to receive.
     */
    private static function square(string $file, int $size): ?string
    {
        $data = @file_get_contents($file);
        $src = $data ? @imagecreatefromstring($data) : false;

        if (! $src) {
            return null;
        }

        $w = imagesx($src);
        $h = imagesy($src);
        $scale = min($size / $w, $size / $h);
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));

        $canvas = imagecreatetruecolor($size, $size);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
        imagealphablending($canvas, true);

        imagecopyresampled(
            $canvas, $src,
            (int) (($size - $nw) / 2), (int) (($size - $nh) / 2),
            0, 0,
            $nw, $nh, $w, $h,
        );

        ob_start();
        imagepng($canvas, null, 9);
        $out = ob_get_clean();

        imagedestroy($canvas);
        imagedestroy($src);

        return $out ?: null;
    }

    /** The uploaded icon, or the file the app shipped with. */
    private static function sourceFile(): ?string
    {
        $stored = BrandSetting::current()->icon_path;

        if ($stored && is_file($p = Storage::disk('public')->path($stored))) {
            return $p;
        }

        $fallback = public_path(BrandSetting::FALLBACK_ICON);

        return is_file($fallback) ? $fallback : null;
    }

    /** Dimensions of whatever the icon currently is, for showing an admin the problem. */
    public static function sourceDimensions(): ?array
    {
        $file = self::sourceFile();
        $info = $file ? @getimagesize($file) : false;

        return $info ? ['width' => $info[0], 'height' => $info[1]] : null;
    }

    /** Drop every generated square. Called when the icon changes. */
    public static function flush(): void
    {
        Storage::disk('public')->deleteDirectory(self::DIR);
    }
}
