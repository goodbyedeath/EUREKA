<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Each team can be given its own floor plan (operator, 16 Sep).
 *
 * Indoor events run several teams through different routes at once, so the plan a team sees is a
 * property of the team, not of the event. Null keeps the old behaviour: the team falls back to the
 * plan on the START code it scanned, which is what every existing team does.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->foreignId('indoor_map_id')->nullable()->after('department')
                ->constrained('indoor_maps')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropForeign(['indoor_map_id']);
            $table->dropColumn('indoor_map_id');
        });
    }
};
