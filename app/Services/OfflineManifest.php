<?php

namespace App\Services;

use App\Http\Controllers\ArExperienceController;
use App\Http\Controllers\IndoorMapController;
use App\Models\GameLocation;
use App\Models\HotspotClaim;
use App\Models\MapRoute;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\QuestLocation;
use App\Models\RaceReset;
use App\Models\User;
use App\Models\UserGuideSection;
use App\Support\BellChoice;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Everything a team's phone caches before it walks out of signal — built once per content version,
 * not once per team.
 *
 * Measured on this host, 15 posts and 15 questionnaires: **91 queries and 1.77 seconds** per sync,
 * and 5,000 teams would have asked for exactly the same bytes (SCALING-PLAN.md §3c). The queries
 * were the smaller half. Most of that second is PBKDF2 — 100,000 iterations per sealed
 * questionnaire, by design, so the seal cannot be brute-forced from a stolen manifest — and it is
 * the *same* derivation every time, because a questionnaire is sealed under the QR code printed at
 * its post, not under anything about the team. 5,000 syncs of that is about two and a half CPU-hours
 * spent recomputing one answer.
 *
 * So the manifest is split in two. `shared()` is the part that is identical for everyone and is
 * cached under the content version; `forUser()` adds the three things that are not:
 *
 *   - `race_reset_at` — one indexed read, and the client compares it against its own cached world
 *   - `indoor_map` — the team's floor plan, which is rotated per team
 *   - `claimed` on each 3D object — whether *this* team has already taken those points
 *
 * Correctness comes from the key, not from a timer: the cache key is
 * `OfflineController::contentUpdatedAt()`, which is the same stamp the client is told to watch, so a
 * crew edit cannot leave a stale manifest behind. The hour-long TTL only stops old versions piling
 * up.
 */
class OfflineManifest
{
    /**
     * The manifest as one team's phone receives it.
     *
     * @return array<string, mixed>
     */
    public function forUser(?User $user): array
    {
        $shared = $this->shared();

        // The team's own floor plan, exactly as /indoor-map answers — so the plan screen opens with
        // no signal. is_open is as of this sync; a crew opening a post needs the venue WiFi.
        $indoorMap = app(IndoorMapController::class)->offlinePayloadFor($user);

        $images = collect($shared['images']);
        if ($indoorMap) {
            $images = $images
                ->merge([$indoorMap['map']['image'] ?? null])
                ->merge(collect($indoorMap['spots'])->pluck('image'))
                ->filter()->unique()->values();
        }

        $payload = $shared;
        $payload['game_locations'] = $this->withClaims($shared['game_locations'], $user);
        $payload['images'] = $images->values();
        $payload['counts']['images'] = $images->count();

        // Assembled in the order the endpoint has always answered in, so a diff of the contract
        // sample shows what changed rather than that the keys moved.
        return [
            'success' => true,
            // Stamped on every sync so a client can tell, on its next /race/status, whether the
            // world it cached has been wiped by an emergency stop since. Never cached: it is the
            // one field whose whole job is to be current.
            'race_reset_at' => RaceReset::lastAt()?->toIso8601String(),
            'content_updated_at' => $payload['content_updated_at'],
            'offline_crypto' => $payload['offline_crypto'],
            'questionnaires' => $payload['questionnaires'],
            'indoor_map' => $indoorMap,
            'bounds' => $payload['bounds'],
            'map' => $payload['map'],
            'bell' => $payload['bell'],
            'images' => $payload['images'],
            'models' => $payload['models'],
            'pages' => $payload['pages'],
            'quest_locations' => $payload['quest_locations'],
            'game_locations' => $payload['game_locations'],
            'routes' => $payload['routes'],
            'user_guide' => $payload['user_guide'],
            'counts' => $payload['counts'],
        ];
    }

    /**
     * The part of the manifest that is the same for every team, cached under the content version.
     *
     * Built behind a lock, because of a trap worth stating plainly: `artisan optimize:clear` runs
     * `cache:clear`, which throws away the 15 PBKDF2 master keys `OfflineQuestionnaireSeal` keeps.
     * Deriving them again is **114 ms each, 1.7 seconds in all** (measured on this host), and the
     * first sync after any deploy is the request that pays it. Without a lock, 5,000 phones syncing
     * at the start of an event would each find the cache empty and each do the same 1.7 seconds of
     * key stretching; with one, the first builds and the rest wait for its answer.
     *
     * `eureka:warm` makes that first builder the deploy rather than a player — run it after
     * `optimize:clear`.
     *
     * @return array<string, mixed>
     */
    public function shared(): array
    {
        $key = 'offline:manifest:'.md5(\App\Http\Controllers\Api\OfflineController::contentUpdatedAt() ?? 'empty');

        if (($cached = Cache::get($key)) !== null) {
            return $cached;
        }

        try {
            // Whoever gets the lock builds it; the others come through once it is in the cache and
            // remember() hands them that instead of building again.
            return Cache::lock($key.':build', 60)->block(15, fn () => Cache::remember($key, 3600, fn () => $this->build()));
        } catch (\Illuminate\Contracts\Cache\LockTimeoutException) {
            // Fifteen seconds and still nothing: build it rather than fail a sync. Slow beats a
            // team standing at the briefing with no questionnaires on their phone.
            return Cache::remember($key, 3600, fn () => $this->build());
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function build(): array
    {
        $questLocations = QuestLocation::where('is_active', true)
            ->get(['id', 'name', 'latitude', 'longitude', 'radius', 'marker_color', 'image_path', 'map_image_path']);

        // Ordered to match the web dashboard, so a native client rebuilding that
        // screen from this manifest lists the outposts in the same order.
        $gameLocations = GameLocation::where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->with(ArExperienceController::sceneRelations())
            ->get(['id', 'name', 'ar_model_path', 'ar_model_id', 'ar_sky_path', 'experience_type', 'ar_enabled',
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
        $frames = Question::where('type', \App\Enums\QuestionType::GROUP_PHOTO->value)
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

        $routes = MapRoute::active()->with('markers')->orderBy('id')->get();

        // Every AR Outpost's scene — which objects, where, and what a tap shows — exactly as
        // /ar/locations answers once the post is open, so the 3D Camera renders with no signal
        // (operator, 22 Sep: the app is installed for the event and removed after it, so the
        // clues travel in the clear). The gate is not in here: indoor still waits for the crew.
        //
        // Built for nobody, which is what makes it shareable: a null viewer reports every object
        // unclaimed, and forUser() marks the ones that team has already taken.
        $ar = app(ArExperienceController::class);
        $scenes = $gameLocations->filter->hasExperience()
            ->mapWithKeys(fn ($g) => [$g->id => $ar->scenePayload($g, null)]);

        $images = $images
            ->merge($scenes->flatMap(fn ($scene) => collect($scene['objects'])->pluck('image')))
            ->filter()->unique()->values();

        // Quiz content is here, but sealed. It used to be left out because questionnaires are
        // QR-gated and shipping them ahead of time would hand the team the whole hunt — true, and
        // still the rule. The operator also needs the game to survive a dead network (22 Sep), so
        // each questionnaire travels encrypted under the QR code printed at its post: readable
        // there, with no signal, and nowhere else. See OfflineQuestionnaireSeal. Answers are still
        // graded server-side; an offline device only queues them.
        //
        // This is where the second went: one 100,000-iteration key derivation per questionnaire,
        // under a code that has nothing to do with the team, so every team was paying for the same
        // arithmetic.
        $seal = app(OfflineQuestionnaireSeal::class);
        $questionnaires = Questionnaire::where('is_active', true)
            ->orderBy('id')
            ->get()
            ->load('extraPosts:id')
            ->map(fn (Questionnaire $q) => $seal->manifestEntry($q))
            ->filter()
            ->values();

        return [
            // When the crew last changed anything the phone keeps a copy of. Newer on /race/status
            // than at sync means: sync again. It is also this payload's cache key.
            'content_updated_at' => \App\Http\Controllers\Api\OfflineController::contentUpdatedAt(),
            // How to open a sealed questionnaire; build guide §10 has the whole scheme.
            'offline_crypto' => $seal->parameters(),
            'questionnaires' => $questionnaires,
            'bounds' => $this->boundsFor($questLocations),
            // Basemap for a native map (APK #20): the same config/maps.php every web map reads, so
            // switching provider is one .env change for both. Raster XYZ; show the attribution.
            'map' => [
                'tiles' => array_values((array) config('maps.tiles', [])),
                'attribution' => html_entity_decode(strip_tags((string) config('maps.attribution', '')), ENT_QUOTES | ENT_HTML5),
                'max_zoom' => (int) config('maps.maxZoom', 19),
            ],
            // What to sound when the race clock runs out — the crew's choice, so the desk and
            // every phone ring the same thing. A built-in tone is a name the client draws itself;
            // 'custom' also carries sound_url, which is cached like any other asset.
            'bell' => BellChoice::payload(),
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
                // "Open the camera here", not "has a model": false for a post whose camera the crew
                // switched off, so a client that has not learned `ar_enabled` yet still does the right
                // thing — it simply offers no 3D at that post (operator, 24 Sep).
                'uses_ar' => $g->usesAr() && $g->arEnabled(),
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
            ])->values()->all(),
            // The same lines /map/routes serves, so a team that synced can draw the route with
            // no signal at all. Empty until the crew switches a route on.
            'routes' => $routes->map(fn (MapRoute $r) => $r->toMapPayload())->values(),
            // The how-to, so Panduan still opens with no signal.
            'user_guide' => UserGuideSection::active()->ordered()->get()
                ->map(fn (UserGuideSection $g) => $g->toApiPayload())->values(),
            'counts' => [
                'quest_locations' => $questLocations->count(),
                'game_locations' => $gameLocations->count(),
                // How many posts actually open a camera. Zero means every post on this event has its
                // 3D switched off, and the dashboard's Kamera 3D button should be locked rather than
                // opening a screen with nothing in it (operator, 24 Sep).
                'ar_posts' => $gameLocations->filter(fn ($g) => $g->usesAr() && $g->arEnabled())->count(),
                // Replaced per request: the team's floor plan brings images of its own.
                'images' => $images->count(),
                'models' => $models->count(),
                'pages' => $pages->count(),
                'routes' => $routes->count(),
            ],
        ];
    }

    /**
     * Mark the objects this team has already been paid for.
     *
     * One query for the whole event, where building the scenes per viewer was one per outpost. The
     * rule is the model's — a claim belongs to the team, not to the phone that tapped it — so the
     * lookup goes through HotspotClaim rather than reimplementing it here.
     *
     * @param  list<array<string, mixed>>  $locations
     * @return list<array<string, mixed>>
     */
    private function withClaims(array $locations, ?User $user): array
    {
        $ids = collect($locations)
            ->pluck('scene.objects')
            ->filter()
            ->flatten(1)
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $claimed = HotspotClaim::claimedIdsFor($user, $ids);

        if ($claimed === []) {
            return $locations;           // the cached payload already says false everywhere
        }

        foreach ($locations as $i => $location) {
            foreach ($location['scene']['objects'] ?? [] as $j => $object) {
                $locations[$i]['scene']['objects'][$j]['claimed'] =
                    in_array((int) $object['id'], $claimed, true);
            }
        }

        return $locations;
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
