<?php

/**
 * Create one AR Outpost per post on a floor plan, each with three 3D objects, and link them.
 *
 * Operator, 23 Sep: ten AR cameras, one per post on the Tim 01 plan, two to three objects each.
 *
 *   php deploy-ar-outposts.php --plan=56              # dry run: says what it would do
 *   php deploy-ar-outposts.php --plan=56 --apply      # writes
 *
 * Idempotent: a spot that already points at an AR Outpost is left alone, so a second run adds only
 * what is missing. Placement values are the ones already proven on posts #39 and #86 — scale and
 * rotation per model, so nothing arrives giant or buried in the floor — spread around the player so
 * the team has to look round. pitch/yaw are RADIANS (ArExperienceController reads them straight into
 * sin/cos); ar_rotation_* are degrees.
 */

chdir('/home/u840623113/domains/questerra-series.com/public_html/EUREKA_APP');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\{GameLocation, Hotspot, IndoorMap, IndoorMapSpot, User};
use Illuminate\Support\Facades\DB;

$args = collect($argv)->slice(1);
$planId = (int) ($args->first(fn ($a) => str_starts_with($a, '--plan=')) ? explode('=', $args->first(fn ($a) => str_starts_with($a, '--plan=')))[1] : 0);
$apply = $args->contains('--apply');

$plan = IndoorMap::findOrFail($planId);
$admin = User::where('role', 'admin')->orderBy('id')->firstOrFail();

/**
 * The three objects, in the order a team meets them. Each model keeps the scale, distance and
 * rotation that already look right in the live scenes; only the bearing changes, so the three sit
 * roughly 120° apart and the team turns on the spot to find them all.
 */
$objects = [
    [
        'title' => 'Peti Harta',
        'ar_model_id' => 6,          // treasure-chest
        'ar_distance' => 4.14,
        'ar_scale' => 1.0,
        'rotation' => [0, 0, 0],
        'yaw' => 2.0944,             // +120°
        'pitch' => -0.20,            // a little below eye level
        'motions' => [['type' => 'bob', 'speed' => 1, 'range' => 0.15]],
        'description' => 'Kamu di jalur yang benar. Masih ada benda lain di sekitarmu — menengoklah.',
    ],
    [
        'title' => 'Koin Emas',
        'ar_model_id' => 8,          // gold_coin
        'ar_distance' => 1.63,
        'ar_scale' => 0.376,
        'rotation' => [-90, 0, 0],   // the coin's face towards the player
        'yaw' => 0.0,                // straight ahead
        'pitch' => 0.18535,
        'motions' => [['type' => 'spin', 'speed' => 1, 'range' => 1]],
        // The one that matters: this is where the paper QR's hiding place is told.
        'description' => 'Petunjuk: QR kertas ada di … (ganti teks ini dengan tempat persembunyian di %POS%)',
    ],
    [
        'title' => 'Bebek',
        'ar_model_id' => 5,          // DEMO Duck
        'ar_distance' => 2.8,
        'ar_scale' => 0.512,
        'rotation' => [0, 0, 0],
        'yaw' => -2.0944,            // −120°
        'pitch' => 0.05,
        'motions' => [['type' => 'sway', 'speed' => 1, 'range' => 10]],
        'description' => 'Belum di sini. Cari benda yang berputar.',
    ],
];

// "Pos 10" must not sort before "Pos 2": order by the number in the name, then by name.
$spots = IndoorMapSpot::where('indoor_map_id', $plan->id)->where('is_active', true)->get()
    ->sortBy(fn ($s) => sprintf('%04d %s', (int) preg_replace('/\D+/', '', $s->name) ?: 9999, $s->name))
    ->values();

echo ($apply ? 'MENULIS' : 'UJI COBA (tidak menulis apa pun)')." — denah #{$plan->id} \"{$plan->name}\", {$spots->count()} pos aktif\n\n";

$made = 0;
$skipped = 0;

foreach ($spots as $spot) {
    if ($spot->game_location_id) {
        echo "  lewati  {$spot->name}: sudah punya AR Outpost #{$spot->game_location_id}\n";
        $skipped++;

        continue;
    }

    echo '  '.($apply ? 'buat   ' : 'akan   ')." {$spot->name}: AR Outpost + ".count($objects)." objek ("
        .collect($objects)->pluck('title')->implode(', ').")\n";

    if (! $apply) {
        $made++;

        continue;
    }

    DB::transaction(function () use ($spot, $plan, $admin, $objects, &$made) {
        $location = GameLocation::create([
            // The plan's name is part of it: Tim 02's plan has posts with the same names, and
            // Outpost Access lists every outpost in one column.
            'name' => "{$plan->name} — {$spot->name}",
            // NOT NULL with no default — a row made without it fails with SQLSTATE 1364.
            'description' => "Pos AR untuk {$spot->name} di denah {$plan->name}.",
            'is_active' => true,
            'created_by' => $admin->id,
            'target_type' => 'all_users',
            'experience_type' => 'ar',
            // Indoor: the crew opens it in Outpost Access. Manual replaces the radius entirely.
            'access_mode' => 'manual',
            'radius' => 50,
            'ar_model_id' => 6,
        ]);

        foreach ($objects as $i => $o) {
            Hotspot::create([
                'game_location_id' => $location->id,
                'title' => $o['title'],
                'description' => str_replace('%POS%', $spot->name, $o['description']),
                'ar_model_id' => $o['ar_model_id'],
                'ar_distance' => $o['ar_distance'],
                'ar_scale' => $o['ar_scale'],
                'ar_rotation_x' => $o['rotation'][0],
                'ar_rotation_y' => $o['rotation'][1],
                'ar_rotation_z' => $o['rotation'][2],
                'pitch' => $o['pitch'],
                'yaw' => $o['yaw'],
                'ar_motions' => $o['motions'],
                'media_type' => null,     // text only when tapped
                'points_value' => 0,
                'tour_order' => $i + 1,
                'type' => 'info',
                'hotspot_type' => 'info',
                'is_active' => true,
            ]);
        }

        $spot->update(['game_location_id' => $location->id]);
        $made++;
    });
}

echo "\n".($apply ? "selesai: {$made} pos dibuat" : "rencana: {$made} pos akan dibuat").", {$skipped} dilewati.\n";
