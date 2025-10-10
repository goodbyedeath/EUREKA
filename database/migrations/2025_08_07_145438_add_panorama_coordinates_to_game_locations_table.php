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
            // Add panorama default view coordinates
            $table->decimal('default_pitch', 8, 5)->nullable()->after('coordinate_y')
                  ->comment('Default vertical view angle for panorama (-90 to 90 degrees)');
            $table->decimal('default_yaw', 8, 5)->nullable()->after('default_pitch')
                  ->comment('Default horizontal view angle for panorama (-180 to 180 degrees)');
            
            // Convert existing coordinate columns to decimal for better precision (GPS coordinates)
            $table->decimal('coordinate_x', 10, 7)->nullable()->change()
                  ->comment('Geographic X coordinate (longitude-like)');
            $table->decimal('coordinate_y', 10, 7)->nullable()->change()
                  ->comment('Geographic Y coordinate (latitude-like)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_locations', function (Blueprint $table) {
            // Remove panorama coordinate columns
            $table->dropColumn(['default_pitch', 'default_yaw']);
            
            // Revert coordinate columns to integers
            $table->integer('coordinate_x')->nullable()->change();
            $table->integer('coordinate_y')->nullable()->change();
        });
    }
};
