<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per emergency race stop: who, when, and what it wiped (counts only — the
        // operator chose no backup of the data itself).
        Schema::create('race_resets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reset_by')->nullable();   // no FK: the log outlives the admin
            $table->timestamp('reset_at');
            $table->json('summary')->nullable();
            $table->timestamps();
        });

        // The attempt and assessment ids the stop deleted, so an app still holding one is told
        // "race_reset" instead of a bare "not found".
        Schema::create('race_reset_refs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('race_reset_id')->constrained('race_resets')->cascadeOnDelete();
            $table->string('kind', 12);                // attempt | assessment
            $table->unsignedBigInteger('ref_id');
            $table->index(['kind', 'ref_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('race_reset_refs');
        Schema::dropIfExists('race_resets');
    }
};
