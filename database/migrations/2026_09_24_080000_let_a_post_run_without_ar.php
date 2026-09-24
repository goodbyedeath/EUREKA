<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A post that works without the 3D camera.
 *
 * Operator, 24 Sep: the flow is right, but not every phone can run ARCore — two of four trial teams
 * were stopped at their first booth by a device that is simply not on Google's list. The content of a
 * post is its objects' text (the clue that names the paper QR's hiding place) and their bonus points;
 * the camera is only how that text is reached. So a post can now serve the same objects as a plain
 * list, and the game carries on.
 *
 * Default true: every existing post keeps its camera.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_locations', function (Blueprint $table) {
            $table->boolean('ar_enabled')->default(true)->after('experience_type');
        });
    }

    public function down(): void
    {
        Schema::table('game_locations', function (Blueprint $table) {
            $table->dropColumn('ar_enabled');
        });
    }
};
