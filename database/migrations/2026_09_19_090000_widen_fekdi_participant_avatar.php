<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Google profile-picture URLs do not fit in 500 characters.
 *
 * Operator, 19 Sep: "Gagal menarik data peserta … Data too long for column 'avatar'". FEKDI sends
 * each participant's Google avatar URL as-is, and those carry a signed token that ran to about
 * 1,500 characters for one participant — so a single long URL aborted the whole import. TEXT holds
 * 64 KB, far past any URL a browser will accept.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fekdi_participants', function (Blueprint $table) {
            $table->text('avatar')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('fekdi_participants', function (Blueprint $table) {
            $table->string('avatar', 500)->nullable()->change();
        });
    }
};
