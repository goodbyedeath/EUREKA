<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Published Android builds, reported by the client itself.
 *
 * `latest_code` lived in config and went four releases stale within a day, because the server has
 * no sight of what the client publishes — builds go to a shared Drive folder nobody here can read.
 * The client is the only party that knows, so it tells us.
 *
 * `minimum_code` deliberately does NOT live here. A remotely settable minimum is a way to lock
 * every team out of an event with one request; that stays in config, where only the operator
 * reaches it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_releases', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('version_code')->unique();
            $table->string('version_name', 32);
            $table->string('download_url', 500)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_releases');
    }
};
