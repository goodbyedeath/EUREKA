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
        Schema::table('game_assessments', function (Blueprint $table) {
            // Change deposit, penalty, and total_deposit from decimal to integer
            $table->integer('deposit')->default(0)->change();
            $table->integer('penalty')->default(0)->change();
            $table->integer('total_deposit')->default(0)->change();
            
            // Check if additional_points column exists and change it too
            if (Schema::hasColumn('game_assessments', 'additional_points')) {
                $table->integer('additional_points')->default(0)->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_assessments', function (Blueprint $table) {
            // Revert back to decimal
            $table->decimal('deposit', 10, 2)->default(0.00)->change();
            $table->decimal('penalty', 10, 2)->default(0.00)->change();
            $table->decimal('total_deposit', 10, 2)->default(0.00)->change();
            
            // Revert additional_points if it exists
            if (Schema::hasColumn('game_assessments', 'additional_points')) {
                $table->decimal('additional_points', 10, 2)->default(0.00)->change();
            }
        });
    }
};