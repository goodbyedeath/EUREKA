<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The check-in evidence columns the code has always written but the table never had.
 *
 * `UserQuestCheckpoint` lists accuracy, distance_from_center and device_info as fillable,
 * and QuestLocationController::checkIn passes the first two on every check-in — but none
 * of them exist, so the insert threw "Unknown column 'accuracy'". The controller catches
 * the exception and answers "Check-in failed. Please try again.", so a team standing
 * exactly on the marker was told to move closer, with nothing surfaced anywhere.
 *
 * Adding the columns rather than dropping them from the insert: distance_from_center is
 * the record of how close the team actually got, and accuracy says how much that number
 * can be trusted. Both are worth keeping once a result is disputed after the event.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_quest_checkpoints', function (Blueprint $table) {
            // Metres, as reported by the device. Null when the client does not send it.
            $table->float('accuracy')->nullable()->after('checked_at');
            // Metres from the marker at the moment of check-in.
            $table->float('distance_from_center')->nullable()->after('accuracy');
            // Free-form client string (model, OS, app version) for chasing odd fixes.
            $table->string('device_info')->nullable()->after('distance_from_center');
        });
    }

    public function down(): void
    {
        Schema::table('user_quest_checkpoints', function (Blueprint $table) {
            $table->dropColumn(['accuracy', 'distance_from_center', 'device_info']);
        });
    }
};
