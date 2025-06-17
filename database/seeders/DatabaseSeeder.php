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
        // Call the UserSeeder to seed admin and user
        $this->call(QuestLocationSeeder::class);
        $this->call(UserSeeder::class);
        

        // Optionally, seed a test user using factory
        // \App\Models\User::factory(10)->create();

       
    }
}
