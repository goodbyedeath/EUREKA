<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One row, holding the event's branding.
 *
 * The logo was hard-coded as `/logo/horizonlogo.png` and `/logo/icon.png` in fourteen
 * places across the admin, the team side, the landing page, the login screen and the PDF
 * export. Changing it meant overwriting files on the server. This puts it behind a setting
 * an admin can change from the panel, with the bundled files as the fallback — so an
 * install that never uploads anything looks exactly as it does today.
 *
 * A table rather than a config file because config is cached on this host: a value written
 * at runtime would not survive `artisan optimize`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brand_settings', function (Blueprint $table) {
            $table->id();
            // The wide wordmark: admin topbar, team navigation, login, PDF header.
            $table->string('horizontal_path')->nullable();
            // The square mark: favicon, landing page, app icon.
            $table->string('icon_path')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Seed the single row so the app never has to reason about "no settings yet".
        // Null paths mean "use the bundled file".
        DB::table('brand_settings')->insert([
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_settings');
    }
};
