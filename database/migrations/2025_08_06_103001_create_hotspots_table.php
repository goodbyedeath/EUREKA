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
        Schema::create('hotspots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_location_id')->constrained('game_locations')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('pitch', 8, 5); // Vertical angle (-90 to 90 degrees)
            $table->decimal('yaw', 8, 5);   // Horizontal angle (-180 to 180 degrees)
            $table->string('type')->default('info'); // info, scene, custom
            $table->string('css_class')->nullable();
            $table->json('extra_data')->nullable(); // For additional properties
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotspots');
    }
};
