<?php

namespace App\Http\Controllers;

use App\Models\GameLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ArExperienceController extends Controller
{
    /**
     * Idle motions an object may have, and the exact rates every client must use.
     * t is seconds since the scene opened; speed and range come from the hotspot.
     *
     *   spin   rotate on own Y axis     deg/s  = speed * 30
     *   orbit  circle the player        deg/s  = speed * 12   (a lap is ~30 s at 1.0)
     *   bob    rise and fall            y      = base.y + sin(2*PI*t*speed/4) * range   (metres)
     *   sway   arc left and right       bearing = sin(2*PI*t*speed/6) * range           (degrees)
     *
     * Orbit and sway rotate the authored position about the player, who is always at
     * the origin, so the distance the admin set is preserved throughout.
     */
    public const ANIMATIONS = ['none', 'spin', 'bob', 'orbit', 'sway'];

    /**
     * Render a game location's 3D / AR experience.
     *
     * Shared by the admin preview and the team view; the only difference is where
     * "back" goes and whether hotspot editing affordances are shown.
     */
    public function show(Request $request, $id)
    {
        $gameLocation = GameLocation::with(['arModel', 'questLocation', 'hotspots.arModel', 'hotspots' => function ($query) {
            $query->where('is_active', true)->orderBy('tour_order');
        }])->findOrFail($id);

        // The panorama viewer is gone, so there is nothing to fall back to. A location
        // flagged for AR without a model would render an empty camera view, which is
        // worse than an honest error — send them back to the list instead.
        if (! $gameLocation->usesAr()) {
            return redirect()
                ->route($request->user()?->isAdmin() ? 'admin.games' : 'user.game-dashboard')
                ->with('error', __('That location has no 3D model yet.'));
        }

        // Indoor: the crew opens this outpost per team. Without it there is nothing to
        // show, so send them back rather than rendering an empty camera.
        if (! $gameLocation->accessGrantedTo($request->user())) {
            return redirect()
                ->route('user.game-dashboard')
                ->with('error', __('Waiting for the crew to open this outpost for your team.'));
        }

        return view('ar.view', [
            'gameLocation' => $gameLocation,
            'library' => $request->user()?->isAdmin()
                ? \App\Models\ArModel::orderBy('name')->get()->map(fn ($m) => [
                    'id' => $m->id, 'name' => $m->name, 'url' => $m->url(), 'size' => $m->size_for_humans,
                ])
                : collect(),
            // Admins get the objects inline because they are authoring them. A team must
            // NOT: the page is reachable from anywhere, and embedding titles, points and clue
            // text here would let them read every clue from the start line by viewing source.
            // Teams fetch from apiShow instead, which demands a position and enforces the radius.
            'hotspots' => $request->user()?->isAdmin()
                ? $gameLocation->hotspots->map(fn ($h) => $this->placeHotspot($h))
                : collect(),
            'objectCount' => $gameLocation->hotspots->count(),
            'isAdmin' => (bool) $request->user()?->isAdmin(),
            // Where the outpost physically is; lat stays null until an admin binds it
            // on site. Built here because Blade's @json cannot parse a multi-line array.
            'outpost' => [
                'lat' => $gameLocation->resolvedLatitude(),
                'lng' => $gameLocation->resolvedLongitude(),
                'radius' => $gameLocation->resolvedRadius(),
                // 'quest_location' means the post owns this point, so the AR view must not
                // overwrite it by pinning — that would fork the two silently.
                'source' => $gameLocation->coordinateSource(),
                // 'manual' tells the viewer to skip the radius check: an indoor outpost is
                // opened by the crew, and its coordinates are unreachable by GPS anyway.
                'mode' => $gameLocation->access_mode ?: 'geofence',
            ],
        ]);
    }

    /**
     * Save an object placed by standing at the outpost and pointing the phone.
     *
     * The browser sends the direction it is facing, measured from the calibration
     * heading (the QR code). That is the same pitch/yaw the panorama editor produced,
     * so both authoring routes write identical data and old hotspots keep working.
     */
    /**
     * Bind this outpost to where the admin is standing.
     *
     * Authoring already requires being at the outpost — you have to aim the phone to
     * place anything — so the GPS fix is free at that moment. Without an explicit
     * force this never overwrites a point that is already set, so simply reopening
     * the viewer somewhere else cannot silently move the outpost.
     */
    public function bindLocation(Request $request, $id)
    {
        $data = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|integer|min:5|max:5000',
            'force' => 'nullable|boolean',
        ]);

        $gameLocation = GameLocation::findOrFail($id);

        // A linked post owns the coordinate. Pinning here would create a second point
        // that silently disagrees with the one the admin set in Quest Locations.
        if ($gameLocation->coordinateSource() === 'quest_location') {
            return response()->json([
                'success' => true,
                'changed' => false,
                'source' => 'quest_location',
                'latitude' => $gameLocation->resolvedLatitude(),
                'longitude' => $gameLocation->resolvedLongitude(),
                'radius' => $gameLocation->resolvedRadius(),
            ]);
        }

        if ($gameLocation->hasCoordinates() && ! ($data['force'] ?? false)) {
            return response()->json([
                'success' => true,
                'changed' => false,
                'latitude' => (float) $gameLocation->latitude,
                'longitude' => (float) $gameLocation->longitude,
                'radius' => (int) $gameLocation->radius,
            ]);
        }

        $gameLocation->update([
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'radius' => $data['radius'] ?? $gameLocation->radius ?? 50,
        ]);

        return response()->json([
            'success' => true,
            'changed' => true,
            'latitude' => (float) $gameLocation->latitude,
            'longitude' => (float) $gameLocation->longitude,
            'radius' => (int) $gameLocation->radius,
        ]);
    }
    public function storeHotspot(Request $request, $id)
    {
        $data = $request->validate([
            'pitch' => 'required|numeric|between:-1.5708,1.5708',
            'yaw' => 'required|numeric|between:-3.1416,3.1416',
            'distance' => 'required|numeric|min:0.5|max:50',
            'scale' => 'required|numeric|min:0.01|max:50',
            'rotation_x' => 'nullable|numeric|between:-360,360',
            'rotation_y' => 'nullable|numeric|between:-360,360',
            'rotation_z' => 'nullable|numeric|between:-360,360',
            'ar_model_id' => 'nullable|integer|exists:ar_models,id',
            'media_type' => 'nullable|in:link,image,none',
            'ar_motions' => 'nullable|array|max:4',
            'ar_motions.*.type' => 'required|string|in:' . implode(',', self::ANIMATIONS),
            'ar_motions.*.speed' => 'required|numeric|min:0.1|max:5',
            'ar_motions.*.range' => 'nullable|numeric|min:0|max:90',
            'content' => 'nullable|string|max:2048',
            'media_path' => 'nullable|string|max:2048',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'points_value' => 'nullable|integer|min:0|max:10000',
        ]);

        $gameLocation = GameLocation::findOrFail($id);

        $hotspot = $gameLocation->hotspots()->create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'pitch' => $data['pitch'],
            'yaw' => $data['yaw'],
            'type' => 'info',
            'hotspot_type' => 'info',
            'points_value' => $data['points_value'] ?? 0,
            'ar_distance' => $data['distance'],
            'ar_scale' => $data['scale'],
            'ar_rotation_x' => $data['rotation_x'] ?? 0,
            'ar_rotation_y' => $data['rotation_y'] ?? 0,
            'ar_rotation_z' => $data['rotation_z'] ?? 0,
            'ar_model_id' => $data['ar_model_id'] ?? null,
            // 'none' is how the phone says "no interaction"; store it as null so the
            // column means the same thing however the object was authored.
            'media_type' => ($data['media_type'] ?? null) === 'none' ? null : ($data['media_type'] ?? null),
            'content' => $data['content'] ?? null,
            'media_path' => $data['media_path'] ?? null,
            'ar_motions' => $data['ar_motions'] ?? null,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'hotspot' => $this->placeHotspot($hotspot),
        ]);
    }

    /**
     * The AR scene as JSON, for clients that render it themselves.
     *
     * Deliberately the same shape the web viewer receives — both go through
     * placeHotspot() — so a native renderer and the browser cannot drift apart.
     * Units are mixed and worth stating: pitch/yaw are RADIANS, rotation is
     * DEGREES, distance and the derived position are METRES, scale is a plain
     * multiplier on the model's own size.
     */
    public function apiShow(Request $request, $id)
    {
        $gameLocation = GameLocation::with(['arModel', 'questLocation', 'hotspots.arModel', 'hotspots' => function ($query) {
            $query->where('is_active', true)->orderBy('tour_order');
        }])->findOrFail($id);

        if (! $gameLocation->usesAr()) {
            return response()->json([
                'success' => false,
                'message' => 'This location has no 3D model yet.',
            ], 404);
        }

        // Once an outpost is bound, a team must say where it is. Making the fix optional
        // would let a client skip the geofence simply by omitting it, which is the same as
        // having no geofence. Admins are exempt so they can inspect from a desk.
        $isAdmin = (bool) $request->user()?->isAdmin();

        // Indoor outposts are opened by a person. GPS cannot satisfy a radius through a
        // roof, so manual mode replaces the geofence rather than adding to it.
        if ($gameLocation->isManualAccess()) {
            if (! $gameLocation->accessGrantedTo($request->user())) {
                return response()->json([
                    'success' => false,
                    'error' => 'awaiting_unlock',
                    'message' => 'Waiting for the crew to open this outpost for your team.',
                ], 403);
            }
        } elseif ($gameLocation->hasCoordinates() && ! $isAdmin
            && ! ($request->filled('lat') && $request->filled('lng'))) {
            return response()->json([
                'success' => false,
                'error' => 'location_required',
                'message' => 'Send lat and lng — this outpost is geofenced.',
            ], 403);
        }

        // Coordinates are client-supplied so this is a convenience check, not proof —
        // the QR scan remains the real evidence of physical presence.
        if ($request->filled('lat') && $request->filled('lng') && $gameLocation->hasCoordinates()) {
            $away = $gameLocation->calculateDistance((float) $request->query('lat'), (float) $request->query('lng'));

            if ($away > $gameLocation->resolvedRadius() && ! $isAdmin) {
                return response()->json([
                    'success' => false,
                    'error' => 'out_of_range',
                    'distance' => $away,
                    'radius' => $gameLocation->resolvedRadius(),
                    'message' => 'You are ' . round($away) . 'm from this outpost.',
                ], 403);
            }
        }

        return response()->json([
            'success' => true,
            'location' => [
                'id' => $gameLocation->id,
                'name' => $gameLocation->name,
                'model' => $gameLocation->modelUrl(),
                // Resolved: a linked quest location owns the coordinates, otherwise this
                // row's own. Null means never bound — render with no geofence at all.
                'latitude' => $gameLocation->resolvedLatitude(),
                'longitude' => $gameLocation->resolvedLongitude(),
                'radius' => $gameLocation->resolvedRadius(),
                'coordinate_source' => $gameLocation->coordinateSource(),
                'quest_location_id' => $gameLocation->quest_location_id,
                // 'geofence' -> the radius decides; 'manual' -> an admin does, and the
                // client should show a waiting state rather than a distance.
                'access_mode' => $gameLocation->access_mode ?: 'geofence',
            ],
            'objects' => $gameLocation->hotspots->map(fn ($h) => $this->placeHotspot($h))->values(),
        ]);
    }

    /**
     * Upload an image for an object's popup.
     *
     * Separate from the hotspot save because the placement panel sends JSON; posting
     * the file on its own keeps that simple and lets the admin see the upload land
     * before committing the object.
     */
    public function storeMedia(Request $request, $id)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,jpg,png,webp,gif|max:5120',
        ], [
            'image.max' => 'The image must be 5 MB or smaller.',
        ]);

        GameLocation::findOrFail($id);

        $path = $request->file('image')->store('hotspot-images', 'public');

        return response()->json([
            'success' => true,
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
        ]);
    }

    /**
     * Reposition, resize, re-rotate or re-model an object that is already placed.
     *
     * Only the spatial fields and the model choice — the text and points are edited
     * elsewhere. Every field is optional so the phone can send just what changed.
     */
    public function updateHotspot(Request $request, $id, $hotspotId)
    {
        $data = $request->validate([
            'pitch' => 'nullable|numeric|between:-1.5708,1.5708',
            'yaw' => 'nullable|numeric|between:-3.1416,3.1416',
            'distance' => 'nullable|numeric|min:0.5|max:50',
            'scale' => 'nullable|numeric|min:0.01|max:50',
            'rotation_x' => 'nullable|numeric|between:-360,360',
            'rotation_y' => 'nullable|numeric|between:-360,360',
            'rotation_z' => 'nullable|numeric|between:-360,360',
            'ar_model_id' => 'nullable|integer|exists:ar_models,id',
            'media_type' => 'nullable|in:link,image,none',
            'ar_motions' => 'nullable|array|max:4',
            'ar_motions.*.type' => 'required|string|in:' . implode(',', self::ANIMATIONS),
            'ar_motions.*.speed' => 'required|numeric|min:0.1|max:5',
            'ar_motions.*.range' => 'nullable|numeric|min:0|max:90',
            'content' => 'nullable|string|max:2048',
            'media_path' => 'nullable|string|max:2048',
        ]);

        $hotspot = GameLocation::findOrFail($id)->hotspots()->findOrFail($hotspotId);

        $map = [
            'pitch' => 'pitch', 'yaw' => 'yaw',
            'distance' => 'ar_distance', 'scale' => 'ar_scale',
            'rotation_x' => 'ar_rotation_x', 'rotation_y' => 'ar_rotation_y',
            'rotation_z' => 'ar_rotation_z', 'ar_model_id' => 'ar_model_id',
            'content' => 'content', 'media_path' => 'media_path',
        ];

        $changes = [];
        foreach ($map as $input => $column) {
            if (array_key_exists($input, $data) && $data[$input] !== null) {
                $changes[$column] = $data[$input];
            }
        }

        // 'none' is how the phone says "clear the interaction"; null would be
        // indistinguishable from "field not sent".
        // A null media_path while still an image means "keep the current picture" —
        // the admin edited position without re-choosing a file.
        if (($data['media_type'] ?? null) === 'image' && empty($data['media_path'])) {
            unset($changes['media_path']);
        }

        // Sent explicitly so an admin can clear every motion; the $map loop above
        // skips nulls and would quietly keep the old list.
        if (array_key_exists('ar_motions', $data)) {
            $changes['ar_motions'] = $data['ar_motions'] ?: null;
        }

        if (array_key_exists('media_type', $data)) {
            $changes['media_type'] = $data['media_type'] === 'none' ? null : $data['media_type'];
        }

        if ($changes) {
            $hotspot->update($changes);
        }

        return response()->json([
            'success' => true,
            'hotspot' => $this->placeHotspot($hotspot->fresh()),
        ]);
    }

    /**
     * Remove a placed object.
     */
    public function destroyHotspot($id, $hotspotId)
    {
        GameLocation::findOrFail($id)->hotspots()->whereKey($hotspotId)->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Turn a panorama hotspot's spherical direction into a point in 3D space.
     *
     * pitch/yaw are radians and already describe *where the team should look* — the
     * same meaning in AR as inside a panorama sphere — so authoring carries over
     * unchanged. Distance is per-hotspot because an object two metres away and one
     * ten metres away need very different placement outdoors.
     */
    private function placeHotspot($hotspot): array
    {
        $pitch = (float) $hotspot->pitch;
        $yaw = (float) $hotspot->yaw;
        $distance = $hotspot->ar_distance > 0 ? (float) $hotspot->ar_distance : 2.0;

        return [
            'id' => $hotspot->id,
            'title' => $hotspot->title,
            'description' => $hotspot->description,
            'points' => (int) ($hotspot->points_value ?? 0),
            'media_type' => $hotspot->media_type,
            'link' => $hotspot->media_type === 'link' ? $hotspot->content : null,
            'image' => $hotspot->media_type === 'image' && $hotspot->media_path
                ? Storage::disk('public')->url($hotspot->media_path) : null,
            'scale' => $hotspot->ar_scale > 0 ? (float) $hotspot->ar_scale : 1.0,
            'distance' => $distance,
            'rotation' => [
                'x' => (float) ($hotspot->ar_rotation_x ?? 0),
                'y' => (float) ($hotspot->ar_rotation_y ?? 0),
                'z' => (float) ($hotspot->ar_rotation_z ?? 0),
            ],
            'model' => $hotspot->modelUrl(),
            'model_id' => $hotspot->ar_model_id,
            // A list: motions touch different axes, so several can run at once.
            // Empty means the object holds the pose it was placed in.
            'animations' => collect($hotspot->ar_motions ?: [])
                ->filter(fn ($m) => in_array($m['type'] ?? null, self::ANIMATIONS, true) && $m['type'] !== 'none')
                ->map(fn ($m) => [
                    'type' => $m['type'],
                    'speed' => (float) ($m['speed'] ?? 1),
                    'range' => (float) ($m['range'] ?? 1),
                ])->values()->all(),
            'position' => [
                'x' => round($distance * cos($pitch) * sin($yaw), 4),
                'y' => round($distance * sin($pitch), 4),
                'z' => round(-$distance * cos($pitch) * cos($yaw), 4),
            ],
        ];
    }
}
