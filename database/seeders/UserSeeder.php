<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@gmail.com',
            'email_verified_at' => now(),
            'password' => Hash::make('123'), // 🔐 Change in production
            'role' => 'admin',
        ]);

        // Regular user
        User::create([
            'name' => 'Regular User',
            'email' => 'user@gmail.com',
            'email_verified_at' => now(),
            'password' => Hash::make('123'), // 🔐 Change in production
            'role' => 'user',
        ]);
    }
}
