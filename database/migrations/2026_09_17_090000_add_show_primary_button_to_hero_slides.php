<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A switch for a slide's primary button (operator, 17 Sep).
 *
 * The primary button was mandatory — text and URL required — so a slide that should only say
 * something had to carry a button anyway. On by default so every existing slide looks the same.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hero_slides', function (Blueprint $table) {
            $table->boolean('show_primary_button')->default(true)->after('primary_button_url');
        });
    }

    public function down(): void
    {
        Schema::table('hero_slides', function (Blueprint $table) {
            $table->dropColumn('show_primary_button');
        });
    }
};
