<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('quest_locations', function (Blueprint $table) {
            $table->string('map_image_path')->nullable()->after('image_path');
            $table->integer('coordinate_x')->nullable()->after('map_image_path');
            $table->integer('coordinate_y')->nullable()->after('coordinate_x');
        });
    }

    public function down()
    {
        Schema::table('quest_locations', function (Blueprint $table) {
            $table->dropColumn(['map_image_path', 'coordinate_x', 'coordinate_y']);
        });
    }
};