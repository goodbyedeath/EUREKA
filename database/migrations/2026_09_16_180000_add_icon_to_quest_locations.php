<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Keep the pin's emoji when it becomes a post.
 *
 * Operator, 16 Sep: the flag, star and camera drawn in the tracker disappeared from the maps.
 * They had become posts — and a post had nowhere to keep the symbol, so every one of them was
 * reduced to a coloured dot the maps did not even draw yet. The column is optional: a post typed
 * in by hand simply has none.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quest_locations', function (Blueprint $table) {
            $table->string('icon', 16)->nullable()->after('marker_color');
        });

        // Posts already promoted from a pin: take the symbol back from the pin they came from.
        foreach (DB::table('map_route_markers')->whereNotNull('quest_location_id')->get() as $marker) {
            if (filled($marker->icon)) {
                DB::table('quest_locations')->where('id', $marker->quest_location_id)->update(['icon' => $marker->icon]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('quest_locations', function (Blueprint $table) {
            $table->dropColumn('icon');
        });
    }
};
