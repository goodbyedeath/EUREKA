<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('position')->nullable();
            $table->boolean('is_leader')->default(false);
            $table->timestamps();
            
            // Add unique constraint to prevent duplicate entries
            $table->unique(['team_id', 'email']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('team_members');
    }
};