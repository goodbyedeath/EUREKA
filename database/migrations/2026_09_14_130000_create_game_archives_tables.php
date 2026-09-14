<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per finished game session: a frozen snapshot of teams, leaderboard, per-post
        // results and photo paths. The live rows it was taken from are deleted afterwards.
        Schema::create('game_archives', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->text('notes')->nullable();
            $table->longText('snapshot');
            $table->unsignedInteger('team_count')->default(0);
            $table->unsignedInteger('account_count')->default(0);
            $table->string('winner_name')->nullable();
            $table->integer('winner_score')->nullable();
            $table->string('storage_dir');
            // No FK: an archive must outlive the admin who made it.
            $table->unsignedBigInteger('archived_by')->nullable();
            $table->timestamp('archived_at');
            $table->timestamps();
        });

        // Tombstones for the removed accounts: their e-mail and their token hashes, so an APK still
        // installed from that session is told "game ended" instead of "wrong password".
        Schema::create('game_archive_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_archive_id')->constrained('game_archives')->cascadeOnDelete();
            $table->string('kind', 10);      // email | token
            $table->string('value', 191);    // lower-cased e-mail, or Sanctum's sha256 token hash
            $table->timestamps();
            $table->index(['kind', 'value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_archive_credentials');
        Schema::dropIfExists('game_archives');
    }
};
