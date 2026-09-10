<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-object orientation, authored while placing.
 *
 * Stored in degrees rather than radians: these are typed and read by admins in the
 * placement UI, and degrees are what the sliders show. The viewer converts once.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotspots', function (Blueprint $table) {
            $table->decimal('ar_rotation_x', 6, 2)->default(0)->after('ar_scale');
            $table->decimal('ar_rotation_y', 6, 2)->default(0)->after('ar_rotation_x');
            $table->decimal('ar_rotation_z', 6, 2)->default(0)->after('ar_rotation_y');
        });
    }

    public function down(): void
    {
        Schema::table('hotspots', function (Blueprint $table) {
            $table->dropColumn(['ar_rotation_x', 'ar_rotation_y', 'ar_rotation_z']);
        });
    }
};
