<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SCALING-PLAN.md §6, the server half: a team taps its card at a booth and the post opens.
 *
 * Today a facilitator radios the admin, who clicks in Outpost Access. At 5,000 players that is ~500
 * teams × 15 posts = **7,500 manual unlocks**, and it is also the weakest proof of presence we have:
 * GPS cannot see through a roof, and a paper QR can be photographed and shared.
 *
 * Three tables, and deliberately no new idea of what "open" means — a tap writes the same
 * `game_location_unlocks` row Outpost Access writes, so the AR outpost, the station gate and the
 * questionnaire all behave as they already do. Outpost Access stays, as the override for a dead reader.
 */
return new class extends Migration
{
    public function up(): void
    {
        // One row per booth reader. `node_id` is what travels in the 26-byte LoRa frame; the post it
        // stands at lives here and not in the frame, so a booth can be moved by editing one row
        // instead of re-provisioning two gateways (APK-WORK-ORDER §46, the client's option (a)).
        Schema::create('tap_nodes', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('node_id')->unique();
            $table->string('label')->nullable();
            // The post this node opens. Null means provisioned but not yet placed — a tap from it is
            // recorded and refused, which is better than guessing a post.
            $table->foreignId('game_location_id')->nullable()->constrained()->nullOnDelete();
            // HMAC-SHA256 key, hex. Only the HTTP endpoint verifies with it: over LoRa the gateway
            // checks the signature and reports `hmac_ok`, because the key must not have to travel.
            $table->string('node_key')->nullable();
            $table->boolean('is_active')->default(true);
            // Health, from the heartbeat: the booths run on powerbanks with no mains, so this is how
            // the admin sees a post about to go dark instead of hearing it from a queue of teams.
            $table->unsignedInteger('last_seq')->nullable();
            $table->unsignedSmallInteger('vbat_mv')->nullable();
            $table->smallInteger('rssi')->nullable();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->timestamps();
        });

        // uid → team. One tag per team, not per player: every unit in this domain is the team, and a
        // team plays on the leader's phone. 500 tags rather than 5,000.
        Schema::create('rfid_tags', function (Blueprint $table) {
            $table->id();
            // Uppercase hex of the UID, 4 or 7 bytes — 8 or 14 characters.
            $table->string('uid', 32)->unique();
            $table->foreignId('team_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('label')->nullable();
            $table->boolean('is_active')->default(true);
            // An active window, so a tag printed for one event cannot open posts at the next.
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });

        // Every tap, accepted or not. The audit that settles a dispute, and the only per-booth queue
        // data this event will ever have.
        Schema::create('post_arrivals', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('node_id')->index();
            // The node's own sequence number. (node_id, seq) is unique, and that single constraint is
            // what makes a replayed frame harmless and two gateways hearing one tap one arrival.
            $table->unsignedInteger('seq');
            $table->string('uid', 32)->nullable()->index();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('game_location_id')->nullable()->constrained()->nullOnDelete();
            // What happened: opened | already_open | unknown_tag | unknown_node | node_unplaced
            // | tag_expired | not_on_plan | bad_signature | no_accounts. Kept for refusals too —
            // a card that does not work is exactly what the crew needs to see.
            $table->string('outcome', 32)->index();
            // When the team tapped, by the node's clock — never when the server heard about it. A
            // gateway that was unplugged for twenty minutes flushes twenty taps with their own times.
            $table->timestamp('tapped_at')->nullable()->index();
            $table->timestamp('received_at')->nullable();
            // Which gateway heard it, and how well. Two gateways are deliberate: one gateway is one
            // device that can silence fifteen booths.
            $table->unsignedTinyInteger('gateway')->nullable();
            $table->smallInteger('rssi')->nullable();
            $table->decimal('snr', 5, 1)->nullable();
            $table->unsignedSmallInteger('vbat_mv')->nullable();
            $table->boolean('hmac_ok')->default(true);
            $table->timestamps();

            $table->unique(['node_id', 'seq']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_arrivals');
        Schema::dropIfExists('rfid_tags');
        Schema::dropIfExists('tap_nodes');
    }
};
