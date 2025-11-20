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
            if (!Schema::hasColumn('game_assessments', 'additional_points')) {
                $table->integer('additional_points')->default(0)->after('penalty');
            }
            if (!Schema::hasColumn('game_assessments', 'facilitator_photo')) {
                $table->text('facilitator_photo')->nullable()->after('assessed_at');
            }
            if (!Schema::hasColumn('game_assessments', 'facilitator_photo_captured_at')) {
                $table->timestamp('facilitator_photo_captured_at')->nullable()->after('facilitator_photo');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_assessments', function (Blueprint $table) {
            $table->dropColumn(['additional_points', 'facilitator_photo', 'facilitator_photo_captured_at']);
        });
    }
};
