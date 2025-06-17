<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('quest_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->text('what_to_do');
            $table->text('google_map_embed_url')->nullable();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->integer('radius')->default(50); // radius in meters
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->integer('max_check_ins_per_user')->nullable();
            $table->integer('quest_points')->default(0);
            $table->string('image_path')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['is_active']);
            $table->index(['latitude', 'longitude']);
            $table->index(['created_by']);
            
            // Foreign key constraint (assuming you have a users table)
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('quest_locations');
    }
};