<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The 360° viewer is gone, so its columns on game_locations go with it.
 *
 * `map_image_path` held the panorama image (files already deleted), and
 * default_pitch/default_yaw set where that viewer first pointed — AR calibrates
 * from the QR code instead.
 *
 * NOTE: quest_locations has its own, unrelated map_image_path. Do not touch it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_locations', function (Blueprint $table) {
            $table->dropColumn(['map_image_path', 'default_pitch', 'default_yaw']);
        });
    }

    public function down(): void
    {
        Schema::table('game_locations', function (Blueprint $table) {
            $table->string('map_image_path')->nullable()->after('image_path');
            $table->decimal('default_pitch', 8, 2)->nullable();
            $table->decimal('default_yaw', 8, 2)->nullable();
        });
    }
};
