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
        Schema::create('feature_settings', function (Blueprint $table) {
            $table->id();
            $table->string('feature_key')->unique();
            $table->string('feature_name');
            $table->text('description')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->integer('sort_order')->default(0);
            $table->json('metadata')->nullable(); // For storing additional config
            $table->timestamps();
            
            $table->index(['is_enabled', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feature_settings');
    }
};
