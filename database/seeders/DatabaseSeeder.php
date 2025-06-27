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
        // Call the UserSeeder first to create admin user, then quest locations
        $this->call(UserSeeder::class);
        $this->call(QuestLocationSeeder::class);
        

        // Optionally, seed a test user using factory
        // \App\Models\User::factory(10)->create();

       
    }
}
