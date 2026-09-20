<?php

/**
 * Remove everything tools/ui-sweep.cjs created.
 *
 * The sweep clicks through the real admin on the live site, so it writes to the live database. Every
 * row it makes is named with the same prefix, and this deletes exactly those — before a run as well
 * as after, so a sweep that died half way does not leave litter for the next one to trip over.
 *
 * Nothing here matches on anything but the prefix. If a crew member ever names a real questionnaire
 * "ZZ UI Sweep", that is on them.
 *
 *   /opt/alt/php83/usr/bin/php tools/ui-sweep-clean.php
 */

chdir(__DIR__.'/..');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\UserGuideSection;
use Illuminate\Support\Facades\Storage;

const PREFIX = 'ZZ UI Sweep';

$removed = [];

$questionnaires = Questionnaire::where('title', 'like', PREFIX.'%')->get();
foreach ($questionnaires as $q) {
    // Frames and images belong to the questions, and nothing else points at them.
    foreach (Question::where('questionnaire_id', $q->id)->get() as $question) {
        foreach (array_filter(array_merge([(string) $question->frame_path], (array) ($question->images ?? []))) as $path) {
            if (is_string($path) && $path !== '' && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }
        $question->delete();
    }
    $q->delete();
}
if ($questionnaires->count()) {
    $removed[] = $questionnaires->count().' questionnaire(s)';
}

$sections = UserGuideSection::where('title', 'like', PREFIX.'%')->get();
foreach ($sections as $section) {
    $section->delete();
}
if ($sections->count()) {
    $removed[] = $sections->count().' guide section(s)';
}

// Frames uploaded by a run that never got as far as saving a question.
$orphanFrames = collect(Storage::disk('public')->files('games/photo-frames'))
    ->filter(fn ($p) => str_contains(basename($p), 'zz-uisweep'));
foreach ($orphanFrames as $path) {
    Storage::disk('public')->delete($path);
}
if ($orphanFrames->count()) {
    $removed[] = $orphanFrames->count().' stray frame(s)';
}

echo $removed ? 'removed '.implode(', ', $removed) : 'nothing to clean';
echo PHP_EOL;
