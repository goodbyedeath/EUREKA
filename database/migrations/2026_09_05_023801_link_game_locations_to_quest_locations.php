<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tie an AR outpost to the post it belongs to.
     *
     * The admin already sets a coordinate for every post in Quest Locations, so making
     * them capture GPS again in the AR view was duplicated work — and two points that
     * could quietly disagree. With a link the post is the single source of truth.
     *
     * Nullable: an AR outpost that belongs to no post keeps using its own coordinates,
     * bound on site. Deleting a post nulls the link rather than deleting the outpost.
     */
    public function up(): void
    {
        Schema::table('game_locations', function (Blueprint $table) {
            $table->foreignId('quest_location_id')
                ->nullable()
                ->after('experience_type')
                ->constrained('quest_locations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('game_locations', function (Blueprint $table) {
            $table->dropForeign(['quest_location_id']);
            $table->dropColumn('quest_location_id');
        });
    }
};
