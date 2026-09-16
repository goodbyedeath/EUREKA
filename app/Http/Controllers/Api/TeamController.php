<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\TeamMember;
use App\Services\PointsCalculationService;
use App\Services\FekdiIntegration;
use App\Models\FekdiParticipant;
use App\Exceptions\TeamSetupException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * The player's own team: naming it, filling it, and reading its score.
 *
 * These exist because the Android app is the whole player experience now, and team registration
 * was the one step still only reachable as a Livewire page — invisible to a native client by
 * construction. The rules below mirror `App\Livewire\Forms\TeamForm` exactly rather than
 * inventing a second set: same validation, same one-team-per-account limit, same 1000 starting
 * points. Two ways to create a team that disagree would be worse than one that is awkward.
 *
 * Reading other teams' scores is deliberately NOT here, and neither is a rank: the operator chose
 * total + breakdown only (14 Sep). The full table belongs to the kiosk screen.
 *
 * Members are fixed at registration (operator, 14 Sep): POST /team sets them once.
 */
class TeamController extends Controller
{
    /** The caller's team, with members and standing. */
    public function show(Request $request)
    {
        $team = $this->teamFor($request);

        if (! $team) {
            return response()->json([
                'success' => false,
                'error' => 'no_team',
                'message' => 'You are not on a team yet.',
                // Team setup offers "already registered" members only while this is true.
                'participant_directory' => app(FekdiIntegration::class)->active(),
            ], 404);
        }

        // The same number /admin/user-progress and the kiosk show.
        $score = app(PointsCalculationService::class)->teamScore($team);

        return response()->json([
            'success' => true,
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
                'description' => $team->description,
                'department' => $team->department,
                'score' => $score,
                // Kept for older builds; both derive from score.
                'points' => $score['total'],
                'initial_points' => $score['base_points'],
                'earned' => $score['total'] - $score['base_points'],
                'is_owner' => $team->created_by === Auth::id(),
                'members' => $team->members()
                    ->orderByDesc('is_leader')
                    ->orderBy('name')
                    ->get()
                    ->map(fn (TeamMember $m) => [
                        'id' => $m->id,
                        'name' => $m->name,
                        'email' => $m->email,
                        'phone' => $m->phone,
                        'position' => $m->position,
                        'is_leader' => (bool) $m->is_leader,
                    ]),
            ],
        ]);
    }

    /**
     * Name a team and fill it in one call. Each member is typed in, or picked from the FEKDI x IFSE
     * participant list with `participant_id` (operator, 15 Sep: both ways stay available).
     */
    public function store(Request $request, FekdiIntegration $fekdi)
    {
        $user = Auth::user();

        // Same guard as TeamForm::mount(): one team per account, whether joined or created.
        if ($user->team_id || Team::where('created_by', $user->id)->exists()) {
            return response()->json([
                'success' => false,
                'error' => 'team_exists',
                'message' => 'You already belong to a team.',
            ], 409);
        }

        $data = $request->validate([
            'name' => 'required|string|min:3|max:100',
            'description' => 'nullable|string|max:500',
            'department' => 'nullable|string|max:100',
            'members' => 'required|array|min:1|max:20',
            'members.*.participant_id' => 'nullable|integer',
            'members.*.name' => 'required_without:members.*.participant_id|nullable|string|max:100',
            'members.*.email' => 'required_without:members.*.participant_id|nullable|email|max:150',
            'members.*.phone' => 'nullable|string|max:30',
            'members.*.position' => 'nullable|string|max:100',
            'members.*.is_leader' => 'nullable|boolean',
        ]);

        $picked = collect($data['members'])->pluck('participant_id')->filter()->map(fn ($v) => (int) $v)->values();

        try {
            if ($picked->isNotEmpty() && ! $fekdi->active()) {
                throw new TeamSetupException('directory_disabled', 'Daftar peserta sedang tidak aktif. Tambahkan anggota secara manual.', 409);
            }
            if ($picked->count() !== $picked->unique()->count()) {
                throw new TeamSetupException('duplicate_participants', 'The same participant was added twice.', 422);
            }
            if (collect($data['members'])->filter(fn ($m) => ! empty($m['is_leader']))->count() > 1) {
                throw new TeamSetupException('one_leader_only', 'A team has one leader.', 422);
            }

            DB::transaction(function () use ($data, $user, $picked) {
                $participants = FekdiParticipant::whereIn('id', $picked)->lockForUpdate()->get()->keyBy('id');

                if ($missing = $picked->first(fn ($id) => ! $participants->has($id))) {
                    throw new TeamSetupException('participant_not_found', 'That participant is not in the list.', 404, ['participant_id' => $missing]);
                }
                if ($taken = $participants->first(fn ($p) => $p->team_id !== null)) {
                    throw new TeamSetupException('participant_taken', "{$taken->name} sudah terdaftar di tim {$taken->team_name}.", 409, ['participant_id' => $taken->id, 'team_name' => $taken->team_name]);
                }

                $members = collect($data['members'])->map(function ($m) use ($participants) {
                    $p = ! empty($m['participant_id']) ? $participants[(int) $m['participant_id']] : null;

                    return [
                        'participant' => $p,
                        'name' => $p ? ($p->name ?: 'Peserta') : $m['name'],
                        // A participant without an e-mail still needs a unique one per team.
                        'email' => strtolower(trim($p ? ($p->email ?: 'gid-'.$p->google_id.'@fekdi.invalid') : $m['email'])),
                        'phone' => $m['phone'] ?? null,
                        'position' => $m['position'] ?? null,
                        'is_leader' => ! empty($m['is_leader']),
                    ];
                })->values();

                // TeamForm rejects a submission whose emails collide; so does this.
                if ($members->count() !== $members->pluck('email')->unique()->count()) {
                    throw new TeamSetupException('duplicate_emails', 'Each member needs a different email address.', 422);
                }

                $team = Team::create([
                    'name' => $data['name'],
                    'description' => $data['description'] ?? null,
                    'department' => $data['department'] ?? null,
                    // Teams start at zero: the score is what they earn (operator, 16 Sep).
                    'initial_points' => 0,
                    'points' => 0,
                    'created_by' => $user->id,
                ]);

                $explicitLeader = $members->contains('is_leader', true);

                foreach ($members as $i => $m) {
                    // Chosen in the app when given; otherwise, as on the web, the account's own e-mail or the first member.
                    $leader = $explicitLeader
                        ? $m['is_leader']
                        : ($m['email'] === strtolower((string) $user->email) || $i === 0);

                    TeamMember::create([
                        'team_id' => $team->id,
                        'fekdi_participant_id' => $m['participant']?->id,
                        'name' => $m['name'],
                        'email' => $m['email'],
                        'phone' => $m['phone'],
                        'position' => $m['position'],
                        'is_leader' => $leader,
                    ]);

                    // Points start from zero for this team; the next refresh sets the team total and sends it.
                    $m['participant']?->update([
                        'team_id' => $team->id,
                        'team_name' => $team->name,
                        'is_leader' => $leader,
                        'points' => 0,
                        'points_synced' => 0,
                        'sync_state' => 'idle',
                        'sync_error' => null,
                    ]);
                }

                $user->team_id = $team->id;
                $user->save();
            });
        } catch (TeamSetupException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->errorKey,
                'message' => $e->getMessage(),
            ] + $e->context, $e->status);
        }

        return $this->show($request)->setStatusCode(201);
    }

    /**
     * Members are fixed at registration (operator, 14 Sep): POST /team sets them once, and after that
     * only an event admin changes them on the website. The route stays so an older app build gets a
     * clear refusal rather than a 404.
     */
    public function addMember(Request $request)
    {
        return $this->teamLocked();
    }

    /** See addMember(): members are fixed after registration. */
    public function removeMember(Request $request, int $member)
    {
        return $this->teamLocked();
    }

    private function teamLocked()
    {
        return response()->json([
            'success' => false,
            'error' => 'team_locked',
            'message' => 'Team members are fixed after registration. Ask the event admin to change them.',
        ], 403);
    }

    private function teamFor(Request $request): ?Team
    {
        $user = $request->user();

        return $user->team_id
            ? Team::find($user->team_id)
            : Team::where('created_by', $user->id)->first();
    }
}
