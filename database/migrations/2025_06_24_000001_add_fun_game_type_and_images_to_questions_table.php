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
        Schema::table('questions', function (Blueprint $table) {
            // Add fun_game to the existing enum type
            $table->dropColumn('type');
        });
        
        Schema::table('questions', function (Blueprint $table) {
            $table->enum('type', ['multiple_choice', 'text', 'true_false', 'fun_game'])->default('text')->after('question');
            $table->text('description')->nullable()->after('question'); // For fun game instructions
            $table->json('images')->nullable()->after('description'); // Store multiple image paths
            $table->string('game_name')->nullable()->after('description'); // For fun game name
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn(['description', 'images', 'game_name']);
            $table->dropColumn('type');
        });
        
        Schema::table('questions', function (Blueprint $table) {
            $table->enum('type', ['multiple_choice', 'text', 'true_false'])->default('text')->after('question');
        });
    }
};