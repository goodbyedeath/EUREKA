<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('questionnaires', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('qr_code')->unique();
            $table->boolean('is_active')->default(true);
            $table->integer('time_limit')->default(30); // minutes
            $table->foreignId('created_by')->constrained('users');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('max_attempts')->default(1);
            $table->decimal('pass_percentage', 5, 2)->default(50.00); // percentage
            $table->integer('total_points')->default(0); // Total points for the questionnaire   
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('questionnaires');
    }
};
