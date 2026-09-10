<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reusable 3D asset library.
 *
 * Models used to be uploaded per game location, so the same chest at three outposts
 * meant three copies on disk and three downloads in the offline patch. A shared
 * library makes each file exist once and cache once, then locations and individual
 * objects just point at it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ar_models', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('path');                          // storage path to the .glb/.gltf
            $table->unsignedBigInteger('size')->default(0);   // bytes, shown in the picker
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Locations and individual objects reference the library rather than a raw path.
        Schema::table('game_locations', function (Blueprint $table) {
            $table->foreignId('ar_model_id')->nullable()->after('experience_type')
                ->constrained('ar_models')->nullOnDelete();
        });

        Schema::table('hotspots', function (Blueprint $table) {
            $table->foreignId('ar_model_id')->nullable()->after('ar_model_path')
                ->constrained('ar_models')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('hotspots', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ar_model_id');
        });
        Schema::table('game_locations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ar_model_id');
        });
        Schema::dropIfExists('ar_models');
    }
};
