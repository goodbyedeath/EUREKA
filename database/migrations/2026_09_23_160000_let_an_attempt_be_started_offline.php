<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Offline phase 2: a questionnaire opened with no signal.
 *
 * The attempt is born on the phone, so the phone names it — `client_token` is that name, and it is
 * what makes the upload idempotent. A team that regains signal, loses it and tries again must not
 * end up with two attempts at the same post; the token is unique, so the second upload finds the
 * first attempt instead of creating another.
 *
 * `started_offline` is kept for the record: a time reconstructed from the phone's stopwatch is not
 * the same evidence as a start the server timed, and a dispute at the finish line deserves to know
 * which it was.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->string('client_token', 64)->nullable()->unique()->after('game_location_id');
            $table->boolean('started_offline')->default(false)->after('client_token');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropUnique(['client_token']);
            $table->dropColumn(['client_token', 'started_offline']);
        });
    }
};
