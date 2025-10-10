<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('game_locations', function (Blueprint $table) {
            // Remove geographic coordinate columns (no longer needed for panorama-only locations)
            $table->dropColumn(['coordinate_x', 'coordinate_y']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_locations', function (Blueprint $table) {
            // Restore coordinate columns for rollback
            $table->decimal('coordinate_x', 10, 7)->nullable()
                  ->comment('Geographic X coordinate (longitude-like)');
            $table->decimal('coordinate_y', 10, 7)->nullable()
                  ->comment('Geographic Y coordinate (latitude-like)');
        });
    }
};