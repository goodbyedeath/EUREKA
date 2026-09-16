<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Switches for what sits on top of a slide's picture (operator, 17 Sep): the dark layer and the
 * title/subtitle. Together with show_primary_button they let a slide be the picture alone — a
 * poster that already carries its own lettering does not want a scrim and a second headline.
 *
 * Both default to on so every existing slide looks exactly as it did.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hero_slides', function (Blueprint $table) {
            $table->boolean('show_text')->default(true)->after('show_primary_button');
            $table->boolean('show_overlay')->default(true)->after('show_text');
        });
    }

    public function down(): void
    {
        Schema::table('hero_slides', function (Blueprint $table) {
            $table->dropColumn(['show_text', 'show_overlay']);
        });
    }
};
