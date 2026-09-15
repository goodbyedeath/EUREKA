<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One printed login card per generated team account (APK report #16, operator-approved 15 Sep).
        // The code is looked up by its sha256; the plain code and password are kept encrypted with the
        // app key only so an admin can reprint a card without invalidating it.
        Schema::create('login_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('code_hash', 64)->unique();
            $table->text('code_encrypted');
            $table->text('password_encrypted');
            $table->string('batch', 40)->nullable()->index();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_cards');
    }
};
