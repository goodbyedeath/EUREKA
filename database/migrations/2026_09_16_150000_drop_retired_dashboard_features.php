<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Drop the sixteen switches left over from the retired web participant dashboard.
 *
 * Operator, 16 Sep: the feature list should match what exists. Players are on the Android app,
 * which reads three flags; the kiosk screens and the server read three more. These sixteen gated
 * parts of `resources/views/user/` that nobody opens, and every one of them was already off — so
 * deleting the rows changes nothing anyone can see. Their code went with them in the same change.
 */
return new class extends Migration
{
    private const KEYS = [
        'game_dashboard',
        'team_management',
        'notifications',
        'user_dashboard_session_timer',
        'user_dashboard_stats',
        'dashboard_stat_total_attempts',
        'dashboard_stat_completed',
        'dashboard_stat_average_points',
        'dashboard_stat_completion_rate',
        'dashboard_stat_total_score',
        'dashboard_stat_history',
        'dashboard_quick_stats',
        'dashboard_quick_stat_completed',
        'dashboard_quick_stat_average_score',
        'dashboard_quick_stat_total_time',
        'dashboard_quick_stat_streak',
    ];

    public function up(): void
    {
        DB::table('feature_settings')->whereIn('feature_key', self::KEYS)->delete();
    }

    /**
     * Not restored: the views, components and seeders these switched are gone, so a row would
     * only put a dead control back on the admin page.
     */
    public function down(): void
    {
        // no-op
    }
};
