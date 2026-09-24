<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A questionnaire used to belong to exactly one post, because `questionnaires.game_location_id` is a
 * single column. The operator's two floor plans walk the same booths in a different order, so the
 * same quiz has to be openable at more than one post — and, for a questionnaire every post shares
 * (a group photo, a closing question), at all of them.
 *
 * Three pieces:
 *  - the pivot lists the extra posts a questionnaire may be opened at;
 *  - `all_posts` means "every active post", so a post added later joins without re-linking;
 *  - an attempt now records the post it was played at, which is what makes a shared questionnaire
 *    worth having: one play per post rather than one play in total. For a questionnaire with a
 *    single post — every existing row — that is exactly the old behaviour.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Named, because the conventional name for a unique index on these two columns is 66
        // characters and MySQL stops at 64.
        Schema::dropIfExists('game_location_questionnaire');
        Schema::create('game_location_questionnaire', function (Blueprint $table) {
            $table->id();
            $table->foreignId('questionnaire_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_location_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['questionnaire_id', 'game_location_id'], 'questionnaire_post_unique');
        });

        Schema::table('questionnaires', function (Blueprint $table) {
            $table->boolean('all_posts')->default(false)->after('game_location_id');
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            // Null for every row that exists, and for outdoor: there the post is the quest location
            // the questionnaire is linked to, and only one questionnaire may name it.
            $table->foreignId('game_location_id')->nullable()->after('questionnaire_id')
                ->constrained()->nullOnDelete();
            $table->index(['user_id', 'questionnaire_id', 'game_location_id'], 'quiz_attempts_user_station_index');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropIndex('quiz_attempts_user_station_index');
            $table->dropConstrainedForeignId('game_location_id');
        });

        Schema::table('questionnaires', function (Blueprint $table) {
            $table->dropColumn('all_posts');
        });

        Schema::dropIfExists('game_location_questionnaire');
    }
};
