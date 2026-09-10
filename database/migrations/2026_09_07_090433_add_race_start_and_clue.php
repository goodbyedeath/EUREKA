<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The indoor opening sequence: scan START, answer a clue, then the plan appears.
     *
     * All three live on the indoor map because in practice one venue is one event, and
     * the START code, the clue and the plan are the same act of starting that event.
     * Posts are random, so the clue is a single gate at the beginning rather than one
     * per outpost — nothing here implies an order.
     */
    public function up(): void
    {
        Schema::table('indoor_maps', function (Blueprint $table) {
            $table->string('start_qr_code')->nullable()->unique()->after('image_path');
            $table->text('clue_question')->nullable()->after('start_qr_code');
            $table->string('clue_answer')->nullable()->after('clue_question');
        });

        Schema::create('race_sessions', function (Blueprint $table) {
            $table->id();
            // One account per team, so this is the team's run.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('indoor_map_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('clue_solved_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            // Wrong clue answers cost points later; the count is the evidence.
            $table->unsignedSmallInteger('clue_wrong_attempts')->default(0);

            $table->timestamps();
            $table->unique(['user_id', 'indoor_map_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('race_sessions');
        Schema::table('indoor_maps', function (Blueprint $table) {
            $table->dropColumn(['start_qr_code', 'clue_question', 'clue_answer']);
        });
    }
};
