<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\TeamMember;
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
 * Reading other teams' scores is deliberately NOT here. A player sees their own total and their
 * rank; the full table belongs to the kiosk screen, which is a public display the whole room can
 * see anyway.
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

        // Rank by points, highest first. Counted rather than listed: the player learns where
        // they stand without being handed every other team's score.
        $ahead = Team::where('points', '>', $team->points)->count();

        return response()->json([
            'success' => true,
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
                'description' => $team->description,
                'department' => $team->department,
                'points' => (float) $team->points,
                'initial_points' => (float) $team->initial_points,
                // What the team has actually gained or lost, which is the number players care
                // about. `points` alone looks like a huge score because it includes the 1000
                // everyone starts with.
                'earned' => (float) $team->points - (float) $team->initial_points,
                'rank' => $ahead + 1,
                'total_teams' => Team::count(),
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

    /** Add one member to a team you own. */
    public function addMember(Request $request)
    {
        $team = $this->teamFor($request);

        if (! $team || $team->created_by !== Auth::id()) {
            return response()->json([
                'success' => false,
                'error' => 'not_team_owner',
                'message' => 'Only the account that created the team can change its members.',
            ], 403);
        }

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:150',
            'phone' => 'nullable|string|max:30',
            'position' => 'nullable|string|max:100',
        ]);

        if ($team->members()->whereRaw('LOWER(email) = ?', [strtolower(trim($data['email']))])->exists()) {
            return response()->json([
                'success' => false,
                'error' => 'duplicate_emails',
                'message' => 'Someone on this team already uses that email address.',
            ], 422);
        }

        if ($team->members()->count() >= 20) {
            return response()->json([
                'success' => false,
                'error' => 'team_full',
                'message' => 'A team can hold at most 20 members.',
            ], 409);
        }

        TeamMember::create([
            'team_id' => $team->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'position' => $data['position'] ?? null,
            'is_leader' => false,
        ]);

        return $this->show($request)->setStatusCode(201);
    }

    /** Remove a member from a team you own. The leader cannot be removed. */
    public function removeMember(Request $request, int $member)
    {
        $team = $this->teamFor($request);

        if (! $team || $team->created_by !== Auth::id()) {
            return response()->json([
                'success' => false,
                'error' => 'not_team_owner',
                'message' => 'Only the account that created the team can change its members.',
            ], 403);
        }

        $row = $team->members()->find($member);

        if (! $row) {
            return response()->json([
                'success' => false,
                'error' => 'member_not_found',
                'message' => 'That member is not on this team.',
            ], 404);
        }

        if ($row->is_leader) {
            return response()->json([
                'success' => false,
                'error' => 'cannot_remove_leader',
                'message' => 'The team leader cannot be removed.',
            ], 409);
        }

        $row->delete();

        return $this->show($request);
    }

    private function teamFor(Request $request): ?Team
    {
        $user = $request->user();

        return $user->team_id
            ? Team::find($user->team_id)
            : Team::where('created_by', $user->id)->first();
    }
}
