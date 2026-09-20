<?php

namespace App\Livewire\Admin;

use App\Models\GameLocation;
use App\Models\IndoorMap;
use App\Models\IndoorMapSpot;
use App\Models\Questionnaire;
use App\Models\RaceStart;
use App\Models\Team;
use App\Models\User;
use Livewire\Component;

/**
 * One screen that answers "is the event ready?".
 *
 * The chain is Team → floor plan → posts → questionnaires, and every link is authored on a
 * different page. Every fault the operator hit in testing was a broken link that no page mentioned:
 * markers with no post (so the whole plan reads as locked), a questionnaire whose window had passed
 * (refused on scan as not_available), a team with no login account, a post nobody can reach.
 *
 * This walks the chain per team and names what is missing, in the order the crew would fix it.
 */
class EventReadiness extends Component
{
    public function render()
    {
        $accounts = User::where('role', 'user')->get(['id', 'name', 'team_id']);
        $startMapId = RaceStart::where('is_active', true)->whereNotNull('indoor_map_id')->orderByDesc('id')->value('indoor_map_id');
        $activePlans = IndoorMap::where('is_active', true)->get(['id', 'name', 'clue_question', 'clue_answer']);

        // Markers per plan, and the posts they point at.
        $spots = IndoorMapSpot::where('is_active', true)->get(['id', 'indoor_map_id', 'name', 'game_location_id']);
        $posts = GameLocation::whereIn('id', $spots->pluck('game_location_id')->filter()->unique())
            ->get(['id', 'name', 'is_active', 'access_mode'])->keyBy('id');

        // Indoor questionnaires, by the post they are linked to.
        $questionnaires = Questionnaire::where('venue_mode', 'indoor')
            ->whereNotNull('game_location_id')
            ->withCount('questions')
            ->get(['id', 'title', 'game_location_id', 'is_active', 'start_date', 'end_date', 'qr_code'])
            ->groupBy('game_location_id');

        $rows = Team::orderBy('name')->get(['id', 'name', 'indoor_map_id'])->map(function (Team $team) use (
            $accounts, $startMapId, $activePlans, $spots, $posts, $questionnaires
        ) {
            $teamAccounts = $accounts->where('team_id', $team->id);
            $planId = $team->indoor_map_id ?: $startMapId;
            $plan = $activePlans->firstWhere('id', $planId);

            $mySpots = $plan ? $spots->where('indoor_map_id', $plan->id) : collect();
            $unlinked = $mySpots->whereNull('game_location_id');

            $myPosts = $mySpots->pluck('game_location_id')->filter()->unique()
                ->map(fn ($id) => $posts->get($id))->filter()->values();

            $postRows = $myPosts->map(function (GameLocation $post) use ($questionnaires) {
                $mine = $questionnaires->get($post->id, collect());

                return (object) [
                    'post' => $post,
                    'questionnaires' => $mine->map(fn (Questionnaire $q) => (object) [
                        'q' => $q,
                        'available' => $q->isAvailable(),
                        'window' => $q->start_date || $q->end_date
                            ? trim(($q->start_date?->format('d M') ?? '—').' → '.($q->end_date?->format('d M') ?? '—'))
                            : 'tanpa batas',
                        'problems' => array_values(array_filter([
                            $q->is_active ? null : 'nonaktif',
                            $q->questions_count ? null : 'belum ada pertanyaan',
                            $q->isAvailable() ? null : 'di luar tanggal berlaku',
                            $q->qr_code ? null : 'belum punya QR',
                        ])),
                    ]),
                    'problems' => array_values(array_filter([
                        $post->is_active ? null : 'pos nonaktif',
                        $mine->isEmpty() ? 'belum ada kuesioner' : null,
                    ])),
                ];
            });

            $problems = array_values(array_filter([
                $teamAccounts->isEmpty() ? 'belum ada akun login' : null,
                $plan ? null : ($team->indoor_map_id ? 'denah tim nonaktif' : 'tidak ada denah (dan QR START belum menunjuk denah)'),
                $plan && $mySpots->isEmpty() ? 'denah belum punya penanda' : null,
                $plan && $mySpots->isNotEmpty() && $myPosts->isEmpty() ? 'tidak satu pun penanda terhubung ke pos' : null,
                $unlinked->isNotEmpty() && $myPosts->isNotEmpty() ? $unlinked->count().' penanda belum terhubung ke pos' : null,
                $plan && blank($plan->clue_answer) ? 'denah tanpa jawaban clue (pos terbuka otomatis setelah START)' : null,
            ]));

            return (object) [
                'team' => $team,
                'accounts' => $teamAccounts,
                'plan' => $plan,
                'assigned' => $team->indoor_map_id !== null,
                'posts' => $postRows,
                'problems' => $problems,
                // Red only for faults that stop a team playing; the rest are worth knowing.
                'blocked' => $teamAccounts->isEmpty() || ! $plan || $myPosts->isEmpty()
                    || $postRows->contains(fn ($p) => $p->questionnaires->isEmpty())
                    || $postRows->contains(fn ($p) => $p->questionnaires->contains(fn ($q) => ! $q->available)),
            ];
        });

        return view('livewire.admin.event-readiness', [
            'rows' => $rows,
            'startMap' => $startMapId ? $activePlans->firstWhere('id', $startMapId) : null,
            // Questionnaires nobody can reach: no post, or a post no active marker points at.
            'orphanQuestionnaires' => Questionnaire::where(function ($q) use ($spots) {
                $q->whereNull('game_location_id')->orWhereNotIn('game_location_id', $spots->pluck('game_location_id')->filter()->unique());
            })->where('is_active', true)->get(['id', 'title', 'venue_mode', 'game_location_id', 'quest_location_id']),
        ]);
    }
}
