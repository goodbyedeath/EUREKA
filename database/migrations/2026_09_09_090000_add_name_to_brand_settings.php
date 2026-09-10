<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The event's name, alongside its logo.
 *
 * The name was in two kinds of place: `config('app.name')` — which reads APP_NAME from
 * `.env` and is baked in by `config:cache`, so changing it needs a redeploy — and plain
 * "EUREKA" typed into thirteen views. Neither is something an admin dressing the app for
 * a client can reach.
 *
 * Null means "use APP_NAME", so an install that never sets a name is unchanged.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brand_settings', function (Blueprint $table) {
            // Shown in the browser tab, headers, the LED board and the PDF report.
            $table->string('app_name')->nullable()->after('id');
            // Optional line under the name on the landing and login screens.
            $table->string('tagline')->nullable()->after('app_name');
        });
    }

    public function down(): void
    {
        Schema::table('brand_settings', function (Blueprint $table) {
            $table->dropColumn(['app_name', 'tagline']);
        });
    }
};
