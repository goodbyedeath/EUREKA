<?php

namespace App\Services;

use App\Models\GameLocation;
use App\Models\Hotspot;
use App\Models\IndoorMap;
use App\Models\IndoorMapSpot;
use App\Models\Question;
use App\Models\Questionnaire;
use Illuminate\Support\Facades\DB;

/**
 * Copy a floor plan and everything a team meets on it.
 *
 * Operator, 20 Sep: each team has its own plan, each plan its own posts, each post its own
 * questionnaires. Setting that up team by team meant the same walk through four pages for every
 * team — the single biggest cost in preparing an event, and where the broken links came from.
 *
 * So the copy is deep on purpose: plan → markers → posts → 3D objects → questionnaires →
 * questions. Two teams must be able to answer the same questions without sharing an attempt or a
 * QR code, which is what a shallow copy would have given them.
 *
 * Images are not re-uploaded; both plans point at the same stored file, because an image is the
 * venue and does not differ per team.
 */
class IndoorMapDuplicator
{
    /**
     * @return array{map: IndoorMap, spots: int, posts: int, questionnaires: int, questions: int}
     */
    public function duplicate(IndoorMap $source, ?string $name = null, ?int $userId = null): array
    {
        return DB::transaction(function () use ($source, $name, $userId) {
            $copy = $source->replicate();
            $copy->name = $name ?: $this->nextName($source->name);
            $copy->created_by = $userId ?: $source->created_by;
            // A fresh plan is not assigned to anyone yet; leaving it inactive would hide it from
            // Team Management, where the crew is about to pick it.
            $copy->save();

            $counts = ['spots' => 0, 'posts' => 0, 'questionnaires' => 0, 'questions' => 0];

            // A post can carry several markers on one plan; each post is copied once.
            $postCopies = [];

            foreach (IndoorMapSpot::where('indoor_map_id', $source->id)->orderBy('id')->get() as $spot) {
                $spotCopy = $spot->replicate();
                $spotCopy->indoor_map_id = $copy->id;

                if ($spot->game_location_id) {
                    $postCopies[$spot->game_location_id] ??= $this->copyPost($spot->game_location_id, $copy->name, $counts);
                    $spotCopy->game_location_id = $postCopies[$spot->game_location_id];
                }

                $spotCopy->save();
                $counts['spots']++;
            }

            return ['map' => $copy] + $counts;
        });
    }

    /** @return int|null the new post's id */
    private function copyPost(int $postId, string $planName, array &$counts): ?int
    {
        $post = GameLocation::find($postId);
        if (! $post) {
            return null;
        }

        $copy = $post->replicate();
        $copy->name = $this->suffixed($post->name, $planName);
        $copy->save();
        $counts['posts']++;

        // The 3D scene: without it the copied post opens an empty camera.
        foreach (Hotspot::where('game_location_id', $post->id)->get() as $hotspot) {
            $h = $hotspot->replicate();
            $h->game_location_id = $copy->id;
            $h->save();
        }

        foreach (Questionnaire::where('game_location_id', $post->id)->get() as $questionnaire) {
            $q = $questionnaire->replicate();
            $q->title = $this->suffixed($questionnaire->title, $planName);
            $q->game_location_id = $copy->id;
            // Its own QR: two teams scanning one code would share attempts and the station gate.
            // The column is NOT NULL, so the code is generated before the insert, not after.
            $q->qr_code = QuestionnaireService::generateUniqueQrCode();
            $q->save();
            $counts['questionnaires']++;

            foreach (Question::where('questionnaire_id', $questionnaire->id)->orderBy('order')->get() as $question) {
                $qq = $question->replicate();
                $qq->questionnaire_id = $q->id;
                $qq->save();
                $counts['questions']++;
            }
        }

        return $copy->id;
    }

    /** "Pos 1" on plan "Rute B" becomes "Pos 1 (Rute B)", so the crew can tell copies apart. */
    private function suffixed(string $name, string $planName): string
    {
        $short = mb_substr($planName, 0, 40);

        return mb_substr($name, 0, 200).' ('.$short.')';
    }

    private function nextName(string $name): string
    {
        $base = preg_replace('/\s*\(salinan(?:\s+\d+)?\)$/u', '', $name);

        for ($i = 2; $i < 99; $i++) {
            $candidate = $i === 2 ? $base.' (salinan)' : $base." (salinan {$i})";

            if (! IndoorMap::where('name', $candidate)->exists()) {
                return $candidate;
            }
        }

        return $base.' (salinan '.now()->format('His').')';
    }
}
