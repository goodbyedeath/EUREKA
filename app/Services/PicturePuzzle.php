<?php

namespace App\Services;

use App\Models\Question;

/**
 * "Tebak Gambar": one picture, several labelled answer boxes, each with its own right answer.
 *
 * A crossword with five across and five down is ten boxes; a sheet of five company logos is five.
 * Both are the same shape, so they are one question type. What the operator decided on 21 Sep:
 *
 *  - Partial credit. The question's points are shared equally across its boxes and rounded down,
 *    so seven of ten right on a 10-point question is 7.
 *  - Loose matching. Case, spaces and punctuation do not count: "Coca-Cola", "coca cola" and
 *    "COCACOLA" are the same answer. A crossword wants the letters, a logo wants the name; neither
 *    wants the team marked wrong for a hyphen.
 *  - A box may accept several answers ("BCA", "Bank Central Asia"). They are stored as a list —
 *    the text type separates alternatives with commas, which means a right answer can never
 *    contain one, and that is not a trap worth copying.
 *  - Empty boxes are allowed and score nothing. The question counts as answered once any box is
 *    filled, so a team stuck on two boxes is neither held back nor pushed to type rubbish.
 *  - Which boxes were right is told only after the session is submitted, and the right answers are
 *    never told at all: the next team to reach this post gets the same puzzle.
 *
 * The stored shape of a box is {key, label, answers: [...], length: int|null}. `key` is stable
 * across edits so a saved answer still lands in the box it was typed into after an admin renames
 * or reorders them.
 */
class PicturePuzzle
{
    public const MAX_SLOTS = 30;

    public const MAX_ANSWER_LENGTH = 200;

    /**
     * What makes two answers the same: letters and digits only, lower-cased, in any script.
     *
     * Everything else — spaces, hyphens, dots, apostrophes, commas — is dropped, and accents are
     * folded so "Café" and "cafe" agree.
     */
    public static function normalize(?string $value): string
    {
        $value = (string) $value;

        if (class_exists(\Normalizer::class)) {
            $value = \Normalizer::normalize($value, \Normalizer::FORM_D) ?: $value;
            // Combining marks are what FORM_D split the accents into.
            $value = preg_replace('/\p{Mn}+/u', '', $value) ?? $value;
        }

        $value = mb_strtolower($value, 'UTF-8');

        return preg_replace('/[^\p{L}\p{N}]+/u', '', $value) ?? '';
    }

    /**
     * The boxes as the app may see them: label and letter count, never the answers.
     *
     * @return list<array{key: string, label: string, length: int|null}>
     */
    public static function publicSlots(Question $question): array
    {
        return collect(self::slots($question))
            ->map(fn (array $s) => ['key' => $s['key'], 'label' => $s['label'], 'length' => $s['length']])
            ->values()
            ->all();
    }

    /** @return list<array{key: string, label: string, answers: list<string>, length: int|null}> */
    public static function slots(Question $question): array
    {
        return array_values(array_filter(
            (array) ($question->answer_slots ?? []),
            fn ($s) => is_array($s) && isset($s['key']),
        ));
    }

    /**
     * Mark one team's boxes.
     *
     * @param  array<string, string|null>  $given  box key => what the team typed
     * @return array{slots: array<string, bool>, correct: int, total: int, filled: int, points: int, all_correct: bool}
     */
    public static function score(Question $question, array $given): array
    {
        $slots = self::slots($question);
        $marks = [];
        $filled = 0;

        foreach ($slots as $slot) {
            $typed = self::normalize($given[$slot['key']] ?? '');

            if ($typed === '') {
                $marks[$slot['key']] = false;

                continue;
            }

            $filled++;
            $accepted = array_map([self::class, 'normalize'], (array) ($slot['answers'] ?? []));
            $marks[$slot['key']] = in_array($typed, array_filter($accepted, fn ($a) => $a !== ''), true);
        }

        $total = count($slots);
        $correct = count(array_filter($marks));

        return [
            'slots' => $marks,
            'correct' => $correct,
            'total' => $total,
            'filled' => $filled,
            // Rounded down: 7 of 10 on a 5-point question is 3, not 4 — a team never gets more
            // than its share.
            'points' => $total > 0 ? intdiv((int) $question->points * $correct, $total) : 0,
            'all_correct' => $total > 0 && $correct === $total,
        ];
    }

    /** The team's typed boxes, from the stored answer. Anything unreadable is simply empty. */
    public static function decode(?string $stored): array
    {
        $decoded = json_decode((string) $stored, true);

        return is_array($decoded)
            ? array_map(fn ($v) => is_scalar($v) ? (string) $v : '', $decoded)
            : [];
    }

    /** Whether the team has typed anything at all — the completion gate's question. */
    public static function anyFilled(?string $stored): bool
    {
        foreach (self::decode($stored) as $value) {
            if (self::normalize($value) !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * Tidy what the admin form submitted into the stored shape.
     *
     * Keys already given are kept, so an edit does not orphan answers the teams have saved; a new
     * box gets the next free key. Alternatives come one per line, and blank ones are dropped.
     *
     * @param  list<array{key?: string|null, label?: string, answers?: string|array, length?: int|string|null}>  $raw
     */
    public static function cleanSlots(array $raw): array
    {
        $used = collect($raw)->pluck('key')->filter()->all();
        $next = 1;
        $out = [];

        foreach ($raw as $slot) {
            $answers = is_array($slot['answers'] ?? null)
                ? $slot['answers']
                : preg_split('/\r\n|\r|\n/', (string) ($slot['answers'] ?? ''));

            $key = trim((string) ($slot['key'] ?? ''));
            if ($key === '') {
                while (in_array('s'.$next, $used, true)) {
                    $next++;
                }
                $key = 's'.$next;
                $used[] = $key;
            }

            $length = (int) ($slot['length'] ?? 0);

            $out[] = [
                'key' => $key,
                'label' => trim((string) ($slot['label'] ?? '')),
                'answers' => array_values(array_filter(array_map('trim', $answers), fn ($a) => $a !== '')),
                'length' => $length > 0 ? $length : null,
            ];
        }

        return $out;
    }

    /**
     * Ready-made boxes for the two shapes the operator described, so nobody types ten labels.
     *
     * @return list<array{key: null, label: string, answers: string, length: null}>
     */
    public static function crosswordSlots(int $across, int $down): array
    {
        $boxes = [];
        for ($i = 1; $i <= $across; $i++) {
            $boxes[] = ['key' => null, 'label' => "Mendatar {$i}", 'answers' => '', 'length' => null];
        }
        for ($i = 1; $i <= $down; $i++) {
            $boxes[] = ['key' => null, 'label' => "Menurun {$i}", 'answers' => '', 'length' => null];
        }

        return $boxes;
    }

    /** @return list<array{key: null, label: string, answers: string, length: null}> */
    public static function listSlots(int $count): array
    {
        $boxes = [];
        for ($i = 1; $i <= $count; $i++) {
            $boxes[] = ['key' => null, 'label' => (string) $i, 'answers' => '', 'length' => null];
        }

        return $boxes;
    }
}
