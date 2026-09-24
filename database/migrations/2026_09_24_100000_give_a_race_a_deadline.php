<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A race can now end in two ways (operator, 24 Sep): every post cleared, or the time on its START
 * code running out.
 *
 * The limit lives on `race_starts` because each rotation has its own code, so a session can be timed
 * differently per route — and because the session already records which code started it
 * (`race_sessions.race_start_id`), the deadline is always recoverable from the row itself.
 *
 * `finished_reason` says which of the two happened. Without it a finished race cannot be explained to
 * a team that asks why its clock stopped, and the kiosk cannot tell "done" from "out of time".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('race_starts', function (Blueprint $table) {
            // Null = no deadline, which is how every existing code behaves today.
            $table->unsignedSmallInteger('time_limit_minutes')->nullable()->after('indoor_map_id');
        });

        Schema::table('race_sessions', function (Blueprint $table) {
            $table->string('finished_reason', 20)->nullable()->after('finished_at');
        });
    }

    public function down(): void
    {
        Schema::table('race_sessions', function (Blueprint $table) {
            $table->dropColumn('finished_reason');
        });

        Schema::table('race_starts', function (Blueprint $table) {
            $table->dropColumn('time_limit_minutes');
        });
    }
};
