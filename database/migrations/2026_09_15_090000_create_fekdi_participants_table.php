<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Participants pulled from the FEKDI x IFSE website (24–27 Sep 2026), plus what EUREKA adds:
        // the team they were registered to and the points they carry — the team's total, per member.
        Schema::create('fekdi_participants', function (Blueprint $table) {
            $table->id();
            $table->string('google_id', 64)->unique();          // the client's member id
            $table->string('name')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('avatar', 500)->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('imported_at')->nullable();

            $table->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->string('team_name')->nullable();            // survives the team being archived
            $table->boolean('is_leader')->default(false);

            $table->integer('points')->default(0);              // current team total
            $table->integer('points_synced')->default(0);       // what the client has accepted for this team
            $table->integer('lifetime_sent')->default(0);       // every increase ever accepted
            $table->string('sync_state', 16)->default('idle');  // idle | sending | uncertain | error
            $table->text('sync_error')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });

        Schema::table('team_members', function (Blueprint $table) {
            $table->foreignId('fekdi_participant_id')->nullable()->after('user_id')
                ->constrained('fekdi_participants')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('team_members', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fekdi_participant_id');
        });
        Schema::dropIfExists('fekdi_participants');
    }
};
