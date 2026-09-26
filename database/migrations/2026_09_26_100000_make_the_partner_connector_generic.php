<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * FEKDI x IFSE is over; the tunnel it was built in stays (operator, 26 Sep).
 *
 * The mechanism is worth keeping — import a partner's member list, mirror each member's team total, send
 * only the increases, never retry an unknown outcome — so it is renamed to something the next partner can
 * use: `partner_participants`, `partner_participant_id`, `external_id` instead of `google_id`.
 *
 * The imported FEKDI rows are dropped: they are one client's member list, not ours to keep after the
 * event, and nothing references them (no team member was ever linked to one).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fekdi_participants') && ! Schema::hasTable('partner_participants')) {
            Schema::rename('fekdi_participants', 'partner_participants');
        }

        if (Schema::hasTable('partner_participants')) {
            // The event's own list, removed with the event. Truncate would fight the foreign key below.
            DB::table('partner_participants')->delete();

            if (Schema::hasColumn('partner_participants', 'google_id')) {
                Schema::table('partner_participants', function (Blueprint $table) {
                    $table->renameColumn('google_id', 'external_id');
                });
            }
        }

        if (Schema::hasColumn('team_members', 'fekdi_participant_id')) {
            Schema::table('team_members', function (Blueprint $table) {
                $table->renameColumn('fekdi_participant_id', 'partner_participant_id');
            });
        }

        // The admin switch keeps its state under a name that does not name a client.
        $old = DB::table('app_settings')->where('key', 'fekdi_enabled')->first();
        if ($old) {
            DB::table('app_settings')->updateOrInsert(
                ['key' => 'partner_connector_enabled'],
                ['value' => $old->value, 'updated_by' => $old->updated_by, 'updated_at' => now(), 'created_at' => now()],
            );
            DB::table('app_settings')->where('key', 'fekdi_enabled')->delete();
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('team_members', 'partner_participant_id')) {
            Schema::table('team_members', function (Blueprint $table) {
                $table->renameColumn('partner_participant_id', 'fekdi_participant_id');
            });
        }

        if (Schema::hasTable('partner_participants')) {
            if (Schema::hasColumn('partner_participants', 'external_id')) {
                Schema::table('partner_participants', function (Blueprint $table) {
                    $table->renameColumn('external_id', 'google_id');
                });
            }
            Schema::rename('partner_participants', 'fekdi_participants');
        }

        $new = DB::table('app_settings')->where('key', 'partner_connector_enabled')->first();
        if ($new) {
            DB::table('app_settings')->updateOrInsert(['key' => 'fekdi_enabled'],
                ['value' => $new->value, 'updated_at' => now(), 'created_at' => now()]);
            DB::table('app_settings')->where('key', 'partner_connector_enabled')->delete();
        }
    }
};
