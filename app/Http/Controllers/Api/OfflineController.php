<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class OfflineController extends Controller
{
    /**
     * The latest edit to anything the offline copy is built from.
     *
     * Two jobs, and they have to be the same answer. The client polls it on /race/status and syncs
     * again when it moves; the manifest is cached under it (SCALING-PLAN.md §3c). A table missing
     * from this list is therefore a table whose edits never reach a phone *and* never invalidate the
     * cache — which is why it now covers everything `OfflineManifest::shared()` reads, not just
     * questionnaires and floor plans. Before this, retouching a 3D object, a map route or the
     * Panduan left every synced phone showing the old one until some unrelated questionnaire was
     * saved.
     *
     * Cached for a couple of seconds because /race/status is the most frequent call in the event and
     * this is eleven MAX() lookups. The delay is bounded by `offline.version_ttl` and costs a crew
     * edit nothing worth noticing.
     */
    public static function contentUpdatedAt(): ?string
    {
        $ttl = (int) config('offline.version_ttl', 2);

        $read = fn () => collect([
            // Quiz content and the sealed copies built from it.
            \App\Models\Questionnaire::max('updated_at'),
            \App\Models\Question::max('updated_at'),
            // The floor plans and their markers.
            \App\Models\IndoorMap::max('updated_at'),
            \App\Models\IndoorMapSpot::max('updated_at'),
            // The outposts, their 3D objects and the models those point at.
            \App\Models\GameLocation::max('updated_at'),
            \App\Models\Hotspot::max('updated_at'),
            \App\Models\ArModel::max('updated_at'),
            // Outdoor posts, routes and their markers.
            \App\Models\QuestLocation::max('updated_at'),
            \App\Models\MapRoute::max('updated_at'),
            \App\Models\MapRouteMarker::max('updated_at'),
            // Panduan, and the crew's bell choice — both travel in the manifest.
            \App\Models\UserGuideSection::max('updated_at'),
            \App\Models\AppSetting::whereIn('key', \App\Support\BellChoice::SETTING_KEYS)->max('updated_at'),
        ])->filter()->max();

        $latest = $ttl > 0
            ? \Illuminate\Support\Facades\Cache::remember('offline:content-version', $ttl, $read)
            : $read();

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
     * The payload is built in App\Services\OfflineManifest, where the part that is identical for
     * every team — which is nearly all of it, sealed quiz content included — is computed once per
     * content version instead of once per sync (SCALING-PLAN.md §3c). This method is the HTTP side
     * of it: who is asking, and whether they already have it.
     *
     * **The ETag is the second half of the saving.** A manifest is ~59 KB and a team re-syncs
     * whenever it has signal, so the usual answer to a sync is "nothing has changed since yours".
     * A client that sends back `If-None-Match` gets a 304 with no body at all. It is per-team
     * because the floor plan and the claimed objects are, hence `private` — a shared cache must not
     * hand one team's plan to another.
     */
    public function manifest(\Illuminate\Http\Request $request)
    {
        $response = response()->json(
            app(\App\Services\OfflineManifest::class)->forUser($request->user())
        );

        $response->setEtag(md5($response->getContent()));
        $response->setPrivate();
        // Turns this into a bodyless 304 when the client already holds this exact manifest.
        $response->isNotModified($request);

        return $response;
    }
}
