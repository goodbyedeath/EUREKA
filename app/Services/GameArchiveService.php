<?php

namespace App\Services;

use App\Models\GameArchive;
use App\Models\GameArchiveCredential;
use App\Models\GameAssessment;
use App\Models\GameLocation;
use App\Models\Questionnaire;
use App\Models\QuizAttempt;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Ends a game session (operator, 14 Sep): snapshot → tombstones → removal from the live system.
 *
 * There is no "session" table: the live system holds one session at a time, so a session is every
 * team and every player account present when the admin archives. After archiving, those accounts
 * no longer exist, and any APK still installed with one of them gets `403 game_ended` on login and
 * on every token-authenticated call — for the security of the running system and the venue network.
 *
 * Kept: questionnaires, posts, maps, AR, branding — the next session reuses them.
 * Removed: teams, members, player accounts, tokens, web sessions and, by the database's own
 * cascades, attempts, answers, assessments, QR scans, race runs, unlocks, check-ins and GPS.
 */
class GameArchiveService
{
    public const CONFIRM_WORD = 'ARSIPKAN';

    public const ENDED_MESSAGE = 'Game sudah berakhir. Terima kasih telah bermain — silakan uninstall aplikasi ini.';

    public function __construct(private PointsCalculationService $points)
    {
    }

    /** What an archive made now would contain, for the admin to check first. */
    public function preview(): array
    {
        $counted = $this->countedStationIds();

        $teams = Team::withCount('members')->orderBy('name')->get()->map(function (Team $t) use ($counted) {
            $accountIds = User::where('team_id', $t->id)->pluck('id');

            return [
                'id' => $t->id,
                'name' => $t->name,
                'members' => $t->members_count,
                'accounts' => $accountIds->count(),
                'score' => $this->points->teamScore($t)['total'],
                'stations_done' => QuizAttempt::whereIn('user_id', $accountIds)
                    ->where('status', QuizAttempt::STATUS_COMPLETED)
                    ->whereIn('questionnaire_id', $counted)
                    ->distinct()
                    ->count('questionnaire_id'),
                'stations_total' => $counted->count(),
            ];
        })->sortByDesc('score')->values();

        return [
            'teams' => $teams,
            'unfinished' => $teams->filter(fn ($t) => $t['stations_done'] < $t['stations_total'])->values(),
            'accounts' => User::where('role', 'user')->count(),
            'accounts_without_team' => User::where('role', 'user')->whereNull('team_id')->count(),
            'live_sessions' => QuizAttempt::where('status', QuizAttempt::STATUS_STARTED)->whereNull('completed_at')->count(),
            'stations_total' => $counted->count(),
        ];
    }

    public function archive(string $name, ?string $notes, User $admin): GameArchive
    {
        $disk = Storage::disk('local');
        $dir = 'game-archives/'.now()->format('Ymd-His').'-'.Str::lower(Str::random(6));
        $originals = [];

        try {
            return DB::transaction(function () use ($name, $notes, $admin, $disk, $dir, &$originals) {
                $iso = fn ($v) => $v ? Carbon::parse($v)->toIso8601String() : null;

                $teams = Team::with(['members' => fn ($q) => $q->orderByDesc('is_leader')->orderBy('name')])
                    ->orderBy('id')->lockForUpdate()->get();
                $accounts = User::where('role', 'user')->orderBy('id')->lockForUpdate()->get();
                $accountIds = $accounts->pluck('id');
                $stations = Questionnaire::orderBy('id')->get(['id', 'title']);
                $counted = $this->countedStationIds();

                $attempts = QuizAttempt::with([
                    'userAnswers.question:id,question,type,points',
                    'gameAssessments.question:id,game_name,question,points',
                ])->whereIn('user_id', $accountIds)->orderBy('started_at')->get();

                $photos = [];

                $attemptRow = function (QuizAttempt $a, int $base) use ($disk, $dir, $iso, &$photos, &$originals) {
                    $verification = null;
                    if (is_string($a->verification_photo) && $a->verification_photo !== '') {
                        $bytes = base64_decode(preg_replace('#^data:image/[a-z]+;base64,#i', '', $a->verification_photo), true);
                        if ($bytes !== false && $bytes !== '') {
                            $verification = 'v-'.$a->id;
                            $photos[$verification] = "$dir/$verification.jpg";
                            $disk->put($photos[$verification], $bytes);
                        }
                    }

                    return [
                        'attempt_id' => $a->id,
                        'status' => $a->status,
                        'started_at' => $iso($a->started_at),
                        'completed_at' => $iso($a->completed_at),
                        'total_time_seconds' => $a->total_time_seconds,
                        'auto_submitted' => (bool) $a->auto_submitted,
                        'verification_photo' => $verification,
                        'answers' => $a->userAnswers->map(fn ($ans) => [
                            'question' => $ans->question?->question,
                            'type' => $ans->question?->type,
                            'answer' => $ans->answer,
                            'is_correct' => (bool) $ans->is_correct,
                            'points_earned' => (int) $ans->points_earned,
                        ])->values()->all(),
                        'games' => $a->gameAssessments->map(function (GameAssessment $g) use ($disk, $dir, $iso, $base, &$photos, &$originals) {
                            $photo = null;
                            $path = (string) $g->facilitator_photo;
                            if (str_starts_with($path, 'facilitator-photos/') && $disk->exists($path)) {
                                $photo = 'f-'.$g->id;
                                $photos[$photo] = "$dir/$photo.".(pathinfo($path, PATHINFO_EXTENSION) ?: 'jpg');
                                $disk->copy($path, $photos[$photo]);
                                $originals[] = $path;
                            }

                            return [
                                'game_name' => $g->question?->game_name ?: $g->question?->question,
                                'max_points' => (int) ($g->question?->points ?? 0),
                                'is_assessed' => (bool) $g->is_assessed,
                                'additional_points' => $g->is_assessed ? (int) $g->additional_points : null,
                                'penalty' => $g->is_assessed ? (int) $g->penalty : null,
                                // Same gain the team score used: total_deposit − the team's base.
                                'gain' => $g->is_assessed ? (int) $g->total_deposit - $base : null,
                                'notes' => $g->notes,
                                'assessed_at' => $iso($g->assessed_at),
                                'facilitator_photo' => $photo,
                            ];
                        })->values()->all(),
                    ];
                };

                $teamRows = $teams->map(function (Team $t) use ($accounts, $attempts, $stations, $counted, $attemptRow, $iso) {
                    $base = (int) ($t->initial_points ?? 1000);
                    $mine = $accounts->where('team_id', $t->id);
                    $theirs = $attempts->whereIn('user_id', $mine->pluck('id')->all());

                    return [
                        'team_id' => $t->id,
                        'name' => $t->name,
                        'department' => $t->department,
                        'description' => $t->description,
                        'registered_at' => $iso($t->created_at),
                        'score' => $this->points->teamScore($t),
                        'stations_done' => $theirs->where('status', QuizAttempt::STATUS_COMPLETED)
                            ->whereIn('questionnaire_id', $counted->all())->pluck('questionnaire_id')->unique()->count(),
                        'stations_total' => $counted->count(),
                        'members' => $t->members->map(fn ($m) => [
                            'name' => $m->name,
                            'email' => $m->email,
                            'phone' => $m->phone,
                            'position' => $m->position,
                            'is_leader' => (bool) $m->is_leader,
                        ])->values()->all(),
                        'accounts' => $mine->map(fn (User $u) => [
                            'name' => $u->name,
                            'email' => $u->email,
                            'last_login_at' => $iso($u->last_login_at),
                        ])->values()->all(),
                        'stations' => $theirs->groupBy('questionnaire_id')->map(fn ($group, $qid) => [
                            'questionnaire_id' => (int) $qid,
                            'title' => $stations->firstWhere('id', (int) $qid)?->title ?? 'Pos #'.$qid,
                            'counted' => $counted->contains((int) $qid),
                            'completed' => $group->contains('status', QuizAttempt::STATUS_COMPLETED),
                            'attempts' => $group->map(fn ($a) => $attemptRow($a, $base))->values()->all(),
                        ])->values()->all(),
                    ];
                })->sortByDesc(fn ($r) => $r['score']['total'])->values();

                // Competition ranking: equal totals share a rank.
                $rank = 0;
                $previous = null;
                $teamRows = $teamRows->map(function ($r, $i) use (&$rank, &$previous) {
                    if ($r['score']['total'] !== $previous) {
                        $rank = $i + 1;
                        $previous = $r['score']['total'];
                    }
                    $r['rank'] = $rank;

                    return $r;
                });

                $loose = $accounts->whereNull('team_id')->map(fn (User $u) => [
                    'name' => $u->name,
                    'email' => $u->email,
                    'last_login_at' => $iso($u->last_login_at),
                    'attempts' => $attempts->where('user_id', $u->id)->map(fn ($a) => $attemptRow($a, 1000))->values()->all(),
                ])->values()->all();

                $winner = $teamRows->first();

                $archive = GameArchive::create([
                    'name' => $name,
                    'notes' => $notes,
                    'snapshot' => [
                        'version' => 1,
                        'name' => $name,
                        'notes' => $notes,
                        'archived_at' => now()->toIso8601String(),
                        'archived_by' => ['id' => $admin->id, 'name' => $admin->name],
                        'stations' => $stations->map(fn ($s) => [
                            'id' => $s->id, 'title' => $s->title, 'counted' => $counted->contains($s->id),
                        ])->values()->all(),
                        'leaderboard' => $teamRows->map(fn ($r) => [
                            'rank' => $r['rank'],
                            'team_id' => $r['team_id'],
                            'name' => $r['name'],
                            'total' => $r['score']['total'],
                            'base_points' => $r['score']['base_points'],
                            'earned_points' => $r['score']['earned_points'],
                            'assessment_points' => $r['score']['assessment_points'],
                            'stations_done' => $r['stations_done'],
                            'stations_total' => $r['stations_total'],
                        ])->values()->all(),
                        'teams' => $teamRows->all(),
                        'accounts_without_team' => $loose,
                        'photos' => $photos,
                    ],
                    'team_count' => $teams->count(),
                    'account_count' => $accounts->count(),
                    'winner_name' => $winner['name'] ?? null,
                    'winner_score' => $winner['score']['total'] ?? null,
                    'storage_dir' => $dir,
                    'archived_by' => $admin->id,
                    'archived_at' => now(),
                ]);

                // Participants keep their team's final total; the sender delivers it after the teams are gone.
                app(FekdiIntegration::class)->refreshPoints();

                // Tombstones, taken before the rows they describe are deleted.
                $morph = (new User)->getMorphClass();
                $tokens = DB::table('personal_access_tokens')
                    ->where('tokenable_type', $morph)->whereIn('tokenable_id', $accountIds)->pluck('token');
                $rows = $accounts->map(fn (User $u) => ['kind' => 'email', 'value' => strtolower(trim($u->email))])
                    ->merge($tokens->map(fn ($hash) => ['kind' => 'token', 'value' => $hash]))
                    ->map(fn ($r) => $r + ['game_archive_id' => $archive->id, 'created_at' => now(), 'updated_at' => now()])
                    ->all();
                foreach (array_chunk($rows, 500) as $chunk) {
                    GameArchiveCredential::insert($chunk);
                }

                // A location authored for one player would be deleted with that player by the
                // cascade; keep the authored content, switched off.
                GameLocation::whereIn('target_user_id', $accountIds)->update(['target_user_id' => null, 'is_active' => false]);

                DB::table('personal_access_tokens')->where('tokenable_type', $morph)->whereIn('tokenable_id', $accountIds)->delete();
                DB::table('sessions')->whereIn('user_id', $accountIds)->delete();
                User::whereIn('id', $accountIds)->delete();            // cascades the player's history
                Team::whereIn('id', $teams->pluck('id'))->delete();    // cascades team_members

                // The copies live in the archive now; drop the originals once the rows are gone for good.
                DB::afterCommit(function () use ($disk, &$originals) {
                    if ($originals) {
                        $disk->delete($originals);
                    }
                });

                return $archive;
            });
        } catch (\Throwable $e) {
            $disk->deleteDirectory($dir);
            throw $e;
        }
    }

    public static function archiveForEmail(?string $email): ?GameArchive
    {
        return self::archiveFor('email', strtolower(trim((string) $email)));
    }

    /** $bearer is the plain "id|secret" token the app holds; Sanctum stores sha256(secret). */
    public static function archiveForToken(?string $bearer): ?GameArchive
    {
        if (! $bearer) {
            return null;
        }
        $secret = str_contains($bearer, '|') ? substr($bearer, strpos($bearer, '|') + 1) : $bearer;

        return self::archiveFor('token', hash('sha256', $secret));
    }

    public static function gameEndedResponse(GameArchive $archive)
    {
        return response()->json([
            'success' => false,
            'error' => 'game_ended',
            'game_ended' => true,
            'message' => self::ENDED_MESSAGE,
            'archive' => [
                'name' => $archive->name,
                'ended_at' => $archive->archived_at?->toIso8601String(),
            ],
        ], 403);
    }

    private static function archiveFor(string $kind, string $value): ?GameArchive
    {
        if ($value === '') {
            return null;
        }
        $id = GameArchiveCredential::where('kind', $kind)->where('value', $value)->latest('id')->value('game_archive_id');

        return $id ? GameArchive::select(['id', 'name', 'archived_at'])->find($id) : null;
    }

    private function countedStationIds()
    {
        return Questionnaire::where('is_active', true)->where('counts_toward_finish', true)->pluck('id');
    }
}
