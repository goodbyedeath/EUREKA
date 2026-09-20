<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Unlimited attempts" needs somewhere to be stored.
 *
 * Operator, 20 Sep: unlimited attempts could not be used. The model already reads a null
 * max_attempts as "no ceiling" (Questionnaire::canUserAttempt), the edit form already offered an
 * empty box — but the column was NOT NULL, so every save of an empty box failed with
 * "Column 'max_attempts' cannot be null" and the form silently kept the old value.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questionnaires', function (Blueprint $table) {
            $table->integer('max_attempts')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('questionnaires', function (Blueprint $table) {
            $table->integer('max_attempts')->default(1)->nullable(false)->change();
        });
    }
};
