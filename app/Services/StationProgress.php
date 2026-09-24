<?php

namespace App\Services;

use App\Models\IndoorMapSpot;
use App\Models\Questionnaire;
use App\Models\QuizAttempt;
use Illuminate\Support\Collection;

/**
 * How many stations a team has to clear, and how many it has cleared.
 *
 * "Station" used to mean "questionnaire", and while every questionnaire belonged to exactly one post
 * the two were the same thing. Since 23 Sep a questionnaire may be opened at several posts — or at
 * every post — and then one questionnaire is ten stations: the team plays it at each booth. Counting
 * questionnaires there would finish a team's clock at the first booth and record 1 of 1 in the
 * archive while nine posts stood untouched.
 *
 * So the unit is the pair (questionnaire, post). For the ordinary one-post questionnaire that is
 * exactly the old count, which is why the finish rule and the archive both read it from here.
 */
class StationProgress
{
    /** The questionnaires an event is finished by. */
    public function required(): Collection
    {
        return Questionnaire::where('is_active', true)
            ->where('counts_toward_finish', true)
            ->with('extraPosts:id')
            ->get(['id', 'venue_mode', 'game_location_id', 'all_posts']);
    }

    /**
     * How many stations this team must clear.
     *
     * A shared questionnaire counts once per post **on the team's own floor plan**: requiring the
     * posts of a plan they never walk would leave the clock running for ever.
     */
    public function requiredCount(?int $indoorMapId = null, ?Collection $required = null): int
    {
        $required ??= $this->required();
        $planPosts = $this->planPostIds($indoorMapId);

        return $required->sum(function (Questionnaire $q) use ($planPosts) {
            $posts = $q->postIds();

            if (count($posts) <= 1) {
                return 1;
            }

            $mine = $planPosts->intersect($posts);

            return max(1, $mine->count());
        });
    }

    /**
     * Completed stations for these accounts: one per questionnaire, or per (questionnaire, post)
     * where the questionnaire is shared.
     *
     * @param  iterable<int>  $userIds
     */
    public function clearedCount(iterable $userIds, ?Collection $required = null): int
    {
        return count($this->clearedKeys($userIds, $required));
    }

    /**
     * The stations these accounts have cleared, as keys — one per questionnaire, or one per
     * (questionnaire, post) where the questionnaire is shared.
     *
     * Counting is done on keys rather than in SQL because the rule differs per questionnaire, and
     * because the live desk needs the same answer for twenty teams without twenty queries.
     *
     * @param  iterable<int>  $userIds
     * @return list<string>
     */
    public function clearedKeys(iterable $userIds, ?Collection $required = null): array
    {
        $required ??= $this->required();
        $shared = $required->filter(fn (Questionnaire $q) => $q->servesManyPosts())->pluck('id');

        return QuizAttempt::whereIn('user_id', collect($userIds)->all())
            ->where('status', QuizAttempt::STATUS_COMPLETED)
            ->whereIn('questionnaire_id', $required->pluck('id'))
            ->get(['questionnaire_id', 'game_location_id'])
            // An attempt from before the post was recorded, or at a one-post questionnaire, counts
            // once for its questionnaire; a shared one counts once per post.
            ->map(fn ($a) => $shared->contains($a->questionnaire_id)
                ? $a->questionnaire_id.'@'.$a->game_location_id
                : (string) $a->questionnaire_id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * The same, for many accounts at once: user_id => the keys that account cleared.
     *
     * One query for the whole desk. A team's own figure is the union of its accounts' keys, since
     * two phones may have split the route between them.
     *
     * @param  iterable<int>  $userIds
     * @return array<int, list<string>>
     */
    public function clearedKeysByAccount(iterable $userIds, ?Collection $required = null): array
    {
        $required ??= $this->required();
        $shared = $required->filter(fn (Questionnaire $q) => $q->servesManyPosts())->pluck('id');

        return QuizAttempt::whereIn('user_id', collect($userIds)->all())
            ->where('status', QuizAttempt::STATUS_COMPLETED)
            ->whereIn('questionnaire_id', $required->pluck('id'))
            ->get(['user_id', 'questionnaire_id', 'game_location_id'])
            ->groupBy('user_id')
            ->map(fn ($rows) => $rows
                ->map(fn ($a) => $shared->contains($a->questionnaire_id)
                    ? $a->questionnaire_id.'@'.$a->game_location_id
                    : (string) $a->questionnaire_id)
                ->unique()->values()->all())
            ->all();
    }

    /**
     * The indoor posts where this team has answered **everything** there is to answer.
     *
     * Not "has answered something": a post may carry more than one questionnaire, and closing it on
     * the first would lock the team out of the second. So a post is finished only when every active
     * questionnaire that opens there has a completed attempt at that post.
     *
     * This is what closes a post on the floor plan (`is_done`) and what revokes its access, so both
     * answer the same question the same way.
     *
     * @param  iterable<int>  $userIds  every account of the team
     * @return Collection<int, int>
     */
    public function finishedPosts(iterable $userIds): Collection
    {
        $questionnaires = Questionnaire::where('is_active', true)
            ->where('venue_mode', 'indoor')
            ->where(fn ($q) => $q->whereNotNull('game_location_id')->orWhere('all_posts', true))
            ->with('extraPosts:id')
            ->get(['id', 'venue_mode', 'game_location_id', 'all_posts']);

        if ($questionnaires->isEmpty()) {
            return collect();
        }

        // post id => the questionnaires that open there
        $byPost = [];
        foreach ($questionnaires as $q) {
            foreach ($q->postIds() as $postId) {
                $byPost[$postId][] = $q;
            }
        }

        $cleared = array_flip($this->clearedKeys($userIds, $questionnaires));

        $finished = [];
        foreach ($byPost as $postId => $here) {
            foreach ($here as $q) {
                // A shared questionnaire is cleared per post, so it counts only where it was played.
                $key = $q->servesManyPosts() ? $q->id.'@'.$postId : (string) $q->id;

                if (! isset($cleared[$key])) {
                    continue 2;
                }
            }

            $finished[] = (int) $postId;
        }

        return collect($finished);
    }

    /** @return Collection<int, int> the posts a plan's markers point at */
    private function planPostIds(?int $indoorMapId): Collection
    {
        if (! $indoorMapId) {
            return collect();
        }

        return IndoorMapSpot::where('indoor_map_id', $indoorMapId)
            ->whereNotNull('game_location_id')
            ->pluck('game_location_id')
            ->unique()
            ->values();
    }
}
