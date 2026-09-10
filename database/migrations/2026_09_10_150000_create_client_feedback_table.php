<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reports from the Android build agent.
 *
 * The contract channel was one-way: the server published, the client consumed, and anything the
 * client had to say travelled through the operator retyping it. That is how a work order came to
 * describe screens as missing four releases after they shipped — the correction existed, but had
 * no way back.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_feedback', function (Blueprint $table) {
            $table->id();

            // What kind of report this is, so a reader can triage without reading every body.
            $table->string('kind', 32);            // mismatch | blocked | done | question | bug
            $table->string('subject', 160);        // endpoint, guide section, task number
            $table->text('detail');

            // Which build and which contract revision it was written against. Without these a
            // report cannot be judged: "this is wrong" means nothing if we cannot tell what the
            // client was reading at the time.
            $table->string('client_version', 32)->nullable();
            $table->string('contract_sha', 32)->nullable();

            $table->boolean('resolved')->default(false);
            $table->timestamps();

            $table->index(['resolved', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_feedback');
    }
};
