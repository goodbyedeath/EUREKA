<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\TeamMember;
use App\Services\PointsCalculationService;
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

    /** Name a team and fill it in one call. */
    public function store(Request $request)
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
            'members.*.name' => 'required|string|max:100',
            'members.*.email' => 'required|email|max:150',
            'members.*.phone' => 'nullable|string|max:30',
            'members.*.position' => 'nullable|string|max:100',
        ]);

        // TeamForm rejects a submission whose emails collide; so does this.
        $emails = array_map(fn ($m) => strtolower(trim($m['email'])), $data['members']);
        if (count($emails) !== count(array_unique($emails))) {
            return response()->json([
                'success' => false,
                'error' => 'duplicate_emails',
                'message' => 'Each member needs a different email address.',
            ], 422);
        }

        $team = DB::transaction(function () use ($data, $user) {
            $team = Team::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'department' => $data['department'] ?? null,
                'initial_points' => 1000,
                'points' => 1000,
                'created_by' => $user->id,
            ]);

            foreach ($data['members'] as $i => $m) {
                TeamMember::create([
                    'team_id' => $team->id,
                    'name' => $m['name'],
                    'email' => $m['email'],
                    'phone' => $m['phone'] ?? null,
                    'position' => $m['position'] ?? null,
                    // The account that created the team leads it, as on the web.
                    'is_leader' => strtolower(trim($m['email'])) === strtolower((string) $user->email) || $i === 0,
                ]);
            }

            $user->team_id = $team->id;
            $user->save();

            return $team;
        });

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
