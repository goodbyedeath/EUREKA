<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('quest_locations', function (Blueprint $table) {
            $table->string('marker_color', 7)->default('#EF4444')->after('longitude'); // Default red color
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quest_locations', function (Blueprint $table) {
            $table->dropColumn('marker_color');
        });
    }
};
