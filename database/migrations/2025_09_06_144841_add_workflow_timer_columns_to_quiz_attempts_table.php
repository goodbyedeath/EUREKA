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
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->string('timer_workflow_id')->nullable()->after('completed_at');
            $table->timestamp('timer_started_at')->nullable()->after('timer_workflow_id');
            $table->boolean('auto_submitted')->default(false)->after('timer_started_at');
            $table->string('submission_reason')->nullable()->after('auto_submitted');
            
            $table->index('timer_workflow_id');
            $table->index(['auto_submitted', 'completed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropIndex(['timer_workflow_id']);
            $table->dropIndex(['auto_submitted', 'completed_at']);
            $table->dropColumn(['timer_workflow_id', 'timer_started_at', 'auto_submitted', 'submission_reason']);
        });
    }
};
