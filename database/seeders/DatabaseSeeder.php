<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Only call seeders that can handle existing data gracefully
        $this->call([
            FeatureSettingsSeeder::class,
            HeroSlideSeeder::class,
        ]);
        
        // UserSeeder is commented out because it creates duplicate users in production
        // Run: php artisan db:seed --class=UserSeeder --force (only once for initial setup)
        
        // QuestLocationSeeder has been removed as it's no longer needed
    }
}
