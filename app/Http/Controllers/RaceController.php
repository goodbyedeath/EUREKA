<?php

namespace App\Http\Controllers;

use App\Models\IndoorMap;
use App\Models\RaceSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * The indoor opening sequence: START scan, then the clue, then the plan.
 *
 * Posts are random and handed out by crew, so this is a single gate at the beginning of
 * the run — there is deliberately no per-post ordering anywhere in here.
 */
class RaceController extends Controller
{
    /**
     * Begin a run. Called by the scanner when the code matches a venue's START code.
     *
     * Re-scanning is harmless and does not restart the clock: teams scan twice by mistake,
     * and a race timer that resets on a fumbled scan would be worse than no timer at all.
     */
    public function start(Request $request, string $code)
    {
        $start = \App\Models\RaceStart::with('indoorMap')
            ->where('code', $code)->where('is_active', true)->first();

        if (! $start) {
            return redirect()->route('user.dashboard')
                ->with('error', __('That is not the start code for this event.'));
        }

        // The plan is the team's when one is assigned; the START code only decides indoor vs
        // outdoor and starts the clock (operator, 16 Sep).
        $mapId = $start->indoor_map_id
            ? (\App\Models\IndoorMap::assignedTo($request->user())?->id ?? $start->indoor_map_id)
            : null;

        $session = RaceSession::firstOrCreate(
            ['user_id' => $request->user()->id, 'indoor_map_id' => $mapId],
            ['started_at' => now(), 'race_start_id' => $start->id],
        );

        // Re-scanning must not restart the clock: teams scan twice by mistake, and a race
        // timer that resets on a fumbled scan is worse than no timer at all.
        if (! $session->started_at) {
            $session->update(['started_at' => now(), 'race_start_id' => $start->id]);
        }

        Log::info('Race started', [
            'user_id' => $request->user()->id,
            'race_start_id' => $start->id,
            'mode' => $start->isIndoor() ? 'indoor' : 'outdoor',
        ]);

        // Indoor goes on to the clue and then the plan. Outdoor has neither — the team
        // simply reads the GPS map — so the clock starts and they carry on.
        return $start->isIndoor()
            ? redirect()->route('user.race.clue', $start->indoor_map_id)
            : redirect()->route('user.dashboard')->with('success', __('Race started. Good luck!'));
    }

    /**
     * The same start line, for a client that holds a token instead of a cookie.
     *
     * `start()` above answers with a 302 to an HTML page, which a native app cannot
     * follow — so before this existed the APK could scan the START code, be told what it
     * was, and still have no way to set the clock running. Everything that matters is in
     * the body instead: whether the run had already begun, and where the app should go.
     *
     * Deliberately the same firstOrCreate as the web path, so a team that scans on the
     * phone and again in the browser shares one clock rather than starting a second.
     */
    public function apiStart(Request $request)
    {
        $data = $request->validate(['code' => 'required|string|max:255']);

        $start = \App\Models\RaceStart::with('indoorMap')
            ->where('code', $data['code'])->where('is_active', true)->first();

        if (! $start) {
            return response()->json([
                'success' => false,
                'error' => 'unknown_start_code',
                'message' => __('That is not the start code for this event.'),
            ], 404);
        }

        // The plan is the team's when one is assigned; the START code only decides indoor vs
        // outdoor and starts the clock (operator, 16 Sep).
        $mapId = $start->indoor_map_id
            ? (\App\Models\IndoorMap::assignedTo($request->user())?->id ?? $start->indoor_map_id)
            : null;

        $session = RaceSession::firstOrCreate(
            ['user_id' => $request->user()->id, 'indoor_map_id' => $mapId],
            ['started_at' => now(), 'race_start_id' => $start->id],
        );

        // True only on the scan that actually set the clock going. A second scan is a
        // normal thing for a team to do and must not read as an error, but the app also
        // should not celebrate a start that already happened an hour ago.
        $justStarted = $session->wasRecentlyCreated;

        if (! $session->started_at) {
            $session->update(['started_at' => now(), 'race_start_id' => $start->id]);
            $justStarted = true;
        }

        Log::info('Race started (api)', [
            'user_id' => $request->user()->id,
            'race_start_id' => $start->id,
            'mode' => $start->isIndoor() ? 'indoor' : 'outdoor',
            'already_running' => ! $justStarted,
        ]);

        $session->refresh();

        return response()->json([
            'success' => true,
            'just_started' => $justStarted,
            'race' => [
                'session_id' => $session->id,
                'started_at' => $session->started_at?->toIso8601String(),
                'elapsed_seconds' => $session->elapsedSeconds(),
                'finished_at' => $session->finished_at?->toIso8601String(),
                // 'indoor' -> answer the clue, then read the plan; 'outdoor' -> straight
                // to the GPS map. This is the only difference between the two modes.
                'mode' => $start->isIndoor() ? 'indoor' : 'outdoor',
                'indoor_map_id' => $start->indoor_map_id,
            ],
            // Present for indoor only, and null once the team has answered it. The answer
            // itself is never sent — it is checked server-side by answerClue().
            'clue' => $start->isIndoor() && ! $session->clueSolved()
                ? ['question' => $start->indoorMap?->clue_question]
                : null,
        ]);
    }

    /**
     * The clue that must be answered before the plan is shown.
     */
    public function clue(Request $request, $mapId)
    {
        $map = IndoorMap::where('is_active', true)->findOrFail($mapId);
        $session = $this->sessionFor($request, $map);

        if (! $session || ! $session->hasStarted()) {
            return redirect()->route('user.dashboard')
                ->with('error', __('Scan the START code first.'));
        }

        // Already past this gate — no reason to make them answer twice.
        if ($session->clueSolved()) {
            return redirect()->route('user.indoor-map', $map->id);
        }

        // A venue with no clue set skips the gate rather than trapping the team.
        if (blank($map->clue_question)) {
            $session->update(['clue_solved_at' => now()]);

            return redirect()->route('user.indoor-map', $map->id);
        }

        return view('user.race-clue', ['map' => $map, 'session' => $session, 'wrong' => false]);
    }

    public function answerClue(Request $request, $mapId)
    {
        $data = $request->validate(['answer' => 'required|string|max:255']);

        $map = IndoorMap::where('is_active', true)->findOrFail($mapId);
        $session = $this->sessionFor($request, $map);

        if (! $session || ! $session->hasStarted()) {
            return redirect()->route('user.dashboard')
                ->with('error', __('Scan the START code first.'));
        }

        if ($session->clueSolved()) {
            return redirect()->route('user.indoor-map', $map->id);
        }

        // Forgiving comparison: this is a treasure hunt, not a spelling test, and a team
        // standing in a corridor should not lose to a trailing space.
        $given = mb_strtolower(trim(preg_replace('/\s+/', ' ', $data['answer'])));
        $expected = mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $map->clue_answer)));

        if ($given !== $expected) {
            $session->increment('clue_wrong_attempts');

            // The flow asks for a red cross and another go — no lockout.
            return view('user.race-clue', [
                'map' => $map,
                'session' => $session->fresh(),
                'wrong' => true,
            ]);
        }

        $session->update(['clue_solved_at' => now()]);
        $this->openPostsAfterClue($session, $map);

        return redirect()->route('user.indoor-map', $map->id);
    }

    /**
     * Where is this team's run right now? Asked when the app comes back from the dead.
     *
     * A native client can be killed mid-race and has no cookie to remember the run by, so
     * without this it cannot tell a team that has not started from one already forty
     * minutes in. The elapsed count comes from the server for the same reason the quiz
     * timer does: a phone that slept is not a clock.
     */
    public function apiStatus(Request $request)
    {
        // Sessions are keyed on (user_id, indoor_map_id), so a team that scanned both an
        // outdoor and an indoor start code has two. `id` breaks the tie when both began in
        // the same second, which ordering on started_at alone leaves to the database.
        $session = RaceSession::with('indoorMap')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->first();

        // Always sent, session or not: it is what lets a client know the cached world is void.
        $resetAt = \App\Models\RaceReset::lastAt()?->toIso8601String();

        if (! $session) {
            return response()->json(['success' => true, 'race' => null, 'race_reset_at' => $resetAt,
                'content_updated_at' => \App\Http\Controllers\Api\OfflineController::contentUpdatedAt()]);
        }

        return response()->json([
            'success' => true,
            'race_reset_at' => $resetAt,
            // Newer than the value stored at Sync means the crew edited a questionnaire or a plan.
            'content_updated_at' => \App\Http\Controllers\Api\OfflineController::contentUpdatedAt(),
            'race' => [
                'session_id' => $session->id,
                'started_at' => $session->started_at?->toIso8601String(),
                'elapsed_seconds' => $session->elapsedSeconds(),
                'finished_at' => $session->finished_at?->toIso8601String(),
                'finished' => $session->finished_at !== null,
                'mode' => $session->indoor_map_id ? 'indoor' : 'outdoor',
                'indoor_map_id' => $session->indoor_map_id,
                'clue_solved' => $session->clueSolved(),
                'clue_wrong_attempts' => (int) $session->clue_wrong_attempts,
            ],
            'clue' => $session->indoor_map_id && ! $session->clueSolved()
                ? ['question' => $session->indoorMap?->clue_question]
                : null,
        ]);
    }

    /**
     * Answer the indoor clue from a native client.
     *
     * A wrong answer is a 200 with `correct: false`, not an error: getting it wrong is a
     * normal move in the game and the flow explicitly asks for a red cross and another
     * go, with no lockout. Only a team that never scanned START gets a 403.
     */
    public function apiAnswerClue(Request $request, $mapId)
    {
        $data = $request->validate(['answer' => 'required|string|max:255']);

        $map = IndoorMap::where('is_active', true)->findOrFail($mapId);
        $session = $this->sessionFor($request, $map);

        if (! $session || ! $session->hasStarted()) {
            return response()->json([
                'success' => false,
                'error' => 'race_not_started',
                'message' => __('Scan the START code first.'),
            ], 403);
        }

        if ($session->clueSolved()) {
            return response()->json(['success' => true, 'correct' => true, 'already_solved' => true,
                                     'indoor_map_id' => $map->id]);
        }

        // Same forgiving comparison as the web path, deliberately: a team standing in a
        // corridor should not lose to a trailing space or a capital letter.
        $given = mb_strtolower(trim(preg_replace('/\s+/', ' ', $data['answer'])));
        $expected = mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $map->clue_answer)));

        if ($given !== $expected) {
            $session->increment('clue_wrong_attempts');

            return response()->json([
                'success' => true,
                'correct' => false,
                'clue_wrong_attempts' => (int) $session->fresh()->clue_wrong_attempts,
                'message' => __('Not quite — try again.'),
            ]);
        }

        $session->update(['clue_solved_at' => now()]);
        $this->openPostsAfterClue($session, $map);

        return response()->json([
            'success' => true,
            'correct' => true,
            'already_solved' => false,
            'indoor_map_id' => $map->id,
        ]);
    }

    /**
     * A correct START clue opens every post on that floor plan for the team (operator, 15 Sep — APK
     * report #21). It writes the same unlock rows the crew's Outpost Access panel writes, so the
     * floor plan's is_open, the station gate and the AR gate all agree without a second rule — and
     * the crew can still close a post for that team afterwards, as they always could.
     */
    private function openPostsAfterClue(RaceSession $session, IndoorMap $map): void
    {
        $locationIds = \App\Models\IndoorMapSpot::where('indoor_map_id', $map->id)
            ->where('is_active', true)
            ->whereNotNull('game_location_id')
            ->distinct()
            ->pluck('game_location_id');

        foreach ($locationIds as $locationId) {
            \App\Models\GameLocationUnlock::updateOrCreate(
                ['game_location_id' => $locationId, 'user_id' => $session->user_id],
                ['granted_by' => null, 'granted_at' => now(), 'revoked_at' => null],
            );
        }
    }

    private function sessionFor(Request $request, IndoorMap $map): ?RaceSession
    {
        return RaceSession::where('user_id', $request->user()->id)
            ->where('indoor_map_id', $map->id)
            ->first();
    }
}
