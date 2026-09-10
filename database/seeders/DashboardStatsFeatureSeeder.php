<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FeatureSetting;

class DashboardStatsFeatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $features = [
            // Quick Stats Container
            [
                'feature_key' => 'dashboard_quick_stats',
                'feature_name' => 'Dashboard Quick Stats',
                'description' => 'Display quick statistics section on user dashboard (Completed, Avg Score, Total Time, Streak)',
                'is_enabled' => true,
                'sort_order' => 250,
                'metadata' => json_encode([
                    'icon' => 'fas fa-chart-bar',
                    'color' => 'blue',
                    'category' => 'Dashboard'
                ])
            ],

            // Individual Quick Stats
            [
                'feature_key' => 'dashboard_quick_stat_completed',
                'feature_name' => 'Quick Stat: Completed Quizzes',
                'description' => 'Show completed quizzes count in quick stats',
                'is_enabled' => true,
                'sort_order' => 251,
                'metadata' => json_encode([
                    'icon' => 'fas fa-clipboard-check',
                    'color' => 'blue',
                    'category' => 'Dashboard Quick Stats'
                ])
            ],

            [
                'feature_key' => 'dashboard_quick_stat_average_score',
                'feature_name' => 'Quick Stat: Average Score',
                'description' => 'Show average score (earned points per quiz) in quick stats',
                'is_enabled' => true,
                'sort_order' => 252,
                'metadata' => json_encode([
                    'icon' => 'fas fa-star',
                    'color' => 'green',
                    'category' => 'Dashboard Quick Stats'
                ])
            ],

            [
                'feature_key' => 'dashboard_quick_stat_total_time',
                'feature_name' => 'Quick Stat: Total Time',
                'description' => 'Show total time spent on quizzes in quick stats',
                'is_enabled' => true,
                'sort_order' => 253,
                'metadata' => json_encode([
                    'icon' => 'fas fa-clock',
                    'color' => 'purple',
                    'category' => 'Dashboard Quick Stats'
                ])
            ],

            [
                'feature_key' => 'dashboard_quick_stat_streak',
                'feature_name' => 'Quick Stat: Daily Streak',
                'description' => 'Show daily streak (consecutive days with completed quizzes) in quick stats',
                'is_enabled' => true,
                'sort_order' => 254,
                'metadata' => json_encode([
                    'icon' => 'fas fa-fire',
                    'color' => 'orange',
                    'category' => 'Dashboard Quick Stats'
                ])
            ],
        ];

        foreach ($features as $featureData) {
            FeatureSetting::updateOrCreate(
                ['feature_key' => $featureData['feature_key']],
                $featureData
            );
        }

        $this->command->info('Dashboard quick stats features have been added successfully!');
    }
}
