<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GameLocation;
use App\Models\QuestLocation;
use Illuminate\Support\Facades\Storage;

class OfflineController extends Controller
{
    /**
     * The latest edit to anything the offline copy is built from. Cheap enough for every
     * /race/status poll: four MAX() lookups on indexed timestamps.
     */
    public static function contentUpdatedAt(): ?string
    {
        $latest = collect([
            \App\Models\Questionnaire::max('updated_at'),
            \App\Models\Question::max('updated_at'),
            \App\Models\IndoorMap::max('updated_at'),
            \App\Models\IndoorMapSpot::max('updated_at'),
        ])->filter()->max();

        return $latest ? \Illuminate\Support\Carbon::parse($latest)->toIso8601String() : null;
    }

    /**
     * One picture of a sealed questionnaire, encrypted with that questionnaire's key.
     *
     * Body: nonce(12) ‖ ciphertext ‖ tag(16). Fetched during Sync, opened on the phone after the
     * post's QR is scanned. The picture is never served in the clear through this route.
     */
    public function questionnaireAsset(\App\Models\Questionnaire $questionnaire, string $ref)
    {
        abort_unless($questionnaire->is_active, 404);

        $body = app(\App\Services\OfflineQuestionnaireSeal::class)->sealedAsset($questionnaire, $ref);
        abort_if($body === null, 404);

        return response($body, 200, [
            'Content-Type' => 'application/octet-stream',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /**
     * Everything a team leader's device needs cached before they walk out of signal.
     *
     * Quiz content is here now, but sealed. It used to be left out because questionnaires are
     * QR-gated and shipping them ahead of time would hand the team the whole hunt — true, and
     * still the rule. The operator also needs the game to survive a dead network (22 Sep), so
     * each questionnaire travels encrypted under the QR code printed at its post: readable
     * there, with no signal, and nowhere else. See OfflineQuestionnaireSeal. Answers are still
     * graded server-side; an offline device only queues them.
     */
    public function manifest()
    {
        $questLocations = QuestLocation::where('is_active', true)
            ->get(['id', 'name', 'latitude', 'longitude', 'radius', 'marker_color', 'image_path', 'map_image_path']);

        // Ordered to match the web dashboard, so a native client rebuilding that
        // screen from this manifest lists the outposts in the same order.
        $gameLocations = GameLocation::where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->with(\App\Http\Controllers\ArExperienceController::sceneRelations())
            ->get(['id', 'name', 'ar_model_path', 'ar_model_id', 'ar_sky_path', 'experience_type',
                'latitude', 'longitude', 'radius', 'quest_location_id', 'access_mode']);

        // Every model a team could see: the location default plus any object that
        // overrides it. Resolved through modelUrl() so library-backed and legacy
        // path-backed models both appear — reading ar_model_path alone silently
        // dropped library models and left those outposts blank offline.
        $models = $gameLocations
            ->flatMap(fn ($g) => array_merge(
                [$g->modelUrl()],
                $g->ar_sky_path ? [Storage::disk('public')->url($g->ar_sky_path)] : [],
                $g->hotspots->map(fn ($h) => $h->modelUrl())->all()
            ))
            ->filter()
            ->unique()
            ->values();

        // Quest-location photos only. Game locations carry no imagery now — they are
        // purely the 3D experience.
        // ...plus every "foto bersama" frame. The app draws the frame over the camera to compose
        // the photo, so a post reached without signal needs it already on the phone.
        $frames = \App\Models\Question::where('type', \App\Enums\QuestionType::GROUP_PHOTO->value)
            ->whereNotNull('frame_path')
            ->pluck('frame_path');

        $images = collect()
            ->merge($questLocations->pluck('image_path'))
            ->merge($questLocations->pluck('map_image_path'))
            ->merge($frames)
            ->filter()
            ->unique()
            ->map(fn ($path) => Storage::disk('public')->url($path))
            ->values();

        // The pages themselves, not just their assets. Caching three.js and the .glb
        // is not enough: without the HTML that mounts them, an AR outpost opened out
        // of signal has an engine and a model and nothing to run them.
        $pages = collect([route('user.game-dashboard')])
            ->merge($gameLocations->filter->usesAr()->map(fn ($g) => route('user.ar.view', $g->id)))
            ->values();

        $routes = \App\Models\MapRoute::active()->with('markers')->orderBy('id')->get();

        // The team's own floor plan, exactly as /indoor-map answers — so the plan screen opens with
        // no signal. is_open is as of this sync; a crew opening a post needs the venue WiFi.
        // Every AR Outpost's scene — which objects, where, and what a tap shows — exactly as
        // /ar/locations answers once the post is open, so the 3D Camera renders with no signal
        // (operator, 22 Sep: the app is installed for the event and removed after it, so the
        // clues travel in the clear). The gate is not in here: indoor still waits for the crew.
        $ar = app(\App\Http\Controllers\ArExperienceController::class);
        $scenes = $gameLocations->filter->usesAr()->mapWithKeys(fn ($g) => [$g->id => $ar->scenePayload($g)]);
        $images = $images
            ->merge($scenes->flatMap(fn ($scene) => collect($scene['objects'])->pluck('image')))
            ->filter()->unique()->values();

        $indoorMap = app(\App\Http\Controllers\IndoorMapController::class)->offlinePayloadFor(request()->user());
        if ($indoorMap) {
            $images = $images
                ->merge([$indoorMap['map']['image'] ?? null])
                ->merge(collect($indoorMap['spots'])->pluck('image'))
                ->filter()->unique()->values();
        }

        $seal = app(\App\Services\OfflineQuestionnaireSeal::class);
        $questionnaires = \App\Models\Questionnaire::where('is_active', true)
            ->orderBy('id')
            ->get()
            ->map(fn (\App\Models\Questionnaire $q) => $seal->manifestEntry($q))
            ->filter()
            ->values();

        return response()->json([
            'success' => true,
            // Stamped on every sync so a client can tell, on its next /race/status, whether the
            // world it cached has been wiped by an emergency stop since.
            'race_reset_at' => \App\Models\RaceReset::lastAt()?->toIso8601String(),
            // When the crew last changed anything the phone keeps a copy of — questionnaires,
            // questions, the floor plans. Newer on /race/status than at sync means: sync again.
            'content_updated_at' => self::contentUpdatedAt(),
            // How to open a sealed questionnaire; build guide §10 has the whole scheme.
            'offline_crypto' => $seal->parameters(),
            'questionnaires' => $questionnaires,
            'indoor_map' => $indoorMap,
            'bounds' => $this->boundsFor($questLocations),
            // Basemap for a native map (APK #20): the same config/maps.php every web map reads, so
            // switching provider is one .env change for both. Raster XYZ; show the attribution.
            'map' => [
                'tiles' => array_values((array) config('maps.tiles', [])),
                'attribution' => html_entity_decode(strip_tags((string) config('maps.attribution', '')), ENT_QUOTES | ENT_HTML5),
                'max_zoom' => (int) config('maps.maxZoom', 19),
            ],
            'images' => $images,
            'models' => $models,
            'pages' => $pages,
            'quest_locations' => $questLocations->map(fn ($l) => [
                'id' => $l->id,
                'name' => $l->name,
                'latitude' => (float) $l->latitude,
                'longitude' => (float) $l->longitude,
                'radius' => (int) $l->radius,
                'marker_color' => $l->marker_color,
            ]),
            // The dashboard's own rows. 'pages' caches the rendered Livewire HTML,
            // which only a browser can use; a native client needs the records the
            // screen is built from. uses_ar mirrors the gate in ArExperienceController
            // so an offline client never offers an outpost the server would 404.
            'game_locations' => $gameLocations->map(fn ($g) => [
                'id' => $g->id,
                'name' => $g->name,
                'experience_type' => $g->experience_type,
                'model' => $g->modelUrl(),
                'uses_ar' => $g->usesAr(),
                // Lets an offline client show distance and refuse to open the scene away
                // from the outpost without needing the network. Null until an admin binds it.
                'latitude' => $g->resolvedLatitude(),
                'longitude' => $g->resolvedLongitude(),
                'radius' => $g->resolvedRadius(),
                'coordinate_source' => $g->coordinateSource(),
                'quest_location_id' => $g->quest_location_id,
                'access_mode' => $g->access_mode ?: 'geofence',
                // The /ar/locations body minus `success`, or null for an outpost with no 3D model.
                'scene' => $scenes[$g->id] ?? null,
            ])->values(),
            // The same lines /map/routes serves, so a team that synced can draw the route with
            // no signal at all. Empty until the crew switches a route on.
            'routes' => $routes->map(fn (\App\Models\MapRoute $r) => $r->toMapPayload())->values(),
            // The how-to, so Panduan still opens with no signal.
            'user_guide' => \App\Models\UserGuideSection::active()->ordered()->get()
                ->map(fn (\App\Models\UserGuideSection $g) => $g->toApiPayload())->values(),
            'counts' => [
                'quest_locations' => $questLocations->count(),
                'game_locations' => $gameLocations->count(),
                'images' => $images->count(),
                'models' => $models->count(),
                'pages' => $pages->count(),
                'routes' => $routes->count(),
            ],
        ]);
    }

    /**
     * Bounding box around every active post, padded so the map still has context
     * when a team walks slightly outside the course.
     */
    private function boundsFor($locations): ?array
    {
        $lats = $locations->pluck('latitude')->filter()->map(fn ($v) => (float) $v);
        $lngs = $locations->pluck('longitude')->filter()->map(fn ($v) => (float) $v);

        if ($lats->isEmpty() || $lngs->isEmpty()) {
            return null;
        }

        // ~350m of padding; enough to cover approach routes without exploding the
        // tile count at high zoom.
        $pad = 0.003;

        return [
            'north' => $lats->max() + $pad,
            'south' => $lats->min() - $pad,
            'east' => $lngs->max() + $pad,
            'west' => $lngs->min() - $pad,
        ];
    }
}
