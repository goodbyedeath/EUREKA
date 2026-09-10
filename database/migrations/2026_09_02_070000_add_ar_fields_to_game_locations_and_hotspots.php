<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the 3D/AR experience alongside the existing 360° panorama.
 *
 * Deliberately additive: `experience_type` defaults to 'panorama', so every
 * existing game location keeps working exactly as it does today. A location only
 * becomes an AR experience once an admin uploads a model and switches it over.
 * That also means AR can be trialled on one outpost without touching the rest.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_locations', function (Blueprint $table) {
            $table->string('experience_type', 20)->default('panorama')->after('map_image_path');
            $table->string('ar_model_path')->nullable()->after('experience_type');
            $table->string('ar_sky_path')->nullable()->after('ar_model_path');
        });

        Schema::table('hotspots', function (Blueprint $table) {
            // Per-hotspot 3D object. Falls back to the location's model when null.
            $table->string('ar_model_path')->nullable()->after('media_path');
            // Metres in front of the viewer, and a plain multiplier on the model.
            $table->decimal('ar_distance', 6, 2)->default(2.00)->after('ar_model_path');
            $table->decimal('ar_scale', 6, 3)->default(1.000)->after('ar_distance');
        });
    }

    public function down(): void
    {
        Schema::table('game_locations', function (Blueprint $table) {
            $table->dropColumn(['experience_type', 'ar_model_path', 'ar_sky_path']);
        });

        Schema::table('hotspots', function (Blueprint $table) {
            $table->dropColumn(['ar_model_path', 'ar_distance', 'ar_scale']);
        });
    }
};
