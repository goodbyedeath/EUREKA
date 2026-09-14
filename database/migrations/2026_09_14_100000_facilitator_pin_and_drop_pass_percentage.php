<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Small key/value store for event-wide secrets and switches that are not feature flags.
        // First tenant: the facilitator PIN hash.
        if (! Schema::hasTable('app_settings')) {
            Schema::create('app_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->foreignId('updated_by')->nullable();
                $table->timestamps();
            });
        }

        // Stored and edited, never evaluated anywhere. Operator: remove it.
        if (Schema::hasColumn('questionnaires', 'pass_percentage')) {
            Schema::table('questionnaires', function (Blueprint $table) {
                $table->dropColumn('pass_percentage');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');

        if (! Schema::hasColumn('questionnaires', 'pass_percentage')) {
            Schema::table('questionnaires', function (Blueprint $table) {
                $table->decimal('pass_percentage', 5, 2)->default(50.00);
            });
        }
    }
};
