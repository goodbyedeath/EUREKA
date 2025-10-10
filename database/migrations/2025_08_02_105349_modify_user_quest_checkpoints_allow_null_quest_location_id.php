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
        Schema::table('user_quest_checkpoints', function (Blueprint $table) {
            // Allow quest_location_id to be null for live tracking entries
            $table->unsignedBigInteger('quest_location_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_quest_checkpoints', function (Blueprint $table) {
            // Revert back to not allowing null
            $table->unsignedBigInteger('quest_location_id')->nullable(false)->change();
        });
    }
};
