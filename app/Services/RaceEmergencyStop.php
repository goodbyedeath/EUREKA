<?php

namespace App\Services;

use App\Models\GameAssessment;
use App\Models\GameLocationUnlock;
use App\Models\QrCodeScan;
use App\Models\QuizAttempt;
use App\Models\RaceReset;
use App\Models\RaceSession;
use App\Models\Team;
use App\Models\User;
use App\Models\UserQuestCheckpoint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;

/**
 * Emergency stop (operator, 14 Sep): put the running session back to before the race started, for
 * every team at once — and keep the teams.
 *
 * Wiped for every player account: race clocks, quiz attempts (their answers and game assessments go
 * with them by cascade), facilitator photos, QR scans, check-ins and live positions, opened indoor
 * posts, GPS tracks. Every team's points go back to its starting balance.
 * Kept: teams, members, accounts, tokens, access windows, and all authored content.
 *
 * No backup — the operator's choice. What is kept is a log row with the counts, and the deleted
 * attempt/assessment ids, so an app still holding one gets `409 race_reset` rather than "not found".
 * Teams start again by scanning START.
 */
class RaceEmergencyStop
{
    public const CONFIRM_WORD = 'STOP RACE';

    public function preview(): array
    {
        $ids = User::where('role', 'user')->pluck('id');

        return [
            'teams' => Team::count(),
            'running_clocks' => RaceSession::whereIn('user_id', $ids)->whereNotNull('started_at')->whereNull('finished_at')->count(),
            'race_sessions' => RaceSession::whereIn('user_id', $ids)->count(),
            'attempts' => QuizAttempt::whereIn('user_id', $ids)->count(),
            'assessments' => GameAssessment::whereIn('user_id', $ids)->count(),
            'scans' => QrCodeScan::whereIn('user_id', $ids)->count(),
            'checkins' => UserQuestCheckpoint::whereIn('user_id', $ids)->count(),
            'unlocks' => GameLocationUnlock::whereIn('user_id', $ids)->count(),
            'teams_with_points' => Team::whereRaw('points <> COALESCE(initial_points, 1000)')->count(),
        ];
    }

    public function stop(User $admin): RaceReset
    {
        $disk = Storage::disk('local');

        return DB::transaction(function () use ($admin, $disk) {
            $ids = User::where('role', 'user')->lockForUpdate()->pluck('id');
            $summary = $this->preview();

            $attemptIds = QuizAttempt::whereIn('user_id', $ids)->pluck('id');
            $assessments = GameAssessment::whereIn('quiz_attempt_id', $attemptIds)->get(['id', 'facilitator_photo']);
            $photos = $assessments->pluck('facilitator_photo')
                ->filter(fn ($p) => str_starts_with((string) $p, 'facilitator-photos/'))
                ->values()->all();

            $reset = RaceReset::create(['reset_by' => $admin->id, 'reset_at' => now(), 'summary' => $summary]);

            $refs = $attemptIds->map(fn ($id) => ['kind' => 'attempt', 'ref_id' => $id])
                ->merge($assessments->map(fn ($a) => ['kind' => 'assessment', 'ref_id' => $a->id]))
                ->map(fn ($r) => $r + ['race_reset_id' => $reset->id])
                ->all();
            foreach (array_chunk($refs, 1000) as $chunk) {
                DB::table('race_reset_refs')->insert($chunk);
            }

            QuizAttempt::whereIn('id', $attemptIds)->delete();          // answers + assessments cascade
            QrCodeScan::whereIn('user_id', $ids)->delete();
            RaceSession::whereIn('user_id', $ids)->delete();
            UserQuestCheckpoint::whereIn('user_id', $ids)->delete();    // check-ins and live positions
            GameLocationUnlock::whereIn('user_id', $ids)->delete();
            DB::table('gps_footprints')->whereIn('user_id', $ids)->delete();
            DB::table('gps_tracking_sessions')->whereIn('user_id', $ids)->delete();
            Team::query()->update(['points' => DB::raw('COALESCE(initial_points, 1000)')]);

            // Files and cache are not transactional: touch them only once the rows are gone for good.
            DB::afterCommit(function () use ($disk, $photos, $ids) {
                if ($photos) {
                    $disk->delete($photos);
                }
                foreach ($ids as $id) {
                    RateLimiter::clear('facilitator-pin:'.$id);
                    RateLimiter::clear('quiz_attempts:'.$id);
                }
            });

            return $reset;
        });
    }
}
