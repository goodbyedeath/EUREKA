<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Teams start at 0, not 1000.
 *
 * Operator, 16 Sep. The 1000-point opening balance came from the original "deposit" idea, where a
 * team paid points into a game and could lose them. Scoring has since moved to earned points, so a
 * starting balance only made every score look 1000 higher than the points actually won.
 *
 * Teams that have not scored yet (points == initial_points) are moved to 0 as well, so the boards
 * do not show a mix of the two. A team that already has a score is left exactly as it is — its
 * total was computed against the balance it started with.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->integer('points')->default(0)->change();
            $table->integer('initial_points')->default(0)->change();
        });

        DB::table('teams')
            ->whereColumn('points', 'initial_points')
            ->update(['points' => 0, 'initial_points' => 0]);
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->integer('points')->default(1000)->change();
            $table->integer('initial_points')->default(1000)->change();
        });
    }
};
