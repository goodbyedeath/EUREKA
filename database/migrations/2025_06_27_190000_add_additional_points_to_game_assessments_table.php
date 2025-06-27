<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('game_assessments', function (Blueprint $table) {
            $table->decimal('additional_points', 10, 2)->default(0)->after('penalty');
        });
    }

    public function down()
    {
        Schema::table('game_assessments', function (Blueprint $table) {
            $table->dropColumn('additional_points');
        });
    }
};