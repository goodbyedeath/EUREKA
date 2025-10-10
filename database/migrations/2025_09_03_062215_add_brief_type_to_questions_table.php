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
            // Drop the existing enum column
            $table->dropColumn('type');
        });
        
        Schema::table('questions', function (Blueprint $table) {
            // Re-create the enum column with brief type added
            $table->enum('type', ['multiple_choice', 'text', 'true_false', 'fun_game', 'brief'])->default('text')->after('question');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            // Drop the enum column
            $table->dropColumn('type');
        });
        
        Schema::table('questions', function (Blueprint $table) {
            // Re-create the enum column without brief type
            $table->enum('type', ['multiple_choice', 'text', 'true_false', 'fun_game'])->default('text')->after('question');
        });
    }
};
