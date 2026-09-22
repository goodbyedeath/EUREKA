<?php

namespace App\Support;

use App\Enums\QuestionType;
use App\Models\Question;
use App\Services\PicturePuzzle;
use Illuminate\Support\Facades\Storage;

/**
 * One question as the app receives it — the single door every question leaves the server through.
 *
 * `quiz/start`, `quiz/continue` and the offline manifest all use this, so what one of them strips the
 * others strip too. It never carries a right answer: `correct_answer` is never selected by the
 * callers, and `answer_slots` — Tebak Gambar's boxes with their answers — is removed here
 * unconditionally, whatever the type and whoever selected the column.
 */
class QuestionPayload
{
    /** The columns a caller should select. `correct_answer` is deliberately absent. */
    public const COLUMNS = ['id', 'questionnaire_id', 'question', 'type', 'options', 'points', 'order', 'description',
        'game_name', 'images', 'frame_path', 'share_caption', 'answer_slots'];

    public static function forApp(Question $question): array
    {
        $row = $question->toArray();

        // Absolute URLs: the app caches for offline and cannot resolve a relative path from a cold start.
        $row['image_urls'] = collect((array) ($question->images ?? []))
            ->filter(fn ($p) => is_string($p) && $p !== '')
            ->map(fn ($p) => preg_match('#^https?://#i', $p) ? $p : Storage::disk('public')->url($p))
            ->values()
            ->all();

        // Foto bersama: the PNG the app lays over the camera, and the words it offers when the team
        // shares the result.
        $row['frame_url'] = $question->frame_path ? Storage::disk('public')->url($question->frame_path) : null;
        $row['share_caption'] = $question->share_caption;

        unset($row['answer_slots'], $row['correct_answer']);
        $row['slots'] = $question->type === QuestionType::PICTURE_PUZZLE->value
            ? PicturePuzzle::publicSlots($question)
            : null;

        return $row;
    }
}
