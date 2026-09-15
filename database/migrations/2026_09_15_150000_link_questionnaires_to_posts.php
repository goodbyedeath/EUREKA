<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A questionnaire belongs to one post (operator, 15 Sep): outdoor = a Quest Location the team
        // checks in at by GPS; indoor = a Game Location the crew opens on Outpost Access. A team may
        // scan the questionnaire only after START and that check-in — see App\Services\StationGate.
        Schema::table('questionnaires', function (Blueprint $table) {
            $table->string('venue_mode', 10)->nullable()->after('counts_toward_finish');   // outdoor | indoor
            $table->foreignId('quest_location_id')->nullable()->after('venue_mode')
                ->constrained('quest_locations')->nullOnDelete();
            $table->foreignId('game_location_id')->nullable()->after('quest_location_id')
                ->constrained('game_locations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('questionnaires', function (Blueprint $table) {
            $table->dropConstrainedForeignId('game_location_id');
            $table->dropConstrainedForeignId('quest_location_id');
            $table->dropColumn('venue_mode');
        });
    }
};
