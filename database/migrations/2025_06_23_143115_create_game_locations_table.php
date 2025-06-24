<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('game_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->text('what_to_do');
            $table->integer('radius')->default(50);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by');
            $table->integer('max_check_ins_per_user')->default(1);
            $table->integer('quest_points')->default(10);
            $table->string('image_path')->nullable();
            $table->string('map_image_path')->nullable();
            $table->integer('coordinate_x')->nullable();
            $table->integer('coordinate_y')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_locations');
    }
};
