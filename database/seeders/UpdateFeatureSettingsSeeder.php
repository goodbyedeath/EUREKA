<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FeatureSetting;

class UpdateFeatureSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $features = [
            // Dashboard Stats Container
            [
                'feature_key' => 'user_dashboard_stats',
                'feature_name' => 'Dashboard Statistics',
                'description' => 'Display statistics section on user dashboard',
                'is_enabled' => true,
                'sort_order' => 100,
                'metadata' => json_encode([
                    'icon' => 'fas fa-chart-line',
                    'color' => 'blue',
                    'category' => 'Dashboard'
                ])
            ],

            // Individual Dashboard Stats
            [
                'feature_key' => 'dashboard_stat_total_attempts',
                'feature_name' => 'Total Attempts Stat',
                'description' => 'Show total quiz attempts count',
                'is_enabled' => true,
                'sort_order' => 101,
                'metadata' => json_encode([
                    'icon' => 'fas fa-list-check',
                    'color' => 'purple',
                    'category' => 'Dashboard Stats'
                ])
            ],
            [
                'feature_key' => 'dashboard_stat_completed',
                'feature_name' => 'Completed Stat',
                'description' => 'Show completed quizzes count',
                'is_enabled' => true,
                'sort_order' => 102,
                'metadata' => json_encode([
                    'icon' => 'fas fa-check-circle',
                    'color' => 'green',
                    'category' => 'Dashboard Stats'
                ])
            ],
            [
                'feature_key' => 'dashboard_stat_average_points',
                'feature_name' => 'Average Points Stat',
                'description' => 'Show average points per quiz',
                'is_enabled' => true,
                'sort_order' => 103,
                'metadata' => json_encode([
                    'icon' => 'fas fa-star',
                    'color' => 'yellow',
                    'category' => 'Dashboard Stats'
                ])
            ],
            [
                'feature_key' => 'dashboard_stat_completion_rate',
                'feature_name' => 'Completion Rate Stat',
                'description' => 'Show quiz completion rate percentage',
                'is_enabled' => true,
                'sort_order' => 104,
                'metadata' => json_encode([
                    'icon' => 'fas fa-percentage',
                    'color' => 'indigo',
                    'category' => 'Dashboard Stats'
                ])
            ],
            [
                'feature_key' => 'dashboard_stat_total_score',
                'feature_name' => 'Total Score Stat',
                'description' => 'Show total team score',
                'is_enabled' => true,
                'sort_order' => 105,
                'metadata' => json_encode([
                    'icon' => 'fas fa-trophy',
                    'color' => 'yellow',
                    'category' => 'Dashboard Stats'
                ])
            ],
            [
                'feature_key' => 'dashboard_stat_history',
                'feature_name' => 'History Stat',
                'description' => 'Show quiz history/attempts',
                'is_enabled' => true,
                'sort_order' => 106,
                'metadata' => json_encode([
                    'icon' => 'fas fa-clock-rotate-left',
                    'color' => 'gray',
                    'category' => 'Dashboard Stats'
                ])
            ],
        ];

        foreach ($features as $featureData) {
            FeatureSetting::updateOrCreate(
                ['feature_key' => $featureData['feature_key']],
                $featureData
            );
        }

        $this->command->info('Dashboard statistics features have been updated successfully!');
    }
}
