<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FeatureSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $features = [
            [
                'feature_key' => 'quiz_system',
                'feature_name' => 'Quiz System',
                'description' => 'Allow users to take quizzes and questionnaires',
                'is_enabled' => true,
                'sort_order' => 1,
                'metadata' => json_encode([
                    'icon' => 'fas fa-question-circle',
                    'color' => 'blue',
                    'route' => 'quiz.*'
                ])
            ],
            [
                'feature_key' => 'quest_locations',
                'feature_name' => 'Quest Locations',
                'description' => 'Enable GPS-based quest location check-ins',
                'is_enabled' => true,
                'sort_order' => 2,
                'metadata' => json_encode([
                    'icon' => 'fas fa-map-marker-alt',
                    'color' => 'green',
                    'route' => 'user.quest-location-dashboard'
                ])
            ],
            [
                'feature_key' => 'game_dashboard',
                'feature_name' => 'Game Dashboard',
                'description' => 'Access to outdoor game locations and interactive maps',
                'is_enabled' => true,
                'sort_order' => 3,
                'metadata' => json_encode([
                    'icon' => 'fas fa-gamepad',
                    'color' => 'purple',
                    'route' => 'user.game-dashboard'
                ])
            ],
            [
                'feature_key' => 'team_management',
                'feature_name' => 'Team Management',
                'description' => 'Team registration and member management features',
                'is_enabled' => true,
                'sort_order' => 4,
                'metadata' => json_encode([
                    'icon' => 'fas fa-users',
                    'color' => 'indigo',
                    'route' => 'team.*'
                ])
            ],
            [
                'feature_key' => 'leaderboard',
                'feature_name' => 'Leaderboard',
                'description' => 'Team rankings and scoring system',
                'is_enabled' => true,
                'sort_order' => 5,
                'metadata' => json_encode([
                    'icon' => 'fas fa-trophy',
                    'color' => 'yellow',
                    'route' => null // Future feature
                ])
            ],
            [
                'feature_key' => 'notifications',
                'feature_name' => 'Notifications',
                'description' => 'Real-time notifications and alerts',
                'is_enabled' => true,
                'sort_order' => 6,
                'metadata' => json_encode([
                    'icon' => 'fas fa-bell',
                    'color' => 'red',
                    'route' => null // Future feature
                ])
            ]
        ];

        foreach ($features as $feature) {
            DB::table('feature_settings')->updateOrInsert(
                ['feature_key' => $feature['feature_key']],
                array_merge($feature, [
                    'created_at' => now(),
                    'updated_at' => now()
                ])
            );
        }
    }
}