<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A manual correction to a team's score, with who made it and why.
 *
 * Asked for on 25 Sep: "harusnya ada sistem untuk penambahan score manual untuk masing-masing team,
 * jika ada trouble dari sisi sistem". A live event cannot wait for a bug to be fixed — a facilitator's
 * phone dies mid-scoring, a questionnaire is handed in twice, a booth's QR never printed — so the crew
 * must be able to put the number right and say what happened.
 *
 * A ledger rather than a column: during an event the question is never only "what is the score" but
 * "who changed it, when, and why", and one row per correction answers that and can be undone on its
 * own. `points` is signed — a correction may take points away.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_score_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->integer('points');
            $table->string('reason');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Read per team, newest first, on every score calculation.
            $table->index(['team_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_score_adjustments');
    }
};
