<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * An object may now have several motions at once.
     *
     * One `ar_anim` column could hold one choice, and the single `ar_anim_range` beside it
     * could not serve two motions that measure different things — bob is metres, sway is
     * degrees. A list of {type, speed, range} gives each motion its own parameters and
     * lets them compose: they touch different axes, so spin + bob is simply both.
     */
    public function up(): void
    {
        Schema::table('hotspots', function (Blueprint $table) {
            $table->json('ar_motions')->nullable()->after('ar_rotation_z');
        });

        // Carry the single motion across so nothing an admin already placed is lost.
        foreach (DB::table('hotspots')->whereNotNull('ar_anim')->where('ar_anim', '!=', 'none')->get() as $h) {
            DB::table('hotspots')->where('id', $h->id)->update([
                'ar_motions' => json_encode([[
                    'type' => $h->ar_anim,
                    'speed' => (float) ($h->ar_anim_speed ?: 1),
                    'range' => (float) ($h->ar_anim_range ?: 1),
                ]]),
            ]);
        }

        Schema::table('hotspots', function (Blueprint $table) {
            $table->dropColumn(['ar_anim', 'ar_anim_speed', 'ar_anim_range']);
        });
    }

    public function down(): void
    {
        Schema::table('hotspots', function (Blueprint $table) {
            $table->string('ar_anim', 16)->default('none')->after('ar_rotation_z');
            $table->decimal('ar_anim_speed', 4, 2)->default(1)->after('ar_anim');
            $table->decimal('ar_anim_range', 5, 2)->default(1)->after('ar_anim_speed');
        });

        // Only the first motion survives going back; the rest cannot be represented.
        foreach (DB::table('hotspots')->whereNotNull('ar_motions')->get() as $h) {
            $first = json_decode($h->ar_motions, true)[0] ?? null;
            if ($first) {
                DB::table('hotspots')->where('id', $h->id)->update([
                    'ar_anim' => $first['type'] ?? 'none',
                    'ar_anim_speed' => $first['speed'] ?? 1,
                    'ar_anim_range' => $first['range'] ?? 1,
                ]);
            }
        }

        Schema::table('hotspots', function (Blueprint $table) {
            $table->dropColumn('ar_motions');
        });
    }
};
