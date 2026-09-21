<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "Tebak Gambar" (operator, 21 Sep): one picture and several labelled answer boxes — a crossword
 * with five across and five down is ten boxes, five company logos are five.
 *
 * The boxes live in their own column rather than in `options`, which already means "the choices a
 * multiple-choice question offers" and is sent to the app as is. These hold the answers, and the
 * answers must never reach the app.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE questions MODIFY type ENUM('multiple_choice','text','true_false','fun_game','brief','group_photo','picture_puzzle') NOT NULL");

        Schema::table('questions', function (Blueprint $table) {
            // [{key, label, answers: [..], length: int|null}] — see App\Services\PicturePuzzle.
            $table->json('answer_slots')->nullable()->after('share_caption');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('answer_slots');
        });

        DB::statement("ALTER TABLE questions MODIFY type ENUM('multiple_choice','text','true_false','fun_game','brief','group_photo') NOT NULL");
    }
};
