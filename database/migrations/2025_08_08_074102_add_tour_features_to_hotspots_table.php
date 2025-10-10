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
        Schema::table('hotspots', function (Blueprint $table) {
            // Tour navigation features
            $table->string('hotspot_type')->default('info')->after('type'); // info, navigation, quiz
            $table->unsignedBigInteger('target_location_id')->nullable()->after('hotspot_type'); // For scene navigation
            
            // Interactive content features  
            $table->text('content')->nullable()->after('description'); // Rich content for popups
            $table->string('media_type')->nullable()->after('content'); // image, video, audio
            $table->string('media_path')->nullable()->after('media_type'); // Path to media files
            
            // Quiz/assessment features
            $table->json('quiz_data')->nullable()->after('media_path'); // Questions and answers
            $table->integer('points_value')->default(0)->after('quiz_data'); // Points for assessment
            $table->boolean('is_required')->default(false)->after('points_value'); // Required for tour completion
            
            // Tour ordering
            $table->integer('tour_order')->nullable()->after('is_required'); // Order in tour sequence
            
            // Add foreign key constraint
            $table->foreign('target_location_id')->references('id')->on('game_locations')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hotspots', function (Blueprint $table) {
            $table->dropForeign(['target_location_id']);
            $table->dropColumn([
                'hotspot_type', 'target_location_id', 'content', 'media_type', 
                'media_path', 'quiz_data', 'points_value', 'is_required', 'tour_order'
            ]);
        });
    }
};
