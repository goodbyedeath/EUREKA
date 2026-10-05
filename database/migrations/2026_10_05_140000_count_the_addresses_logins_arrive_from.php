<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SCALING-PLAN.md §3e rests on a claim about the venue network: that 5,000 phones arrive from one
 * address. Whether that is true is a property of the network, not of the players, and it decides
 * whether every per-address limit in this app is per-phone or shared by the whole event.
 *
 * Nothing could answer it. This can: one row per address logins arrive from, so `/admin/kesiapan`
 * can say "5,000 logins from 1 address — every per-address limit is effectively global" or "from
 * 4,812 addresses — they are per-phone", before an event rather than during one.
 *
 * Small by construction: one row per address, not per login, and the row counts the logins.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_sources', function (Blueprint $table) {
            $table->id();
            // 45 characters: an IPv6 address with an embedded IPv4 mapping is the longest form.
            $table->string('ip', 45)->unique();
            $table->unsignedInteger('hits')->default(0);
            $table->unsignedInteger('failures')->default(0);
            $table->timestamp('first_seen_at')->nullable();
            // Indexed: every question asked of this table is "in the last N minutes".
            $table->timestamp('last_seen_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_sources');
    }
};
