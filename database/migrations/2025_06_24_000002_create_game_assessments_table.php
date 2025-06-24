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
        Schema::create('game_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_attempt_id')->constrained()->onDelete('cascade');
            $table->foreignId('question_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('assessed_by')->nullable()->constrained('users')->onDelete('set null'); // Admin who did the assessment
            $table->decimal('deposit', 10, 2)->default(0);
            $table->decimal('penalty', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->decimal('total_deposit', 10, 2)->default(0); // Calculated field: deposit - penalty
            $table->boolean('is_assessed')->default(false);
            $table->timestamp('assessed_at')->nullable();
            $table->timestamps();
            
            // Ensure unique assessment per user per question per attempt
            $table->unique(['quiz_attempt_id', 'question_id', 'user_id'], 'unique_game_assessment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_assessments');
    }
};