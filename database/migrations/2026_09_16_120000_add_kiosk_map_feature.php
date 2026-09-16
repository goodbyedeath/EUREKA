<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * A switch for the map half of the LED board.
 *
 * Operator, 16 Sep: indoor events still showed "TEAM POSITIONS" with a map nobody is walking on.
 * Enabled by default so an outdoor board keeps behaving as it did; the crew turns it off for an
 * indoor game and the leaderboard takes the whole screen.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('feature_settings')->where('feature_key', 'kiosk_map')->exists()) {
            return;
        }

        $after = (int) DB::table('feature_settings')->where('feature_key', 'gps_tracking')->value('sort_order');

        DB::table('feature_settings')->insert([
            'feature_key' => 'kiosk_map',
            'feature_name' => 'Kiosk Map Panel',
            'description' => 'Peta dan posisi tim di layar kiosk LED. Matikan untuk acara indoor: papan skor memakai seluruh layar.',
            'is_enabled' => true,
            'sort_order' => $after ?: 99,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('feature_settings')->where('feature_key', 'kiosk_map')->delete();
    }
};
