<?php

namespace App\Services;

use App\Models\GameLocationUnlock;
use App\Models\Questionnaire;
use App\Models\RaceSession;
use App\Models\UserQuestCheckpoint;
use Illuminate\Http\JsonResponse;

/**
 * Operator, 15 Sep: after an emergency reset, teams scanned a questionnaire QR straight away and
 * answered it — without scanning START or reaching the post. A questionnaire now opens only when:
 *
 *  1. it is linked to a post (unlinked questionnaires cannot be scanned at all);
 *  2. the team has scanned START (a race session exists);
 *  3. the team has checked in at that post — outdoor: a GPS check-in at the Quest Location;
 *     indoor: the crew has opened the Game Location for the team on Outpost Access.
 *
 * Checked by qr/lookup (before the scan is recorded, so a refusal burns no attempt) and again by
 * quiz/start for a new attempt, which closes scans recorded before this rule existed.
 */
class StationGate
{
    /** A JSON refusal, or null when the team may open this questionnaire. */
    public function refusal(int $userId, int $questionnaireId): ?JsonResponse
    {
        if (! config('eureka.station_gate', true)) {
            return null;
        }

        // Read fresh: quiz/start holds a cached copy of the questionnaire that predates any relinking.
        $q = Questionnaire::with(['questLocation:id,name', 'gameLocation:id,name'])
            ->find($questionnaireId, ['id', 'title', 'venue_mode', 'quest_location_id', 'game_location_id']);
        if (! $q) {
            return null;
        }

        $outdoor = $q->venue_mode === 'outdoor' && $q->questLocation;
        $indoor = $q->venue_mode === 'indoor' && $q->gameLocation;

        if (! $outdoor && ! $indoor) {
            return $this->refuse('station_not_linked', 'Pos untuk kuesioner ini belum disiapkan. Hubungi panitia.');
        }

        if (! RaceSession::where('user_id', $userId)->whereNotNull('started_at')->exists()) {
            return $this->refuse('race_not_started', 'Scan QR START terlebih dahulu untuk memulai race.');
        }

        if ($outdoor) {
            $checkedIn = UserQuestCheckpoint::where('user_id', $userId)->where('quest_location_id', $q->quest_location_id)->exists();

            return $checkedIn ? null : $this->refuse(
                'checkin_required',
                "Check-in di pos {$q->questLocation->name} terlebih dahulu.",
                ['post' => ['type' => 'outdoor', 'id' => $q->quest_location_id, 'name' => $q->questLocation->name]],
            );
        }

        $opened = GameLocationUnlock::where('user_id', $userId)->where('game_location_id', $q->game_location_id)
            ->whereNotNull('granted_at')->whereNull('revoked_at')->exists();

        return $opened ? null : $this->refuse(
            'checkin_required',
            "Tunggu kru membuka pos {$q->gameLocation->name} untuk tim Anda.",
            ['post' => ['type' => 'indoor', 'id' => $q->game_location_id, 'name' => $q->gameLocation->name]],
        );
    }

    private function refuse(string $error, string $message, array $extra = []): JsonResponse
    {
        return response()->json(['success' => false, 'error' => $error, 'message' => $message] + $extra, 403);
    }
}
