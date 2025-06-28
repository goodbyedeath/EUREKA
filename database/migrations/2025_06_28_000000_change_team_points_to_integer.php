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
        Schema::table('teams', function (Blueprint $table) {
            // Change points and initial_points from decimal to integer
            $table->integer('points')->default(1000)->change();
            $table->integer('initial_points')->default(1000)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            // Revert back to decimal
            $table->decimal('points', 10, 2)->default(1000.00)->change();
            $table->decimal('initial_points', 10, 2)->default(1000.00)->change();
        });
    }
};