<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Points for tapping a 3D object, which the AR editor has always offered and nothing ever paid.
 *
 * `hotspots.points_value` was written by the authoring form and shown to the team in the object
 * sheet ("+15 points"), but no endpoint accepted the tap and no sum included it: the number was
 * decoration (operator, 23 Sep — a client requirement). This is where a claim is recorded.
 *
 * One row per object per account, and the team is checked before awarding, so a team on two phones
 * collects once. `points` is a snapshot: an admin who later re-prices the object must not silently
 * change what a team was already paid.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotspot_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotspot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Denormalised so a report can group by post without joining back through hotspots,
            // and so the row still says where it happened if the object is later moved.
            $table->foreignId('game_location_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('points')->default(0);
            $table->timestamp('claimed_at');
            $table->timestamps();

            $table->unique(['hotspot_id', 'user_id'], 'hotspot_claim_once');
            $table->index(['user_id', 'claimed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotspot_claims');
    }
};
