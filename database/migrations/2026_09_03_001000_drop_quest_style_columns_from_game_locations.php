<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A game location is only the 3D experience now — it is not a place you check in to.
 *
 * These columns were copied from quest_locations when game locations were first
 * modelled on them. Nothing ever read them for a game location: GameManager::save()
 * wrote fixed defaults (radius 50, 10 points, 1 check-in) and `what_to_do` was just a
 * duplicate of `description`. `image_path` had a reader on the team card but no
 * uploader, so it was always the placeholder gradient.
 *
 * Check-in, radius and points belong to quest_locations, which keeps its own copies.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_locations', function (Blueprint $table) {
            $table->dropColumn([
                'what_to_do',
                'radius',
                'max_check_ins_per_user',
                'quest_points',
                'image_path',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('game_locations', function (Blueprint $table) {
            $table->text('what_to_do')->nullable();
            $table->integer('radius')->default(50);
            $table->integer('max_check_ins_per_user')->default(1);
            $table->integer('quest_points')->default(10);
            $table->string('image_path')->nullable();
        });
    }
};
