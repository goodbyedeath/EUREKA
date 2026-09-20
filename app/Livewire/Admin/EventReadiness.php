<?php

namespace App\Livewire\Admin;

use App\Models\GameLocation;
use App\Models\IndoorMap;
use App\Models\IndoorMapSpot;
use App\Models\Questionnaire;
use App\Models\QuestLocation;
use App\Models\RaceStart;
use App\Models\Team;
use App\Models\User;
use Livewire\Component;

/**
 * One screen that answers "is the event ready?".
 *
 * There are two chains, and an event may run either or both (operator, 20 Sep):
 *
 *   indoor   Tim → denah → penanda → pos → kuesioner, and a crew member opens the pos
 *   outdoor  Pos (Quest Location) dengan koordinat → check-in GPS → kuesioner
 *
 * Both start at the same QR START. Every link is authored on a different page, and every fault the
 * operator hit in testing was a broken link that no page mentioned: markers with no post (so the
 * whole plan reads as locked), a questionnaire whose window had passed (refused on scan as
 * not_available), an outdoor post with no coordinates (check-in can never succeed), a team with no
 * login account.
 *
 * This walks both chains and names what is missing, in the order the crew would fix it. A chain
 * nobody uses is not a fault: an all-outdoor event has no floor plans, and saying "no plan" for
 * every team would bury the faults that matter.
 */
class EventReadiness extends Component
{
    public function render()
    {
        $accounts = User::where('role', 'user')->get(['id', 'name', 'team_id']);
        $starts = RaceStart::where('is_active', true)->orderByDesc('id')->get(['id', 'name', 'code', 'indoor_map_id']);
        $startMapId = $starts->firstWhere('indoor_map_id', '!=', null)?->indoor_map_id;
        $activePlans = IndoorMap::where('is_active', true)->get(['id', 'name', 'clue_question', 'clue_answer']);

        // Markers per plan, and the posts they point at.
        $spots = IndoorMapSpot::where('is_active', true)->get(['id', 'indoor_map_id', 'name', 'game_location_id']);
        $posts = GameLocation::whereIn('id', $spots->pluck('game_location_id')->filter()->unique())
            ->get(['id', 'name', 'is_active', 'access_mode'])->keyBy('id');

        $questionnaireColumns = ['id', 'title', 'game_location_id', 'quest_location_id', 'venue_mode',
            'is_active', 'start_date', 'end_date', 'qr_code'];

        // Indoor questionnaires, by the post they are linked to.
        $indoorQuestionnaires = Questionnaire::where('venue_mode', 'indoor')
            ->whereNotNull('game_location_id')
            ->withCount('questions')
            ->get($questionnaireColumns)
            ->groupBy('game_location_id');

        // Outdoor questionnaires, by the quest location they are linked to.
        $outdoorQuestionnaires = Questionnaire::where('venue_mode', 'outdoor')
            ->whereNotNull('quest_location_id')
            ->withCount('questions')
            ->get($questionnaireColumns)
            ->groupBy('quest_location_id');

        // Posts this event actually uses: the active ones, plus any switched-off post that an active
        // questionnaire still points at — that one is a fault worth naming. A post retired with
        // nothing attached is simply not part of this event, and listing it as "not ready" for ever
        // is how a readiness page teaches people to ignore it.
        $stillLinked = $outdoorQuestionnaires
            ->filter(fn ($group) => $group->contains(fn (Questionnaire $q) => (bool) $q->is_active))
            ->keys();

        $questLocations = QuestLocation::orderBy('name')
            ->where(fn ($q) => $q->where('is_active', true)->orWhereIn('id', $stillLinked))
            ->get(['id', 'name', 'latitude', 'longitude', 'radius', 'is_active']);

        // Which chains this event actually uses. Authoring either one switches its section on.
        $indoorInUse = $activePlans->isNotEmpty() || $indoorQuestionnaires->isNotEmpty();
        $outdoorInUse = $questLocations->where('is_active', true)->isNotEmpty() || $outdoorQuestionnaires->isNotEmpty();

        $rows = Team::orderBy('name')->get(['id', 'name', 'indoor_map_id'])->map(function (Team $team) use (
            $accounts, $startMapId, $activePlans, $spots, $posts, $indoorQuestionnaires, $indoorInUse
        ) {
            $teamAccounts = $accounts->where('team_id', $team->id);
            $planId = $team->indoor_map_id ?: $startMapId;
            $plan = $activePlans->firstWhere('id', $planId);

            $mySpots = $plan ? $spots->where('indoor_map_id', $plan->id) : collect();
            $unlinked = $mySpots->whereNull('game_location_id');

            $myPosts = $mySpots->pluck('game_location_id')->filter()->unique()
                ->map(fn ($id) => $posts->get($id))->filter()->values();

            $postRows = $myPosts->map(function (GameLocation $post) use ($indoorQuestionnaires) {
                $mine = $indoorQuestionnaires->get($post->id, collect());

                return (object) [
                    'post' => $post,
                    'questionnaires' => $mine->map(fn (Questionnaire $q) => (object) [
                        'q' => $q,
                        'available' => $q->isAvailable(),
                        'window' => $this->window($q),
                        'problems' => $this->questionnaireProblems($q),
                    ]),
                    'problems' => array_values(array_filter([
                        $post->is_active ? null : 'pos nonaktif',
                        $mine->isEmpty() ? 'belum ada kuesioner' : null,
                    ])),
                ];
            });

            // The floor-plan chain is only a fault when this event has an indoor side at all.
            $planProblems = $indoorInUse ? array_filter([
                $plan ? null : ($team->indoor_map_id ? 'denah tim nonaktif' : 'tidak ada denah (dan QR START belum menunjuk denah)'),
                $plan && $mySpots->isEmpty() ? 'denah belum punya penanda' : null,
                $plan && $mySpots->isNotEmpty() && $myPosts->isEmpty() ? 'tidak satu pun penanda terhubung ke pos' : null,
                $unlinked->isNotEmpty() && $myPosts->isNotEmpty() ? $unlinked->count().' penanda belum terhubung ke pos' : null,
                $plan && blank($plan->clue_answer) ? 'denah tanpa jawaban clue (pos terbuka otomatis setelah START)' : null,
            ]) : [];

            $problems = array_values(array_filter(array_merge(
                [$teamAccounts->isEmpty() ? 'belum ada akun login' : null],
                $planProblems,
            )));

            return (object) [
                'team' => $team,
                'accounts' => $teamAccounts,
                'plan' => $plan,
                'assigned' => $team->indoor_map_id !== null,
                'posts' => $postRows,
                'problems' => $problems,
                // Red only for faults that stop a team playing; the rest are worth knowing.
                'blocked' => $teamAccounts->isEmpty() || ($indoorInUse && (
                    ! $plan || $myPosts->isEmpty()
                    || $postRows->contains(fn ($p) => $p->questionnaires->isEmpty())
                    || $postRows->contains(fn ($p) => $p->questionnaires->contains(fn ($q) => ! $q->available))
                )),
            ];
        });

        // The outdoor chain has no per-team branch: every team walks the same posts, and the gate is
        // the GPS radius rather than a crew member. So it is checked once, per post.
        $outdoorRows = $questLocations->map(function (QuestLocation $post) use ($outdoorQuestionnaires) {
            $mine = $outdoorQuestionnaires->get($post->id, collect());

            // The columns are NOT NULL, so a post saved without touching the map keeps 0,0 — off the
            // coast of Africa. That is the shape this fault actually takes here, not a null.
            $noCoordinates = $post->latitude === null || $post->longitude === null
                || ((float) $post->latitude === 0.0 && (float) $post->longitude === 0.0);

            return (object) [
                'post' => $post,
                'hasCoordinates' => ! $noCoordinates,
                'questionnaires' => $mine->map(fn (Questionnaire $q) => (object) [
                    'q' => $q,
                    'available' => $q->isAvailable(),
                    'window' => $this->window($q),
                    'problems' => $this->questionnaireProblems($q),
                ]),
                'problems' => array_values(array_filter([
                    $post->is_active ? null : 'pos nonaktif',
                    // Without a coordinate the check-in can never succeed, and the questionnaire
                    // behind it stays shut for the whole event.
                    $noCoordinates ? 'belum ada koordinat (masih 0,0) — check-in tidak akan pernah berhasil' : null,
                    ! $noCoordinates && ! $post->radius ? 'radius kosong' : null,
                    $mine->isEmpty() ? 'belum ada kuesioner' : null,
                ])),
                'blocked' => ! $post->is_active || $noCoordinates || $mine->isEmpty()
                    || $mine->contains(fn (Questionnaire $q) => ! $q->isAvailable()),
            ];
        })->values();

        return view('livewire.admin.event-readiness', [
            'rows' => $rows,
            'outdoorRows' => $outdoorRows,
            'indoorInUse' => $indoorInUse,
            'outdoorInUse' => $outdoorInUse,
            'starts' => $starts,
            'startMap' => $startMapId ? $activePlans->firstWhere('id', $startMapId) : null,
            'orphanQuestionnaires' => $this->orphans($spots),
        ]);
    }

    /** The four things that keep a questionnaire from opening on a scan. */
    private function questionnaireProblems(Questionnaire $q): array
    {
        return array_values(array_filter([
            $q->is_active ? null : 'nonaktif',
            $q->questions_count ? null : 'belum ada pertanyaan',
            $q->isAvailable() ? null : 'di luar tanggal berlaku',
            $q->qr_code ? null : 'belum punya QR',
        ]));
    }

    private function window(Questionnaire $q): string
    {
        return $q->start_date || $q->end_date
            ? trim(($q->start_date?->format('d M') ?? '—').' → '.($q->end_date?->format('d M') ?? '—'))
            : 'tanpa batas';
    }

    /**
     * Active questionnaires nothing can reach — the station gate will refuse every one of them.
     *
     * Indoor: no post, or a post no active marker points at. Outdoor: no quest location, or one
     * that is switched off. A questionnaire with no venue_mode at all is refused as
     * station_not_linked, so it counts too.
     */
    private function orphans($spots)
    {
        $reachableIndoor = $spots->pluck('game_location_id')->filter()->unique();
        $activeOutdoor = QuestLocation::where('is_active', true)->pluck('id');

        return Questionnaire::where('is_active', true)
            ->get(['id', 'title', 'venue_mode', 'game_location_id', 'quest_location_id'])
            ->map(function (Questionnaire $q) use ($reachableIndoor, $activeOutdoor) {
                $reason = match ($q->venue_mode) {
                    'indoor' => match (true) {
                        ! $q->game_location_id => 'belum dihubungkan ke pos',
                        ! $reachableIndoor->contains($q->game_location_id) => 'pos #'.$q->game_location_id.' tidak ada di denah mana pun',
                        default => null,
                    },
                    'outdoor' => match (true) {
                        ! $q->quest_location_id => 'belum dihubungkan ke Quest Location',
                        ! $activeOutdoor->contains($q->quest_location_id) => 'Quest Location #'.$q->quest_location_id.' nonaktif',
                        default => null,
                    },
                    default => 'belum dipilih indoor atau outdoor',
                };

                return $reason ? (object) ['q' => $q, 'reason' => $reason] : null;
            })
            ->filter()
            ->values();
    }
}
