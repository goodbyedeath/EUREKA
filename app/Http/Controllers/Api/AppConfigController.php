<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BrandSetting;
use App\Models\FeatureSetting;
use App\Models\HeroSlide;
use Illuminate\Http\JsonResponse;

/**
 * What the native app needs to dress itself: the logo, the opening slides, and which
 * features are switched on.
 *
 * Branding and slides are **public**. The app has to paint its splash and login screen
 * before anyone has a token, and neither is a secret — the web landing page serves both
 * to anonymous visitors already.
 *
 * Features sit behind a token, matching the rest of /api/v1. They are not secret either,
 * but there is no reason to hand out the event's shape before a team has logged in.
 * As on the web, a flag decides only what is *shown*; every route is still guarded by
 * middleware, so an app that ignores a flag gains nothing.
 */
class AppConfigController extends Controller
{
    /**
     * The event's logo, both variants, absolute.
     *
     * Each URL carries a `?v=` stamp that changes when an admin uploads a new logo, so a
     * client may cache aggressively and still pick up a change: if the string differs,
     * re-download; if it does not, the image has not changed.
     */
    public function branding(): JsonResponse
    {
        $brand = BrandSetting::current();

        return response()->json([
            'success' => true,
            'branding' => [
                'app_name' => BrandSetting::appName(),
                'tagline' => BrandSetting::tagline() ?: null,
                // Wide wordmark for headers and splash.
                'logo_wide' => BrandSetting::horizontalUrl(),
                // Square mark for the app icon and anywhere a small badge is wanted.
                'logo_icon' => BrandSetting::iconUrl(),
                'theme_color' => '#6777ef',
                // Bump this and the client knows both URLs are stale in one comparison.
                'version' => (string) $brand->updated_at?->timestamp,
            ],
        ]);
    }

    /**
     * The opening slides, in the order an admin arranged them.
     */
    public function heroSlides(): JsonResponse
    {
        $slides = HeroSlide::getActiveSlides()->map(fn (HeroSlide $s) => [
            'id' => $s->id,
            'order' => (int) $s->order,
            'title' => $s->title,
            'subtitle' => $s->subtitle,
            // Absolute, or null when the slide is a plain gradient.
            'background_image' => $s->background_image_url,
            // A CSS gradient string. A native client that cannot parse it should fall
            // back to `text_color` on a solid colour rather than trying.
            'background_gradient' => $s->background_gradient,
            'text_color' => $s->text_color,
            'button_color' => $s->button_color,
            'button_style' => $s->button_style,
            'primary_button' => $s->primary_button_text ? [
                'text' => $s->primary_button_text,
                'url' => $s->primary_button_url,
            ] : null,
            'secondary_button' => $s->secondary_button_text ? [
                'text' => $s->secondary_button_text,
                'url' => $s->secondary_button_url,
            ] : null,
            // Raw SVG markup the admin pasted. Null on most slides.
            'icon_svg' => $s->icon_svg,
            'updated_at' => $s->updated_at?->toIso8601String(),
        ])->values();

        return response()->json([
            'success' => true,
            'count' => $slides->count(),
            'slides' => $slides,
        ]);
    }

    /**
     * Every flag and its state.
     *
     * Returned as a flat `key => bool` map under `flags` for the common case — an app
     * asking "is the leaderboard on?" wants one lookup, not a search through a list — and
     * as a described list under `features` for a settings screen that shows names.
     */
    public function features(): JsonResponse
    {
        $all = FeatureSetting::orderBy('sort_order')->orderBy('feature_key')->get();

        return response()->json([
            'success' => true,
            'flags' => $all->pluck('is_enabled', 'feature_key')->map(fn ($v) => (bool) $v),
            'features' => $all->map(fn (FeatureSetting $f) => [
                'key' => $f->feature_key,
                'name' => $f->feature_name,
                'description' => $f->description,
                'enabled' => (bool) $f->is_enabled,
                'sort_order' => (int) $f->sort_order,
            ])->values(),
        ]);
    }
}
