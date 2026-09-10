<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Idle motion for placed objects.
     *
     * Stored as parameters, never as baked keyframes: the browser and the Android client
     * both animate from the same numbers, so a moving object cannot end up somewhere
     * different on two devices. The exact formulas live in ArExperienceController's
     * ANIMATIONS constant and must be mirrored, not reinvented.
     */
    public function up(): void
    {
        Schema::table('hotspots', function (Blueprint $table) {
            // none | spin | bob | orbit | sway
            $table->string('ar_anim', 16)->default('none')->after('ar_rotation_z');
            // Multiplier on the base rate of whichever motion is chosen.
            $table->decimal('ar_anim_speed', 4, 2)->default(1.00)->after('ar_anim');
            // Metres for bob, degrees of bearing for sway; unused by spin and orbit.
            $table->decimal('ar_anim_range', 5, 2)->default(1.00)->after('ar_anim_speed');
        });
    }

    public function down(): void
    {
        Schema::table('hotspots', function (Blueprint $table) {
            $table->dropColumn(['ar_anim', 'ar_anim_speed', 'ar_anim_range']);
        });
    }
};
