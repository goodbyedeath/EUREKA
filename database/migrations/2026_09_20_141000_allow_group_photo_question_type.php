<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * questions.type is a database enum, so a new question type has to be let in here as well as in
 * App\Enums\QuestionType — otherwise saving one fails with "Data truncated for column 'type'".
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE questions MODIFY type ENUM('multiple_choice','text','true_false','fun_game','brief','group_photo') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE questions MODIFY type ENUM('multiple_choice','text','true_false','fun_game','brief') NOT NULL");
    }
};
