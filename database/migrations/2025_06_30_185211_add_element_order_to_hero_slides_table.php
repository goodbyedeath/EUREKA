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
        Schema::table('hero_slides', function (Blueprint $table) {
            $table->json('element_order')->nullable()->after('icon_svg')
                ->comment('Stores the order of elements: {"icon": 0, "title": 1, "subtitle": 2, "primary_button": 3, "secondary_button": 4}');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hero_slides', function (Blueprint $table) {
            $table->dropColumn('element_order');
        });
    }
};