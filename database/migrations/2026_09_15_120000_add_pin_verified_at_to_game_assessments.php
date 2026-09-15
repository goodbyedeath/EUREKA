<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // When the server last accepted the facilitator PIN for this game. A score the app queued on a
        // weak signal may arrive a little after time-out; it is accepted only if this moment was before
        // the deadline — the server's clock, never the phone's.
        Schema::table('game_assessments', function (Blueprint $table) {
            $table->timestamp('pin_verified_at')->nullable()->after('assessed_at');
        });
    }

    public function down(): void
    {
        Schema::table('game_assessments', function (Blueprint $table) {
            $table->dropColumn('pin_verified_at');
        });
    }
};
