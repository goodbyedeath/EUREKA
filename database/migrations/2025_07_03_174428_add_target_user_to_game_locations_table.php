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
        Schema::table('game_locations', function (Blueprint $table) {
            $table->foreignId('target_user_id')->nullable()->after('created_by')
                  ->constrained('users')->onDelete('cascade');
            $table->enum('target_type', ['all_users', 'specific_user'])->default('all_users')->after('target_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_locations', function (Blueprint $table) {
            $table->dropForeign(['target_user_id']);
            $table->dropColumn(['target_user_id', 'target_type']);
        });
    }
};