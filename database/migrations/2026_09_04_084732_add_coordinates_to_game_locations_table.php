<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bind an AR outpost to a physical place.
     *
     * Without this a game location exists nowhere: objects are stored only as a
     * direction and distance from wherever the phone was calibrated, so opening the
     * viewer at home drew them in the living room. Coordinates let the app refuse to
     * open the scene away from the outpost, which is how Pokemon GO keeps a creature
     * "in one place" — GPS, not AR anchoring.
     *
     * Nullable on purpose: existing locations keep working unchanged and become bound
     * the first time an admin authors at the outpost. Precision mirrors
     * quest_locations — longitude needs 11 digits to hold Indonesia's ~106-141E.
     */
    public function up(): void
    {
        Schema::table('game_locations', function (Blueprint $table) {
            $table->decimal('latitude', 10, 8)->nullable()->after('experience_type');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            // Metres. Generous by default: a GPS fix near buildings is 5-20 m out, and
            // the QR scan is the real proof of presence — this is a convenience check.
            $table->unsignedSmallInteger('radius')->default(50)->after('longitude');
            $table->index(['latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::table('game_locations', function (Blueprint $table) {
            $table->dropIndex(['latitude', 'longitude']);
            $table->dropColumn(['latitude', 'longitude', 'radius']);
        });
    }
};
