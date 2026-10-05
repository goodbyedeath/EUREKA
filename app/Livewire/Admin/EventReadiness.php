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
        $accounts = User::where('role', 'user')->get(['id', 'name', 'team_id', 'is_active', 'role', 'session_timeout', 'session_expired_at', 'last_login_at']);

        // Races under way, per account. A team walks one route; a clock running on another one means
        // somebody scanned the wrong START card (operator, 24 Sep). The server refuses that now, but
        // a team that started before the rule — or was moved by an admin — still shows up here.
        $running = \App\Models\RaceSession::whereNotNull('started_at')
            ->whereNull('finished_at')
            ->get(['id', 'user_id', 'indoor_map_id', 'started_at'])
            ->groupBy('user_id');
        $starts = RaceStart::where('is_active', true)->orderByDesc('id')->get(['id', 'name', 'code', 'indoor_map_id']);
        $startMapId = $starts->firstWhere('indoor_map_id', '!=', null)?->indoor_map_id;
        $activePlans = IndoorMap::where('is_active', true)->get(['id', 'name', 'clue_question', 'clue_answer']);

        // Markers per plan, and the posts they point at.
        $spots = IndoorMapSpot::where('is_active', true)->get(['id', 'indoor_map_id', 'name', 'game_location_id']);
        $posts = GameLocation::whereIn('id', $spots->pluck('game_location_id')->filter()->unique())
            ->get(['id', 'name', 'is_active', 'access_mode'])->keyBy('id');

        $questionnaireColumns = ['id', 'title', 'game_location_id', 'quest_location_id', 'venue_mode',
            'is_active', 'start_date', 'end_date', 'qr_code', 'all_posts'];

        // Indoor questionnaires, by the post they are linked to. One may serve several posts — or
        // every post — so it is listed under each of them rather than under a single id.
        $indoorQuestionnaires = Questionnaire::where('venue_mode', 'indoor')
            ->where(fn ($q) => $q->whereNotNull('game_location_id')->orWhere('all_posts', true))
            ->withCount('questions')
            ->with('extraPosts:id')
            ->get($questionnaireColumns)
            ->reduce(function (\Illuminate\Support\Collection $byPost, Questionnaire $q) {
                foreach ($q->postIds() as $postId) {
                    $byPost->put($postId, ($byPost->get($postId) ?? collect())->push($q));
                }

                return $byPost;
            }, collect());

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
            $accounts, $startMapId, $activePlans, $spots, $posts, $indoorQuestionnaires, $indoorInUse, $running
        ) {
            $teamAccounts = $accounts->where('team_id', $team->id);

            // An account that is switched off, or whose access window has run out, cannot play —
            // and the page used to call such a team "siap" anyway (operator, 24 Sep: borokokok was
            // green with a window that expired at 21:38). Readiness has to read the accounts too.
            $locked = $teamAccounts->filter(fn (User $u) => ! $u->is_active || $u->accessWindowExpired());
            $usable = $teamAccounts->reject(fn (User $u) => ! $u->is_active || $u->accessWindowExpired());
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
                $plan && blank($plan->clue_answer) ? 'denah tanpa jawaban clue — di aplikasi tim tidak bisa melewati pertanyaan START' : null,
            ]) : [];

            // The routes this team is actually running on, by the plan each clock belongs to.
            $liveRoutes = $teamAccounts
                ->flatMap(fn (User $u) => $running->get($u->id, collect()))
                ->pluck('indoor_map_id')
                ->unique()
                ->values();

            $routeName = fn ($id) => $id
                ? ($activePlans->firstWhere('id', $id)->name ?? IndoorMap::find($id)?->name ?? '#'.$id)
                : 'outdoor';

            $routeProblems = array_filter([
                // Two clocks on two routes: /race/status reports the newer one, so the team's route
                // changes under them and the older clock keeps running.
                $liveRoutes->count() > 1
                    ? 'sedang berjalan di '.$liveRoutes->count().' rute sekaligus ('
                        .$liveRoutes->map($routeName)->implode(', ').') — hentikan salah satunya lewat Emergency Stop'
                    : null,
                // One clock, but not on the plan this team is supposed to walk.
                $liveRoutes->count() === 1 && $plan && $liveRoutes->first() !== null && $liveRoutes->first() !== $plan->id
                    ? 'sedang berjalan di rute '.$routeName($liveRoutes->first()).', padahal denahnya '.$plan->name
                    : null,
            ]);

            $problems = array_values(array_filter(array_merge(
                [
                    $teamAccounts->isEmpty() ? 'belum ada akun login' : null,
                    // Named one by one: with two phones the crew needs to know which is locked.
                    $teamAccounts->isNotEmpty() && $usable->isEmpty()
                        ? 'akun tidak bisa dipakai: '.$locked->map(fn (User $u) => $u->name.' ('.mb_strtolower($u->accessWindowStatus()['label']).')')->implode(', ')
                            .' — perbarui aksesnya di Account Management'
                        : null,
                    $usable->isNotEmpty() && $locked->isNotEmpty()
                        ? $locked->count().' akun terkunci: '.$locked->pluck('name')->implode(', ')
                        : null,
                ],
                $planProblems,
                $routeProblems,
            )));

            return (object) [
                'team' => $team,
                'accounts' => $teamAccounts,
                'usable_accounts' => $usable,
                'live_routes' => $liveRoutes->map(fn ($id) => ['id' => $id, 'name' => $routeName($id)])->all(),
                'locked_accounts' => $locked,
                'plan' => $plan,
                'assigned' => $team->indoor_map_id !== null,
                'posts' => $postRows,
                'problems' => $problems,
                // Red only for faults that stop a team playing; the rest are worth knowing.
                'blocked' => $teamAccounts->isEmpty() || $usable->isEmpty() || $routeProblems !== [] || ($indoorInUse && (
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
            // Will the venue network let 5,000 people log in? SCALING-PLAN §3e rests on a claim about
            // the network — that every phone arrives from one address — and nothing in this app could
            // check it. LoginGuard counts the addresses logins actually came from, so this is
            // measured here, before the event, rather than discovered during it.
            'network' => app(\App\Services\LoginGuard::class)->verdict(180),
            'venue' => [
                'players' => (int) config('venue.players'),
                'doors' => (int) config('venue.doors_minutes'),
                'needed' => (int) ceil((int) config('venue.players') / max(1, (int) config('venue.doors_minutes'))),
                'ceiling' => (int) config('venue.logins.per_minute_per_address'),
                'failure_ceiling' => (int) config('venue.logins.failures_per_minute_per_address'),
                'per_account' => (int) config('venue.logins.per_minute_per_account'),
            ],
            'busiestAddresses' => app(\App\Services\LoginGuard::class)->busiest(5, 180),
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
            // The offline copy of a questionnaire is sealed with its QR code, so the code is also the
            // key. The generated ones are 36-character UUIDs; a short hand-typed code can be guessed,
            // and with it the questions read before anyone reaches the post.
            $q->qr_code && mb_strlen(trim((string) $q->qr_code)) < 16 ? 'kode QR pendek — soal offline-nya bisa ditebak tanpa ke pos' : null,
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
            ->with('extraPosts:id')
            ->get(['id', 'title', 'venue_mode', 'game_location_id', 'quest_location_id', 'all_posts'])
            ->map(function (Questionnaire $q) use ($reachableIndoor, $activeOutdoor) {
                $reason = match ($q->venue_mode) {
                    'indoor' => match (true) {
                        $q->postIds() === [] => 'belum dihubungkan ke pos',
                        // Reachable through any one of its posts is enough; the team only ever
                        // stands at one of them.
                        ! $reachableIndoor->intersect($q->postIds())->isNotEmpty()
                            => 'posnya tidak ada di denah mana pun',
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
