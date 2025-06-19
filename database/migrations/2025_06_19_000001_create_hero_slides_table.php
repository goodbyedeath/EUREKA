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
        Schema::create('hero_slides', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('subtitle');
            $table->string('primary_button_text')->default('Get Started');
            $table->string('primary_button_url')->default('/register');
            $table->string('secondary_button_text')->nullable();
            $table->string('secondary_button_url')->nullable();
            $table->string('background_gradient')->default('from-blue-600 via-purple-600 to-indigo-800');
            $table->string('text_color')->default('text-white');
            $table->string('button_color')->default('text-blue-600 bg-white');
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('icon_svg')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hero_slides');
    }
};