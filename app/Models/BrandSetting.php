<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * The event's logo, in one place.
 *
 * Every view asks this class rather than naming a file, so uploading a new logo changes
 * the admin topbar, the team navigation, the landing page, the login screen, the LED
 * board and the PDF export at once.
 *
 * Two variants, because the app genuinely uses two shapes and one cannot stand in for the
 * other: a wide wordmark for headers, and a square mark for the favicon and app icon.
 */
class BrandSetting extends Model
{
    protected $fillable = ['app_name', 'tagline', 'horizontal_path', 'icon_path', 'updated_by'];

    /** Shipped with the app; used whenever nothing has been uploaded. */
    public const FALLBACK_HORIZONTAL = 'logo/horizonlogo.png';
    public const FALLBACK_ICON = 'logo/icon.png';

    private const CACHE_KEY = 'brand_settings.current';

    /**
     * The single row. Cached because a logo appears several times per page and this would
     * otherwise be a query per image.
     */
    public static function current(): self
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => static::query()->firstOrCreate([]));
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    protected static function booted(): void
    {
        // Any write invalidates the cache, including one made from tinker or a seeder.
        static::saved(fn () => self::forget());
        static::deleted(fn () => self::forget());
    }

    // ----------------------------------------------------------------- name

    /**
     * What this event is called.
     *
     * Falls back to APP_NAME, so an install that never sets a name reads exactly as it
     * did. Views call this instead of `config('app.name')`: that value comes from `.env`
     * and is baked in by `config:cache`, which puts it out of an admin's reach.
     */
    public static function appName(): string
    {
        return self::current()->app_name ?: (string) config('app.name', 'EUREKA');
    }

    /** The optional line under the name. Empty string when unset, so views can print it raw. */
    public static function tagline(): string
    {
        return (string) (self::current()->tagline ?? '');
    }

    /**
     * "Page — Event Name", the shape a browser tab wants: the specific part first, since a
     * narrow tab truncates the end.
     */
    public static function title(?string $page = null): string
    {
        return $page ? $page . ' - ' . self::appName() : self::appName();
    }

    // ----------------------------------------------------------------- urls

    /**
     * The wide wordmark, ready for `src`.
     *
     * The `?v=` is what makes a new logo appear immediately: the URL is otherwise
     * identical and every browser and the service worker would keep serving the old file.
     */
    public static function horizontalUrl(): string
    {
        return self::url(self::current()->horizontal_path, self::FALLBACK_HORIZONTAL);
    }

    public static function iconUrl(): string
    {
        return self::url(self::current()->icon_path, self::FALLBACK_ICON);
    }

    private static function url(?string $stored, string $fallback): string
    {
        // Fall back when the file is GONE, not merely when the column is empty.
        //
        // A stored path whose file has been removed — a storage directory replaced by a
        // re-upload, a manual tidy-up — used to produce a URL to a 404. Everyone with the
        // old file cached kept seeing a logo, so it looked like it only broke "for some new
        // users"; it was broken for everyone without a cache. horizontalFile() below has
        // always checked is_file() for the PDF export; this is the same check for the web.
        $usable = $stored && Storage::disk('public')->exists($stored);
        $base = $usable ? Storage::disk('public')->url($stored) : asset($fallback);

        return $base . '?v=' . self::current()->updated_at?->timestamp;
    }

    // ------------------------------------------------------------- file paths

    /**
     * A filesystem path, for things that cannot fetch a URL — the PDF export renders with
     * `public_path()` and would print a broken image given a link.
     */
    public static function horizontalFile(): string
    {
        $stored = self::current()->horizontal_path;

        if ($stored && is_file($p = Storage::disk('public')->path($stored))) {
            return $p;
        }

        return public_path(self::FALLBACK_HORIZONTAL);
    }

    /**
     * "Custom" means a custom logo is actually being served, not merely that a path is stored.
     *
     * These drive the Custom/Default badge on the admin page. Reading the column alone made it
     * claim a custom logo while the page below it rendered the default, because the file the
     * column pointed at had been removed — which is exactly the state that hid a broken logo
     * from the operator.
     */
    public function usesCustomHorizontal(): bool
    {
        return filled($this->horizontal_path) && Storage::disk('public')->exists($this->horizontal_path);
    }

    public function usesCustomIcon(): bool
    {
        return filled($this->icon_path) && Storage::disk('public')->exists($this->icon_path);
    }
}
