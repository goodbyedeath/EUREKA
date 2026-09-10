<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The start line, for both modes.
     *
     * The START code used to live on `indoor_maps`, which made it unreachable outdoors —
     * an outdoor event has no plan to hang it from, so it had no race clock at all. A
     * start code belongs to the event, not to a picture of a venue, so it moves here.
     *
     * `indoor_map_id` is what makes one code behave as indoor and another as outdoor:
     * set, the scan leads on to that venue's clue; null, it simply starts the clock.
     */
    public function up(): void
    {
        Schema::create('race_starts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->foreignId('indoor_map_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Carry across any code already printed and taped to a wall.
        foreach (DB::table('indoor_maps')->whereNotNull('start_qr_code')->get() as $map) {
            DB::table('race_starts')->insert([
                'name' => $map->name . ' — start',
                'code' => $map->start_qr_code,
                'indoor_map_id' => $map->id,
                'is_active' => (bool) $map->is_active,
                'created_by' => $map->created_by,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('indoor_maps', function (Blueprint $table) {
            $table->dropUnique(['start_qr_code']);
            $table->dropColumn('start_qr_code');
        });

        Schema::table('race_sessions', function (Blueprint $table) {
            $table->foreignId('race_start_id')->nullable()->after('indoor_map_id')
                ->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('race_sessions', function (Blueprint $table) {
            $table->dropForeign(['race_start_id']);
            $table->dropColumn('race_start_id');
        });

        Schema::table('indoor_maps', function (Blueprint $table) {
            $table->string('start_qr_code')->nullable()->unique()->after('image_path');
        });

        Schema::dropIfExists('race_starts');
    }
};
