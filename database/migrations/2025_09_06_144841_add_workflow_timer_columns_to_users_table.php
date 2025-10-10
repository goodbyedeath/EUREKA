<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Only add columns if they don't exist
            if (!Schema::hasColumn('users', 'session_workflow_id')) {
                $table->string('session_workflow_id')->nullable()->after('remember_token');
            }
            if (!Schema::hasColumn('users', 'last_activity_at')) {
                $table->timestamp('last_activity_at')->nullable()->after('session_workflow_id');
            }
            if (!Schema::hasColumn('users', 'session_expired_at')) {
                $table->timestamp('session_expired_at')->nullable()->after('last_activity_at');
            }
            if (!Schema::hasColumn('users', 'session_timeout')) {
                $table->integer('session_timeout')->default(300)->after('session_expired_at');
            }
        });
        
        // Add indexes separately to avoid duplicate index errors
        Schema::table('users', function (Blueprint $table) {
            if (!$this->hasIndex('users', 'users_session_workflow_id_index')) {
                $table->index('session_workflow_id');
            }
            if (!$this->hasIndex('users', 'users_last_activity_at_index')) {
                $table->index('last_activity_at');
            }
        });
    }
    
    /**
     * Check if an index exists
     */
    private function hasIndex($table, $indexName)
    {
        try {
            $indexes = DB::select("SHOW INDEX FROM {$table}");
            foreach ($indexes as $index) {
                if ($index->Key_name === $indexName) {
                    return true;
                }
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['session_workflow_id']);
            $table->dropIndex(['last_activity_at']);
            $table->dropColumn(['session_workflow_id', 'last_activity_at', 'session_expired_at', 'session_timeout']);
        });
    }
};
