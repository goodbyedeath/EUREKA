<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The GPS Tracker draws routes; EUREKA owns the game.
 *
 * Operator, 16 Sep: teams should see the route as well as the posts. The tracker app is where a
 * route is recorded, but its export API has no authentication at all, so post locations, clues and
 * points cannot live there. Instead EUREKA pulls the recorded routes in on a schedule and keeps its
 * own copy: that is what every map and the APK read, it survives the tracker being down, and it is
 * the only thing that can go into the offline bundle behind a token.
 *
 * A tracker marker is only a pin with a title. Promoting one creates a real quest_location here —
 * where radius, points and check-ins already work — and the marker remembers which post it became,
 * so the same place is never drawn twice.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('map_routes', function (Blueprint $table) {
            $table->id();
            // One row per recorded tracker session; re-syncing updates in place.
            $table->unsignedBigInteger('tracker_session_id')->unique();
            $table->string('name');
            // Who recorded it, for the admin list only. Never sent to players.
            $table->string('recorded_by')->nullable();
            $table->string('color', 9)->default('#2563eb');
            $table->unsignedInteger('distance_m')->nullable();
            // [[lng, lat], …] after simplification — the order MapLibre and the APK draw.
            $table->json('points');
            $table->unsignedInteger('point_count')->default(0);
            $table->timestamp('recorded_at')->nullable();
            // Off by default: a venue has many old recordings and the crew picks the event's route.
            $table->boolean('is_active')->default(false);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index('is_active');
        });

        Schema::create('map_route_markers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('map_route_id')->constrained()->cascadeOnDelete();
            // Identifies the marker across syncs, so a promoted one keeps its link when the
            // tracker resends the list.
            $table->string('source_key', 64);
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('icon', 16)->nullable();
            $table->string('color', 9)->nullable();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->unsignedInteger('position')->default(0);
            // Set once this pin has become a real post; then the maps draw the post, not the pin.
            $table->foreignId('quest_location_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['map_route_id', 'source_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('map_route_markers');
        Schema::dropIfExists('map_routes');
    }
};
