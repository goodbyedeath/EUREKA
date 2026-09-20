<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "Foto bersama": the team photographs itself at the post (operator, 20 Sep).
 *
 * The admin uploads a frame — a PNG with a transparent middle, the event's border and logo — and
 * the app lays it over the camera, so every team's photo comes back in the same dress and is worth
 * posting. Two columns because a frame is a file, not one of the answer `images`, and the caption
 * is what the app offers when the team shares it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->string('frame_path', 500)->nullable()->after('images');
            $table->string('share_caption', 500)->nullable()->after('frame_path');
        });

        // The answer to this type is a stored photo path, which does not fit the old column.
        DB::statement('ALTER TABLE user_answers MODIFY answer TEXT NULL');
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn(['frame_path', 'share_caption']);
        });
    }
};
