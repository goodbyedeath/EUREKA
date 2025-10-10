<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HeroSlide;

class HeroSlideSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $slides = [
            [
                'title' => 'Interactive Learning Made Simple',
                'subtitle' => 'Discover knowledge through engaging quizzes, track your progress, and compete with teams in a gamified learning environment.',
                'primary_button_text' => 'Start Learning Now',
                'primary_button_url' => '/register',
                'secondary_button_text' => 'Sign In',
                'secondary_button_url' => '/login',
                'background_gradient' => 'from-blue-600 via-purple-600 to-indigo-800',
                'text_color' => 'text-white',
                'button_color' => 'text-blue-600 bg-white',
                'order' => 0,
                'is_active' => true,
            ],
            [
                'title' => 'QR Code Powered Quests',
                'subtitle' => 'Scan QR codes to unlock exciting questionnaires and challenges. Learn by exploring your environment and discovering hidden knowledge.',
                'primary_button_text' => 'Explore Quests',
                'primary_button_url' => '/register',
                'secondary_button_text' => null,
                'secondary_button_url' => null,
                'background_gradient' => 'from-green-500 via-teal-500 to-blue-600',
                'text_color' => 'text-white',
                'button_color' => 'text-green-600 bg-white',
                'order' => 1,
                'is_active' => true,
            ],
            [
                'title' => 'Team Collaboration & Analytics',
                'subtitle' => 'Join teams, track your learning progress, and compete with others. Comprehensive analytics help you understand your growth journey.',
                'primary_button_text' => 'Join Community',
                'primary_button_url' => '/register',
                'secondary_button_text' => null,
                'secondary_button_url' => null,
                'background_gradient' => 'from-purple-600 via-pink-500 to-red-500',
                'text_color' => 'text-white',
                'button_color' => 'text-purple-600 bg-white',
                'order' => 2,
                'is_active' => true,
            ]
        ];

        foreach ($slides as $slideData) {
            HeroSlide::firstOrCreate(
                ['title' => $slideData['title']], // Match on title
                array_merge($slideData, [
                    'created_at' => now(),
                    'updated_at' => now()
                ])
            );
        }
    }
}