<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Two ways an outpost can open its 3D camera.
     *
     * Outdoor posts gate on the radius, which is already built. Indoor posts cannot —
     * GPS does not work through a roof, which is exactly why the flow puts a person in
     * the loop instead: crew radio in, an admin opens that team's access by hand.
     *
     * So 'manual' bypasses the geofence entirely rather than adding to it.
     */
    public function up(): void
    {
        Schema::table('game_locations', function (Blueprint $table) {
            $table->string('access_mode', 16)->default('geofence')->after('radius');
        });

        Schema::create('game_location_unlocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_location_id')->constrained()->cascadeOnDelete();
            // One account per team, so this is per team in practice.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('granted_at')->nullable();
            // Revoking keeps the row so the audit trail of who opened what survives.
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->unique(['game_location_id', 'user_id']);
            $table->index(['user_id', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_location_unlocks');
        Schema::table('game_locations', function (Blueprint $table) {
            $table->dropColumn('access_mode');
        });
    }
};
