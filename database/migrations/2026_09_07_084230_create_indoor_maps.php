<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Indoor venues have no usable GPS, so navigation is a picture instead of a map:
     * the admin uploads a top-view plan and marks the outposts on it.
     */
    public function up(): void
    {
        Schema::create('indoor_maps', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image_path');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('indoor_map_spots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indoor_map_id')->constrained()->cascadeOnDelete();
            $table->string('name');

            // Percentages of the image, not pixels: the plan is displayed at whatever
            // width the screen allows, and pixel coordinates would drift on every device.
            $table->decimal('x', 6, 3);
            $table->decimal('y', 6, 3);

            $table->string('shape', 16)->default('circle');   // circle | square | diamond | pin
            $table->string('color', 9)->default('#ef4444');
            $table->unsignedSmallInteger('size')->default(28); // px at 100% zoom

            // What a tap reveals. Either, both, or neither.
            $table->text('content')->nullable();
            $table->string('image_path')->nullable();

            // Optional link to the AR outpost this spot stands for.
            $table->foreignId('game_location_id')->nullable()->constrained()->nullOnDelete();

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['indoor_map_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indoor_map_spots');
        Schema::dropIfExists('indoor_maps');
    }
};
