<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->decimal('points', 10, 2)->default(1000.00)->after('department');
            $table->decimal('initial_points', 10, 2)->default(1000.00)->after('points');
        });
    }

    public function down()
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn(['points', 'initial_points']);
        });
    }
};