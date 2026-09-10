<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Which posts a team must clear before the race clock stops.
     *
     * "All posts" is ambiguous the moment an event has a bonus post or one a team is
     * allowed to skip, so it is stated rather than inferred: only questionnaires flagged
     * here count toward finishing. Bonus posts are switched off and still score points —
     * they simply do not hold the clock open.
     *
     * Defaults to true so every existing post keeps counting; an admin opts a post out.
     */
    public function up(): void
    {
        Schema::table('questionnaires', function (Blueprint $table) {
            $table->boolean('counts_toward_finish')->default(true)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('questionnaires', function (Blueprint $table) {
            $table->dropColumn('counts_toward_finish');
        });
    }
};
